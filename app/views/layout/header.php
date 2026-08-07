<?php
/** Shared document header and role-aware navigation. */
$user = current_user();
$role = $user['role'] ?? null;
$nav = match ($role) {
    'admin' => [
        'admin.dashboard' => 'Overview', 'admin.children' => 'Children',
        'admin.requests' => 'Requests', 'admin.hospitals' => 'Hospitals',
        'admin.vaccines' => 'Vaccines', 'admin.bookings' => 'Bookings',
        'admin.reports' => 'Reports',
    ],
    'parent' => [
        'parent.dashboard' => 'Dashboard', 'parent.children' => 'My children',
        'parent.hospitals' => 'Book hospital', 'parent.bookings' => 'My bookings',
        'parent.report' => 'Vaccination report', 'parent.profile' => 'My profile',
    ],
    'hospital' => [
        'hospital.dashboard' => 'Dashboard', 'hospital.appointments' => 'Appointments',
        'hospital.inventory' => 'Vaccine status',
    ],
    default => [],
};
$activeRoute = (string) ($_GET['route'] ?? 'home');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Vaccination management for parents, hospitals, and administrators.">
    <title><?= e($pageTitle) ?> · <?= e($config['app_name']) ?></title>
    <link rel="stylesheet" href="assets/css/app.css">
    <script src="assets/js/app.js" defer></script>
</head>
<body class="role-<?= e($role ?? 'guest') ?>">
<a class="skip-link" href="#main-content">Skip to content</a>
<header class="site-header">
    <a class="brand" href="<?= e(url($user ? dashboard_route($role) : 'home')) ?>" aria-label="VaxCare home">
        <span class="brand-mark" aria-hidden="true"><span></span><span></span><span></span></span>
        <span>VaxCare<small>vaccination made clear</small></span>
    </a>
    <?php if ($user): ?>
        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="primary-nav">Menu</button>
        <nav id="primary-nav" class="primary-nav" aria-label="Primary navigation">
            <?php foreach ($nav as $navRoute => $label): ?>
                <a href="<?= e(url($navRoute)) ?>" <?= $activeRoute === $navRoute ? 'aria-current="page"' : '' ?>><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="account-chip">
            <span><strong><?= e($user['name']) ?></strong><small><?= e(status_label($role)) ?></small></span>
            <form method="post" action="<?= e(url('logout')) ?>">
                <?= csrf_field() ?>
                <button class="button button-quiet button-small" type="submit">Sign out</button>
            </form>
        </div>
    <?php else: ?>
        <nav class="guest-nav" aria-label="Guest navigation">
            <a href="<?= e(url('login')) ?>">Sign in</a>
            <a class="button button-small" href="<?= e(url('register.parent')) ?>">Parent registration</a>
        </nav>
    <?php endif; ?>
</header>

<?php if ($user): ?><div class="app-frame"><?php endif; ?>
<main id="main-content" class="<?= $user ? 'app-main' : 'public-main' ?>">
    <?php foreach ($flashes as $flash): ?>
        <div class="toast toast-<?= e($flash['type']) ?>" role="status">
            <span><?= e($flash['message']) ?></span>
            <button type="button" aria-label="Dismiss message">×</button>
        </div>
    <?php endforeach; ?>
