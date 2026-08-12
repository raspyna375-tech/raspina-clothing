<?php
$displayProductName = function ($name): string {
    return str_ireplace(array('whith', 'plated pants', 'lnner top'), array('with', 'pleated pants', 'inner top'), (string) $name);
};
$primaryCategory = product_category($product);
$reference = money_reference($product['price'], (string) $product['currency']);
$displayName = $displayProductName($product['name']);
$description = $displayProductName(clean_product_copy((string) ($product['summary'] ?: $product['description'])));
$galleryImages = array_values(array_filter((array) $product['images'], function ($image): bool {
    return trim((string) $image) !== '';
}));
$galleryTotal = count($galleryImages);
?>
<nav class="breadcrumbs" aria-label="Breadcrumb">
    <a href="<?= e(url('/')) ?>">Home</a><span aria-hidden="true">/</span><a href="<?= e(url('/shop')) ?>">Shop</a>
    <?php if ($primaryCategory['slug'] !== ''): ?><span aria-hidden="true">/</span><a href="<?= e(url('/collection/' . $primaryCategory['slug'])) ?>"><?= e($primaryCategory['name']) ?></a><?php endif; ?>
    <span aria-hidden="true">/</span><span aria-current="page"><?= e($displayName) ?></span>
</nav>
<article class="product-page">
    <section class="product-gallery" aria-label="Product images">
        <?php if ($galleryTotal > 0): ?>
            <button class="gallery-main" type="button" data-gallery-open="0" aria-label="Open image 1 of <?= $galleryTotal ?> for <?= e($displayName) ?>" aria-haspopup="dialog" aria-controls="product-image-gallery">
                <img src="<?= e(url((string) $galleryImages[0])) ?>" alt="<?= e($displayName) ?> - view 1" width="1000" height="1300" decoding="async" data-image-fallback data-fallback-label="Image unavailable for <?= e($displayName) ?>">
                <span>Open gallery <span aria-hidden="true">&#8599;</span></span>
            </button>
            <?php if ($galleryTotal > 1): ?>
                <div class="gallery-thumbs" aria-label="More product images">
                    <?php foreach (array_slice($galleryImages, 1, 5) as $index => $image): $position = $index + 2; ?>
                        <button type="button" data-gallery-open="<?= $index + 1 ?>" aria-label="Open image <?= $position ?> of <?= $galleryTotal ?> for <?= e($displayName) ?>" aria-haspopup="dialog" aria-controls="product-image-gallery">
                            <img src="<?= e(url((string) $image)) ?>" alt="" loading="lazy" decoding="async" width="360" height="460" data-image-fallback data-fallback-label="Image <?= $position ?> unavailable for <?= e($displayName) ?>">
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="gallery-placeholder" role="img" aria-label="Image unavailable for <?= e($displayName) ?>"><b aria-hidden="true">R</b><p>Image archive pending</p></div>
        <?php endif; ?>
    </section>
    <section class="product-summary">
        <div class="product-title-row"><p class="eyebrow"><?= e($primaryCategory['name']) ?> / SKU <?= e($product['sku'] ?: 'N/A') ?></p><h1><?= e($displayName) ?></h1></div>
        <div class="availability-line"><span class="stock <?= $product['stock_status'] === 'instock' ? 'is-in' : 'is-out' ?>"><?= $product['stock_status'] === 'instock' ? 'Available to quote' : 'Archive / confirm availability' ?></span><?php if ($reference !== ''): ?><span>Reference price <?= e($reference) ?></span><?php else: ?><span>Wholesale price on request</span><?php endif; ?></div>
        <?php if ($description !== ''): ?><p class="product-description"><?= e($description) ?></p><?php else: ?><p class="product-description">A Raspina collection piece available for wholesale consultation. Contact our team for current fabric, sizing, colour and production options.</p><?php endif; ?>
        <?php if (!empty($product['attributes'])): ?><dl class="attribute-list"><?php foreach ($product['attributes'] as $name => $values): ?><div><dt><?= e($name) ?></dt><dd><?= e(implode(' / ', (array) $values)) ?></dd></div><?php endforeach; ?></dl><?php endif; ?>
        <div class="quote-note"><span>Pricing note</span><p><?= $reference !== '' ? 'This is a recovered reference value. Your quotation confirms current wholesale pricing, currency, quantities and delivery.' : 'Pricing depends on quantity, fabric, sizing and destination. Your quotation confirms every commercial detail.' ?></p></div>
        <div class="product-actions"><a class="button button-dark button-wide" href="<?= e(url('/wholesale?product=' . rawurlencode($product['slug']))) ?>">Request wholesale quote</a><button class="button button-line button-wide" type="button" data-shortlist-toggle="<?= e($product['slug']) ?>" data-shortlist-name="<?= e($displayName) ?>" aria-label="Save <?= e($displayName) ?> to shortlist" aria-pressed="false">+ Add to inquiry shortlist</button></div>
        <details class="product-disclosure" data-disclosure>
            <summary aria-expanded="false" aria-controls="size-guide-panel">Size &amp; customisation guide <span aria-hidden="true">+</span></summary>
            <div id="size-guide-panel" data-disclosure-panel><p>Available sizing and custom production vary by style and order volume. Include your target size range, fabric preferences and market in the enquiry.</p></div>
        </details>
        <details class="product-disclosure" data-disclosure>
            <summary aria-expanded="false" aria-controls="wholesale-tiers-panel">Wholesale tiers <span aria-hidden="true">+</span></summary>
            <div id="wholesale-tiers-panel" data-disclosure-panel><p>Minimums and unit pricing are confirmed for each destination and production run. No unverified legacy tiers are used.</p></div>
        </details>
        <div class="product-contact"><span>Need a quick answer?</span><a href="tel:<?= e(site_setting('site.order_mobile_link', $config['site']['order_mobile_link'])) ?>">Call the order team <span aria-hidden="true">&#8599;</span></a></div>
    </section>
</article>

<?php if ($galleryTotal > 0): ?>
<dialog class="gallery-dialog<?= $galleryTotal === 1 ? ' is-single' : '' ?>" id="product-image-gallery" data-gallery-dialog aria-label="Image gallery for <?= e($displayName) ?>" aria-describedby="gallery-instructions" aria-modal="true" aria-hidden="true">
    <p class="sr-only" id="gallery-instructions">Use the previous and next buttons or the left and right arrow keys to move through images. Press Escape to close.</p>
    <button type="button" class="gallery-close" data-gallery-close aria-label="Close image gallery">Close <span aria-hidden="true">&times;</span></button>
    <?php if ($galleryTotal > 1): ?><button type="button" class="gallery-nav gallery-prev" data-gallery-prev aria-label="Previous product image"><span aria-hidden="true">&#8592;</span></button><?php endif; ?>
    <figure><img data-gallery-image src="<?= e(url((string) $galleryImages[0])) ?>" alt="<?= e($displayName) ?> - view 1" data-image-fallback data-fallback-label="Product image unavailable"><figcaption data-gallery-caption aria-live="polite" aria-atomic="true">1 / <?= $galleryTotal ?></figcaption></figure>
    <?php if ($galleryTotal > 1): ?><button type="button" class="gallery-nav gallery-next" data-gallery-next aria-label="Next product image"><span aria-hidden="true">&#8594;</span></button><?php endif; ?>
    <script type="application/json" data-gallery-data><?= json_for_html(array_map(function ($image, $index) use ($displayName) { return array('src' => url((string) $image), 'alt' => $displayName . ' - view ' . ($index + 1)); }, $galleryImages, array_keys($galleryImages))) ?></script>
</dialog>
<?php endif; ?>

<nav class="product-adjacent" aria-label="Adjacent products"><div><?php if ($adjacent_products['previous']): ?><span>Previous piece</span><a href="<?= e(url('/product/' . $adjacent_products['previous']['slug'])) ?>">&#8592; <?= e($displayProductName($adjacent_products['previous']['name'])) ?></a><?php endif; ?></div><div><?php if ($adjacent_products['next']): ?><span>Next piece</span><a href="<?= e(url('/product/' . $adjacent_products['next']['slug'])) ?>"><?= e($displayProductName($adjacent_products['next']['name'])) ?> &#8594;</a><?php endif; ?></div></nav>

<?php if ($related_products): ?><section class="section related"><div class="section-heading"><p class="eyebrow">Continue the edit</p><h2>Related pieces</h2><a class="text-link" href="<?= e($primaryCategory['slug'] !== '' ? url('/collection/' . $primaryCategory['slug']) : url('/shop')) ?>">View collection &#8599;</a></div><div class="product-grid"><?php foreach ($related_products as $related) { render_product_card($related); } ?></div></section><?php endif; ?>
