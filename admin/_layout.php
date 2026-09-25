<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin_auth.php';

/** Translate shared UI labels without changing public labels or stored content. */
function et_admin_label(string $label): string
{
    return [
        'Tangazo'=>'Announcement', 'Habari mpya'=>'News', 'Matokeo'=>'Exam results',
        'Wote'=>'Everyone', 'Wanafunzi'=>'Students', 'Wazazi'=>'Parents',
        'Walimu'=>'Teachers', 'Shule'=>'Schools', 'Mashirika'=>'Organisations',
        'Kurasa zote za umma'=>'All public pages', 'Search pages zote'=>'All search pages',
        'Result pages zote'=>'All result pages', 'School lists zote'=>'All school lists',
        'Kurasa za taarifa'=>'Information pages', 'Error pages zote'=>'All error pages',
    ][$label] ?? $label;
}

function et_admin_header(string $title, array $user, string $active = 'dashboard', string $base = ''): void
{
    $GLOBALS['et_admin_layout_base'] = $base;
    $flash = et_take_flash();
    $navItems = [
        'dashboard' => ['Dashboard', $base . 'index.php'],
        'content' => ['Content', $base . 'content/'],
        'placements' => ['Sponsors view', $base . 'placements/'],
        'submissions' => ['Messages', $base . 'submissions/'],
        'sources' => ['Result Pages', $base . 'sources.php'],
        'monitoring' => ['Traffic & Errors', $base . 'monitoring/'],
        'audit' => ['Audit Log', $base . 'audit.php'],
        'account' => ['Account', $base . 'account.php'],
    ];
    if (($user['role'] ?? '') === 'owner') {
        $navItems['users'] = ['Admins', $base . 'users/'];
    }
    ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#031B4E">
    <title><?= et_e($title) ?> | ElimuTaifa Admin</title>
    <link rel="icon" type="image/x-icon" href="<?= et_e($base) ?>../assets/img/brand/favicon32px.ico">
    <link rel="stylesheet" href="<?= et_e($base) ?>assets/admin.css">
</head>
<body>
<div class="admin-shell">
    <aside class="admin-sidebar" id="adminSidebar">
        <a class="admin-brand" href="<?= et_e($base) ?>index.php">
            <img src="<?= et_e($base) ?>../assets/img/brand/circle_logo.png" alt="">
            <span><strong>ElimuTaifa</strong><small>Content Manager</small></span>
        </a>
        <nav aria-label="Admin navigation">
            <?php foreach ($navItems as $key => [$label, $href]): ?>
                <a href="<?= et_e($href) ?>"<?= $active === $key ? ' class="active" aria-current="page"' : '' ?>><?= et_e($label) ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="admin-sidebar-bottom">
            <a href="<?= et_e($base) ?>../" target="_blank" rel="noopener">Open website ↗</a>
            <form action="<?= et_e($base) ?>logout.php" method="post">
                <input type="hidden" name="csrf_token" value="<?= et_e(et_csrf_token()) ?>">
                <button type="submit">Sign out</button>
            </form>
        </div>
    </aside>
    <div class="admin-main">
        <header class="admin-topbar">
            <div class="admin-topbar-user">
                <button type="button" class="admin-menu-button" id="adminMenuButton" aria-controls="adminSidebar" aria-expanded="false">☰</button>
                <div><span id="display_name"><?= et_e($user['display_name']) ?></span><small><?= et_e(ucfirst((string) ($user['role'] ?? 'admin'))) ?></small></div>
            </div>
            <h1 class="admin-topbar-title"><?= et_e($title) ?></h1>
        </header>
        <main class="admin-content">
            <?php if ($user['role'] === 'owner' && et_local_development_access() && !et_mfa_state((int) $user['id'])): ?>
                <div class="admin-alert" role="status">Local development: the owner can set up 2FA later. A password is still required. The live site requires 2FA.</div>
            <?php endif; ?>
            <?php if ($flash): ?>
                <div class="admin-alert <?= et_e($flash['type'] ?? 'info') ?>" role="status"><?= et_e($flash['message'] ?? '') ?></div>
            <?php endif; ?>
    <?php
}

function et_admin_footer(): void
{
    $base = (string) ($GLOBALS['et_admin_layout_base'] ?? '');
    ?>
        </main>
    </div>
</div>
<script src="<?= et_e($base) ?>assets/admin.js"></script>
</body>
</html>
    <?php
}

function et_admin_status_badge(string $status): string
{
    return '<span class="status-badge status-' . et_e($status) . '">' . et_e(ET_CONTENT_STATUSES[$status] ?? ucfirst($status)) . '</span>';
}

function et_admin_pagination(int $currentPage, int $totalPages, array $query = [], string $label = 'Pagination'): void
{
    $totalPages = max(1, $totalPages);
    $currentPage = max(1, min($currentPage, $totalPages));
    $query = array_filter($query, static fn(mixed $value): bool => $value !== '' && $value !== null);
    $pageUrl = static function (int $page) use ($query): string {
        return '?' . http_build_query([...$query, 'page' => $page], '', '&', PHP_QUERY_RFC3986);
    };
    $startPage = max(1, min($currentPage - 2, $totalPages - 4));
    $endPage = min($totalPages, $startPage + 4);
    ?>
    <nav class="admin-pagination" aria-label="<?= et_e($label) ?>">
        <?php if ($currentPage > 1): ?>
            <a href="<?= et_e($pageUrl(1)) ?>" aria-label="First page">« First</a>
            <a href="<?= et_e($pageUrl($currentPage - 1)) ?>" aria-label="Previous page">‹ Previous</a>
        <?php else: ?>
            <span class="is-disabled" aria-disabled="true">« First</span>
            <span class="is-disabled" aria-disabled="true">‹ Previous</span>
        <?php endif; ?>

        <?php for ($pageNumber = $startPage; $pageNumber <= $endPage; $pageNumber++): ?>
            <?php if ($pageNumber === $currentPage): ?>
                <span class="page-number is-current" aria-current="page"><?= $pageNumber ?></span>
            <?php else: ?>
                <a class="page-number" href="<?= et_e($pageUrl($pageNumber)) ?>" aria-label="Page <?= $pageNumber ?>"><?= $pageNumber ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($currentPage < $totalPages): ?>
            <a href="<?= et_e($pageUrl($currentPage + 1)) ?>" aria-label="Next page">Next ›</a>
            <a href="<?= et_e($pageUrl($totalPages)) ?>" aria-label="Last page">Last »</a>
        <?php else: ?>
            <span class="is-disabled" aria-disabled="true">Next ›</span>
            <span class="is-disabled" aria-disabled="true">Last »</span>
        <?php endif; ?>
    </nav>
    <?php
}
