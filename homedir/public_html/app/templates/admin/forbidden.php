<div class="empty-state table-empty" style="padding: 5rem 2rem; max-width: 600px; margin: 4rem auto; text-align: center;">
    <span style="font-size: 4rem; display: block; margin-bottom: 2rem;">🔒</span>
    <h1 style="font-family: inherit; font-size: 2rem; margin-bottom: 1rem; color: #cc3333;">عدم دسترسی کافی</h1>
    <p style="font-size: 1.1rem; line-height: 1.6; color: #666; margin-bottom: 2rem;">
        شما مجوز لازم برای انجام این عملیات یا مشاهده این بخش را ندارید.
        <br>
        <small style="display: block; margin-top: 1rem; color: #999;">کد مجوز مورد نیاز: <code><?= e($required_permission) ?></code></small>
    </p>
    <a class="admin-primary-button" href="<?= e(admin_url('/')) ?>">بازگشت به داشبورد</a>
</div>
