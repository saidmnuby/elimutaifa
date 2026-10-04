<?php
declare(strict_types=1);
require_once __DIR__ . '/exam_cycles.php';

/** Keep previously saved check messages readable without changing audit history. */
function et_exam_display_report(string $report): string {
    return strtr($report, [
        'Sample haijathibitishwa: ' => 'The sample check failed: ',
        'Hakuna directory yenye matokeo iliyothibitishwa. Huenda mwaka haujatolewa, source imehamishwa, au muundo umebadilika.' => 'No result directory passed the check. The results may not be released yet, the source may have moved, or its layout may have changed.',
        'Source haijafikiwa. Kagua internet, HTTPS certificate au hali ya provider, kisha jaribu tena.' => 'The source could not be reached. Check the connection, HTTPS certificate or provider status, then try again.',
        'Maombi yamefikia kikomo. Subiri dakika moja kisha kagua tena.' => 'Too many requests. Wait one minute, then check again.',
        'Ukaguzi wa sample umefikia kikomo. Jaribu tena au kagua link kupitia Advanced settings.' => 'The check reached its page limit. Try again or review the paths in Advanced settings.',
        'Source settings si sahihi. Kagua Advanced settings.' => 'The source settings are not valid. Review Advanced settings.',
    ]);
}

function et_exam_provider(array $cycle): string {
    return parse_url($cycle['base_url'], PHP_URL_HOST) === 'maktaba.tetea.org' ? 'tetea' : 'necta';
}

function et_exam_provider_cycle(string $level, int $year, string $provider, ?array $current = null): array {
    if (!in_array($provider, ['necta','tetea'], true)) throw new InvalidArgumentException('Choose NECTA or Maktaba / TETEA.');
    if ($current && et_exam_provider($current) === $provider) return $current;
    $cycle = et_exam_legacy_cycle($level, $year);
    $archive = $provider === 'tetea';
    $cycle['base_url'] = $archive ? 'https://maktaba.tetea.org/exam-results/' . strtoupper($level) . $year . '/'
        : 'https://' . ($level === 'acsee' && $year === 2026 ? 'matokeo.necta.go.tz' : 'onlinesys.necta.go.tz') . '/results/' . $year . '/' . $level . '/';
    $cycle['school_path'] = ($archive ? '' : 'results/') . ($level === 'psle' ? 'shl_' : '') . '{school}.htm';
    $cycle['directory_path'] = in_array($level,['psle','sfna'],true) ? ($archive ? '' : 'results/') . ($level === 'sfna' ? 'distr_ps' : 'distr_') . '{district}.htm'
        : ($archive && $level === 'ftna' ? 'ftna.htm' : 'index.htm');
    $cycle['school_case'] = $level === 'ftna' && !$archive ? 'upper' : 'lower';
    return et_exam_prepare_change(null, $cycle);
}

/** Resolve only HTML pages inside this approved examination/year directory. */
function et_exam_discovery_link(string $href, string $page, string $base): ?string {
    $href = str_replace('\\', '/', trim($href));
    if ($href === '' || str_contains($href, '?') || str_contains($href, '#') || str_contains($href, '\\') || str_starts_with($href,'//')) return null;
    if (str_contains($href, ':')) $url = $href;
    else {
        $p = parse_url($page); $parts = [];
        $path = str_starts_with($href,'/') ? $href : dirname($p['path']) . '/' . $href;
        foreach (explode('/',$path) as $part) {
            if ($part === '..') array_pop($parts);
            elseif ($part !== '' && $part !== '.') $parts[] = $part;
        }
        $url = 'https://' . $p['host'] . '/' . implode('/',$parts);
    }
    $relative = str_starts_with($url,$base) ? substr($url,strlen($base)) : '';
    return $relative !== '' && preg_match('#^[a-zA-Z0-9_/-]+\.(?:htm|html)$#D',$relative) && !str_contains($relative,'..') ? $url : null;
}

/** Discover a source sample with a bounded number of live pages; never scan all schools. */
function et_exam_auto_check(array $cycle, ?callable $fetch = null): array {
    if (et_exam_validate($cycle)) throw new RuntimeException('The source settings are not valid. Review Advanced settings.');
    if (!$fetch) {
        require_once __DIR__ . '/result_request.php';
        $fetch = static fn(string $url): array => grf_fetch_result($url,true);
    }
    $responses = []; $calls = 0; $trace = [];
    $boundedFetch = static function(string $url) use ($fetch, &$responses, &$calls, &$trace): array {
        if (isset($responses[$url])) return $responses[$url];
        if (++$calls > 8) throw new RuntimeException('The check reached its page limit. Try again or review the paths in Advanced settings.');
        $response = $fetch($url); $trace[] = ['page'=>$url,'status'=>(int)($response['status'] ?? 0)];
        if (($response['status'] ?? 0) === 429) throw new RuntimeException('Too many requests. Wait one minute, then check again.');
        if (($response['status'] ?? 0) === 0) throw new RuntimeException('The source could not be reached. Check the connection, HTTPS certificate or provider status, then try again.');
        return $responses[$url] = $response;
    };
    $primary = in_array($cycle['level'],['psle','sfna'],true);
    $paths = $primary ? ['index.htm','index.html'] : array_unique([$cycle['directory_path'],'index.htm','index.html', $cycle['level'] === 'ftna' ? 'ftna.htm' : 'index.htm']);
    $queue = array_map(static fn(string $path): string => $cycle['base_url'] . $path, $paths);
    if ($primary) {
        require_once __DIR__ . '/district_directory.php';
        $districts = array_merge(...array_values(grf_district_directory()));
        $hint = (string)($cycle['sample_district'] ?? '');
        if (!preg_match('/^\d{4}$/D',$hint)) $hint = (string)reset($districts);
        array_unshift($queue, et_exam_url($cycle,'directory',$hint));
    }
    $seen = []; $lastFailure = '';
    while ($queue && $calls < 7) {
        $page = array_shift($queue);
        if (isset($seen[$page])) continue;
        $seen[$page] = true;
        $response = $boundedFetch($page);
        if (($response['status'] ?? 0) !== 200 || !is_string($response['html'] ?? null)) continue;
        $dom = new DOMDocument(); $previous = libxml_use_internal_errors(true);
        try { $dom->loadHTML($response['html'],LIBXML_NONET); } finally { libxml_clear_errors(); libxml_use_internal_errors($previous); }
        $candidates = []; $directories = [];
        foreach ((new DOMXPath($dom))->query('//a[@href] | //frame[@src] | //iframe[@src]') as $link) {
            $href = $link->getAttribute($link->nodeName === 'a' ? 'href' : 'src');
            $url = et_exam_discovery_link($href,$page,$cycle['base_url']);
            if (!$url) continue;
            $pattern = $primary ? '/^(?:shl_)?(ps\d{4,7}|p\d{7})\.html?$/iD' : '/^([sp]q?\d{4})\.html?$/iD';
            if (preg_match($pattern,basename($url),$m)) $candidates[] = [$url,$m[1]];
            else $directories[] = $url;
        }
        foreach (array_slice($candidates,0,2) as [$schoolUrl,$sourceCode]) {
            $candidate = $cycle;
            $schoolCode = strtoupper($sourceCode);
            if ($primary && preg_match('/^P\d{7}$/D',$schoolCode)) $schoolCode = 'PS' . substr($schoolCode,1);
            $relative = substr($schoolUrl,strlen($cycle['base_url']));
            $candidate['school_path'] = str_replace($sourceCode,'{school}',$relative);
            $candidate['school_case'] = $sourceCode === strtoupper($sourceCode) ? 'upper' : 'lower';
            $candidate['school_format'] = $primary && !str_starts_with(strtoupper($sourceCode),'PS') ? 'primary_p' : 'standard';
            $candidate['sample_school'] = $schoolCode;
            $directoryPath = substr($page,strlen($cycle['base_url']));
            if ($primary) {
                if (!preg_match('/^(.*distr_(?:ps)?)(\d{4})(\.html?)$/iD',$directoryPath,$district)) continue;
                $candidate['directory_path'] = $district[1] . '{district}' . $district[3];
                $candidate['sample_district'] = $district[2];
            } else $candidate['directory_path'] = $directoryPath;
            try {
                $candidate['verification_report'] = et_exam_verify($candidate,$boundedFetch);
                $candidate['discovery_trace'] = $trace;
                return $candidate;
            } catch (Throwable $e) { $lastFailure = $e->getMessage(); }
            if ($calls >= 7) break;
        }
        // Prefer actual directory links over guessed filename alternatives.
        $queue = array_merge(array_slice($directories,0,3),$queue);
    }
    throw new RuntimeException($lastFailure !== '' ? 'The sample check failed: ' . $lastFailure : 'No result directory passed the check. The results may not be released yet, the source may have moved, or its layout may have changed.');
}

function et_exam_health(array $cycle, int $failures = 0): array {
    $checked = empty($cycle['last_check_at']) ? 0 : (strtotime($cycle['last_check_at'] . ' UTC') ?: 0);
    if (($cycle['last_check_ok'] ?? null) === false || $failures >= 3) return ['key'=>'issue','label'=>'Needs attention','hint'=>'Check the source again. Review the error message and Traffic & Errors.'];
    if ($cycle['status'] === 'archived' || $cycle['status'] === 'suspended') return ['key'=>'paused','label'=>'Paused','hint'=>'This year is hidden from public search. Check the source before publishing it again.'];
    if (empty($cycle['verified_at']) || !empty($cycle['inherited'])) return ['key'=>'pending','label'=>'Not checked','hint'=>'Click Check source. The system will choose a sample school.'];
    if ($checked < time()-86400) return ['key'=>'pending','label'=>'Check due','hint'=>'The last check is more than 24 hours old. Check again before a release or source change.'];
    return $cycle['status'] === 'published' ? ['key'=>'healthy','label'=>'Healthy','hint'=>'The sample check passed. This year is available to users.'] : ['key'=>'ready','label'=>'Ready to publish','hint'=>'The sample check passed. Click Publish cycle to make this year available to users.'];
}

function et_exam_monitor_check(?array $current, array $proposed, int $ownerId, ?callable $fetch = null): array {
    try {
        $checked = et_exam_auto_check($proposed,$fetch);
        $values = et_exam_prepare_change($current,$checked);
        $values['verification_report'] = $checked['verification_report']; $values['discovery_trace'] = $checked['discovery_trace'];
        $values['verified_at'] = et_utc_now(); $values['verified_by'] = $ownerId;
        $values['last_check_ok'] = true; $values['inherited'] = false;
    } catch (Throwable $e) {
        $values = $current ?? $proposed;
        $values['last_check_ok'] = false; $values['verification_report'] = $e->getMessage(); $values['discovery_trace'] = [];
    }
    $values['last_check_provider'] = et_exam_provider($proposed);
    $values['last_check_at'] = et_utc_now(); $values['updated_at'] = et_utc_now();
    return $values;
}
