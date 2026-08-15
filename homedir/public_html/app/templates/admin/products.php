<?php
$stockLabels = array(
    'instock' => 'موجود در انبار',
    'onbackorder' => 'قابل پیش‌خرید',
    'outofstock' => 'ناموجود',
);
?>
<header class="admin-page-head compact-head">
    <div>
        <p class="admin-eyebrow">مدیریت کاتالوگ محصولات / وردپرس style</p>
        <h1>محصولات <a class="admin-light-button" style="margin-right: 12px; font-size: 0.85rem;" href="<?= e(admin_url('/products/new')) ?>">افزودن جدید</a> <span class="head-count"><?= e($products['total'] ?? 0) ?></span></h1>
        <p class="admin-lede">مدیریت کامل محصولات، قیمت‌گذاری، دسته‌بندی‌ها، برچسب‌ها و موجودی کالاها.</p>
    </div>
</header>

<?php if (!empty($admin_error)): ?>
<div class="admin-state-card <?= e($admin_repository->status()) ?>">
    <span class="state-dot"></span>
    <div><strong>وضعیت کاتالوگ</strong><p><?= e($admin_error) ?></p></div>
</div>
<?php endif; ?>

<section class="admin-panel admin-table-panel">
    <div class="table-toolbar" style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap;">
        <form class="admin-search" method="get" action="<?= e(admin_url('/products')) ?>" style="display: flex; gap: 0.5rem;">
            <input id="product-search" type="search" name="q" value="<?= e($query) ?>" placeholder="جستجوی نام، اسلاگ یا SKU محصول..." style="min-width: 280px;">
            <button type="submit" class="admin-primary-button">جستجوی محصولات</button>
        </form>
        <span class="toolbar-meta">نمایش <?= e(count($products['items'] ?? array())) ?> از <?= e($products['total'] ?? 0) ?> مورد</span>
    </div>

    <?php if (empty($products['items'])): ?>
    <div class="empty-state table-empty">
        <span>▦</span>
        <strong>هیچ محصولی یافت نشد</strong>
        <p>محصولی مطابق با عبارت جستجو شده پیدا نشد یا هنوز محصولی ثبت نشده است.</p>
    </div>
    <?php else: ?>
    <div class="admin-table-scroll">
        <table class="admin-table wp-products-table">
            <thead>
                <tr>
                    <th style="width: 60px; text-align: center;">تصویر</th>
                    <th>نام محصول</th>
                    <th>شناسه (SKU)</th>
                    <th>موجودی</th>
                    <th>قیمت</th>
                    <th>دسته‌بندی‌ها</th>
                    <th>برچسب‌ها / هشتگ‌ها</th>
                    <th>تاریخ ثبت</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products['items'] as $product): ?>
                <?php
                    $thumb = !empty($product['images'][0]) ? $product['images'][0] : '/assets/images/placeholder.jpg';
                    $priceDisplay = !empty($product['sale_price']) ? '<del style="opacity:0.6; font-size:0.85em; margin-left:4px;">' . e($product['regular_price']) . '</del> ' . e($product['sale_price']) : e($product['regular_price'] ?: ($product['reference_price'] ?: '—'));
                    if ($priceDisplay !== '—' && !empty($product['currency'])) {
                        $priceDisplay .= ' ' . e($product['currency']);
                    }
                    $tagsList = !empty($product['tags']) ? (is_array($product['tags']) ? implode(', ', $product['tags']) : $product['tags']) : '—';
                ?>
                <tr>
                    <td style="text-align: center; vertical-align: middle;">
                        <img src="<?= e($thumb) ?>" alt="<?= e($product['name']) ?>" style="width: 48px; height: 48px; object-fit: cover; border-radius: 4px; border: 1px solid var(--admin-border, #ddd);" onerror="this.onerror=null;this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'48\' height=\'48\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'%23ccc\' stroke-width=\'1.5\'><rect x=\'3\' y=\'3\' width=\'18\' height=\'18\' rx=\'2\'/><circle cx=\'8.5\' cy=\'8.5\' r=\'1.5\'/><path d=\'M21 15l-5-5L5 21\'/></svg>';">
                    </td>
                    <td>
                        <div class="table-primary">
                            <strong style="font-size: 1rem;"><a href="<?= e(admin_url('/products/edit/' . (int) $product['id'])) ?>"><?= e($product['name']) ?></a></strong>
                            <?php if (!empty($product['featured'])): ?><span class="tiny-tag gold" style="margin-right: 6px;">ویژه / منتخب</span><?php endif; ?>
                            <div class="row-actions" style="margin-top: 4px; font-size: 0.85rem; display: flex; gap: 8px;">
                                <a href="<?= e(admin_url('/products/edit/' . (int) $product['id'])) ?>">ویرایش</a> |
                                <a href="<?= e(url('/shop')) ?>" target="_blank" style="color: #666;">نمایش</a> |
                                <form method="post" action="<?= e(admin_url('/products')) ?>" data-confirm="آیا از حذف این محصول اطمینان دارید؟" style="display:inline;">
                                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="admin_action" value="delete_product">
                                    <input type="hidden" name="id" value="<?= e($product['id']) ?>">
                                    <button type="submit" style="background:none; border:none; color:#c00; padding:0; cursor:pointer; font-size:inherit;">حذف زباله‌دان</button>
                                </form>
                            </div>
                        </div>
                    </td>
                    <td class="mono-cell"><?= e($product['sku'] !== '' ? $product['sku'] : '—') ?></td>
                    <td>
                        <span class="status-pill <?= e($product['stock_status']) ?>"><i></i><?= e($stockLabels[$product['stock_status']] ?? $product['stock_status']) ?></span>
                        <?php if ($product['stock_quantity'] !== null): ?>
                        <small class="stock-number" style="display:block; margin-top:2px; color:#666;"><?= e($product['stock_quantity']) ?> عدد</small>
                        <?php endif; ?>
                    </td>
                    <td><?= $priceDisplay ?></td>
                    <td><?= e($product['category_names'] ?: 'دسته‌بندی نشده') ?></td>
                    <td><small style="color: #666;"><?= e($tagsList) ?></small></td>
                    <td>
                        <small style="display:block;"><?= ($product['publish_status'] ?? '') === 'published' ? 'منتشر شده' : 'پیش‌نویس' ?></small>
                        <small style="color:#888;"><?= e($product['updated_at'] ? date('Y/m/d H:i', strtotime($product['updated_at'])) : '—') ?></small>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if (($products['pages'] ?? 1) > 1): ?>
    <nav class="admin-pagination" aria-label="صفحات محصولات">
        <?php for ($page = 1; $page <= (int) $products['pages']; $page++): ?>
        <a class="<?= $page === (int) $products['page'] ? 'is-current' : '' ?>" href="<?= e(admin_url('/products?' . http_build_query(array_filter(array('q' => $query, 'page' => $page))))) ?>"><?= e($page) ?></a>
        <?php endfor; ?>
    </nav>
    <?php endif; ?>
</section>
