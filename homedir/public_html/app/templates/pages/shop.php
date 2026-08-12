<?php
$showcaseDescription = isset($current_category) && $current_category['description'] !== ''
    ? (string) $current_category['description']
    : 'Browse a refined selection of Raspina products with a premium editorial layout, fast category access, and a clean boutique shopping experience.';
?>
<section class="shop-showcase" aria-labelledby="shop-showcase-title">
    <div class="shop-showcase-copy">
        <p class="hero-badge"><?= e($archive_kicker) ?></p>
        <h1 id="shop-showcase-title">Curated women’s fashion for modern boutiques.</h1>
        <p><?= e($showcaseDescription) ?></p>
        <div class="shop-showcase-actions">
            <a class="button button-gold" href="#shop-archive">Browse the archive</a>
            <a class="button button-dark-outline" href="<?= e(url('/wholesale')) ?>">Wholesale enquiry</a>
        </div>
    </div>
    <div class="shop-showcase-images" aria-hidden="true">
        <figure><img src="<?= e(asset('images/editorial/shop_02.webp')) ?>" alt="" width="560" height="760"></figure>
        <figure><img src="<?= e(asset('images/editorial/shop_03.webp')) ?>" alt="" width="560" height="760"></figure>
        <figure><img src="<?= e(asset('images/editorial/shop_05.webp')) ?>" alt="" width="560" height="760"></figure>
    </div>
</section>

<nav class="quick-categories" aria-label="Popular categories">
    <?php foreach (array_slice($categories, 0, 8) as $category): ?>
        <a href="<?= e(url('/collection/' . $category['slug'])) ?>"><?= e($category['name']) ?><span><?= e($category['product_count']) ?></span></a>
    <?php endforeach; ?>
</nav>

<header class="archive-hero">
    <div><p class="eyebrow"><?= e($archive_kicker) ?></p><h1><?= e($archive_title) ?></h1></div>
    <p><?= isset($current_category) && $current_category['description'] !== '' ? e($current_category['description']) : 'An evolving archive of Raspina silhouettes for boutique and distribution partners.' ?></p>
    <span class="archive-total"><?= str_pad((string) $results['total'], 3, '0', STR_PAD_LEFT) ?></span>
</header>

<section class="archive-layout" id="shop-archive">
    <aside class="filter-panel" aria-label="Filter products">
        <div class="filter-heading"><span>Filter the archive</span><button type="button" data-filter-toggle aria-expanded="false">Filters +</button></div>
        <form method="get" action="<?= e(isset($current_category) ? url('/collection/' . $current_category['slug']) : url('/shop')) ?>" data-filter-form>
            <label><span>Search</span><input type="search" name="q" maxlength="80" value="<?= e($filters['q']) ?>" placeholder="Name or SKU"></label>
            <?php if (!isset($current_category)): ?>
            <label><span>Collection</span><select name="category"><option value="">All collections</option><?php foreach ($categories as $category): ?><option value="<?= e($category['slug']) ?>"<?= $filters['category'] === $category['slug'] ? ' selected' : '' ?>><?= e($category['name']) ?> (<?= e($category['product_count']) ?>)</option><?php endforeach; ?></select></label>
            <?php endif; ?>
            <label><span>Availability</span><select name="stock"><option value="">All pieces</option><option value="instock"<?= $filters['stock'] === 'instock' ? ' selected' : '' ?>>Available to quote</option><option value="onbackorder"<?= $filters['stock'] === 'onbackorder' ? ' selected' : '' ?>>Confirm production timing</option><option value="outofstock"<?= $filters['stock'] === 'outofstock' ? ' selected' : '' ?>>Archive items</option></select></label>
            <label><span>Order by</span><select name="sort"><option value="latest"<?= $filters['sort'] === 'latest' ? ' selected' : '' ?>>Latest</option><option value="az"<?= $filters['sort'] === 'az' ? ' selected' : '' ?>>Name A–Z</option><option value="za"<?= $filters['sort'] === 'za' ? ' selected' : '' ?>>Name Z–A</option><option value="sku"<?= $filters['sort'] === 'sku' ? ' selected' : '' ?>>SKU</option><option value="price-low"<?= $filters['sort'] === 'price-low' ? ' selected' : '' ?>>Reference price: low</option><option value="price-high"<?= $filters['sort'] === 'price-high' ? ' selected' : '' ?>>Reference price: high</option></select></label>
            <button class="button button-dark" type="submit">Apply filters</button>
            <?php if (array_filter($filters)): ?><a class="reset-link" href="<?= e(isset($current_category) ? url('/collection/' . $current_category['slug']) : url('/shop')) ?>">Reset all</a><?php endif; ?>
        </form>
    </aside>
    <div class="archive-results">
        <div class="results-top"><p>Showing <?= $results['total'] === 0 ? '0' : e((($results['page'] - 1) * $results['per_page']) + 1) ?>–<?= e(min($results['page'] * $results['per_page'], $results['total'])) ?> of <?= e($results['total']) ?></p><a href="<?= e(url('/wishlist')) ?>">Open shortlist <span data-shortlist-count>0</span> ↗</a></div>
        <?php if ($results['items']): ?>
            <div class="product-grid"><?php foreach ($results['items'] as $product) { render_product_card($product); } ?></div>
            <?php if ($results['pages'] > 1): ?>
                <nav class="pagination" aria-label="Product pages">
                    <?php if ($results['page'] > 1): ?><a href="<?= e(pagination_url($results['page'] - 1)) ?>" rel="prev">← Previous</a><?php else: ?><span></span><?php endif; ?>
                    <span>Page <?= e($results['page']) ?> / <?= e($results['pages']) ?></span>
                    <?php if ($results['page'] < $results['pages']): ?><a href="<?= e(pagination_url($results['page'] + 1)) ?>" rel="next">Next →</a><?php else: ?><span></span><?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php else: ?>
            <div class="empty-state"><span>00</span><h2>No pieces matched this edit.</h2><p>Try a broader search or clear the current filters.</p><a class="button button-dark" href="<?= e(isset($current_category) ? url('/collection/' . $current_category['slug']) : url('/shop')) ?>">Reset the archive</a></div>
        <?php endif; ?>
    </div>
</section>
