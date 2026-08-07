<div class="page-heading"><div><span class="eyebrow">Admin · Child records</span><h1>All child details</h1><p>View every registered child with parent contact and vaccination progress.</p></div></div>
<form class="filter-bar" method="get" action="index.php">
    <input type="hidden" name="route" value="admin.children">
    <label class="search-field"><span class="sr-only">Search children</span><input name="search" value="<?= e($search) ?>" placeholder="Search child, parent, or email"><button type="submit">Search</button></label>
    <?php if ($search): ?><a href="<?= e(url('admin.children')) ?>">Clear</a><?php endif; ?>
</form>
<section class="panel table-panel">
    <?php if (!$children): ?><div class="empty-inline"><h2>No matching children</h2><p>Try a different name or email.</p></div><?php else: ?>
    <div class="table-scroll"><table><thead><tr><th>Child</th><th>Age / profile</th><th>Parent contact</th><th>Vaccination progress</th></tr></thead><tbody>
    <?php foreach ($children as $child): ?><tr>
        <td><strong><?= e($child['name']) ?></strong><small>Born <?= e(display_date($child['date_of_birth'])) ?></small></td>
        <td><?= e(age_label($child['date_of_birth'])) ?><small><?= e(status_label($child['gender'])) ?><?= $child['blood_group'] ? ' · ' . e($child['blood_group']) : '' ?></small></td>
        <td><?= e($child['parent_name']) ?><small><?= e($child['parent_email']) ?><?= $child['parent_phone'] ? ' · ' . e($child['parent_phone']) : '' ?></small></td>
        <td><span class="dose-count"><?= (int) $child['completed_doses'] ?> dose<?= (int) $child['completed_doses'] === 1 ? '' : 's' ?></span><small><?= e($child['medical_notes'] ?: 'No medical notes') ?></small></td>
    </tr><?php endforeach; ?>
    </tbody></table></div><?php endif; ?>
</section>
