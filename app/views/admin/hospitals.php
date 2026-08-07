<div class="page-heading"><div><span class="eyebrow">Admin · Care network</span><h1>Hospital management</h1><p>Add, update, delete, and view registered hospital details.</p></div><button class="button" type="button" data-dialog-open="hospital-form"><?= $editing ? 'Edit hospital' : 'Add hospital' ?></button></div>
<section class="card-grid hospital-grid">
<?php foreach ($hospitals as $hospital): ?><article class="info-card <?= $hospital['status'] === 'inactive' ? 'is-muted' : '' ?>">
    <div class="card-top"><span class="initial-badge"><?= e(mb_strtoupper(mb_substr($hospital['name'], 0, 1))) ?></span><span class="status status-<?= e($hospital['status']) ?>"><?= e(status_label($hospital['status'])) ?></span></div>
    <h2><?= e($hospital['name']) ?></h2><p><?= e($hospital['address']) ?>, <?= e($hospital['city']) ?></p>
    <?php if ($hospital['location_details']): ?><p class="muted"><?= e($hospital['location_details']) ?></p><?php endif; ?>
    <dl class="mini-stats"><div><dt>Available</dt><dd><?= (int) $hospital['available_count'] ?> vaccines</dd></div><div><dt>Bookings</dt><dd><?= (int) $hospital['booking_count'] ?></dd></div></dl>
    <p class="contact-line"><?= e($hospital['email']) ?><br><?= e($hospital['phone'] ?: 'No phone') ?></p>
    <div class="card-actions"><a class="button button-secondary button-small" href="<?= e(url('admin.hospitals', ['edit' => $hospital['id']])) ?>">Edit</a><form method="post" action="<?= e(url('admin.hospital.delete')) ?>" data-confirm="Remove this hospital? Existing booking history will be preserved."><?= csrf_field() ?><input type="hidden" name="hospital_id" value="<?= (int) $hospital['id'] ?>"><button class="button button-quiet button-small" type="submit">Delete</button></form></div>
</article><?php endforeach; ?>
<?php if (!$hospitals): ?><div class="empty-state compact"><h2>No hospitals yet</h2><p>Add the first hospital to begin receiving bookings.</p></div><?php endif; ?>
</section>
<dialog id="hospital-form" class="dialog" <?= $editing ? 'data-open-on-load' : '' ?>>
    <form method="post" action="<?= e(url('admin.hospital.save')) ?>" data-validate>
        <?= csrf_field() ?><input type="hidden" name="hospital_id" value="<?= (int) ($editing['id'] ?? 0) ?>">
        <div class="dialog-heading"><div><span class="eyebrow"><?= $editing ? 'Update details' : 'New care provider' ?></span><h2><?= $editing ? 'Edit hospital' : 'Add hospital' ?></h2></div><button type="button" data-dialog-close aria-label="Close">×</button></div>
        <div class="form-grid">
            <label>Hospital name<input name="name" value="<?= e($editing['name'] ?? '') ?>" minlength="3" maxlength="100" required></label>
            <label>Email<input type="email" name="email" value="<?= e($editing['email'] ?? '') ?>" maxlength="190" required></label>
            <label>Phone<input name="phone" value="<?= e($editing['phone'] ?? '') ?>" maxlength="30"></label>
            <label>City<input name="city" value="<?= e($editing['city'] ?? '') ?>" minlength="2" maxlength="100" required></label>
            <label class="span-2">Address<input name="address" value="<?= e($editing['address'] ?? '') ?>" minlength="5" maxlength="255" required></label>
            <label class="span-2">Location details<input name="location_details" value="<?= e($editing['location_details'] ?? '') ?>" maxlength="255"></label>
            <label>Password <small><?= $editing ? 'Leave blank to keep current password' : 'Minimum 8 characters' ?></small><input type="password" name="password" minlength="8" <?= $editing ? '' : 'required' ?>></label>
            <label>Status<select name="status"><option value="active" <?= ($editing['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option><option value="inactive" <?= ($editing['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option></select></label>
        </div><button class="button button-full" type="submit"><?= $editing ? 'Save changes' : 'Add hospital' ?></button>
    </form>
</dialog>
