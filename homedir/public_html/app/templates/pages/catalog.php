<header class="editorial-header catalog-header">
    <div><p class="eyebrow">Raspina paper archive</p><h1>The<br><em>catalogs.</em></h1></div>
    <p>Seasonal studies in silhouette, proportion and fabric—collected as a visual reference for wholesale partners.</p>
    <span aria-hidden="true">23</span>
</header>

<section class="catalog-lead">
    <div class="catalog-lead-image"><img src="<?= e(asset('images/editorial/shop_08.jpg')) ?>" alt="Raspina Clothing 2023 catalogue cover" width="900" height="1200"><span>Catalogue / 2023 / PDF</span></div>
    <div class="catalog-lead-copy"><p class="eyebrow">Available now</p><h2>Collection<br>book 2023</h2><p>Open the verified Raspina catalogue as a downloadable PDF. Use it as an archival reference; current availability and wholesale terms are confirmed separately.</p><dl><div><dt>Format</dt><dd>PDF / 6.8 MB</dd></div><div><dt>Use</dt><dd>Wholesale reference</dd></div><div><dt>Status</dt><dd>Verified archive</dd></div></dl><a class="button button-dark" href="<?= e(asset('catalog/raspina-catalog-2023.pdf')) ?>" download>Download catalog ↓</a></div>
</section>

<section class="section catalog-grid-section">
    <div class="section-heading"><p class="eyebrow">Visual index</p><h2>Collection highlights</h2><a class="text-link" href="<?= e(url('/shop')) ?>">Explore every piece ↗</a></div>
    <div class="catalog-mosaic"><?php foreach ($catalog_products as $index => $product): ?><a class="catalog-tile reveal" href="<?= e(url('/product/' . $product['slug'])) ?>"><div><?php if (product_image($product) !== ''): ?><img src="<?= e(product_image($product)) ?>" alt="<?= e($product['name']) ?>" loading="lazy" width="650" height="850"><?php else: ?><span class="product-placeholder"><b>R</b></span><?php endif; ?></div><span><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?> / <?= e($product['name']) ?></span></a><?php endforeach; ?></div>
</section>

<section class="archive-note"><p class="eyebrow">Earlier catalogues</p><div><h2>2022 / 2024</h2><p>The recovered archive does not contain verified files for these editions yet. They will appear here after source files are confirmed—no placeholder downloads.</p></div><a href="<?= e(url('/contact')) ?>">Request an archival reference ↗</a></section>
