<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/validation.php';
require_once dirname(__DIR__) . '/includes/result_request.php';
require_once dirname(__DIR__) . '/includes/admin_db.php';
require_once dirname(__DIR__) . '/includes/content.php';
require_once dirname(__DIR__) . '/includes/district_directory.php';
require_once dirname(__DIR__) . '/admin/_layout.php';

$failures = [];

function expect(bool $condition, string $message): void
{
    global $failures;
    if (!$condition) {
        $failures[] = $message;
    }
}

expect(grf_is_valid_exam_year(2025), 'Current examination year should be accepted.');
expect(!grf_is_valid_exam_year(2009), 'Year below the supported range should be rejected.');
expect(!grf_is_valid_exam_year('2025'), 'String years must be normalized before validation.');
expect(grf_is_valid_secondary_candidate('S0101/0001'), 'Valid secondary index should be accepted.');
expect(grf_is_valid_secondary_candidate('PQ0101/0001'), 'Valid qualifying secondary index should be accepted.');
expect(!grf_is_valid_secondary_candidate('PS010101-0001'), 'Primary index must not pass secondary validation.');
expect(grf_is_valid_secondary_candidate('S5682/0501'), 'A current ACSEE school candidate format should be accepted without a stale centre allow-list.');
expect(grf_is_valid_secondary_candidate('P1495/0501'), 'A current ACSEE private-centre format should be accepted without a stale centre allow-list.');
expect(grf_is_valid_primary_candidate('PS0101010-0001'), 'Valid primary index should be accepted.');
expect(grf_is_valid_primary_candidate('PS0101010-001'), 'Three-digit primary candidate number should be accepted.');
expect(!grf_is_valid_primary_candidate('S0101/0001'), 'Secondary index must not pass primary validation.');
expect(count(grf_district_directory()) === 26, 'The district directory should contain all 26 mainland regions.');
expect(array_sum(array_map('count', grf_district_directory())) === 184, 'The district directory should contain all 184 current council entries.');
expect(grf_district_code_for_selection('tanga', 'Bumbuli') === '2011', 'Bumbuli should map to Tanga district code 2011.');
expect(grf_district_code_for_selection('singida', 'Itigi') === '1807', 'Itigi should map to Singida district code 1807.');
expect(grf_district_code_for_selection('simiyu', 'Bariadi Town') === '2701', 'Bariadi Town should use district code 2701.');
expect(grf_district_code_for_selection('arusha', 'Bumbuli') === null, 'A council must be rejected when it does not belong to the submitted region.');
expect(grf_district_code_for_selection('dar-es-salaam', 'Ilala Municipal') === null, 'The obsolete duplicate Ilala Municipal option should not be accepted.');

$blockedRequest = grf_fetch_result('http://127.0.0.1/internal');
expect($blockedRequest['status'] === 400, 'Non-allow-listed upstream hosts must be rejected.');

expect(et_slugify('Matokeo ya Kidato cha Nne 2026') === 'matokeo-ya-kidato-cha-nne-2026', 'Content titles should produce stable slugs.');
expect(et_is_safe_public_url('https://example.com/notice'), 'HTTPS content destinations should be accepted.');
expect(!et_is_safe_public_url('javascript:alert(1)'), 'Unsafe destination schemes must be rejected.');

$testDatabasePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'elimutaifa-test-' . bin2hex(random_bytes(6)) . '.sqlite';
$testDatabase = et_open_database($testDatabasePath);
$tables = $testDatabase->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
expect(in_array('admin_users', $tables, true), 'Admin user table should be created by migration.');
expect(in_array('content_items', $tables, true), 'Content table should be created by migration.');
expect(in_array('submissions', $tables, true), 'Submission table should be created by migration.');
expect(in_array('traffic_daily', $tables, true), 'Traffic summary table should be created by migration.');
expect(in_array('system_events', $tables, true), 'System event table should be created by migration.');
$adminColumns = array_column($testDatabase->query('PRAGMA table_info(admin_users)')->fetchAll(), 'name');
expect(in_array('role', $adminColumns, true) && in_array('deleted_at', $adminColumns, true), 'Admin role and removal columns should exist.');
$contentColumns = array_column($testDatabase->query('PRAGMA table_info(content_items)')->fetchAll(), 'name');
expect(
    in_array('media_type', $contentColumns, true) && in_array('media_url', $contentColumns, true) && in_array('media_caption', $contentColumns, true),
    'Content media columns should exist.'
);
expect(et_normalize_upstream_target('https://onlinesys.necta.go.tz/results/2026/psle/results/distr_1101.htm') === 'onlinesys.necta.go.tz/results/2026/psle/results/distr_0000.htm', 'District monitoring targets should redact the district code.');
expect(!str_contains(et_normalize_public_path('/results/S0101%2F0001/'), 'S0101'), 'Monitoring paths should redact candidate numbers.');
ob_start();
et_admin_pagination(3, 10, ['status' => 'new'], 'Test pagination');
$paginationHtml = (string) ob_get_clean();
expect(str_contains($paginationHtml, '?status=new&amp;page=2'), 'Pagination should preserve filters on the previous-page link.');
expect(str_contains($paginationHtml, 'aria-current="page">3</span>'), 'Pagination should identify the active page.');
expect(str_contains($paginationHtml, '?status=new&amp;page=10'), 'Pagination should provide access to the last page.');
expect(et_youtube_video_id('https://youtu.be/dQw4w9WgXcQ') === 'dQw4w9WgXcQ', 'YouTube share URLs should be recognized.');
expect(et_youtube_video_id('https://www.youtube.com/shorts/dQw4w9WgXcQ?feature=share') === 'dQw4w9WgXcQ', 'YouTube Shorts URLs should be recognized.');
expect(et_youtube_video_id('https://example.com/watch?v=dQw4w9WgXcQ') === null, 'Non-YouTube video URLs should be rejected.');
expect(et_youtube_thumbnail_url('dQw4w9WgXcQ') === 'https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg', 'A valid YouTube ID should produce a cover image URL.');
expect(et_youtube_thumbnail_url('../invalid') === '', 'Invalid YouTube IDs must not produce cover image URLs.');
expect(et_is_safe_image_url('https://images.example.com/notice.webp'), 'HTTPS image URLs should be accepted.');
expect(!et_is_safe_image_url('http://images.example.com/notice.webp'), 'Insecure image URLs should be rejected.');
[$videoContent, $videoContentErrors] = et_validate_content_input([
    'category' => 'announcement',
    'title' => 'Tangazo lenye video',
    'slug' => 'tangazo-lenye-video',
    'excerpt' => 'Maelezo mafupi ya tangazo lenye video ya majaribio.',
    'body' => 'Haya ni maudhui kamili ya tangazo lenye video ya majaribio.',
    'media_type' => 'youtube',
    'media_url' => 'https://youtu.be/dQw4w9WgXcQ?t=10',
    'media_caption' => 'Maelezo ya video',
    'audience' => 'all',
    'destination_type' => 'internal',
    'status' => 'draft',
]);
expect($videoContentErrors === [], 'Valid YouTube content should pass admin validation.');
expect($videoContent['media_url'] === 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'YouTube URLs should be normalized before storage.');
[,$invalidContentErrors] = et_validate_content_input(['title' => 'x']);
expect(isset($invalidContentErrors['title'], $invalidContentErrors['excerpt'], $invalidContentErrors['body']), 'Invalid admin content must return field errors.');
$sitemapXml = et_build_sitemap_xml($testDatabase, '2026-09-12');
$parsedSitemap = simplexml_load_string($sitemapXml);
expect($parsedSitemap !== false && count($parsedSitemap->url) === 11, 'Generated sitemap should contain all base public pages.');
$testPasswordHash = password_hash('Test-Admin-Password-2026!', PASSWORD_DEFAULT);
$testDatabase->prepare(<<<'SQL'
INSERT INTO admin_users (username, display_name, password_hash, is_active, created_at, updated_at)
VALUES ('testadmin', 'Test Admin', :password_hash, 1, :created_at, :updated_at)
SQL)->execute(['password_hash' => $testPasswordHash, 'created_at' => et_utc_now(), 'updated_at' => et_utc_now()]);
expect(password_verify('Test-Admin-Password-2026!', $testPasswordHash), 'Admin password hashes should verify correctly.');
$adminId = (int) $testDatabase->lastInsertId();
$testDatabase->prepare(<<<'SQL'
INSERT INTO content_items
(category,title,slug,excerpt,body,audience,source_name,source_url,destination_type,external_url,status,is_featured,is_popup,published_at,expires_at,created_by,updated_by,created_at,updated_at)
VALUES
('announcement','Test announcement','test-announcement','A valid test announcement excerpt.','A valid test announcement body for publication.','all','','','internal','','published',1,1,:published_at,NULL,:admin_id,:admin_id,:created_at,:updated_at)
SQL)->execute(['published_at' => et_utc_now(), 'admin_id' => $adminId, 'created_at' => et_utc_now(), 'updated_at' => et_utc_now()]);
$publicContent = et_public_content($testDatabase, 6);
expect(count($publicContent) === 1 && $publicContent[0]['slug'] === 'test-announcement', 'Published content should be returned to the public announcement feed.');
$dynamicSitemap = simplexml_load_string(et_build_sitemap_xml($testDatabase, '2026-09-12'));
expect($dynamicSitemap !== false && count($dynamicSitemap->url) === 12, 'Published internal announcements should be added to the sitemap.');
$testDatabase = null;
foreach ([$testDatabasePath, $testDatabasePath . '-shm', $testDatabasePath . '-wal'] as $temporaryDatabaseFile) {
    if (is_file($temporaryDatabaseFile)) {
        unlink($temporaryDatabaseFile);
    }
}

if ($failures !== []) {
    fwrite(STDERR, "FAIL\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "PASS: validation, upstream allow-list, admin database, content feed, and sitemap checks\n";
