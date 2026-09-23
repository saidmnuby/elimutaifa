<?php
declare(strict_types=1);

require_once __DIR__ . '/admin_db.php';

const ET_CONTENT_CATEGORIES = [
    'announcement' => 'Tangazo',
    'news' => 'Habari mpya',
    'results' => 'Matokeo',
    'selection' => 'Selection',
    'admission' => 'Admission',
    'scholarship' => 'Scholarship',
];

const ET_CONTENT_AUDIENCES = [
    'all' => 'Wote',
    'students' => 'Wanafunzi',
    'parents' => 'Wazazi',
    'teachers' => 'Walimu',
    'schools' => 'Shule',
    'organizations' => 'Mashirika',
];

const ET_CONTENT_STATUSES = [
    'draft' => 'Draft',
    'scheduled' => 'Scheduled',
    'published' => 'Published',
    'archived' => 'Archived',
];

const ET_CONTENT_MEDIA_TYPES = [
    'none' => 'Hakuna media',
    'image' => 'Picha',
    'youtube' => 'YouTube video',
];

const ET_CONTENT_IMAGE_MAX_BYTES = 5242880;

function et_slugify(string $value): string
{
    $value = trim(mb_strtolower($value, 'UTF-8'));
    $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    if (is_string($transliterated)) {
        $value = $transliterated;
    }
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    return trim($value, '-');
}

function et_validate_content_input(array $input): array
{
    $data = [
        'category' => trim((string) ($input['category'] ?? 'announcement')),
        'title' => trim((string) ($input['title'] ?? '')),
        'slug' => et_slugify((string) ($input['slug'] ?? '')),
        'excerpt' => trim((string) ($input['excerpt'] ?? '')),
        'body' => trim((string) ($input['body'] ?? '')),
        'media_type' => trim((string) ($input['media_type'] ?? 'none')),
        'media_url' => trim((string) ($input['media_url'] ?? '')),
        'media_caption' => trim((string) ($input['media_caption'] ?? '')),
        'audience' => trim((string) ($input['audience'] ?? 'all')),
        'source_name' => trim((string) ($input['source_name'] ?? '')),
        'source_url' => trim((string) ($input['source_url'] ?? '')),
        'destination_type' => trim((string) ($input['destination_type'] ?? 'internal')),
        'external_url' => trim((string) ($input['external_url'] ?? '')),
        'status' => trim((string) ($input['status'] ?? 'draft')),
        'is_featured' => isset($input['is_featured']) ? 1 : 0,
        'is_popup' => isset($input['is_popup']) ? 1 : 0,
        'published_at' => et_local_datetime_to_utc((string) ($input['published_at'] ?? '')),
        'expires_at' => et_local_datetime_to_utc((string) ($input['expires_at'] ?? '')),
    ];

    if ($data['slug'] === '' && $data['title'] !== '') {
        $data['slug'] = et_slugify($data['title']);
    }

    $errors = [];
    if (mb_strlen($data['title']) < 5 || mb_strlen($data['title']) > 140) {
        $errors['title'] = 'Use 5 to 140 characters for the title.';
    }
    if ($data['slug'] === '' || strlen($data['slug']) > 160) {
        $errors['slug'] = 'Enter a URL name with no more than 160 characters.';
    }
    if (mb_strlen($data['excerpt']) < 20 || mb_strlen($data['excerpt']) > 300) {
        $errors['excerpt'] = 'Use 20 to 300 characters for the short description.';
    }
    if (mb_strlen($data['body']) < 20 || mb_strlen($data['body']) > 20000) {
        $errors['body'] = 'Use 20 to 20,000 characters for the full text.';
    }
    if (!array_key_exists($data['category'], ET_CONTENT_CATEGORIES)) {
        $errors['category'] = 'Choose a valid category.';
    }
    if (!array_key_exists($data['audience'], ET_CONTENT_AUDIENCES)) {
        $errors['audience'] = 'Choose a valid audience.';
    }
    if (!array_key_exists($data['status'], ET_CONTENT_STATUSES)) {
        $errors['status'] = 'Choose a valid status.';
    }
    if (!array_key_exists($data['media_type'], ET_CONTENT_MEDIA_TYPES)) {
        $errors['media_type'] = 'Choose a valid media type.';
    }
    if (!in_array($data['destination_type'], ['internal', 'external'], true)) {
        $errors['destination_type'] = 'Choose a valid link type.';
    }
    if ($data['media_type'] !== 'none' && $data['destination_type'] === 'external') {
        $errors['destination_type'] = 'Images and embedded videos need an internal ElimuTaifa article.';
    }

    if (strlen($data['media_url']) > 1000) {
        $errors['media_url'] = 'The media URL must not exceed 1,000 characters.';
    } elseif ($data['media_type'] === 'image') {
        $hasPendingUpload = ($input['_has_image_upload'] ?? false) === true;
        if (!$hasPendingUpload && ($data['media_url'] === '' || !et_is_safe_image_url($data['media_url']))) {
            $errors['media_url'] = 'Upload an image or enter its full HTTPS URL.';
        }
    } elseif ($data['media_type'] === 'youtube') {
        $videoId = et_youtube_video_id($data['media_url']);
        if ($videoId === null) {
            $errors['media_url'] = 'Enter a valid YouTube video link.';
        } else {
            $data['media_url'] = 'https://www.youtube.com/watch?v=' . $videoId;
        }
    } elseif ($data['media_type'] === 'none') {
        $data['media_url'] = '';
        $data['media_caption'] = '';
    }
    if (mb_strlen($data['media_caption']) > 200) {
        $errors['media_caption'] = 'The media description must not exceed 200 characters.';
    }

    foreach (['source_url', 'external_url'] as $urlField) {
        if ($data[$urlField] !== '' && !et_is_safe_public_url($data[$urlField])) {
            $errors[$urlField] = 'Use a full HTTP or HTTPS URL.';
        }
    }
    if ($data['destination_type'] === 'external' && $data['external_url'] === '') {
        $errors['external_url'] = 'Enter the external website URL.';
    }
    if ($data['status'] === 'scheduled' && $data['published_at'] === null) {
        $errors['published_at'] = 'Set a publish time for scheduled content.';
    }
    if (trim((string) ($input['published_at'] ?? '')) !== '' && $data['published_at'] === null) {
        $errors['published_at'] = 'Invalid publish time.';
    }
    if (trim((string) ($input['expires_at'] ?? '')) !== '' && $data['expires_at'] === null) {
        $errors['expires_at'] = 'Invalid expiry time.';
    }
    if (mb_strlen($data['source_name']) > 120) {
        $errors['source_name'] = 'The source name must not exceed 120 characters.';
    }
    if ($data['expires_at'] !== null && $data['published_at'] !== null && $data['expires_at'] <= $data['published_at']) {
        $errors['expires_at'] = 'Expiry must be after the publish time.';
    }

    return [$data, $errors];
}

function et_is_safe_public_url(string $url): bool
{
    if (filter_var($url, FILTER_VALIDATE_URL) === false) {
        return false;
    }
    $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
    return in_array($scheme, ['http', 'https'], true);
}

function et_is_safe_image_url(string $url): bool
{
    if (et_is_managed_content_image($url)) {
        return true;
    }
    if (filter_var($url, FILTER_VALIDATE_URL) === false) {
        return false;
    }
    return strtolower((string) parse_url($url, PHP_URL_SCHEME)) === 'https';
}

function et_is_managed_content_image(string $path): bool
{
    return preg_match('#^uploads/content/[a-f0-9]{32}\.(?:jpg|png|webp)$#', $path) === 1;
}

function et_store_content_image_upload(?array $file): array
{
    if (!$file || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return [null, null];
    }
    if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return [null, 'Picha haikuweza kupakiwa. Jaribu tena.'];
    }

    $size = (int) ($file['size'] ?? 0);
    $temporaryPath = (string) ($file['tmp_name'] ?? '');
    if ($size <= 0 || $size > ET_CONTENT_IMAGE_MAX_BYTES || $temporaryPath === '' || !is_uploaded_file($temporaryPath)) {
        return [null, 'Picha iwe JPG, PNG au WebP na isizidi MB 5.'];
    }

    $mimeDetector = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = (string) $mimeDetector->file($temporaryPath);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $imageDetails = @getimagesize($temporaryPath);
    if (!isset($extensions[$mimeType]) || $imageDetails === false || ($imageDetails['mime'] ?? '') !== $mimeType) {
        return [null, 'Faili lililochaguliwa si picha halali ya JPG, PNG au WebP.'];
    }
    if ((int) ($imageDetails[0] ?? 0) > 8000 || (int) ($imageDetails[1] ?? 0) > 8000) {
        return [null, 'Vipimo vya picha visizidi pixel 8,000 kwa upande wowote.'];
    }

    $uploadDirectory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'content';
    if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
        return [null, 'Sehemu ya kuhifadhi picha haipatikani.'];
    }
    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mimeType];
    $target = $uploadDirectory . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file($temporaryPath, $target)) {
        return [null, 'Picha haikuweza kuhifadhiwa.'];
    }
    @chmod($target, 0644);
    return ['uploads/content/' . $filename, null];
}

function et_delete_managed_content_image(string $path): bool
{
    if (!et_is_managed_content_image($path)) {
        return true;
    }
    $target = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
    return !is_file($target) || unlink($target);
}

function et_youtube_video_id(string $url): ?string
{
    if (filter_var($url, FILTER_VALIDATE_URL) === false) {
        return null;
    }
    if (strtolower((string) parse_url($url, PHP_URL_SCHEME)) !== 'https') {
        return null;
    }

    $host = strtolower((string) parse_url($url, PHP_URL_HOST));
    $host = preg_replace('/^(www\.|m\.|music\.)/', '', $host) ?? $host;
    $path = trim((string) parse_url($url, PHP_URL_PATH), '/');
    $videoId = '';
    if ($host === 'youtu.be') {
        $videoId = explode('/', $path)[0] ?? '';
    } elseif ($host === 'youtube.com') {
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        if ($path === 'watch') {
            $videoId = (string) ($query['v'] ?? '');
        } elseif (preg_match('#^(?:embed|shorts|live)/([^/]+)#', $path, $matches)) {
            $videoId = $matches[1];
        }
    } elseif ($host === 'youtube-nocookie.com' && preg_match('#^embed/([^/]+)#', $path, $matches)) {
        $videoId = $matches[1];
    }

    return preg_match('/^[A-Za-z0-9_-]{11}$/', $videoId) ? $videoId : null;
}

function et_content_image_url(array $item, string $siteBasePath = ''): string
{
    $url = (string) ($item['media_url'] ?? '');
    if (($item['media_type'] ?? 'none') !== 'image' || !et_is_safe_image_url($url)) {
        return '';
    }
    if (et_is_managed_content_image($url)) {
        return rtrim($siteBasePath, '/') . '/' . $url;
    }
    return $url;
}

function et_content_youtube_id(array $item): ?string
{
    if (($item['media_type'] ?? 'none') !== 'youtube') {
        return null;
    }
    return et_youtube_video_id((string) ($item['media_url'] ?? ''));
}

function et_youtube_thumbnail_url(?string $videoId): string
{
    if ($videoId === null || preg_match('/^[A-Za-z0-9_-]{11}$/', $videoId) !== 1) {
        return '';
    }
    return 'https://i.ytimg.com/vi/' . rawurlencode($videoId) . '/hqdefault.jpg';
}

function et_local_datetime_to_utc(string $value): ?string
{
    $value = trim($value);
    if ($value === '') {
        return null;
    }
    $date = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $value, new DateTimeZone('Africa/Dar_es_Salaam'));
    if (!$date) {
        return null;
    }
    return $date->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
}

function et_utc_datetime_to_local(?string $value): string
{
    if ($value === null || trim($value) === '') {
        return '';
    }
    try {
        return (new DateTimeImmutable($value, new DateTimeZone('UTC')))
            ->setTimezone(new DateTimeZone('Africa/Dar_es_Salaam'))
            ->format('Y-m-d\TH:i');
    } catch (Exception) {
        return '';
    }
}

function et_public_content(PDO $database, int $limit = 6): array
{
    $limit = max(1, min($limit, 20));
    $statement = $database->prepare(<<<'SQL'
SELECT id, category, title, slug, excerpt, body, audience, source_name, source_url,
       media_type, media_url, media_caption, destination_type, external_url,
       is_featured, is_popup, published_at, expires_at, updated_at
FROM content_items
WHERE status IN ('published', 'scheduled')
  AND published_at IS NOT NULL
  AND published_at <= :now
  AND (expires_at IS NULL OR expires_at > :expires_now)
ORDER BY is_featured DESC, published_at DESC, id DESC
LIMIT :limit
SQL);
    $statement->bindValue(':now', et_utc_now());
    $statement->bindValue(':expires_now', et_utc_now());
    $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
    $statement->execute();
    return $statement->fetchAll();
}

function et_release_scheduled_content(PDO $database): int
{
    $statement = $database->prepare(<<<'SQL'
UPDATE content_items
SET status = 'published', updated_at = :updated_now
WHERE status = 'scheduled'
  AND published_at IS NOT NULL
  AND published_at <= :now
SQL);
    $statement->execute(['now' => et_utc_now(), 'updated_now' => et_utc_now()]);
    return $statement->rowCount();
}

function et_refresh_content_states(PDO $database): int
{
    $changed = et_release_scheduled_content($database);
    $statement = $database->prepare(<<<'SQL'
UPDATE content_items
SET status = 'archived', is_popup = 0, updated_at = :updated_now
WHERE status = 'published'
  AND expires_at IS NOT NULL
  AND expires_at <= :now
SQL);
    $statement->execute(['now' => et_utc_now(), 'updated_now' => et_utc_now()]);
    return $changed + $statement->rowCount();
}

function et_content_href(array $item, string $internalPrefix = 'announcements/'): string
{
    if (($item['destination_type'] ?? '') === 'external' && et_is_safe_public_url((string) ($item['external_url'] ?? ''))) {
        return (string) $item['external_url'];
    }
    return $internalPrefix . rawurlencode((string) $item['slug']) . '/';
}

function et_build_sitemap_xml(PDO $database, string $lastModifiedDate = ''): string
{
    $lastModifiedDate = $lastModifiedDate !== '' ? $lastModifiedDate : gmdate('Y-m-d');
    $urls = [
        ['url' => 'https://saidmnuby.github.io/elimutaifa/', 'lastmod' => $lastModifiedDate],
        ['url' => 'https://saidmnuby.github.io/elimutaifa/results/acsee/', 'lastmod' => $lastModifiedDate],
        ['url' => 'https://saidmnuby.github.io/elimutaifa/results/csee/', 'lastmod' => $lastModifiedDate],
        ['url' => 'https://saidmnuby.github.io/elimutaifa/results/ftna/', 'lastmod' => $lastModifiedDate],
        ['url' => 'https://saidmnuby.github.io/elimutaifa/results/psle/', 'lastmod' => $lastModifiedDate],
        ['url' => 'https://saidmnuby.github.io/elimutaifa/results/sfna/', 'lastmod' => $lastModifiedDate],
        ['url' => 'https://saidmnuby.github.io/elimutaifa/about/', 'lastmod' => $lastModifiedDate],
        ['url' => 'https://saidmnuby.github.io/elimutaifa/contact/', 'lastmod' => $lastModifiedDate],
        ['url' => 'https://saidmnuby.github.io/elimutaifa/contribution/', 'lastmod' => $lastModifiedDate],
        ['url' => 'https://saidmnuby.github.io/elimutaifa/privacy/', 'lastmod' => $lastModifiedDate],
        ['url' => 'https://saidmnuby.github.io/elimutaifa/announcements/', 'lastmod' => $lastModifiedDate],
        ['url' => 'https://saidmnuby.github.io/elimutaifa/selection/form-one/', 'lastmod' => $lastModifiedDate],
        ['url' => 'https://saidmnuby.github.io/elimutaifa/selection/form-five/', 'lastmod' => $lastModifiedDate],
    ];

    $statement = $database->prepare(<<<'SQL'
SELECT slug, updated_at
FROM content_items
WHERE status = 'published'
  AND destination_type = 'internal'
  AND published_at IS NOT NULL
  AND published_at <= :now
  AND (expires_at IS NULL OR expires_at > :expires_now)
ORDER BY published_at DESC
SQL);
    $statement->execute(['now' => et_utc_now(), 'expires_now' => et_utc_now()]);
    foreach ($statement->fetchAll() as $item) {
        $urls[] = [
            'url' => 'https://saidmnuby.github.io/elimutaifa/announcements/' . rawurlencode((string) $item['slug']) . '/',
            'lastmod' => substr((string) $item['updated_at'], 0, 10),
        ];
    }

    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    $xml .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
    foreach ($urls as $url) {
        $xml .= "    <url>\n";
        $xml .= '        <loc>' . htmlspecialchars($url['url'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</loc>\n";
        $xml .= '        <lastmod>' . htmlspecialchars($url['lastmod'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</lastmod>\n";
        $xml .= "    </url>\n";
    }
    return $xml . "</urlset>\n";
}

function et_rebuild_sitemap(PDO $database): bool
{
    $configuredTarget = getenv('ELIMUTAIFA_SITEMAP_PATH');
    $target = is_string($configuredTarget) && trim($configuredTarget) !== ''
        ? $configuredTarget
        : dirname(__DIR__) . DIRECTORY_SEPARATOR . 'sitemap.xml';
    $temporary = $target . '.tmp';
    $bytes = file_put_contents($temporary, et_build_sitemap_xml($database), LOCK_EX);
    if ($bytes === false) {
        return false;
    }
    if (!rename($temporary, $target)) {
        @unlink($temporary);
        return false;
    }
    return true;
}
