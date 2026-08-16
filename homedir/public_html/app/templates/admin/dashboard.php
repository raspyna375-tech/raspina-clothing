<header class="admin-page-head dashboard-head">
    <div><p class="admin-eyebrow">عملیات / <?= e(date('Y/m/d')) ?></p><h1>روز بخیر،<br><em><?= e((string) ($admin_user['name'] ?? 'همکار')) ?>.</em></h1><p class="admin-lede">نمای زنده از کاتالوگ رسپینا و گفتگوهایی که در حال حاضر مجموعه بعدی را شکل می‌دهند.</p></div>
    <a class="admin-primary-button" href="<?= e(admin_url('/products/new')) ?>">افزودن محصول <span aria-hidden="true">↗</span></a>
</header>
<?php if (!empty($admin_error)): ?><div class="admin-state-card <?= e($admin_repository->status()) ?>"><span class="state-dot"></span><div><strong>اتصال داده نیاز به بررسی دارد</strong><p><?= e($admin_error) ?></p></div></div><?php endif; ?>
<section class="admin-stat-grid" aria-label="آمارهای کاتالوگ">
    <a class="admin-stat-card stat-ink" href="<?= e(admin_url('/products')) ?>"><span class="stat-label">محصولات</span><strong><?= e($stats['products'] ?? 0) ?></strong><small>رکوردهای فعال کاتالوگ <span>↗</span></small></a>
    <a class="admin-stat-card" href="<?= e(admin_url('/categories')) ?>"><span class="stat-label">دسته‌بندی‌ها</span><strong><?= e($stats['categories'] ?? 0) ?></strong><small>بخش‌های کالکشن <span>↗</span></small></a>
    <a class="admin-stat-card" href="<?= e(admin_url('/messages')) ?>"><span class="stat-label">درخواست‌های همکاری</span><strong><?= e($stats['messages'] ?? 0) ?></strong><small>عمده‌فروشی + تماس <span>↗</span></small></a>
    <div class="admin-stat-card stat-gold"><span class="stat-label">منتخب کالکشن</span><strong><?= e($stats['featured'] ?? 0) ?></strong><small>کارهای در حال نمایش در صفحه اصلی</small></div>
</section>
<section class="admin-dashboard-grid">
    <div class="admin-panel admin-panel-tall">
        <div class="panel-heading"><div><p class="admin-eyebrow">آخرین گفتگوها</p><h2>پیام‌ها را بررسی کنید.</h2></div><a class="panel-link" href="<?= e(admin_url('/messages')) ?>">همه درخواست‌ها ↗</a></div>
        <?php if (empty($stats['latest_messages'])): ?><div class="empty-state"><span>◌</span><strong>هنوز درخواستی ثبت نشده است</strong><p>وقتی دیتابیس MySQL پیام‌ها را دریافت کند، درخواست‌های وب‌سایت جدید در اینجا ظاهر می‌شوند.</p></div>
        <?php else: ?><div class="mini-message-list"><?php foreach ($stats['latest_messages'] as $message): ?><a class="mini-message" href="<?= e(admin_url('/messages/view/' . (int) $message['id'])) ?>"><span class="message-type <?= e($message['message_type']) ?>"><?= $message['message_type'] === 'wholesale' ? 'عمده فروشی' : 'تماس باما' ?></span><div><strong><?= e($message['name']) ?></strong><small><?= e($message['business_name'] !== '' ? $message['business_name'] : $message['email']) ?><?= $message['country'] !== '' ? ' / ' . e($message['country']) : '' ?></small></div><time datetime="<?= e($message['created_at']) ?>"><?= e(date('m/d', strtotime($message['created_at']))) ?></time><span class="mini-arrow">↗</span></a><?php endforeach; ?></div><?php endif; ?>
    </div>
    <div class="admin-panel admin-panel-dark">
        <p class="admin-eyebrow">نبض آتلیه</p><h2>کالکشن را<br><em>زنده نگه دارید.</em></h2><p class="panel-copy">کاتالوگ عمومی شما در حال حاضر از <strong><?= e($catalog_mode === 'mysql' ? 'دیتابیس MySQL' : 'فایل پشتیبان JSON') ?></strong> خوانده می‌شود. از فضای کاری مدیریت برای به‌روز نگه‌داشتن کارهای آتلیه، وضعیت موجودی و متن‌های نمایش داده شده به خریداران استفاده کنید.</p>
        <div class="panel-actions"><a class="admin-light-button" href="<?= e(admin_url('/settings')) ?>">ویرایش متون سایت <span>↗</span></a><a class="admin-text-link" href="<?= e(url('/')) ?>" target="_blank" rel="noopener">پیش‌نمایش سایت اصلی ↗</a></div>
    </div>
</section>
<section class="admin-quick-links"><p class="admin-eyebrow">دسترسی سریع</p><div><a href="<?= e(admin_url('/products/new')) ?>"><span>01</span><strong>محصول جدید</strong><small>ساخت یک رکورد در کاتالوگ</small><b>↗</b></a><a href="<?= e(admin_url('/categories/new')) ?>"><span>02</span><strong>دسته‌بندی جدید</strong><small>ایجاد یک بخش در کالکشن</small><b>↗</b></a><a href="<?= e(admin_url('/settings')) ?>"><span>03</span><strong>تنظیمات سایت</strong><small>تغییر متون و اطلاعات عمومی سایت</small><b>↗</b></a></div></section>
