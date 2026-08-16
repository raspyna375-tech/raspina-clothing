<section class="auth-card" aria-labelledby="setup-title">
    <p class="admin-eyebrow">رسپینا / دسترسی اول</p>
    <h1 id="setup-title">ایجاد اولین<br><em>کلیددار سیستم.</em></h1>
    <p class="auth-intro">این مسیر خصوصی تنها یکبار کار می‌کند. این کار نیاز به کلید راه‌اندازی ذخیره شده در <code>app/config.local.php</code> دارد و پس از ایجاد اولین مدیر، برای همیشه بسته خواهد شد.</p>
    <?php if ($locked): ?>
        <div class="admin-state-card ready"><span class="state-dot"></span><div><strong>راه‌اندازی قفل شده است</strong><p>یک مدیر سیستم در حال حاضر وجود دارد. از صفحه ورود برای ادامه استفاده کنید.</p></div></div>
        <a class="admin-primary-button as-link" href="<?= e(admin_url('/login')) ?>">رفتن به صفحه ورود <span aria-hidden="true">↗</span></a>
    <?php elseif (!$setup_enabled): ?>
        <div class="admin-state-card schema_missing"><span class="state-dot"></span><div><strong>کلید راه‌اندازی الزامی است</strong><p>یک مقدار تصادفی طولانی به تنظیمات <code>admin.setup_key</code> در پیکربندی خصوصی خود اضافه کنید و سپس به اینجا برگردید.</p></div></div>
        <a class="auth-back-link" href="<?= e(admin_url('/login')) ?>">← بازگشت به صفحه ورود</a>
    <?php else: ?>
        <?php if (!empty($errors)): ?><div class="admin-alert error" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
        <form class="auth-form" method="post" action="<?= e(admin_url('/setup')) ?>">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <label><span>کلید راه‌اندازی (Setup Key)</span><input type="password" name="setup_key" maxlength="300" autocomplete="off" required autofocus></label>
            <div class="auth-field-grid"><label><span>نام شما</span><input type="text" name="name" value="<?= e($form['name'] ?? '') ?>" maxlength="120" autocomplete="name" required></label><label><span>ایمیل مدیر سیستم</span><input type="email" name="email" value="<?= e($form['email'] ?? '') ?>" maxlength="190" autocomplete="email" required></label></div>
            <div class="auth-field-grid"><label><span>کلمه عبور / حداقل ۱۲ کاراکتر</span><input type="password" name="password" minlength="12" maxlength="200" autocomplete="new-password" required></label><label><span>تأیید کلمه عبور</span><input type="password" name="password_confirmation" minlength="12" maxlength="200" autocomplete="new-password" required></label></div>
            <button class="admin-primary-button" type="submit">ایجاد دسترسی مالک سیستم <span aria-hidden="true">↗</span></button>
        </form>
        <div class="auth-secondary"><span>قبلاً راه‌اندازی شده است؟</span><a href="<?= e(admin_url('/login')) ?>">ورود ↗</a></div>
    <?php endif; ?>
</section>
