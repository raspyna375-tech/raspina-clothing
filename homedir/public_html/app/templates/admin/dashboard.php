<header class="admin-page-head dashboard-head">
    <div><p class="admin-eyebrow">Operations / <?= e(date('d M Y')) ?></p><h1>Good morning,<br><em><?= e((string) ($admin_user['name'] ?? 'studio')) ?>.</em></h1><p class="admin-lede">A live view of the Raspina catalogue and the conversations currently shaping the next edit.</p></div>
    <a class="admin-primary-button" href="<?= e(admin_url('/products/new')) ?>">Add product <span aria-hidden="true">↗</span></a>
</header>
<?php if (!empty($admin_error)): ?><div class="admin-state-card <?= e($admin_repository->status()) ?>"><span class="state-dot"></span><div><strong>Data connection needs attention</strong><p><?= e($admin_error) ?></p></div></div><?php endif; ?>
<section class="admin-stat-grid" aria-label="Catalogue totals">
    <a class="admin-stat-card stat-ink" href="<?= e(admin_url('/products')) ?>"><span class="stat-label">Products</span><strong><?= e($stats['products'] ?? 0) ?></strong><small>Live catalogue records <span>↗</span></small></a>
    <a class="admin-stat-card" href="<?= e(admin_url('/categories')) ?>"><span class="stat-label">Categories</span><strong><?= e($stats['categories'] ?? 0) ?></strong><small>Collection chapters <span>↗</span></small></a>
    <a class="admin-stat-card" href="<?= e(admin_url('/messages')) ?>"><span class="stat-label">Enquiries</span><strong><?= e($stats['messages'] ?? 0) ?></strong><small>Wholesale + contact <span>↗</span></small></a>
    <div class="admin-stat-card stat-gold"><span class="stat-label">Featured edit</span><strong><?= e($stats['featured'] ?? 0) ?></strong><small>Pieces in homepage rotation</small></div>
</section>
<section class="admin-dashboard-grid">
    <div class="admin-panel admin-panel-tall">
        <div class="panel-heading"><div><p class="admin-eyebrow">Latest conversations</p><h2>Open the thread.</h2></div><a class="panel-link" href="<?= e(admin_url('/messages')) ?>">All enquiries ↗</a></div>
        <?php if (empty($stats['latest_messages'])): ?><div class="empty-state"><span>◌</span><strong>No enquiries yet</strong><p>New website enquiries will appear here when MySQL is receiving messages.</p></div>
        <?php else: ?><div class="mini-message-list"><?php foreach ($stats['latest_messages'] as $message): ?><a class="mini-message" href="<?= e(admin_url('/messages/view/' . (int) $message['id'])) ?>"><span class="message-type <?= e($message['message_type']) ?>"><?= e($message['message_type']) ?></span><div><strong><?= e($message['name']) ?></strong><small><?= e($message['business_name'] !== '' ? $message['business_name'] : $message['email']) ?><?= $message['country'] !== '' ? ' / ' . e($message['country']) : '' ?></small></div><time datetime="<?= e($message['created_at']) ?>"><?= e(date('d M', strtotime($message['created_at']))) ?></time><span class="mini-arrow">↗</span></a><?php endforeach; ?></div><?php endif; ?>
    </div>
    <div class="admin-panel admin-panel-dark">
        <p class="admin-eyebrow">Studio pulse</p><h2>Keep the edit<br><em>alive.</em></h2><p class="panel-copy">Your public catalogue is currently reading from <strong><?= e($catalog_mode === 'mysql' ? 'MySQL' : 'JSON fallback') ?></strong>. Use the admin workspace to keep product stories, availability and buyer-facing copy current.</p>
        <div class="panel-actions"><a class="admin-light-button" href="<?= e(admin_url('/settings')) ?>">Edit site copy <span>↗</span></a><a class="admin-text-link" href="<?= e(url('/')) ?>" target="_blank" rel="noopener">Preview live site ↗</a></div>
    </div>
</section>
<section class="admin-quick-links"><p class="admin-eyebrow">Quick actions</p><div><a href="<?= e(admin_url('/products/new')) ?>"><span>01</span><strong>New product</strong><small>Build a catalogue record</small><b>↗</b></a><a href="<?= e(admin_url('/categories/new')) ?>"><span>02</span><strong>New category</strong><small>Create a collection chapter</small><b>↗</b></a><a href="<?= e(admin_url('/settings')) ?>"><span>03</span><strong>Site settings</strong><small>Change public-facing copy</small><b>↗</b></a></div></section>
