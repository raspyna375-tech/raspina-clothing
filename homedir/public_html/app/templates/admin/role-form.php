<?php
$disabled = !empty($read_only) ? 'disabled' : '';
$selected = array_fill_keys((array) ($form['permissions'] ?? array()), true);
$grouped = array();
foreach ((array) $permissions as $permission) { $grouped[$permission['module']][] = $permission; }
$action = !empty($form['id']) ? admin_url('/roles/edit/' . (int) $form['id']) : admin_url('/roles/new');

$translatedModules = array(
    'Dashboard' => 'داشبورد و آمارها',
    'Products' => 'محصولات',
    'Catalogue' => 'دسته‌بندی‌ها و کاتالوگ',
    'Enquiries' => 'درخواست‌های همکاری',
    'Configuration' => 'تنظیمات و پیکربندی',
    'Team access' => 'مدیریت اعضای تیم و دسترسی‌ها',
);
?>
<header class="admin-page-head compact-head"><div><a class="admin-back-link" href="<?= e(admin_url('/roles')) ?>">← بازگشت به نقش‌ها</a><p class="admin-eyebrow">تیم / دسترسی‌ها</p><h1><?= !empty($form['id']) ? 'ویرایش نقش' : 'ایجاد نقش جدید' ?></h1><p class="admin-lede">برای این نقش یک نام مشخص و مأموریت معین به همراه دسترسی‌های لازم انتخاب کنید.</p></div></header>
<?php if (!empty($errors)): ?><div class="admin-alert error" id="role-form-errors" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form class="admin-editor-form" method="post" action="<?= e($action) ?>"<?= !empty($errors) ? ' aria-describedby="role-form-errors"' : '' ?>><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= e($form['id'] ?? 0) ?>"><div class="editor-layout"><main class="editor-main"><section class="admin-panel editor-panel"><div class="form-grid"><label><span>عنوان نقش دسترسی <b>*</b></span><input name="title" value="<?= e($form['title'] ?? '') ?>" placeholder="مثال: مدیر محتوا" required <?= $disabled ?>></label><label><span>اسلاگ (شناسه انگلیسی آدرس) <b>*</b></span><input name="slug" value="<?= e($form['slug'] ?? '') ?>" pattern="[a-z0-9-]+" placeholder="مثال: content-manager" required <?= $disabled ?>></label><label class="span-2"><span>توضیحات و مسئولیت‌ها</span><textarea name="description" rows="4" placeholder="توضیحاتی در مورد وظایف این نقش بنویسید" <?= $disabled ?>><?= e($form['description'] ?? '') ?></textarea></label><label class="toggle-field"><span>وضعیت نقش</span><input type="checkbox" name="is_active" value="1" <?= !isset($form['is_active']) || !empty($form['is_active']) ? 'checked' : '' ?> <?= $disabled ?>><strong>برای تخصیص به کاربران فعال باشد</strong></label></div></section><section class="admin-panel editor-panel"><div class="panel-heading"><div><p class="admin-eyebrow">ماتریس دسترسی‌ها</p><h2>محدوده اختیارات این نقش</h2></div></div><p class="form-note" id="role-permissions-help">دسترسی‌هایی که مایلید این نقش دارا باشد را انتخاب کنید. نقش‌های سیستمی محافظت شده هستند.</p><div class="permission-groups" aria-describedby="role-permissions-help"><?php foreach ($grouped as $module => $items): ?><fieldset class="permission-group"><legend><?= e($translatedModules[$module] ?? ucwords(str_replace(array('_', '-'), ' ', $module))) ?></legend><?php foreach ($items as $permission): ?><label class="permission-item"><input type="checkbox" name="permissions[]" value="<?= e($permission['code']) ?>" <?= isset($selected[$permission['code']]) ? 'checked' : '' ?> <?= $disabled ?>><span><strong><?= e($permission['title']) ?></strong><small><?= e($permission['code']) ?></small></span></label><?php endforeach; ?></fieldset><?php endforeach; ?></div></section></main></div><?php if (empty($read_only)): ?><div class="editor-submit"><a class="admin-cancel-link" href="<?= e(admin_url('/roles')) ?>">انصراف</a><button class="admin-primary-button" type="submit">ذخیره نقش دسترسی <span aria-hidden="true">→</span></button></div><?php endif; ?></form>
