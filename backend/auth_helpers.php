<?php

function vcs_supported_roles(): array
{
    return [
        'owner' => 'Vehicle Owner',
        'officer' => 'Traffic Officer',
        'admin' => 'System Administrator',
    ];
}

function vcs_admin_role_labels(): array
{
    return vcs_supported_roles();
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

function vcs_vehicle_vin(array $vehicle): string
{
    if (!empty($vehicle['chassis_number'])) {
        return (string) $vehicle['chassis_number'];
    }

    $plate = strtoupper(preg_replace('/[^A-Z0-9]/i', '', (string) ($vehicle['plate_number'] ?? 'VCS')));
    $year = preg_replace('/\D/', '', (string) ($vehicle['year'] ?? '2016'));
    $id = str_pad((string) (int) ($vehicle['vehicle_id'] ?? 0), 6, '0', STR_PAD_LEFT);

    return 'KE' . substr(str_pad($plate, 10, 'X'), 0, 10) . substr($year, -2) . $id;
}

function vcs_vehicle_insurance_type(array $vehicle): string
{
    if (!empty($vehicle['insurance_type'])) {
        return (string) $vehicle['insurance_type'];
    }

    return ((int) ($vehicle['vehicle_id'] ?? 0) % 2 === 0) ? 'Comprehensive' : 'Third Party';
}

function vcs_vehicle_payment_period(array $vehicle): string
{
    if (!empty($vehicle['payment_period'])) {
        return (string) $vehicle['payment_period'];
    }

    return 'Annual';
}

function vcs_vehicle_licence_class(array $vehicle): string
{
    if (!empty($vehicle['driver_licence_class'])) {
        return (string) $vehicle['driver_licence_class'];
    }

    return ((int) ($vehicle['year'] ?? 0) >= 2020) ? 'B' : 'C1';
}

function vcs_vehicle_service_interval_km(array $vehicle): int
{
    if (!empty($vehicle['service_interval_km'])) {
        return (int) $vehicle['service_interval_km'];
    }

    return 5000;
}

function vcs_vehicle_odometer_km(array $vehicle): int
{
    if (!empty($vehicle['odometer_km'])) {
        return (int) $vehicle['odometer_km'];
    }

    $vehicleId = (int) ($vehicle['vehicle_id'] ?? 0);

    return 42000 + ($vehicleId * 850);
}

function vcs_vehicle_next_probable_service_km(array $vehicle): int
{
    if (!empty($vehicle['next_probable_service_km'])) {
        return (int) $vehicle['next_probable_service_km'];
    }

    return vcs_vehicle_odometer_km($vehicle) + vcs_vehicle_service_interval_km($vehicle);
}

function vcs_vehicle_service_notes(array $vehicle): string
{
    if (!empty($vehicle['service_notes'])) {
        return (string) $vehicle['service_notes'];
    }

    $service = trim((string) ($vehicle['service_details'] ?? 'Full service'));

    return $service . ': oil, filters, brakes, tyres, fluids, lights, and diagnostics.';
}

function vcs_inspector_badge_label(array $vehicle): string
{
    if (!empty($vehicle['inspection_checked_by_badge'])) {
        return (string) $vehicle['inspection_checked_by_badge'];
    }

    if (!empty($vehicle['inspection_checked_by'])) {
        return 'Badge #' . (string) $vehicle['inspection_checked_by'];
    }

    return 'Officer not recorded';
}

function vcs_vehicle_insurance_types(): array
{
    return [
        'Comprehensive' => 'Comprehensive',
        'Third Party' => 'Third Party',
    ];
}

function vcs_vehicle_payment_periods(): array
{
    return [
        'Annual' => 'Annual',
        'Semi-Annual' => 'Semi-Annual',
        'Quarterly' => 'Quarterly',
        'Monthly' => 'Monthly',
    ];
}

function vcs_vehicle_licence_classes(): array
{
    return [
        'A1' => 'A1 - Moped',
        'A2' => 'A2 - Motorcycle up to 550cc',
        'A3' => 'A3 - Taxi / Ride-hailing motorcycle',
        'B' => 'B - Private motor vehicle',
        'B1' => 'B1 - Light passenger vehicle',
        'C1' => 'C1 - Light truck',
        'C' => 'C - Medium truck',
        'C2' => 'C2 - Heavy truck',
        'C3' => 'C3 - Extra-heavy truck',
        'CE' => 'CE - Articulated vehicle',
        'D1' => 'D1 - PSV up to 14 seats',
        'D2' => 'D2 - PSV 14-32 seats',
        'D3' => 'D3 - PSV over 32 seats',
        'G' => 'G - Industrial / construction equipment',
    ];
}

function vcs_normalized_role_set(array $roles): array
{
    return array_values(array_unique(array_filter(array_map('vcs_normalize_role', $roles))));
}

function vcs_role_label(string $role): string
{
    $labels = vcs_supported_roles();
    $normalized = vcs_normalize_role($role);

    return $labels[$normalized] ?? ucfirst($normalized);
}

function vcs_vehicle_ownership_role_options(): array
{
    return [
        'Primary owner' => 'Primary owner',
        'Co-owner' => 'Co-owner',
        'Beneficial owner' => 'Beneficial owner',
    ];
}
