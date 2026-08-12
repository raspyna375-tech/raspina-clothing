<header class="search-hero">
    <p class="eyebrow">Search / Catalogue index</p>
    <h1><?= $query === '' ? 'Find a piece' : 'Results for “' . e($query) . '”' ?></h1>
    <form action="<?= e(url('/search')) ?>" method="get" role="search"><label for="page-search">Search by name, SKU or collection</label><div><input id="page-search" name="q" type="search" maxlength="80" value="<?= e($query) ?>" placeholder="Start typing…"><button type="submit">Search ↗</button></div></form>
</header>
<section class="section search-results">
    <?php if ($query === ''): ?>
        <div class="empty-state"><span>⌕</span><h2>The archive is ready.</h2><p>Search a garment name, collection or product SKU.</p><a class="button button-dark" href="<?= e(url('/shop')) ?>">Browse everything</a></div>
    <?php elseif ($results['items']): ?>
        <div class="results-top"><p><?= e($results['total']) ?> result<?= $results['total'] === 1 ? '' : 's' ?></p><a href="<?= e(url('/shop?q=' . rawurlencode($query))) ?>">Open in shop filters ↗</a></div>
        <div class="product-grid"><?php foreach ($results['items'] as $product) { render_product_card($product); } ?></div>
        <?php if ($results['pages'] > 1): ?><nav class="pagination" aria-label="Search result pages"><?php if ($results['page'] > 1): ?><a href="<?= e(pagination_url($results['page'] - 1)) ?>">← Previous</a><?php else: ?><span></span><?php endif; ?><span>Page <?= e($results['page']) ?> / <?= e($results['pages']) ?></span><?php if ($results['page'] < $results['pages']): ?><a href="<?= e(pagination_url($results['page'] + 1)) ?>">Next →</a><?php else: ?><span></span><?php endif; ?></nav><?php endif; ?>
    <?php else: ?>
        <div class="empty-state"><span>00</span><h2>No matching pieces.</h2><p>Try a shorter phrase, a SKU, or browse the full archive.</p><a class="button button-dark" href="<?= e(url('/shop')) ?>">Browse the collection</a></div>
    <?php endif; ?>
</section>
