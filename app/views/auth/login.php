<section class="auth-shell">
    <div class="auth-aside">
        <span class="eyebrow">Welcome back</span>
        <h1>Your next clear step starts here.</h1>
        <p>One sign-in works for parent, hospital, and administrator accounts.</p>
        <div class="mini-path" aria-hidden="true"><span></span><span></span><span></span></div>
    </div>
    <div class="form-card">
        <h2>Sign in</h2>
        <p class="muted">Use the email and password for your account.</p>
        <?php if ($errors): ?><div class="form-errors" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
        <form method="post" action="<?= e(url('login')) ?>" data-validate>
            <?= csrf_field() ?>
            <label>Email address<input type="email" name="email" value="<?= e($email) ?>" autocomplete="email" required></label>
            <label>Password<span class="password-field"><input type="password" name="password" autocomplete="current-password" required><button type="button" data-password-toggle>Show</button></span></label>
            <button class="button button-full" type="submit">Sign in</button>
        </form>
        <div class="form-foot"><a href="<?= e(url('register.parent')) ?>">Create parent account</a><a href="<?= e(url('register.hospital')) ?>">Register hospital</a></div>
    </div>
</section>
