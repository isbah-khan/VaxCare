<div class="page-heading">
    <div><span class="eyebrow">Administrator</span><h1>System overview</h1><p>Today’s children, requests, hospitals, and completed vaccinations at a glance.</p></div>
    <a class="button" href="<?= e(url('admin.requests')) ?>">Review requests</a>
</div>
<section class="stat-grid" aria-label="System totals">
    <article class="stat-card mint"><span>Registered children</span><strong><?= number_format($stats['children']) ?></strong><a href="<?= e(url('admin.children')) ?>">View details →</a></article>
    <article class="stat-card coral"><span>Pending requests</span><strong><?= number_format($stats['pending']) ?></strong><a href="<?= e(url('admin.requests')) ?>">Review queue →</a></article>
    <article class="stat-card blue"><span>Active hospitals</span><strong><?= number_format($stats['hospitals']) ?></strong><a href="<?= e(url('admin.hospitals')) ?>">Manage list →</a></article>
    <article class="stat-card yellow"><span>Vaccinations recorded</span><strong><?= number_format($stats['completed']) ?></strong><a href="<?= e(url('admin.reports')) ?>">Open reports →</a></article>
</section>
<section class="panel">
    <div class="panel-heading"><div><span class="eyebrow">Next in line</span><h2>Upcoming vaccination dates</h2></div><a href="<?= e(url('admin.bookings')) ?>">All bookings</a></div>
    <?php if (!$upcoming): ?><div class="empty-inline"><p>No upcoming pending or approved appointments.</p></div><?php else: ?>
    <div class="timeline-list">
        <?php foreach ($upcoming as $item): ?>
            <article><time datetime="<?= e($item['appointment_date']) ?>"><strong><?= e(display_date($item['appointment_date'], 'd')) ?></strong><span><?= e(display_date($item['appointment_date'], 'M')) ?></span></time><div><h3><?= e($item['child_name']) ?> · <?= e($item['vaccine_name']) ?></h3><p><?= e($item['dose_label']) ?> at <?= e($item['hospital_name']) ?>, <?= e(display_date($item['appointment_time'], 'g:i A')) ?></p></div><span class="status status-<?= e($item['status']) ?>"><?= e(status_label($item['status'])) ?></span></article>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>
