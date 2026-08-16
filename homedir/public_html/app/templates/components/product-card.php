<?php
$cardCategory = product_category($product);
$cardImage = product_image($product);
$reference = money_reference($product['price'], (string) $product['currency']);
$displayName = str_ireplace(array('whith', 'plated pants', 'lnner top'), array('with', 'pleated pants', 'inner top'), (string) $product['name']);
$translatedName = t($displayName);
$translatedCategoryName = t($cardCategory['name']);
?>
<article class="product-card reveal" data-product-card data-slug="<?= e($product['slug']) ?>">
    <div class="product-media">
        <a href="<?= e(url('/product/' . $product['slug'])) ?>" aria-label="<?= e(t('view')) ?> <?= e($translatedName) ?>">
            <?php if ($cardImage !== ''): ?>
                <img src="<?= e($cardImage) ?>" alt="<?= e($translatedName) ?>" loading="lazy" decoding="async" width="600" height="780" data-image-fallback data-fallback-label="Image unavailable for <?= e($translatedName) ?>">
            <?php else: ?>
                <span class="product-placeholder" role="img" aria-label="Image unavailable for <?= e($translatedName) ?>"><b aria-hidden="true">R</b><span>Image archive pending</span></span>
            <?php endif; ?>
        </a>
        <button class="save-button" type="button" data-shortlist-toggle="<?= e($product['slug']) ?>" data-shortlist-name="<?= e($translatedName) ?>" aria-label="<?= e(t('save_to_shortlist')) ?>" aria-pressed="false"><span class="sr-only"><?= e(t('save_to_shortlist')) ?></span><span aria-hidden="true">＋</span></button>
        <span class="card-index"><?= e($product['sku'] !== '' ? $product['sku'] : 'RASPINA') ?></span>
    </div>
    <div class="product-copy">
        <div><a class="product-category" href="<?= e($cardCategory['slug'] !== '' ? url('/collection/' . $cardCategory['slug']) : url('/shop')) ?>"><?= e($translatedCategoryName) ?></a><h3><a href="<?= e(url('/product/' . $product['slug'])) ?>"><?= e($translatedName) ?></a></h3></div>
        <div class="product-meta">
            <span class="stock <?= $product['stock_status'] === 'instock' ? 'is-in' : 'is-out' ?>"><?= $product['stock_status'] === 'instock' ? e(t('available_stock')) : e(t('out_of_stock')) ?></span>
            <span><?= $reference !== '' ? 'Ref. ' . e($reference) : e(t('quote_on_request')) ?></span>
        </div>
        <a class="card-enquire" href="<?= e(url('/wholesale?product=' . rawurlencode($product['slug']))) ?>"><?= e(t('request_wholesale_quote')) ?> <span aria-hidden="true">↗</span></a>
    </div>
</article>
