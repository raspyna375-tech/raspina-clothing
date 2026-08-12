<?php
$cardCategory = product_category($product);
$cardImage = product_image($product);
$reference = money_reference($product['price'], (string) $product['currency']);
$displayName = str_ireplace(array('whith', 'plated pants', 'lnner top'), array('with', 'pleated pants', 'inner top'), (string) $product['name']);
?>
<article class="product-card reveal" data-product-card data-slug="<?= e($product['slug']) ?>">
    <div class="product-media">
        <a href="<?= e(url('/product/' . $product['slug'])) ?>" aria-label="View <?= e($displayName) ?>">
            <?php if ($cardImage !== ''): ?>
                <img src="<?= e($cardImage) ?>" alt="<?= e($displayName) ?>" loading="lazy" decoding="async" width="600" height="780" data-image-fallback data-fallback-label="Image unavailable for <?= e($displayName) ?>">
            <?php else: ?>
                <span class="product-placeholder" role="img" aria-label="Image unavailable for <?= e($displayName) ?>"><b aria-hidden="true">R</b><span>Image archive pending</span></span>
            <?php endif; ?>
        </a>
        <button class="save-button" type="button" data-shortlist-toggle="<?= e($product['slug']) ?>" data-shortlist-name="<?= e($displayName) ?>" aria-label="Save <?= e($displayName) ?> to shortlist" aria-pressed="false"><span class="sr-only">Save <?= e($displayName) ?> to shortlist</span><span aria-hidden="true">＋</span></button>
        <span class="card-index"><?= e($product['sku'] !== '' ? $product['sku'] : 'RASPINA') ?></span>
    </div>
    <div class="product-copy">
        <div><a class="product-category" href="<?= e($cardCategory['slug'] !== '' ? url('/collection/' . $cardCategory['slug']) : url('/shop')) ?>"><?= e($cardCategory['name']) ?></a><h3><a href="<?= e(url('/product/' . $product['slug'])) ?>"><?= e($displayName) ?></a></h3></div>
        <div class="product-meta">
            <span class="stock <?= $product['stock_status'] === 'instock' ? 'is-in' : 'is-out' ?>"><?= $product['stock_status'] === 'instock' ? 'Available to quote' : 'Archive item' ?></span>
            <span><?= $reference !== '' ? 'Ref. ' . e($reference) : 'Quote on request' ?></span>
        </div>
        <a class="card-enquire" href="<?= e(url('/wholesale?product=' . rawurlencode($product['slug']))) ?>">Request wholesale quote <span aria-hidden="true">↗</span></a>
    </div>
</article>
