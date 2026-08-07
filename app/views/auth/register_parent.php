<section class="auth-shell">
    <div class="auth-aside"><span class="eyebrow">Parent registration</span><h1>Start with the person who knows them best.</h1><p>Create your secure account first. Child details are added after registration.</p></div>
    <div class="form-card form-card-wide">
        <h2>Create parent account</h2>
        <?php if ($errors): ?><div class="form-errors" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
        <form method="post" action="<?= e(url('register.parent')) ?>" data-validate>
            <?= csrf_field() ?>
            <div class="form-grid">
                <label>Full name<input name="name" value="<?= e($values['name']) ?>" minlength="2" maxlength="100" autocomplete="name" required></label>
                <label>Email address<input type="email" name="email" value="<?= e($values['email']) ?>" maxlength="190" autocomplete="email" required></label>
                <label>Phone number<input type="tel" name="phone" value="<?= e($values['phone']) ?>" minlength="7" maxlength="30" autocomplete="tel" required></label>
                <label>Home address<input name="address" value="<?= e($values['address']) ?>" minlength="5" maxlength="255" autocomplete="street-address" required></label>
                <label>Password<span class="password-field"><input type="password" name="password" minlength="8" autocomplete="new-password" required><button type="button" data-password-toggle>Show</button></span></label>
                <label>Confirm password<input type="password" name="password_confirmation" minlength="8" autocomplete="new-password" required></label>
            </div>
            <button class="button button-full" type="submit">Create account</button>
        </form>
        <div class="form-foot"><span>Already registered?</span><a href="<?= e(url('login')) ?>">Sign in</a></div>
    </div>
</section>
