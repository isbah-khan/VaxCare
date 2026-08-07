<?php
declare(strict_types=1);

/** Shows the public landing page or forwards signed-in users. */
function auth_home(PDO $pdo): never
{
    if ($user = current_user()) {
        redirect(dashboard_route($user['role']));
    }
    render('home', ['title' => 'Vaccination made clear']);
}

/** Authenticates all three required account roles. */
function auth_login(PDO $pdo): never
{
    if ($user = current_user()) {
        redirect(dashboard_route($user['role']));
    }

    $errors = [];
    $email = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();
        $email = strtolower(input('email'));
        $password = input('password');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
            $errors[] = 'Enter a valid email address and password.';
        } else {
            $statement = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
            $statement->execute([$email]);
            $account = $statement->fetch();
            if (!$account || !password_verify($password, $account['password_hash'])) {
                $errors[] = 'The email or password is incorrect.';
            } elseif ($account['status'] !== 'active') {
                $errors[] = 'This account is inactive. Contact the administrator.';
            } else {
                session_regenerate_id(true);
                refresh_user($pdo, (int) $account['id']);
                flash('success', 'Welcome back, ' . $account['name'] . '.');
                redirect(dashboard_route($account['role']));
            }
        }
    }

    render('auth/login', ['title' => 'Sign in', 'errors' => $errors, 'email' => $email]);
}

/** Registers a parent and its address profile in one transaction. */
function auth_register_parent(PDO $pdo): never
{
    if (current_user()) {
        redirect(dashboard_route(current_user()['role']));
    }

    $errors = [];
    $values = ['name' => '', 'email' => '', 'phone' => '', 'address' => ''];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();
        foreach (array_keys($values) as $key) {
            $values[$key] = input($key);
        }
        $values['email'] = strtolower($values['email']);
        $password = input('password');
        $confirmation = input('password_confirmation');

        if (mb_strlen($values['name']) < 2) $errors[] = 'Name must contain at least 2 characters.';
        if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
        if (mb_strlen($values['phone']) < 7) $errors[] = 'Enter a valid phone number.';
        if (mb_strlen($values['address']) < 5) $errors[] = 'Enter a complete address.';
        if (strlen($password) < 8) $errors[] = 'Password must contain at least 8 characters.';
        if ($password !== $confirmation) $errors[] = 'Password confirmation does not match.';

        if (!$errors) {
            try {
                $pdo->beginTransaction();
                $statement = $pdo->prepare(
                    'INSERT INTO users (role, name, email, password_hash, phone) VALUES ("parent", ?, ?, ?, ?)'
                );
                $statement->execute([$values['name'], $values['email'], password_hash($password, PASSWORD_DEFAULT), $values['phone']]);
                $userId = (int) $pdo->lastInsertId();
                $pdo->prepare('INSERT INTO parent_profiles (user_id, address) VALUES (?, ?)')->execute([$userId, $values['address']]);
                $pdo->commit();
                session_regenerate_id(true);
                refresh_user($pdo, $userId);
                flash('success', 'Your parent account is ready. Add your child to begin.');
                redirect('parent.dashboard');
            } catch (PDOException $exception) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $errors[] = $exception->getCode() === '23000'
                    ? 'An account already uses this email address.'
                    : 'Registration could not be completed. Please try again.';
            }
        }
    }

    render('auth/register_parent', ['title' => 'Parent registration', 'errors' => $errors, 'values' => $values]);
}

/** Registers a hospital account and location details atomically. */
function auth_register_hospital(PDO $pdo): never
{
    if (current_user()) {
        redirect(dashboard_route(current_user()['role']));
    }

    $errors = [];
    $values = ['name' => '', 'email' => '', 'phone' => '', 'address' => '', 'city' => '', 'location_details' => ''];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();
        foreach (array_keys($values) as $key) {
            $values[$key] = input($key);
        }
        $values['email'] = strtolower($values['email']);
        $password = input('password');
        $confirmation = input('password_confirmation');

        if (mb_strlen($values['name']) < 3) $errors[] = 'Hospital name must contain at least 3 characters.';
        if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
        if (mb_strlen($values['phone']) < 7) $errors[] = 'Enter a valid phone number.';
        if (mb_strlen($values['address']) < 5 || mb_strlen($values['city']) < 2) $errors[] = 'Enter a complete address and city.';
        if (strlen($password) < 8) $errors[] = 'Password must contain at least 8 characters.';
        if ($password !== $confirmation) $errors[] = 'Password confirmation does not match.';

        if (!$errors) {
            try {
                $pdo->beginTransaction();
                $statement = $pdo->prepare(
                    'INSERT INTO users (role, name, email, password_hash, phone) VALUES ("hospital", ?, ?, ?, ?)'
                );
                $statement->execute([$values['name'], $values['email'], password_hash($password, PASSWORD_DEFAULT), $values['phone']]);
                $userId = (int) $pdo->lastInsertId();
                $pdo->prepare(
                    'INSERT INTO hospitals (user_id, address, city, location_details) VALUES (?, ?, ?, ?)'
                )->execute([$userId, $values['address'], $values['city'], $values['location_details'] ?: null]);
                $hospitalId = (int) $pdo->lastInsertId();
                $pdo->prepare(
                    'INSERT INTO hospital_vaccines (hospital_id, vaccine_id, is_available, stock_quantity)
                     SELECT ?, id, 0, 0 FROM vaccines WHERE is_active = 1'
                )->execute([$hospitalId]);
                $pdo->commit();
                session_regenerate_id(true);
                refresh_user($pdo, $userId);
                flash('success', 'Hospital registration complete. Update vaccine availability next.');
                redirect('hospital.inventory');
            } catch (PDOException $exception) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $errors[] = $exception->getCode() === '23000'
                    ? 'An account already uses this email address.'
                    : 'Registration could not be completed. Please try again.';
            }
        }
    }

    render('auth/register_hospital', ['title' => 'Hospital registration', 'errors' => $errors, 'values' => $values]);
}

/** Ends the authenticated session through a CSRF-protected POST request. */
function auth_logout(PDO $pdo): never
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Method not allowed.');
    }
    verify_csrf();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    session_start();
    flash('success', 'You have been signed out safely.');
    redirect('login');
}
