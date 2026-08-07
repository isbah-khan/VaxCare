<section class="hero">
    <div class="hero-copy">
        <span class="eyebrow">One clear path to every dose</span>
        <h1>Keep childhood vaccination on track.</h1>
        <p>Parents, hospitals, and administrators share one simple place for schedules, appointment approval, vaccine availability, and vaccination reports.</p>
        <div class="button-row">
            <a class="button" href="<?= e(url('register.parent')) ?>">Register as parent</a>
            <a class="button button-secondary" href="<?= e(url('login')) ?>">Sign in</a>
        </div>
        <a class="text-link" href="<?= e(url('register.hospital')) ?>">Register a hospital →</a>
    </div>
    <div class="hero-visual" aria-label="A simple vaccination journey">
        <div class="orbit orbit-one"></div><div class="orbit orbit-two"></div>
        <div class="journey-card card-parent"><span>1</span><strong>Add child</strong><small>Keep details together</small></div>
        <div class="journey-card card-book"><span>2</span><strong>Book a date</strong><small>Choose an available hospital</small></div>
        <div class="journey-card card-done"><span>3</span><strong>Stay protected</strong><small>See every completed dose</small></div>
    </div>
</section>

<section class="public-section" aria-labelledby="how-title">
    <div class="section-heading"><span class="eyebrow">How it works</span><h2 id="how-title">Three roles, one understandable flow</h2></div>
    <div class="feature-grid">
        <article><span class="feature-number">01</span><h3>Parents</h3><p>Add child details, see due dates, search hospitals, request appointments, and review vaccination history.</p></article>
        <article><span class="feature-number">02</span><h3>Administrators</h3><p>Review requests, maintain hospitals, monitor children and bookings, and prepare date-wise reports.</p></article>
        <article><span class="feature-number">03</span><h3>Hospitals</h3><p>Publish vaccine availability, receive approved appointments, and record vaccinated or not-vaccinated outcomes.</p></article>
    </div>
</section>
