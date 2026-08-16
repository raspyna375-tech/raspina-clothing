<header class="admin-page-head compact-head">
    <div>
        <p class="admin-eyebrow">امنیت / ردیابی رویدادهای سیستم</p>
        <h1>گزارش رویدادها <span class="head-count"><?= e($audit_logs['total'] ?? 0) ?></span></h1>
        <p class="admin-lede">آرشیو زنده از اقدامات انجام شده توسط اعضای تیم و مالکان سیستم در پنل مدیریت.</p>
    </div>
</header>
<?php if (!empty($admin_error)): ?><div class="admin-state-card <?= e($admin_repository->status()) ?>"><span class="state-dot"></span><div><strong>وضعیت گزارش رویدادها</strong><p><?= e($admin_error) ?></p></div></div><?php endif; ?>

<section class="admin-panel admin-table-panel">
    <div class="table-toolbar">
        <div>
            <p class="admin-eyebrow">گزارشات امنیت</p>
            <h2>لیست لاگ‌های سیستمی</h2>
        </div>
        <span class="toolbar-meta">نمایش <?= e(count($audit_logs['items'] ?? array())) ?> از <?= e($audit_logs['total'] ?? 0) ?> مورد</span>
    </div>

    <?php if (empty($audit_logs['items'])): ?>
        <div class="empty-state table-empty">
            <span>📋</span>
            <strong>هیچ رویدادی ثبت نشده است</strong>
            <p>لاگ‌های مربوط به عملیات کاربران پس از اجرای اقدامات در اینجا ثبت خواهند شد.</p>
        </div>
    <?php else: ?>
        <div class="admin-table-scroll">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>شناسه لاگ</th>
                        <th>کاربر</th>
                        <th>عملیات</th>
                        <th>بخش / موجودیت</th>
                        <th>شناسه موجودیت</th>
                        <th>هش IP</th>
                        <th>تاریخ و زمان</th>
                        <th>جزئیات اضافی (Metadata)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($audit_logs['items'] as $log): ?>
                        <tr>
                            <td class="mono-cell">#<?= e($log['id']) ?></td>
                            <td>
                                <?php if (!empty($log['user_name'])): ?>
                                    <strong><?= e($log['user_name']) ?></strong>
                                    <small><?= e($log['user_email']) ?></small>
                                <?php else: ?>
                                    <span style="color: #999;">سیستم خودکار / نامشخص</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="tiny-tag <?= in_array($log['action'], array('delete', 'deactivate'), true) ? 'danger' : 'gold' ?>">
                                    <?= e($log['action']) ?>
                                </span>
                            </td>
                            <td><?= e($log['entity_type'] ?: '—') ?></td>
                            <td class="mono-cell"><?= e($log['entity_id'] ?: '—') ?></td>
                            <td class="mono-cell" style="font-size: 0.8rem; opacity: 0.7;" title="<?= e($log['ip_hash']) ?>">
                                <?= e(substr($log['ip_hash'], 0, 10)) ?>...
                            </td>
                            <td>
                                <time datetime="<?= e($log['created_at']) ?>">
                                    <?= e(date('Y/m/d H:i:s', strtotime($log['created_at']))) ?>
                                </time>
                            </td>
                            <td>
                                <?php if (!empty($log['metadata_json'])): ?>
                                    <pre style="margin: 0; padding: 0.2rem; font-size: 0.8rem; background: #faf5eb; border: 1px solid #ebdcb2; border-radius: 4px; max-width: 250px; overflow-x: auto; font-family: monospace; direction: ltr; text-align: left;"><?= e($log['metadata_json']) ?></pre>
                                <?php else: ?>
                                    <small style="color: #999;">—</small>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if (($audit_logs['pages'] ?? 1) > 1): ?>
        <nav class="admin-pagination" aria-label="صفحات گزارش رویدادها">
            <?php for ($page = 1; $page <= (int) $audit_logs['pages']; $page++): ?>
                <a class="<?= $page === (int) $audit_logs['page'] ? 'is-current' : '' ?>" href="<?= e(admin_url('/audit?' . http_build_query(array('page' => $page)))) ?>">
                    <?= e($page) ?>
                </a>
            <?php endfor; ?>
        </nav>
    <?php endif; ?>
</section>
