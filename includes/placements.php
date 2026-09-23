<?php
declare(strict_types=1);
require_once __DIR__ . '/content.php';

function et_placement_pages(): array
{
    $pages = ['home'=>'Home','about'=>'About','contact'=>'Contact','privacy'=>'Privacy',
        'contribution'=>'Michango / ujumbe','announcements'=>'Matangazo / articles',
        'form-one'=>'Form One search','form-one-schools'=>'Form One school browsing','form-one-results'=>'Form One selection results',
        'form-one-error'=>'Form One error','form-five'=>'Form Five search','form-five-schools'=>'Form Five school browsing',
        'form-five-results'=>'Form Five selection results','form-five-error'=>'Form Five error'];
    foreach (['acsee','csee','ftna','psle','sfna'] as $exam) {
        $pages[$exam] = strtoupper($exam) . ' — search';
        $pages[$exam . '-results'] = strtoupper($exam) . ' — results';
        $pages[$exam . '-error'] = strtoupper($exam) . ' — error';
    }
    foreach (['psle','sfna'] as $exam) { $pages[$exam . '-schools'] = strtoupper($exam) . ' — schools'; }
    return $pages;
}

function et_placement_targets(): array
{
    return ['all'=>'Kurasa zote za umma','levels'=>'Search pages zote','results'=>'Result pages zote',
        'schools'=>'School lists zote','information'=>'Kurasa za taarifa','errors'=>'Error pages zote'] + et_placement_pages();
}

function et_placement_matches(array $targets, string $page): bool
{
    if (!isset(et_placement_pages()[$page])) { return false; }
    if (in_array('all', $targets, true) || in_array($page, $targets, true)) { return true; }
    $group = str_ends_with($page, '-results') ? 'results'
        : (str_ends_with($page, '-schools') ? 'schools'
        : (str_ends_with($page, '-error') ? 'errors'
        : (in_array($page, ['acsee','csee','ftna','psle','sfna','form-one','form-five'], true) ? 'levels' : 'information')));
    return in_array($group, $targets, true);
}

function et_validate_placement(array $input): array
{
    $data = [];
    foreach (['title','description','image_url','sponsor_name','external_url'] as $field) { $data[$field] = trim((string) ($input[$field] ?? '')); }
    foreach (['kind'=>'system','format'=>'banner','slot'=>'bottom','status'=>'draft','destination_type'=>'external','popup_style'=>'corner','display_mode'=>'session'] as $field=>$default) {
        $data[$field] = (string) ($input[$field] ?? $default);
    }
    $data['priority'] = filter_var($input['priority'] ?? 0, FILTER_VALIDATE_INT);
    $data['skip_delay'] = filter_var($input['skip_delay'] ?? 0, FILTER_VALIDATE_INT);
    $data['content_id'] = filter_var($input['content_id'] ?? 0, FILTER_VALIDATE_INT) ?: null;
    $targets = is_array($input['targets'] ?? null) ? array_values(array_unique($input['targets'], SORT_REGULAR)) : [];
    $errors = [];
    foreach ($targets as $target) { if (!is_string($target) || !isset(et_placement_targets()[$target])) { $errors[] = 'Invalid display page.'; } }
    if ($targets === []) { $errors[] = 'Choose at least one page or page group.'; }
    $data['targets'] = json_encode($targets, JSON_THROW_ON_ERROR);
    if (mb_strlen($data['title']) < 3 || mb_strlen($data['title']) > 140) { $errors[] = 'Use 3–140 characters for the title.'; }
    if (mb_strlen($data['description']) > 300 || mb_strlen($data['sponsor_name']) > 120) { $errors[] = 'Use up to 300 characters for the description and 120 for the sponsor name.'; }
    foreach (['kind'=>['system','sponsor'],'format'=>['banner','card'],'slot'=>['top','bottom','popup'],
        'status'=>['draft','published','archived'],'destination_type'=>['internal','external'],
        'popup_style'=>['corner','interstitial'],'display_mode'=>['session','always','three']] as $field=>$values) {
        if (!in_array($data[$field], $values, true)) { $errors[] = $field . ' is invalid.'; }
    }
    if ($data['priority'] === false || $data['priority'] < 0 || $data['priority'] > 100) { $errors[] = 'Set priority between 0 and 100.'; }
    if ($data['skip_delay'] === false || $data['skip_delay'] < 0 || $data['skip_delay'] > 30) { $errors[] = 'Set the skip delay between 0 and 30 seconds.'; }
    if ($data['kind'] === 'sponsor' && $data['sponsor_name'] === '') { $errors[] = 'Enter the sponsor name.'; }
    if ($data['image_url'] !== '' && (strlen($data['image_url']) > 2048 || !et_is_safe_image_url($data['image_url']))) { $errors[] = 'Use an HTTPS image URL or a valid uploaded image.'; }
    if ($data['destination_type'] === 'external') {
        if (strlen($data['external_url']) > 2048 || !et_is_safe_public_url($data['external_url'])
            || parse_url($data['external_url'], PHP_URL_SCHEME) !== 'https') { $errors[] = 'Enter a valid HTTPS destination URL.'; }
        $data['content_id'] = null;
    } else {
        $data['external_url'] = '';
        if (!$data['content_id'] || $data['content_id'] < 1) { $errors[] = 'Choose an internal article.'; }
    }
    foreach (['starts_at','ends_at'] as $field) {
        $raw = trim((string) ($input[$field] ?? ''));
        $data[$field] = $raw === '' ? null : et_local_datetime_to_utc($raw);
        if ($raw !== '' && (!$data[$field] || et_utc_datetime_to_local($data[$field]) !== $raw)) { $errors[] = 'Date for ' . $field . ' is invalid.'; }
    }
    if ($data['starts_at'] && $data['ends_at'] && $data['ends_at'] <= $data['starts_at']) { $errors[] = 'The end date must be after the start date.'; }
    return [$data, $errors];
}

function et_placement_entry(PDO $database, array $row, string $base): ?array
{
    $href = $row['external_url'];
    if ($row['destination_type'] === 'internal') {
        $statement = $database->prepare("SELECT slug FROM content_items WHERE id=? AND destination_type='internal'
            AND status IN ('published','scheduled') AND published_at<=? AND (expires_at IS NULL OR expires_at>?)");
        $now = et_utc_now();
        $statement->execute([$row['content_id'], $now, $now]);
        $slug = $statement->fetchColumn();
        if (!$slug) { return null; }
        $href = $base . '/announcements/' . rawurlencode((string) $slug) . '/';
    } elseif (!et_is_safe_public_url($href) || parse_url($href, PHP_URL_SCHEME) !== 'https') { return null; }
    $image = $row['image_url'];
    if ($image !== '' && !et_is_safe_image_url($image)) { return null; }
    if (et_is_managed_content_image($image)) { $image = $base . '/' . $image; }
    return ['id'=>(int)$row['id'],'title'=>$row['title'],'description'=>$row['description'],
        'kind'=>$row['kind'],'sponsor_name'=>$row['sponsor_name'],'format'=>$row['format'],'slot'=>$row['slot'],
        'image_url'=>$image,'href'=>$href,'external'=>$row['destination_type']==='external',
        'popup_style'=>$row['popup_style']??'corner','display_mode'=>$row['display_mode']??'session','skip_delay'=>(int)($row['skip_delay']??0)];
}

function et_public_placements(PDO $database, string $page, string $base): array
{
    if (!isset(et_placement_pages()[$page])) { return []; }
    $statement = $database->prepare("SELECT * FROM placement_items WHERE status='published'
        AND (starts_at IS NULL OR starts_at<=?) AND (ends_at IS NULL OR ends_at>?) ORDER BY priority DESC,id DESC");
    $now = et_utc_now(); $statement->execute([$now,$now]);
    $items=[]; $slots=['top'=>0,'bottom'=>0,'popup'=>0];
    while ($row=$statement->fetch()) {
        $targets=json_decode($row['targets'],true);
        if (!is_array($targets) || !et_placement_matches($targets,$page) || !isset($slots[$row['slot']])) { continue; }
        if ($slots[$row['slot']] >= ($row['slot']==='bottom'?2:1)) { continue; }
        $entry=et_placement_entry($database,$row,$base);
        if ($entry) { $items[]=$entry; $slots[$row['slot']]++; }
        if ($slots['top']===1 && $slots['bottom']===2 && $slots['popup']===1) { break; }
    }
    return $items;
}
