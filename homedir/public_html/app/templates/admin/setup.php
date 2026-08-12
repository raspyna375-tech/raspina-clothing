<section class="auth-card" aria-labelledby="setup-title">
    <p class="admin-eyebrow">Raspina / first access</p>
    <h1 id="setup-title">Make the first<br><em>keyholder.</em></h1>
    <p class="auth-intro">This private route works once. It requires the setup key stored in <code>app/config.local.php</code>, then closes permanently after the first admin is created.</p>
    <?php if ($locked): ?>
        <div class="admin-state-card ready"><span class="state-dot"></span><div><strong>Setup is locked</strong><p>An administrator already exists. Use the sign-in page to continue.</p></div></div>
        <a class="admin-primary-button as-link" href="<?= e(admin_url('/login')) ?>">Go to sign in <span aria-hidden="true">↗</span></a>
    <?php elseif (!$setup_enabled): ?>
        <div class="admin-state-card schema_missing"><span class="state-dot"></span><div><strong>Setup key required</strong><p>Add a long random value to the <code>admin.setup_key</code> setting in your private config, then return here.</p></div></div>
        <a class="auth-back-link" href="<?= e(admin_url('/login')) ?>">← Back to sign in</a>
    <?php else: ?>
        <?php if (!empty($errors)): ?><div class="admin-alert error" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
        <form class="auth-form" method="post" action="<?= e(admin_url('/setup')) ?>">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <label><span>Setup key</span><input type="password" name="setup_key" maxlength="300" autocomplete="off" required autofocus></label>
            <div class="auth-field-grid"><label><span>Your name</span><input type="text" name="name" value="<?= e($form['name'] ?? '') ?>" maxlength="120" autocomplete="name" required></label><label><span>Admin email</span><input type="email" name="email" value="<?= e($form['email'] ?? '') ?>" maxlength="190" autocomplete="email" required></label></div>
            <div class="auth-field-grid"><label><span>Password / 12+ characters</span><input type="password" name="password" minlength="12" maxlength="200" autocomplete="new-password" required></label><label><span>Confirm password</span><input type="password" name="password_confirmation" minlength="12" maxlength="200" autocomplete="new-password" required></label></div>
            <button class="admin-primary-button" type="submit">Create owner access <span aria-hidden="true">↗</span></button>
        </form>
        <div class="auth-secondary"><span>Already configured?</span><a href="<?= e(admin_url('/login')) ?>">Sign in ↗</a></div>
    <?php endif; ?>
</section>
