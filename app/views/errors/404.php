<section class="empty-state">
    <span class="empty-icon" aria-hidden="true">?</span>
    <h1>We could not find that page</h1>
    <p>The link may be old or mistyped.</p>
    <a class="button" href="<?= e(url(current_user() ? dashboard_route(current_user()['role']) : 'home')) ?>">Go home</a>
</section>
