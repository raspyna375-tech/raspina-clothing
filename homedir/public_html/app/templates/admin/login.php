<section class="auth-card" aria-labelledby="login-title">
    <p class="admin-eyebrow">Raspina / private access</p>
    <h1 id="login-title">Welcome back<br><em>to the studio.</em></h1>
    <p class="auth-intro">Sign in to manage the live catalogue, enquiries and the words your buyers see.</p>
    <?php if (!empty($errors)): ?><div class="admin-alert error" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <?php if (isset($admin_repository) && $admin_repository->status() !== 'ready'): ?><div class="admin-state-card <?= e($admin_repository->status()) ?>"><span class="state-dot"></span><div><strong><?= e($admin_repository->status() === 'schema_missing' ? 'One setup step remains' : 'Database status') ?></strong><p><?= e($admin_repository->statusMessage()) ?></p></div></div><?php endif; ?>
    <form class="auth-form" method="post" action="<?= e(admin_url('/login')) ?>">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <?php if (!empty($next)): ?><input type="hidden" name="next" value="<?= e($next) ?>"><?php endif; ?>
        <label><span>Email address</span><input type="email" name="email" value="<?= e($email ?? '') ?>" maxlength="190" autocomplete="email" required autofocus></label>
        <label><span>Password</span><input type="password" name="password" maxlength="200" autocomplete="current-password" required></label>
        <button class="admin-primary-button" type="submit">Enter the console <span aria-hidden="true">↗</span></button>
    </form>
    <div class="auth-secondary"><span>First time here?</span><a href="<?= e(admin_url('/setup')) ?>">Run first-admin setup ↗</a></div>
</section>
