<?php
$page_title = isset($page_title) ? (string) $page_title : 'مدیریت';
$hasShell = !empty($admin_user['id']);
$status = isset($admin_repository) && $admin_repository instanceof AdminRepository ? $admin_repository->status() : 'unavailable';

$statusLabel = 'دیتابیس آفلاین';
if ($status === 'ready') {
    $statusLabel = 'دیتابیس متصل (زنده)';
} elseif ($status === 'schema_missing') {
    $statusLabel = 'نیاز به اجرای مهاجرت (Schema)';
} elseif ($status === 'disabled') {
    $statusLabel = 'حالت آفلاین (JSON)';
}

$adminCss = asset('css/admin.css') . '?v=' . rawurlencode((string) ($admin_css_version ?? '20260803'));
$adminJs = asset('js/admin.js') . '?v=' . rawurlencode((string) ($admin_js_version ?? '20260803'));
$currentPath = request_path($config);

// دسترسی‌های پیشرفته برای نمایش منوهای جانبی
$repository = isset($admin_repository) ? $admin_repository : null;
$userId = !empty($admin_user['id']) ? (int) $admin_user['id'] : 0;
$canViewUsers = $repository && $userId > 0 && $repository->hasPermission($userId, 'users.view');
$canManageRoles = $repository && $userId > 0 && $repository->hasPermission($userId, 'roles.manage');
$canViewAudit = $repository && $userId > 0 && $repository->hasPermission($userId, 'audit.view');
?><!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <meta name="theme-color" content="#181410">
    <title><?= e($page_title) ?> — پنل مدیریت رسپینا</title>
    <link rel="stylesheet" href="<?= e($adminCss) ?>">
</head>
<body class="admin-body <?= $hasShell ? 'has-admin-shell' : 'is-admin-auth' ?>">
<a class="admin-skip" href="#admin-main">رفتن به محتوای اصلی</a>
<?php if ($hasShell): ?>
    <div class="admin-app" data-admin-app>
        <aside class="admin-sidebar" data-admin-sidebar>
            <div class="admin-brand-block">
                <a class="admin-brand" href="<?= e(admin_url('/')) ?>" aria-label="پیشخوان مدیریت رسپینا">
                    <span class="admin-brand-mark">R</span>
                    <span><strong>رسپینا</strong><small>عملیات آتلیه</small></span>
                </a>
                <button class="admin-sidebar-close" type="button" data-admin-sidebar-close aria-label="بستن منو">×</button>
            </div>
            <div class="admin-sidebar-state <?= e($status) ?>"><span></span><span><?= e($statusLabel) ?></span></div>
            <nav class="admin-nav" aria-label="منوی مدیریت">
                <p class="admin-nav-label">فضای کاری</p>
                <a class="<?= $currentPath === '/admin' || $currentPath === '/admin/dashboard' ? 'is-active' : '' ?>" href="<?= e(admin_url('/')) ?>"><span class="nav-glyph">↗</span><span>نمای کلی</span></a>
                <a class="<?= strpos($currentPath, '/admin/products') === 0 ? 'is-active' : '' ?>" href="<?= e(admin_url('/products')) ?>"><span class="nav-glyph">▦</span><span>محصولات</span></a>
                <a class="<?= strpos($currentPath, '/admin/categories') === 0 ? 'is-active' : '' ?>" href="<?= e(admin_url('/categories')) ?>"><span class="nav-glyph">◇</span><span>دسته‌بندی‌ها</span></a>
                <a class="<?= strpos($currentPath, '/admin/messages') === 0 ? 'is-active' : '' ?>" href="<?= e(admin_url('/messages')) ?>"><span class="nav-glyph">◌</span><span>درخواست‌های همکاری</span></a>

                <p class="admin-nav-label admin-nav-label-spaced">پیکربندی و تنظیمات</p>
                <a class="<?= strpos($currentPath, '/admin/settings') === 0 ? 'is-active' : '' ?>" href="<?= e(admin_url('/settings')) ?>"><span class="nav-glyph">⊙</span><span>تنظیمات سایت</span></a>

                <?php if ($canViewUsers || $canManageRoles || $canViewAudit): ?>
                    <p class="admin-nav-label admin-nav-label-spaced">مدیریت دسترسی‌ها</p>
                    <?php if ($canViewUsers): ?>
                        <a class="<?= strpos($currentPath, '/admin/users') === 0 ? 'is-active' : '' ?>" href="<?= e(admin_url('/users')) ?>"><span class="nav-glyph">👥</span><span>اعضای تیم</span></a>
                    <?php endif; ?>
                    <?php if ($canManageRoles): ?>
                        <a class="<?= strpos($currentPath, '/admin/roles') === 0 ? 'is-active' : '' ?>" href="<?= e(admin_url('/roles')) ?>"><span class="nav-glyph">🛡️</span><span>نقش‌ها و دسترسی‌ها</span></a>
                    <?php endif; ?>
                    <?php if ($canViewAudit): ?>
                        <a class="<?= strpos($currentPath, '/admin/audit') === 0 ? 'is-active' : '' ?>" href="<?= e(admin_url('/audit')) ?>"><span class="nav-glyph">📋</span><span>گزارش رویدادها</span></a>
                    <?php endif; ?>
                <?php endif; ?>
            </nav>
            <div class="admin-sidebar-foot">
                <a href="<?= e(url('/')) ?>" target="_blank" rel="noopener">مشاهده سایت اصلی <span aria-hidden="true">↗</span></a>
                <p>پوشاک رسپینا / تهران</p>
            </div>
        </aside>
        <div class="admin-scrim" data-admin-scrim></div>
        <div class="admin-main-wrap">
            <header class="admin-topbar">
                <button class="admin-sidebar-open" type="button" data-admin-sidebar-open aria-label="باز کردن منو"><span></span><span></span><span></span></button>
                <div class="admin-breadcrumb"><span>RASPINA</span><b>/</b><strong><?= e($page_title) ?></strong></div>
                <div class="admin-top-actions">
                    <span class="admin-user-chip"><span class="admin-avatar"><?= e(strtoupper(substr((string) ($admin_user['name'] ?? 'A'), 0, 1))) ?></span><span class="admin-user-name"><?= e((string) ($admin_user['name'] ?? 'مدیر سیستم')) ?></span></span>
                    <form method="post" action="<?= e(admin_url('/logout')) ?>" class="admin-logout-form"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><button type="submit">خروج</button></form>
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
        <a class="admin-auth-brand" href="<?= e(url('/')) ?>" aria-label="خانه پوشاک رسپینا"><span>R</span><strong>رسپینا</strong><small>عملیات آتلیه</small></a>
        <?php if ($admin_flash): ?><div class="admin-alert <?= e($admin_flash['type']) ?>" role="status"><?= e($admin_flash['message']) ?></div><?php endif; ?>
        <?= $content ?>
        <p class="admin-auth-foot">پوشاک رسپینا / کنسول خصوصی مدیریت آتلیه</p>
    </main>
<?php endif; ?>
<script src="<?= e($adminJs) ?>" defer></script>
</body>
</html>
