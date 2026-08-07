<?php
declare(strict_types=1);

/** Returns the signed-in user or null for a guest. */
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

/** Refreshes the session identity from the database. */
function refresh_user(PDO $pdo, int $userId): void
{
    $statement = $pdo->prepare('SELECT id, role, name, email, phone, status FROM users WHERE id = ?');
    $statement->execute([$userId]);
    $user = $statement->fetch();
    if ($user) {
        $_SESSION['user'] = $user;
    }
}

/** Requires an authenticated account. */
function require_login(): array
{
    $user = current_user();
    if (!$user) {
        flash('warning', 'Please sign in to continue.');
        redirect('login');
    }
    if ($user['status'] !== 'active') {
        $_SESSION = [];
        flash('error', 'This account is not active.');
        redirect('login');
    }
    return $user;
}

/** Requires one of the supplied roles and blocks cross-role access. */
function require_role(string ...$roles): array
{
    $user = require_login();
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        render('errors/403', ['title' => 'Access denied']);
    }
    return $user;
}

/** Sends each role to its required dashboard. */
function dashboard_route(string $role): string
{
    return match ($role) {
        'admin' => 'admin.dashboard',
        'parent' => 'parent.dashboard',
        'hospital' => 'hospital.dashboard',
        default => 'home',
    };
}
