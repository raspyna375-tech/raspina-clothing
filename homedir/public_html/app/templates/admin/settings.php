<?php
$translatedGroups = array(
    'Site identity' => 'هویت سایت و اطلاعات تماس',
    'Homepage' => 'تنظیمات صفحه اصلی',
    'Footer' => 'تنظیمات فوتر سایت',
    'Page copy' => 'متون صفحات ثابت',
);

$translatedLabels = array(
    'Site name' => 'نام سایت',
    'Contact email' => 'ایمیل تماس',
    'Studio phone' => 'تلفن ثابت آتلیه',
    'Studio phone link' => 'لینک شماره تلفن ثابت',
    'Order mobile display' => 'نمایش موبایل سفارشات',
    'Order mobile link' => 'لینک شماره موبایل سفارشات',
    'Instagram URL' => 'آدرس اینستاگرام',
    'Studio address' => 'آدرس آتلیه',
    'Announcement strip' => 'نوار اعلان بالای سایت',
    'Hero kicker' => 'عنوان فرعی هیرو',
    'Hero title' => 'عنوان اصلی هیرو',
    'Hero description' => 'توضیحات هیرو',
    'Hero footer hint' => 'راهنمای فوتر هیرو',
    'Arrivals kicker' => 'عنوان فرعی کارهای جدید',
    'Arrivals title' => 'عنوان اصلی کارهای جدید',
    'Arrivals link label' => 'برچسب لینک کارهای جدید',
    'Collections kicker' => 'عنوان فرعی بخش کالکشن‌ها',
    'Collections title' => 'عنوان اصلی بخش کالکشن‌ها',
    'Collections description' => 'توضیحات بخش کالکشن‌ها',
    'Wholesale band kicker' => 'عنوان فرعی نوار عمده‌فروشی',
    'Wholesale band title' => 'عنوان اصلی نوار عمده‌فروشی',
    'Wholesale band button' => 'دکمه نوار عمده‌فروشی',
    'Catalog kicker' => 'عنوان فرعی بخش کاتالوگ',
    'Catalog title' => 'عنوان اصلی بخش کاتالوگ',
    'Catalog archive label' => 'برچسب آرشیو کاتالوگ',
    'Catalog description' => 'توضیحات بخش کاتالوگ',
    'Catalog button' => 'دکمه دانلود کاتالوگ',
    'Closing kicker' => 'عنوان فرعی ارتباط با ما در پایین صفحه',
    'Closing title' => 'عنوان اصلی ارتباط با ما در پایین صفحه',
    'Footer CTA kicker' => 'عنوان فرعی فراخوانی فوتر',
    'Footer CTA title' => 'عنوان اصلی فراخوانی فوتر',
    'Footer CTA description' => 'توضیحات فراخوانی فوتر',
    'Footer provenance' => 'متن اصالت و کپی‌رایت فوتر',
    'About introduction' => 'مقدمه صفحه درباره ما',
    'Contact introduction' => 'مقدمه صفحه تماس با ما',
    'Wholesale introduction' => 'مقدمه صفحه فروش عمده',
);
?>
<header class="admin-page-head compact-head"><div><p class="admin-eyebrow">پیکربندی / صدای عمومی برند</p><h1>تنظیمات سایت</h1><p class="admin-lede">متون و اطلاعات تماسی که سایت اصلی از دیتابیس MySQL می‌خواند را به‌روزرسانی کنید. تنظیمات فنی در فایل کانفیگ خصوصی باقی می‌ماند.</p></div></header>
<?php if (!empty($admin_error)): ?><div class="admin-state-card <?= e($admin_repository->status()) ?>"><span class="state-dot"></span><div><strong>وضعیت تنظیمات</strong><p><?= e($admin_error) ?></p></div></div><?php endif; ?>
<?php if (!empty($errors)): ?><div class="admin-alert error" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form class="settings-form" method="post" action="<?= e(admin_url('/settings')) ?>"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <?php $groups = array(); foreach ($setting_keys as $key => $definition) { $groups[$definition['group']][$key] = $definition; } $groupIndex = 0; ?>
    <?php foreach ($groups as $group => $groupKeys): $groupIndex++; ?>
        <section class="admin-panel settings-panel">
            <div class="panel-heading">
                <div>
                    <p class="admin-eyebrow">0<?= e($groupIndex) ?> / <?= e(strtolower($group)) ?></p>
                    <h2><?= e($translatedGroups[$group] ?? $group) ?></h2>
                </div>
                <?php if ($group === 'Site identity'): ?>
                    <span class="setting-note">هویت و اطلاعات تماس آتلیه</span>
                <?php elseif ($group === 'Homepage'): ?>
                    <span class="setting-note">متون صفحه اصلی</span>
                <?php elseif ($group === 'Footer'): ?>
                    <span class="setting-note">بخش پایینی سایت</span>
                <?php else: ?>
                    <span class="setting-note">متون صفحات داخلی</span>
                <?php endif; ?>
            </div>
            <div class="settings-grid">
                <?php foreach ($groupKeys as $key => $definition): ?>
                    <label class="setting-field <?= $definition['type'] === 'textarea' ? 'span-2' : '' ?>">
                        <span><?= e($translatedLabels[$definition['label']] ?? $definition['label']) ?><small><?= e($key) ?></small></span>
                        <?php if ($definition['type'] === 'textarea'): ?>
                            <textarea name="settings[<?= e($key) ?>]" maxlength="<?= e($definition['max']) ?>" rows="4"><?= e($settings[$key] ?? '') ?></textarea>
                        <?php else: ?>
                            <input type="<?= e($definition['type'] === 'url' ? 'url' : $definition['type']) ?>" name="settings[<?= e($key) ?>]" value="<?= e($settings[$key] ?? '') ?>" maxlength="<?= e($definition['max']) ?>" required>
                        <?php endif; ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>
    <div class="editor-submit settings-submit"><span class="settings-save-note">تغییرات پس از ذخیره‌سازی بلافاصله روی صفحات عمومی سایت اعمال خواهند شد.</span><button class="admin-primary-button" type="submit">ذخیره تمام تنظیمات <span aria-hidden="true">↗</span></button></div>
</form>
