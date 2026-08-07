<?php
declare(strict_types=1);

/** Escapes user-controlled output for safe HTML rendering. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Builds an application URL that works in both XAMPP and PHP's local server. */
function url(string $route = 'home', array $query = []): string
{
    global $config;
    $query = array_merge(['route' => $route], $query);
    return ($config['base_url'] ?: 'index.php') . '?' . http_build_query($query);
}

/** Redirects to a named application route. */
function redirect(string $route, array $query = []): never
{
    header('Location: ' . url($route, $query));
    exit;
}

/** Stores a one-request notification in the session. */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/** Retrieves and clears all pending notifications. */
function pull_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/** Returns a stable CSRF token for the current browser session. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Prints the hidden CSRF field used by every modifying form. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/** Rejects forged POST requests before any data is changed. */
function verify_csrf(): void
{
    $provided = (string) ($_POST['csrf_token'] ?? '');
    if (!hash_equals(csrf_token(), $provided)) {
        http_response_code(419);
        exit('Your form session expired. Go back, refresh the page, and try again.');
    }
}

/** Reads and trims a posted text value. */
function input(string $key, string $default = ''): string
{
    return trim((string) ($_POST[$key] ?? $default));
}

/** Formats database dates for friendly display. */
function display_date(?string $date, string $format = 'd M Y'): string
{
    if (!$date) {
        return 'Not set';
    }
    return (new DateTimeImmutable($date))->format($format);
}

/** Converts a status value to readable title case. */
function status_label(string $status): string
{
    return ucwords(str_replace('_', ' ', $status));
}

/** Calculates age using the child's date of birth. */
function age_label(string $dateOfBirth): string
{
    $birth = new DateTimeImmutable($dateOfBirth);
    $now = new DateTimeImmutable('today');
    $difference = $birth->diff($now);
    if ($difference->y > 0) {
        return $difference->y . ' yr' . ($difference->y === 1 ? '' : 's');
    }
    return $difference->m . ' mo' . ($difference->m === 1 ? '' : 's');
}

/** Renders a view inside the shared, responsive application shell. */
function render(string $view, array $data = []): never
{
    global $config;
    $viewFile = __DIR__ . '/views/' . $view . '.php';
    if (!is_file($viewFile)) {
        http_response_code(500);
        exit('The requested view is unavailable.');
    }

    extract($data, EXTR_SKIP);
    $pageTitle = $data['title'] ?? $config['app_name'];
    $flashes = pull_flashes();
    require __DIR__ . '/views/layout/header.php';
    require $viewFile;
    require __DIR__ . '/views/layout/footer.php';
    exit;
}
