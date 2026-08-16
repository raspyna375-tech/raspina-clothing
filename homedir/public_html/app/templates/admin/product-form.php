<?php
$disabled = !empty($read_only) ? 'disabled' : '';
$value = static function (string $key, $fallback = '') use ($form) { return $form[$key] ?? $fallback; };
$selectedCategories = array_map('intval', (array) ($form['category_ids'] ?? array()));
$productAction = !empty($form['id']) ? admin_url('/products/edit/' . (int) $form['id']) : admin_url('/products/new');

$apparelFields = array(
    'brand' => 'برند',
    'garment_type' => 'نوع لباس',
    'collection_name' => 'کالکشن',
    'season' => 'فصل / سال تولید',
    'fabric_composition' => 'جنس و ترکیب پارچه',
    'fabric_weight' => 'وزن پارچه',
    'color' => 'رنگ اصلی',
    'color_family' => 'طیف رنگی',
    'pattern' => 'طرح پارچه',
    'fit' => 'نوع تن‌خور (Fit)',
    'silhouette' => 'سیلوئت (فرم کلی)',
    'neckline' => 'مدل یقه',
    'sleeve_length' => 'قد آستین',
    'garment_length' => 'قد لباس',
    'closure' => 'نحوه بسته‌شدن',
    'lining' => 'وضعیت آستر',
    'stretch' => 'میزان کشسانی',
    'origin_country' => 'کشور سازنده',
    'size_range' => 'محدوده سایزها'
);

// Determine primary image
$imagesList = (array) ($form['images'] ?? array());
$imageRecords = (array) ($form['image_records'] ?? array());
$primaryImg = $form['primary_image_path'] ?? ($imagesList[0] ?? '');
?>

<style>
/* WordPress / WooCommerce Admin Form Styling */
.wp-editor-wrap {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 20px;
    margin-top: 15px;
}
@media (max-width: 992px) {
    .wp-editor-wrap {
        grid-template-columns: 1fr;
    }
}
.wp-box {
    background: #ffffff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    border-radius: 4px;
    margin-bottom: 20px;
}
.wp-box-header {
    padding: 12px 15px;
    border-bottom: 1px solid #ccd0d4;
    background: #f8f9fa;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.wp-box-header h2, .wp-box-header h3 {
    margin: 0;
    font-size: 0.95rem;
    font-weight: 700;
    color: #1d2327;
}
.wp-box-body {
    padding: 15px;
}
.wp-title-input {
    width: 100%;
    font-size: 1.25rem;
    padding: 10px 14px;
    border: 1px solid #8c8f94;
    border-radius: 4px;
    margin-bottom: 8px;
}
.wp-slug-wrap {
    font-size: 0.85rem;
    color: #646970;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.wp-slug-wrap input {
    font-size: 0.85rem;
    padding: 3px 8px;
    border: 1px solid #8c8f94;
    border-radius: 3px;
}
.wp-tabs {
    display: flex;
    border-bottom: 1px solid #ccd0d4;
    background: #f6f7f7;
    overflow-x: auto;
}
.wp-tab-btn {
    padding: 10px 16px;
    border: none;
    background: none;
    font-size: 0.88rem;
    font-weight: 600;
    color: #2c3338;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    white-space: nowrap;
}
.wp-tab-btn.active {
    background: #ffffff;
    border-bottom-color: #2271b1;
    color: #2271b1;
}
.wp-tab-content {
    display: none;
    padding: 15px;
}
.wp-tab-content.active {
    display: block;
}
.wp-field-group {
    margin-bottom: 14px;
}
.wp-field-group label {
    display: block;
    font-size: 0.85rem;
    font-weight: 600;
    margin-bottom: 5px;
    color: #1d2327;
}
.wp-field-group input[type="text"],
.wp-field-group input[type="number"],
.wp-field-group input[type="date"],
.wp-field-group select,
.wp-field-group textarea {
    width: 100%;
    padding: 7px 10px;
    border: 1px solid #8c8f94;
    border-radius: 4px;
    font-size: 0.9rem;
    box-sizing: border-box;
}
.wp-field-group .help-text {
    font-size: 0.78rem;
    color: #646970;
    margin-top: 4px;
}
.wp-category-checklist {
    max-height: 180px;
    overflow-y: auto;
    border: 1px solid #8c8f94;
    padding: 8px 12px;
    border-radius: 4px;
    background: #fafafa;
}
.wp-category-item {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 6px;
    font-size: 0.88rem;
}
.wp-image-preview-box {
    text-align: center;
    background: #f0f0f1;
    border: 2px dashed #c3c4c7;
    padding: 12px;
    border-radius: 4px;
    margin-bottom: 10px;
}
.wp-image-preview-box img {
    max-width: 100%;
    max-height: 180px;
    object-fit: contain;
    border-radius: 4px;
}
.variation-generator-box {
    background: #f0f6fc;
    border: 1px dashed #2271b1;
    border-radius: 6px;
    padding: 12px;
    margin-bottom: 15px;
}
.attribute-input-row {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 10px;
    margin-bottom: 8px;
}
</style>

<header class="admin-page-head compact-head">
    <div>
        <a class="admin-back-link" href="<?= e(admin_url('/products')) ?>">← بازگشت به فهرست محصولات</a>
        <p class="admin-eyebrow">مدیریت محصولات وردپرس / WooCommerce</p>
        <h1><?= !empty($form['id']) ? 'ویرایش محصول: ' . e($value('name')) : 'افزودن محصول جدید' ?></h1>
    </div>
</header>

<?php if (!empty($errors)): ?>
    <div class="admin-alert error" role="alert">
        <ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<form method="post" action="<?= e($productAction) ?>" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="id" value="<?= e($form['id'] ?? 0) ?>">

    <div class="wp-editor-wrap">
        <!-- MAIN CONTENT AREA (Left Side in RTL) -->
        <div class="wp-editor-main">
            <!-- Product Title Box -->
            <div class="wp-box">
                <div class="wp-box-body">
                    <input type="text" name="name" class="wp-title-input" value="<?= e($value('name')) ?>" placeholder="نام محصول را اینجا وارد کنید..." required <?= $disabled ?>>

                    <div class="wp-slug-wrap">
                        <strong>پیوند یکتا (اسلاگ):</strong>
                        <input type="text" name="slug" value="<?= e($value('slug')) ?>" pattern="[a-z0-9-]+" placeholder="product-slug" required <?= $disabled ?>>
                        <small>(فقط حروف کوچک انگلیسی، اعداد و خط تیره)</small>
                    </div>
                </div>
            </div>

            <!-- Product Full Description -->
            <div class="wp-box">
                <div class="wp-box-header">
                    <h2>توضیحات کامل محصول</h2>
                </div>
                <div class="wp-box-body">
                    <textarea name="description" rows="12" placeholder="توضیحات جامع و کامل محصول..." <?= $disabled ?>><?= e($value('description')) ?></textarea>
                </div>
            </div>

            <!-- WooCommerce Product Data Tabs -->
            <div class="wp-box">
                <div class="wp-box-header">
                    <h2>اطلاعات محصول (WooCommerce Product Data)</h2>
                    <span style="font-size:0.8rem; color:#666;">نوع محصول:
                        <select name="product_type" id="wp_product_type_select" onchange="toggleProductType()" style="padding:2px 6px; font-size:0.82rem;" <?= $disabled ?>>
                            <option value="simple" <?= $value('product_type', 'simple') === 'simple' ? 'selected' : '' ?>>محصول ساده (Simple)</option>
                            <option value="variable" <?= $value('product_type') === 'variable' ? 'selected' : '' ?>>محصول متغیر (Variable)</option>
                        </select>
                    </span>
                </div>

                <!-- Tabs header -->
                <div class="wp-tabs" id="product-tabs-header">
                    <button type="button" class="wp-tab-btn active" onclick="openWpTab(event, 'tab-pricing')">قیمت‌گذاری</button>
                    <button type="button" class="wp-tab-btn" onclick="openWpTab(event, 'tab-inventory')">موجودی و انبار</button>
                    <button type="button" class="wp-tab-btn" onclick="openWpTab(event, 'tab-apparel')">مشخصات پوشاک</button>
                    <button type="button" class="wp-tab-btn" onclick="openWpTab(event, 'tab-attributes')">ویژگی‌ها (Attributes)</button>
                    <button type="button" class="wp-tab-btn" id="btn-tab-variants" onclick="openWpTab(event, 'tab-variants')">متغیرها (Variations)</button>
                    <button type="button" class="wp-tab-btn" onclick="openWpTab(event, 'tab-wholesale')">عمده‌فروشی & تحویل</button>
                </div>

                <!-- Tab 1: Pricing -->
                <div id="tab-pricing" class="wp-tab-content active">
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:15px;">
                        <div class="wp-field-group">
                            <label>قیمت عادی (Regular Price)</label>
                            <input name="regular_price" inputmode="decimal" value="<?= e($value('regular_price')) ?>" placeholder="مثال: 1500000" <?= $disabled ?>>
                        </div>
                        <div class="wp-field-group">
                            <label>قیمت فروش ویژه (Sale Price)</label>
                            <input name="sale_price" inputmode="decimal" value="<?= e($value('sale_price')) ?>" placeholder="مثال: 1200000" <?= $disabled ?>>
                        </div>
                        <div class="wp-field-group">
                            <label>قیمت مرجع استعلام</label>
                            <input name="reference_price" inputmode="decimal" value="<?= e($value('reference_price')) ?>" placeholder="مثال: 1350000" <?= $disabled ?>>
                        </div>
                        <div class="wp-field-group">
                            <label>واحد ارز</label>
                            <input type="text" name="currency" value="<?= e($value('currency', 'IRR')) ?>" maxlength="10" <?= $disabled ?>>
                        </div>
                    </div>
                </div>

                <!-- Tab 2: Inventory -->
                <div id="tab-inventory" class="wp-tab-content">
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:15px;">
                        <div class="wp-field-group">
                            <label>شناسه محصول (SKU)</label>
                            <input type="text" name="sku" value="<?= e($value('sku')) ?>" maxlength="96" placeholder="مثال: RAS-102" <?= $disabled ?>>
                        </div>
                        <div class="wp-field-group">
                            <label>وضعیت موجودی</label>
                            <select name="stock_status" <?= $disabled ?>>
                                <option value="instock" <?= $value('stock_status') === 'instock' ? 'selected' : '' ?>>موجود در انبار</option>
                                <option value="onbackorder" <?= $value('stock_status') === 'onbackorder' ? 'selected' : '' ?>>قابل پیش‌خرید</option>
                                <option value="outofstock" <?= $value('stock_status') === 'outofstock' ? 'selected' : '' ?>>ناموجود</option>
                            </select>
                        </div>
                        <div class="wp-field-group">
                            <label>تعداد موجودی انبار</label>
                            <input type="number" min="0" name="stock_quantity" value="<?= e($value('stock_quantity')) ?>" <?= $disabled ?>>
                        </div>
                        <div class="wp-field-group">
                            <label>حداقل تعداد سفارش عمده</label>
                            <input type="number" min="0" name="minimum_order_quantity" value="<?= e($value('minimum_order_quantity')) ?>" <?= $disabled ?>>
                        </div>
                    </div>
                </div>

                <!-- Tab 3: Apparel Details -->
                <div id="tab-apparel" class="wp-tab-content">
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:12px;">
                        <?php foreach ($apparelFields as $field => $label): ?>
                        <div class="wp-field-group">
                            <label><?= e($label) ?></label>
                            <input type="text" name="<?= e($field) ?>" value="<?= e($value($field)) ?>" maxlength="255" <?= $disabled ?>>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="wp-field-group" style="margin-top:10px;">
                        <label>دستورالعمل مراقبت و شستشو</label>
                        <textarea name="care_instructions" rows="2" <?= $disabled ?>><?= e($value('care_instructions')) ?></textarea>
                    </div>
                    <div style="display:flex; gap:20px; margin-top:10px;">
                        <label style="display:flex; align-items:center; gap:6px; font-size:0.88rem;">
                            <input type="checkbox" name="customizable_size" value="1" <?= !empty($form['customizable_size']) ? 'checked' : '' ?> <?= $disabled ?>>
                            امکان دوخت سفارشی سایز
                        </label>
                        <label style="display:flex; align-items:center; gap:6px; font-size:0.88rem;">
                            <input type="checkbox" name="customizable_fabric" value="1" <?= !empty($form['customizable_fabric']) ? 'checked' : '' ?> <?= $disabled ?>>
                            امکان سفارش پارچه دلخواه
                        </label>
                    </div>
                </div>

                <!-- Tab 4: Attributes (WooCommerce Style) -->
                <div id="tab-attributes" class="wp-tab-content">
                    <div class="variation-generator-box">
                        <h4 style="margin:0 0 8px 0; font-size:0.9rem; color:#1d2327;">تعریف ویژگی‌های محصول (مانند سایز و رنگ)</h4>
                        <p style="margin:0 0 10px 0; font-size:0.82rem; color:#50575e;">مقادیر هر ویژگی را با خط عمودی (|) جدا کنید. برای مثال: <code>S | M | L | XL</code> یا <code>قرمز | آبی | مشکی</code></p>

                        <div class="wp-field-group">
                            <label>سایزها (Sizes)</label>
                            <input type="text" id="attr_sizes_input" placeholder="مثال: S | M | L | XL | 38 | 40" value="<?= e($value('size_range')) ?>" <?= $disabled ?>>
                        </div>

                        <div class="wp-field-group">
                            <label>رنگ‌ها (Colors)</label>
                            <input type="text" id="attr_colors_input" placeholder="مثال: مشکی | سرمه‌ای | کرم | قرمز" value="<?= e($value('color')) ?>" <?= $disabled ?>>
                        </div>

                        <button type="button" class="admin-primary-button" onclick="generateVariationsFromAttributes()" <?= $disabled ?>>⚡ ساخت ترکیبات متغیر به‌صورت خودکار</button>
                    </div>
                </div>

                <!-- Tab 5: Variations (WooCommerce Style) -->
                <div id="tab-variants" class="wp-tab-content">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                        <p style="margin:0; font-size:0.85rem; color:#666;">لیست تمام متغیرهای محصول (ترکیب سایز، رنگ، قیمت و موجودی):</p>
                        <button type="button" class="admin-light-button" onclick="addSingleVariantRow()" <?= $disabled ?>>+ افزودن دستی متغیر</button>
                    </div>
                    <div class="variant-list" id="wp_variant_container" data-variant-list>
                        <?php foreach ((array) ($form['variants'] ?? array()) as $index => $variant): ?>
                        <div class="variant-row" data-variant-row style="display:grid; grid-template-columns: repeat(auto-fit, minmax(110px, 1fr)) 40px; gap:8px; background:#f9f9f9; padding:10px; border:1px solid #e0e0e0; border-radius:4px; margin-bottom:8px;">
                            <label><span style="font-size:0.75rem;">SKU</span><input name="variants[<?= (int) $index ?>][variant_sku]" value="<?= e($variant['variant_sku'] ?? '') ?>" <?= $disabled ?>></label>
                            <label><span style="font-size:0.75rem;">سایز</span><input name="variants[<?= (int) $index ?>][size]" value="<?= e($variant['size'] ?? '') ?>" <?= $disabled ?>></label>
                            <label><span style="font-size:0.75rem;">رنگ</span><input name="variants[<?= (int) $index ?>][color]" value="<?= e($variant['color'] ?? '') ?>" <?= $disabled ?>></label>
                            <label><span style="font-size:0.75rem;">تعداد</span><input type="number" min="0" name="variants[<?= (int) $index ?>][stock_quantity]" value="<?= e($variant['stock_quantity'] ?? '') ?>" <?= $disabled ?>></label>
                            <label><span style="font-size:0.75rem;">قیمت</span><input name="variants[<?= (int) $index ?>][price_override]" value="<?= e($variant['price_override'] ?? '') ?>" <?= $disabled ?>></label>
                            <label><span style="font-size:0.75rem;">وضعیت</span>
                                <select name="variants[<?= (int) $index ?>][stock_status]" <?= $disabled ?>>
                                    <option value="instock" <?= ($variant['stock_status'] ?? '') === 'instock' ? 'selected' : '' ?>>موجود</option>
                                    <option value="outofstock" <?= ($variant['stock_status'] ?? '') === 'outofstock' ? 'selected' : '' ?>>ناموجود</option>
                                </select>
                            </label>
                            <button type="button" class="icon-action danger" onclick="this.closest('.variant-row').remove()" style="align-self:end; margin-bottom:4px;">✕</button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Tab 6: Wholesale & Delivery -->
                <div id="tab-wholesale" class="wp-tab-content">
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:15px;">
                        <div class="wp-field-group">
                            <label>زمان تحویل سفارش</label>
                            <input name="lead_time" value="<?= e($value('lead_time')) ?>" placeholder="مثال: ۲ الی ۳ هفته" <?= $disabled ?>>
                        </div>
                        <div class="wp-field-group">
                            <label>تاریخ آماده عرضه</label>
                            <input type="date" name="available_from" value="<?= e($value('available_from')) ?>" <?= $disabled ?>>
                        </div>
                        <div class="wp-field-group" style="grid-column: 1 / -1;">
                            <label>توضیح موجودی / عرضه</label>
                            <input name="availability_note" value="<?= e($value('availability_note')) ?>" <?= $disabled ?>>
                        </div>
                        <div class="wp-field-group" style="grid-column: 1 / -1;">
                            <label>یادداشت‌های ویژه عمده‌فروشی</label>
                            <textarea name="wholesale_notes" rows="3" <?= $disabled ?>><?= e($value('wholesale_notes')) ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Short Summary / Description -->
            <div class="wp-box">
                <div class="wp-box-header">
                    <h2>توضیحات کوتاه محصول (Product Short Description)</h2>
                </div>
                <div class="wp-box-body">
                    <textarea name="summary" rows="4" placeholder="خلاصه کوتاه برای نمایش در بالا یا کارت محصول..." <?= $disabled ?>><?= e($value('summary')) ?></textarea>
                </div>
            </div>

            <!-- SEO Meta Box -->
            <div class="wp-box">
                <div class="wp-box-header">
                    <h2>تنظیمات سئو (Yoast / SEO Settings)</h2>
                </div>
                <div class="wp-box-body">
                    <div class="wp-field-group">
                        <label>عنوان سئو (SEO Title)</label>
                        <input type="text" name="seo_title" value="<?= e($value('seo_title')) ?>" placeholder="عنوان برای گوگل..." <?= $disabled ?>>
                    </div>
                    <div class="wp-field-group">
                        <label>توضیحات سئو (SEO Meta Description)</label>
                        <textarea name="seo_description" rows="3" placeholder="توضیحات مختصر جهت نمایش در نتایج جستجو..." <?= $disabled ?>><?= e($value('seo_description')) ?></textarea>
                    </div>
                    <div class="wp-field-group">
                        <label>ویژگی‌های فرعی (Attributes)</label>
                        <textarea name="attributes" rows="3" placeholder="مثال: جنس آستر: حریر&#10;شستشو: خشک‌شویی" <?= $disabled ?>><?= e(implode("\n", (array) ($form['attributes'] ?? array()))) ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- SIDEBAR AREA (Right Side in RTL) -->
        <div class="wp-editor-sidebar">
            <!-- Publish Box -->
            <div class="wp-box">
                <div class="wp-box-header">
                    <h3>انتشار (Publish)</h3>
                </div>
                <div class="wp-box-body">
                    <div class="wp-field-group">
                        <label>وضعیت انتشار:</label>
                        <select name="publish_status" <?= $disabled ?>>
                            <option value="published" <?= $value('publish_status', 'published') === 'published' ? 'selected' : '' ?>>منتشر شده (Published)</option>
                            <option value="draft" <?= $value('publish_status') === 'draft' ? 'selected' : '' ?>>پیش‌نویس (Draft)</option>
                            <option value="archived" <?= $value('publish_status') === 'archived' ? 'selected' : '' ?>>بایگانی شده (Archived)</option>
                        </select>
                    </div>

                    <div class="wp-field-group" style="margin-top: 10px;">
                        <label style="display:flex; align-items:center; gap:6px; font-weight:normal;">
                            <input type="checkbox" name="featured" value="1" <?= !empty($form['featured']) ? 'checked' : '' ?> <?= $disabled ?>>
                            <strong>محصول ویژه / منتخب</strong>
                        </label>
                    </div>

                    <hr style="border:none; border-top:1px solid #eee; margin:15px 0;">

                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <a href="<?= e(admin_url('/products')) ?>" style="color:#d63638; text-decoration:none; font-size:0.85rem;">انصراف</a>
                        <button type="submit" class="admin-primary-button" style="padding: 8px 18px; font-weight: bold;" <?= $disabled ?>>
                            <?= !empty($form['id']) ? 'به‌روزرسانی محصول' : 'انتشار محصول' ?>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Product Categories Box -->
            <div class="wp-box">
                <div class="wp-box-header">
                    <h3>دسته‌بندی‌های محصول</h3>
                </div>
                <div class="wp-box-body">
                    <div class="wp-category-checklist">
                        <?php if (empty($categories)): ?>
                            <p style="font-size:0.8rem; color:#888; margin:0;">هیچ دسته‌بندی تعریف نشده است.</p>
                        <?php else: ?>
                            <?php foreach ($categories as $category): ?>
                                <label class="wp-category-item">
                                    <input type="checkbox" name="categories[]" value="<?= e($category['id']) ?>" <?= in_array((int) $category['id'], $selectedCategories, true) ? 'checked' : '' ?> <?= $disabled ?>>
                                    <span><?= e($category['name']) ?></span>
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Product Tags / Hashtags Box -->
            <div class="wp-box">
                <div class="wp-box-header">
                    <h3>برچسب‌ها و هشتگ‌ها</h3>
                </div>
                <div class="wp-box-body">
                    <div class="wp-field-group">
                        <textarea name="tags" rows="3" placeholder="برچسب‌ها را با کاما جدا کنید (مثال: #زنانه, پالتو, پاییزه)" <?= $disabled ?>><?= e(implode(', ', (array) ($form['tags'] ?? array()))) ?></textarea>
                        <p class="help-text">برچسب‌ها و هشتگ‌ها را با کاما (،) از یکدیگر جدا کنید.</p>
                    </div>
                </div>
            </div>

            <!-- Product Image (Featured Image) Box -->
            <div class="wp-box">
                <div class="wp-box-header">
                    <h3>تصویر شاخص محصول</h3>
                </div>
                <div class="wp-box-body">
                    <?php if (!empty($primaryImg)): ?>
                        <div class="wp-image-preview-box">
                            <img src="<?= e($primaryImg) ?>" alt="تصویر شاخص" onerror="this.style.display='none';">
                        </div>
                    <?php else: ?>
                        <div class="wp-image-preview-box" style="color:#888; font-size:0.85rem;">
                            تصویر شاخص انتخاب نشده است
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($can_upload)): ?>
                        <div class="wp-field-group">
                            <label style="font-size:0.8rem;">بارگذاری تصویر شاخص / جدید:</label>
                            <input type="file" name="product_images[]" accept="image/jpeg,image/png,image/webp" multiple <?= $disabled ?>>
                        </div>
                        <div class="wp-field-group">
                            <label style="font-size:0.8rem;">متن جایگزین (Alt):</label>
                            <input type="text" name="upload_alt" maxlength="255" placeholder="توضیح تصویر..." <?= $disabled ?>>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Gallery & Existing Images Box -->
            <div class="wp-box">
                <div class="wp-box-header">
                    <h3>گالری تصاویر & مدیریت رسانه</h3>
                </div>
                <div class="wp-box-body">
                    <div class="wp-field-group">
                        <label style="font-size:0.8rem;">آدرس محلی تصاویر (هر خط یک آدرس):</label>
                        <textarea name="images" rows="3" placeholder="/assets/images/..." <?= $disabled ?>><?= e(implode("\n", $imagesList)) ?></textarea>
                    </div>

                    <?php foreach ($imageRecords as $image):
                        $path = (string) ($image['path'] ?? '');
                        if ($path === '') continue;
                        $encoded = rawurlencode($path);
                    ?>
                        <div style="background:#f8f9fa; border:1px solid #e2e4e7; border-radius:4px; padding:8px; margin-bottom:8px; font-size:0.8rem;">
                            <input type="hidden" name="keep_images[]" value="<?= e($path) ?>">
                            <div style="display:flex; gap:8px; align-items:center; margin-bottom:4px;">
                                <img src="<?= e($path) ?>" style="width:36px; height:36px; object-fit:cover; border-radius:3px;" onerror="this.style.display='none';">
                                <strong style="word-break:break-all; flex:1;"><?= e(basename($path)) ?></strong>
                            </div>
                            <input type="text" name="image_alt[<?= e($encoded) ?>]" value="<?= e($image['alt_text'] ?? '') ?>" placeholder="متن Alt" style="width:100%; font-size:0.75rem; padding:3px; margin-bottom:4px;" <?= $disabled ?>>
                            <div style="display:flex; justify-content:space-between; font-size:0.78rem;">
                                <label><input type="radio" name="primary_image_path" value="<?= e($path) ?>" <?= $primaryImg === $path ? 'checked' : '' ?> <?= $disabled ?>> اصلی</label>
                                <label style="color:#c00;"><input type="checkbox" name="remove_images[]" value="<?= e($path) ?>" <?= $disabled ?>> حذف</label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
function openWpTab(evt, tabId) {
    var tabs = document.getElementsByClassName("wp-tab-content");
    for (var i = 0; i < tabs.length; i++) {
        tabs[i].classList.remove("active");
    }
    var buttons = document.querySelectorAll("#product-tabs-header .wp-tab-btn");
    for (var i = 0; i < buttons.length; i++) {
        buttons[i].classList.remove("active");
    }
    document.getElementById(tabId).classList.add("active");
    evt.currentTarget.classList.add("active");
}

function toggleProductType() {
    var type = document.getElementById("wp_product_type_select").value;
    var variantTabBtn = document.getElementById("btn-tab-variants");
    if (type === "variable") {
        variantTabBtn.style.display = "inline-block";
        openWpTab({ currentTarget: variantTabBtn }, "tab-variants");
    }
}

function addSingleVariantRow(skuVal, sizeVal, colorVal) {
    var container = document.getElementById("wp_variant_container");
    var index = container.children.length;
    skuVal = skuVal || "";
    sizeVal = sizeVal || "";
    colorVal = colorVal || "";

    var row = document.createElement("div");
    row.className = "variant-row";
    row.style.cssText = "display:grid; grid-template-columns: repeat(auto-fit, minmax(110px, 1fr)) 40px; gap:8px; background:#f9f9f9; padding:10px; border:1px solid #e0e0e0; border-radius:4px; margin-bottom:8px;";
    row.innerHTML = `
        <label><span style="font-size:0.75rem;">SKU</span><input name="variants[${index}][variant_sku]" value="${skuVal}"></label>
        <label><span style="font-size:0.75rem;">سایز</span><input name="variants[${index}][size]" value="${sizeVal}"></label>
        <label><span style="font-size:0.75rem;">رنگ</span><input name="variants[${index}][color]" value="${colorVal}"></label>
        <label><span style="font-size:0.75rem;">تعداد</span><input type="number" min="0" name="variants[${index}][stock_quantity]" value="10"></label>
        <label><span style="font-size:0.75rem;">قیمت</span><input name="variants[${index}][price_override]" value=""></label>
        <label><span style="font-size:0.75rem;">وضعیت</span>
            <select name="variants[${index}][stock_status]">
                <option value="instock" selected>موجود</option>
                <option value="outofstock">ناموجود</option>
            </select>
        </label>
        <button type="button" class="icon-action danger" onclick="this.closest('.variant-row').remove()" style="align-self:end; margin-bottom:4px;">✕</button>
    `;
    container.appendChild(row);
}

function generateVariationsFromAttributes() {
    var sizesRaw = document.getElementById("attr_sizes_input").value;
    var colorsRaw = document.getElementById("attr_colors_input").value;

    var sizes = sizesRaw.split("|").map(s => s.trim()).filter(Boolean);
    var colors = colorsRaw.split("|").map(c => c.trim()).filter(Boolean);

    if (sizes.length === 0 && colors.length === 0) {
        alert("لطفاً حداقل مقادیر یک ویژگی (سایز یا رنگ) را وارد کنید.");
        return;
    }

    var baseSku = document.querySelector('input[name="sku"]').value || "VAR";
    document.getElementById("wp_product_type_select").value = "variable";
    toggleProductType();

    var container = document.getElementById("wp_variant_container");
    container.innerHTML = ""; // Clear existing

    if (sizes.length > 0 && colors.length > 0) {
        sizes.forEach(size => {
            colors.forEach(color => {
                var sku = `${baseSku}-${size}-${color}`;
                addSingleVariantRow(sku, size, color);
            });
        });
    } else if (sizes.length > 0) {
        sizes.forEach(size => {
            var sku = `${baseSku}-${size}`;
            addSingleVariantRow(sku, size, "");
        });
    } else if (colors.length > 0) {
        colors.forEach(color => {
            var sku = `${baseSku}-${color}`;
            addSingleVariantRow(sku, "", color);
        });
    }
}
</script>
