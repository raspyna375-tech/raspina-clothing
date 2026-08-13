<header class="shortlist-header"><div><p class="eyebrow"><?= e(t('Your private edit')) ?></p><h1 id="shortlist-title"><?= t('Inquiry<br><em>shortlist.</em>') ?></h1></div><p><?= e(t('Save pieces as you browse, review them here, then include the complete list in a wholesale request.')) ?></p><span data-shortlist-count aria-label="0 saved items">0</span></header>
<section class="section shortlist-content" aria-labelledby="shortlist-title">
    <div class="shortlist-tools"><p><span data-shortlist-count aria-label="0 saved items">0</span> <?= e(t('saved pieces')) ?></p><button type="button" data-shortlist-clear aria-controls="wishlist-grid"><?= e(t('Clear shortlist')) ?></button></div>
    <div class="wishlist-grid" id="wishlist-grid" data-wishlist-grid aria-label="Saved products"></div>
    <div class="empty-state wishlist-empty" data-wishlist-empty role="status"><span>00</span><h2><?= e(t('Your edit is still open.')) ?></h2><p><?= e(t('Save garments from the shop to build a focused wholesale enquiry.')) ?></p><a class="button button-dark" href="<?= e(url('/shop')) ?>"><?= e(t('Explore the collection')) ?></a></div>
    <div class="shortlist-cta" data-wishlist-cta hidden><div><p class="eyebrow"><?= e(t('Ready for the next step?')) ?></p><h2><?= t('Send this edit<br>to the studio.') ?></h2></div><a class="button button-light" href="<?= e(url('/wholesale#enquiry')) ?>"><?= e(t('Start wholesale enquiry')) ?></a></div>
    <script type="application/json" id="wishlist-catalog"><?= json_for_html($wishlist_catalog) ?></script>
</section>
