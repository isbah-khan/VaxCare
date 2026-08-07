<div class="page-heading"><div><span class="eyebrow">Admin · Approval desk</span><h1>Requests from parents</h1><p>Approve or reject appointment requests before hospitals receive them.</p></div></div>
<nav class="filter-tabs" aria-label="Request status">
    <?php foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All'] as $key => $label): ?><a href="<?= e(url('admin.requests', ['status' => $key])) ?>" <?= $status === $key ? 'aria-current="page"' : '' ?>><?= e($label) ?></a><?php endforeach; ?>
</nav>
<section class="request-grid">
<?php if (!$requests): ?><div class="empty-state compact"><span class="empty-icon">✓</span><h2>No <?= e($status) ?> requests</h2><p>This queue is clear.</p></div><?php endif; ?>
<?php foreach ($requests as $request): ?><article class="request-card">
    <div class="request-top"><span class="status status-<?= e($request['status']) ?>"><?= e(status_label($request['status'])) ?></span><time><?= e(display_date($request['appointment_date'])) ?> · <?= e(display_date($request['appointment_time'], 'g:i A')) ?></time></div>
    <h2><?= e($request['child_name']) ?></h2><p class="request-vaccine"><?= e($request['vaccine_name']) ?> · <?= e($request['dose_label']) ?></p>
    <dl><div><dt>Parent</dt><dd><?= e($request['parent_name']) ?><small><?= e($request['parent_email']) ?></small></dd></div><div><dt>Hospital</dt><dd><?= e($request['hospital_name']) ?></dd></div><div><dt>Reason</dt><dd><?= e($request['reason'] ?: 'Routine vaccination') ?></dd></div></dl>
    <?php if ($request['status'] === 'pending'): ?><form method="post" action="<?= e(url('admin.request.update')) ?>">
        <?= csrf_field() ?><input type="hidden" name="appointment_id" value="<?= (int) $request['id'] ?>">
        <label>Decision note <small>Optional</small><textarea name="admin_notes" maxlength="500" rows="2" placeholder="Add guidance for the parent or hospital"></textarea></label>
        <div class="button-row"><button class="button" name="decision" value="approved" type="submit">Approve</button><button class="button button-danger" name="decision" value="rejected" type="submit">Reject</button></div>
    </form><?php elseif ($request['admin_notes']): ?><div class="note"><strong>Admin note</strong><p><?= e($request['admin_notes']) ?></p></div><?php endif; ?>
</article><?php endforeach; ?>
</section>
