<section class="auth-shell">
    <div class="auth-aside"><span class="eyebrow">Hospital registration</span><h1>Join the care network.</h1><p>Register the hospital name, address, city, and location details required by the project.</p></div>
    <div class="form-card form-card-wide">
        <h2>Register hospital</h2>
        <?php if ($errors): ?><div class="form-errors" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
        <form method="post" action="<?= e(url('register.hospital')) ?>" data-validate>
            <?= csrf_field() ?>
            <div class="form-grid">
                <label>Hospital name<input name="name" value="<?= e($values['name']) ?>" minlength="3" maxlength="100" autocomplete="organization" required></label>
                <label>Email address<input type="email" name="email" value="<?= e($values['email']) ?>" maxlength="190" autocomplete="email" required></label>
                <label>Phone number<input type="tel" name="phone" value="<?= e($values['phone']) ?>" minlength="7" maxlength="30" autocomplete="tel" required></label>
                <label>City<input name="city" value="<?= e($values['city']) ?>" minlength="2" maxlength="100" autocomplete="address-level2" required></label>
                <label class="span-2">Address<input name="address" value="<?= e($values['address']) ?>" minlength="5" maxlength="255" autocomplete="street-address" required></label>
                <label class="span-2">Location details <small>Optional landmark or directions</small><input name="location_details" value="<?= e($values['location_details']) ?>" maxlength="255"></label>
                <label>Password<span class="password-field"><input type="password" name="password" minlength="8" autocomplete="new-password" required><button type="button" data-password-toggle>Show</button></span></label>
                <label>Confirm password<input type="password" name="password_confirmation" minlength="8" autocomplete="new-password" required></label>
            </div>
            <button class="button button-full" type="submit">Register hospital</button>
        </form>
        <div class="form-foot"><span>Already registered?</span><a href="<?= e(url('login')) ?>">Sign in</a></div>
    </div>
</section>
