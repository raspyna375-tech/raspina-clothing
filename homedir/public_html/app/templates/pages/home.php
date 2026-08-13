<?php
$heroPool = !empty($hero_products) ? $hero_products : (array) $featured_products;
$heroData = array();
foreach ($heroPool as $heroProduct) {
    $heroImage = product_image($heroProduct);
    if ($heroImage === '') {
        continue;
    }
    $heroCategory = product_category($heroProduct);
    $heroData[] = array(
        'src' => $heroImage,
        'category' => (string) $heroCategory['name'],
        'title' => (string) $heroProduct['name'],
        'text' => product_meta_description($heroProduct),
    );
}
if (!$heroData) {
    $heroData[] = array(
        'src' => asset('images/editorial/shop_01.jpg'),
        'category' => t('Raspina archive'),
        'title' => t('Explore the Raspina collection'),
        'text' => t('Browse the current wholesale catalogue and build a focused edit for your market.'),
    );
}
$totalProducts = count($catalog->allProducts());
?>
<section class="raspina-vortex-hero" id="raspinaHero" dir="ltr" aria-label="Raspina luxury fashion hero">
    <canvas id="raspinaVortexCanvas" class="raspina-vortex-canvas" width="5120" height="1440" aria-label="Interactive Raspina product gallery"></canvas>
    <div class="raspina-vortex-shade"></div>
    <div class="raspina-hero-ui">
        <div class="raspina-hero-copy">
            <span class="raspina-hero-kicker"><?= e(t(site_setting('home.hero_kicker', "Wholesale Women's Fashion"))) ?></span>
            <h1><?= nl2br(e(t(site_setting('home.hero_title', 'Designed for boutiques that move with style.')))) ?></h1>
            <p><?= e(t(site_setting('home.hero_description', "Explore Raspina's real product collections through an immersive motion gallery. Premium women's apparel, refined silhouettes, and wholesale-ready pieces crafted for modern fashion retailers."))) ?></p>
            <div class="raspina-hero-actions">
                <a href="<?= e(url('/shop')) ?>"><?= e(t('View Collection')) ?></a>
                <a href="<?= e(asset('catalog/raspina-catalog-2023.pdf')) ?>" class="ghost" download><?= e(t('Download Catalog')) ?></a>
            </div>
        </div>
        <div class="raspina-hero-footer"><?= e(t(site_setting('home.hero_footer', 'Move pointer or scroll to explore real Raspina products'))) ?></div>
    </div>
    <div class="raspina-hero-modal" id="raspinaHeroModal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="raspinaHeroModalTitle">
        <button type="button" class="raspina-hero-close" id="raspinaHeroClose" aria-label="Close product preview">×</button>
        <img id="raspinaHeroModalImg" src="" alt="Selected Raspina product">
        <div>
            <span id="raspinaHeroModalCat"></span>
            <h2 id="raspinaHeroModalTitle"></h2>
            <p id="raspinaHeroModalText"></p>
        </div>
    </div>
    <script type="application/json" id="raspina-hero-data"><?= json_for_html($heroData) ?></script>
</section>

<section class="section arrivals" aria-labelledby="arrivals-title">
    <div class="section-heading"><p class="eyebrow"><?= e(t(site_setting('home.arrivals_kicker', 'New in the archive'))) ?></p><h2 id="arrivals-title"><?= e(t(site_setting('home.arrivals_title', 'Recent arrivals'))) ?></h2><a class="text-link" href="<?= e(url('/shop')) ?>"><?= e(t('View all pieces')) ?> <?= e($totalProducts) ?> ↗</a></div>
    <div class="product-grid home-product-grid"><?php foreach ($featured_products as $product) { render_product_card($product); } ?></div>
</section>

<section class="section collection-index" aria-labelledby="collection-title">
    <div class="collection-intro"><p class="eyebrow"><?= e(t(site_setting('home.collections_kicker', 'Index'))) ?> / <?= count($home_collections) ?> <?= e(t('selected chapters')) ?></p><h2 id="collection-title"><?= nl2br(e(t(site_setting('home.collections_title', "A wardrobe,\nedited by chapter.")))) ?></h2><p><?= e(t(site_setting('home.collections_description', 'From fluid blouses to considered tailoring, each chapter is designed to work as a coherent boutique selection.'))) ?></p></div>
    <div class="collection-list">
        <?php foreach ($home_collections as $index => $collection): ?>
            <a class="collection-row reveal" href="<?= e(url('/collection/' . $collection['slug'])) ?>">
                <span class="collection-number"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
                <span class="collection-name"><?= e($collection['name']) ?></span>
                <span class="collection-count"><?= e($collection['product_count']) ?> <?= e(t('products')) ?></span>
                <?php if ($collection['image'] !== ''): ?><img src="<?= e($collection['image']) ?>" alt="" loading="lazy"><?php endif; ?>
                <span class="collection-arrow" aria-hidden="true">↗</span>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<section class="wholesale-band">
    <div class="wholesale-statement"><p class="eyebrow"><?= e(t(site_setting('home.wholesale_kicker', 'Built for wholesale'))) ?></p><h2><?= nl2br(e(t(site_setting('home.wholesale_title', "From our studio\nto your store.")))) ?></h2></div>
    <ol class="process-list">
        <li><span>01</span><div><h3><?= e(t('Curate')) ?></h3><p><?= e(t('Save the pieces that fit your customer and market.')) ?></p></div></li>
        <li><span>02</span><div><h3><?= e(t('Enquire')) ?></h3><p><?= e(t('Share quantities, sizing, destination and timing.')) ?></p></div></li>
        <li><span>03</span><div><h3><?= e(t('Confirm')) ?></h3><p><?= e(t('Our team confirms current availability, pricing and production details.')) ?></p></div></li>
    </ol>
    <a class="button button-light" href="<?= e(url('/wholesale')) ?>"><?= e(t(site_setting('home.wholesale_button', 'How wholesale works'))) ?></a>
</section>

<section class="catalog-feature">
    <div class="catalog-image"><img src="<?= e(asset('images/editorial/shop_09.jpg')) ?>" alt="Raspina Clothing editorial catalogue" loading="lazy" width="900" height="1100"><span><?= e(t(site_setting('home.catalog_label', 'Archive / 2023'))) ?></span></div>
    <div class="catalog-copy"><p class="eyebrow"><?= e(t(site_setting('home.catalog_kicker', 'The printed edit'))) ?></p><h2><?= nl2br(e(t(site_setting('home.catalog_title', "Catalog\nNo. 23")))) ?></h2><p><?= e(t(site_setting('home.catalog_description', 'A tactile overview of silhouettes, styling and collection stories from the Raspina archive.'))) ?></p><div><a class="button button-dark" href="<?= e(asset('catalog/raspina-catalog-2023.pdf')) ?>" download><?= e(t(site_setting('home.catalog_button', 'Download PDF'))) ?></a><a class="text-link" href="<?= e(url('/catalog')) ?>"><?= e(t('Open catalog archive')) ?> ↗</a></div></div>
</section>

<section class="home-close"><p class="eyebrow"><?= e(t(site_setting('home.close_kicker', 'Keep in touch'))) ?></p><h2><?= nl2br(e(t(site_setting('home.close_title', "For appointments, distribution\nand collection enquiries.")))) ?></h2><div><a href="<?= e(url('/contact')) ?>"><?= e(t('Contact the studio')) ?> ↗</a><a href="<?= e(t(site_setting('site.instagram', $config['site']['instagram']))) ?>" target="_blank" rel="noopener"><?= e(t('Follow on Instagram')) ?> ↗</a></div></section>
