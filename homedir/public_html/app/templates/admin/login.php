<section class="auth-card" aria-labelledby="login-title">
    <p class="admin-eyebrow">رسپینا / دسترسی خصوصی</p>
    <h1 id="login-title">خوش آمدید<br><em>به آتلیه مدیریت.</em></h1>
    <p class="auth-intro">برای مدیریت کاتالوگ آنلاین، درخواست‌های همکاری و محتوای نمایش داده شده به خریداران، وارد شوید.</p>
    <?php if (!empty($errors)): ?><div class="admin-alert error" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <?php if (isset($admin_repository) && $admin_repository->status() !== 'ready'): ?><div class="admin-state-card <?= e($admin_repository->status()) ?>"><span class="state-dot"></span><div><strong><?= e($admin_repository->status() === 'schema_missing' ? 'یک مرحله از پیکربندی باقی مانده است' : 'وضعیت دیتابیس') ?></strong><p><?= e($admin_repository->statusMessage()) ?></p></div></div><?php endif; ?>
    <form class="auth-form" method="post" action="<?= e(admin_url('/login')) ?>">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <?php if (!empty($next)): ?><input type="hidden" name="next" value="<?= e($next) ?>"><?php endif; ?>
        <label><span>آدرس ایمیل</span><input type="email" name="email" value="<?= e($email ?? '') ?>" maxlength="190" autocomplete="email" required autofocus></label>
        <label><span>کلمه عبور</span><input type="password" name="password" maxlength="200" autocomplete="current-password" required></label>
        <button class="admin-primary-button" type="submit">ورود به پنل <span aria-hidden="true">↗</span></button>
    </form>
    <div class="auth-secondary"><span>اولین بار است که اینجا هستید؟</span><a href="<?= e(admin_url('/setup')) ?>">راه‌اندازی اولیه مدیر سیستم ↗</a></div>
</section>
