<?php
$page_title = isset($page_title) ? (string) $page_title : 'Admin';
$hasShell = !empty($admin_user['id']);
$status = isset($admin_repository) && $admin_repository instanceof AdminRepository ? $admin_repository->status() : 'unavailable';
$statusLabel = $status === 'ready' ? 'Live database' : ($status === 'schema_missing' ? 'Schema required' : ($status === 'disabled' ? 'JSON mode' : 'Database offline'));
$adminCss = asset('css/admin.css') . '?v=' . rawurlencode((string) ($admin_css_version ?? '20260803'));
$adminJs = asset('js/admin.js') . '?v=' . rawurlencode((string) ($admin_js_version ?? '20260803'));
$currentPath = request_path($config);
?><!doctype html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <meta name="theme-color" content="#181410">
    <title><?= e($page_title) ?> — Raspina Admin</title>
    <link rel="stylesheet" href="<?= e($adminCss) ?>">
</head>
<body class="admin-body <?= $hasShell ? 'has-admin-shell' : 'is-admin-auth' ?>">
<a class="admin-skip" href="#admin-main">Skip to content</a>
<?php if ($hasShell): ?>
    <div class="admin-app" data-admin-app>
        <aside class="admin-sidebar" data-admin-sidebar>
            <div class="admin-brand-block">
                <a class="admin-brand" href="<?= e(admin_url('/')) ?>" aria-label="Raspina admin dashboard">
                    <span class="admin-brand-mark">R</span>
                    <span><strong>Raspina</strong><small>Studio operations</small></span>
                </a>
                <button class="admin-sidebar-close" type="button" data-admin-sidebar-close aria-label="Close navigation">×</button>
            </div>
            <div class="admin-sidebar-state <?= e($status) ?>"><span></span><span><?= e($statusLabel) ?></span></div>
            <nav class="admin-nav" aria-label="Admin navigation">
                <p class="admin-nav-label">Workspace</p>
                <a class="<?= $currentPath === '/admin' || $currentPath === '/admin/dashboard' ? 'is-active' : '' ?>" href="<?= e(admin_url('/')) ?>"><span class="nav-glyph">↗</span><span>Overview</span></a>
                <a class="<?= strpos($currentPath, '/admin/products') === 0 ? 'is-active' : '' ?>" href="<?= e(admin_url('/products')) ?>"><span class="nav-glyph">▦</span><span>Products</span></a>
                <a class="<?= strpos($currentPath, '/admin/categories') === 0 ? 'is-active' : '' ?>" href="<?= e(admin_url('/categories')) ?>"><span class="nav-glyph">◇</span><span>Categories</span></a>
                <a class="<?= strpos($currentPath, '/admin/messages') === 0 ? 'is-active' : '' ?>" href="<?= e(admin_url('/messages')) ?>"><span class="nav-glyph">◌</span><span>Enquiries</span></a>
                <p class="admin-nav-label admin-nav-label-spaced">Configuration</p>
                <a class="<?= strpos($currentPath, '/admin/settings') === 0 ? 'is-active' : '' ?>" href="<?= e(admin_url('/settings')) ?>"><span class="nav-glyph">⊙</span><span>Site settings</span></a>
            </nav>
            <div class="admin-sidebar-foot">
                <a href="<?= e(url('/')) ?>" target="_blank" rel="noopener">View live site <span aria-hidden="true">↗</span></a>
                <p>Raspina Clothing / Tehran</p>
            </div>
        </aside>
        <div class="admin-scrim" data-admin-scrim></div>
        <div class="admin-main-wrap">
            <header class="admin-topbar">
                <button class="admin-sidebar-open" type="button" data-admin-sidebar-open aria-label="Open navigation"><span></span><span></span><span></span></button>
                <div class="admin-breadcrumb"><span>RASPINA</span><b>/</b><strong><?= e($page_title) ?></strong></div>
                <div class="admin-top-actions">
                    <span class="admin-user-chip"><span class="admin-avatar"><?= e(strtoupper(substr((string) ($admin_user['name'] ?? 'A'), 0, 1))) ?></span><span class="admin-user-name"><?= e((string) ($admin_user['name'] ?? 'Administrator')) ?></span></span>
                    <form method="post" action="<?= e(admin_url('/logout')) ?>" class="admin-logout-form"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><button type="submit">Sign out</button></form>
                </div>
            </header>
            <main class="admin-main" id="admin-main">
                <?php if ($admin_flash): ?><div class="admin-alert <?= e($admin_flash['type']) ?>" role="status"><?= e($admin_flash['message']) ?></div><?php endif; ?>
                <?= $content ?>
            </main>
        </div>
    </div>
<?php else: ?>
    <main class="admin-auth" id="admin-main">
        <a class="admin-auth-brand" href="<?= e(url('/')) ?>" aria-label="Raspina Clothing home"><span>R</span><strong>Raspina</strong><small>Studio operations</small></a>
        <?php if ($admin_flash): ?><div class="admin-alert <?= e($admin_flash['type']) ?>" role="status"><?= e($admin_flash['message']) ?></div><?php endif; ?>
        <?= $content ?>
        <p class="admin-auth-foot">Raspina Clothing / private operations console</p>
    </main>
<?php endif; ?>
<script src="<?= e($adminJs) ?>" defer></script>
</body>
</html>
