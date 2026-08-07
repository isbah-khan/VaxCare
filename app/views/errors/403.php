<section class="empty-state">
    <span class="empty-icon" aria-hidden="true">!</span>
    <h1>That area belongs to another role</h1>
    <p>Your account is safe, but it does not have permission to view this page.</p>
    <a class="button" href="<?= e(url(dashboard_route(current_user()['role']))) ?>">Return to dashboard</a>
</section>
