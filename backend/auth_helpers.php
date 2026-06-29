<?php

function vcs_supported_roles(): array
{
    return [
        'owner' => 'Vehicle Owner',
        'officer' => 'Traffic Officer',
        'admin' => 'System Administrator',
    ];
}

function vcs_normalize_role(?string $role): string
{
    return strtolower(trim((string) $role));
}

function vcs_password_rules(): array
{
    return [
        'min_length' => 10,
        'messages' => [
            'At least 10 characters long.',
            'Contains an uppercase letter.',
            'Contains a lowercase letter.',
            'Contains a number.',
            'Contains a special character.',
        ],
    ];
}

function vcs_validate_password_strength(string $password): array
{
    $errors = [];
    $rules = vcs_password_rules();

    if (strlen($password) < $rules['min_length']) {
        $errors[] = 'Password must be at least ' . $rules['min_length'] . ' characters long.';
    }

    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must include at least one uppercase letter.';
    }

    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must include at least one lowercase letter.';
    }

    if (!preg_match('/\d/', $password)) {
        $errors[] = 'Password must include at least one number.';
    }

    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = 'Password must include at least one special character.';
    }

    return $errors;
}

function vcs_dashboard_url_for_role(string $role): string
{
    return match (vcs_normalize_role($role)) {
        'admin' => '/views/admin_panel.php',
        'officer' => '/views/officer_dashboard.php',
        default => '/views/citizen_portal.php',
    };
}

function vcs_has_table(PDO $pdo, string $table): bool
{
    static $cache = [];

    if (!array_key_exists($table, $cache)) {
        try {
            $stmt = $pdo->prepare('SHOW TABLES LIKE :table_name');
            $stmt->execute(['table_name' => $table]);
            $cache[$table] = (bool) $stmt->fetchColumn();
        } catch (PDOException $e) {
            $cache[$table] = false;
        }
    }

    return $cache[$table];
}

function vcs_has_column(PDO $pdo, string $table, string $column): bool
{
    static $cache = [];
    $key = $table . '.' . $column;

    if (!array_key_exists($key, $cache)) {
        try {
            $stmt = $pdo->prepare(
                'SELECT COUNT(*)
                 FROM information_schema.columns
                 WHERE table_schema = DATABASE()
                   AND table_name = :table_name
                   AND column_name = :column_name'
            );
            $stmt->execute([
                'table_name' => $table,
                'column_name' => $column,
            ]);
            $cache[$key] = ((int) $stmt->fetchColumn()) > 0;
        } catch (PDOException $e) {
            $cache[$key] = false;
        }
    }

    return $cache[$key];
}

function vcs_get_user_roles(PDO $pdo, int $userId, ?string $fallbackRole = null): array
{
    $roles = [];

    if (vcs_has_table($pdo, 'user_roles')) {
        try {
            $stmt = $pdo->prepare(
                'SELECT role
                 FROM user_roles
                 WHERE user_id = :user_id
                 ORDER BY is_primary DESC, role ASC'
            );
            $stmt->execute(['user_id' => $userId]);
            $roles = array_values(array_filter(array_map(
                static fn ($role) => vcs_normalize_role($role['role'] ?? ''),
                $stmt->fetchAll()
            )));
        } catch (PDOException $e) {
            $roles = [];
        }
    }

    if (!$roles && $fallbackRole !== null && $fallbackRole !== '') {
        $roles = [vcs_normalize_role($fallbackRole)];
    }

    return array_values(array_unique($roles));
}

function vcs_available_roles_for_user(PDO $pdo, array $user): array
{
    return vcs_get_user_roles($pdo, (int) ($user['user_id'] ?? 0), $user['role'] ?? null);
}

function vcs_store_auth_session(array $user, string $role, array $roles = []): void
{
    session_regenerate_id(true);

    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['role'] = vcs_normalize_role($role);
    $_SESSION['roles'] = array_values(array_unique(array_map('vcs_normalize_role', $roles)));
    $_SESSION['primary_role'] = vcs_normalize_role($user['role'] ?? $role);
}

function vcs_password_reset_expiry(): string
{
    return (new DateTimeImmutable('+1 hour'))->format('Y-m-d H:i:s');
}
