<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: /login');
    exit;
}

require_once '../config/db.php';
require_once __DIR__ . '/auth_helpers.php';

function flash_admin_vehicle(string $type, string $message): void
{
    $_SESSION['admin_flash'] = [
        'type' => $type,
        'message' => $message,
    ];

    header('Location: /views/admin_panel.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /views/admin_panel.php');
    exit;
}

if (!vcs_has_table($pdo, 'vehicle_owners')) {
    flash_admin_vehicle('warning', 'Joint ownership is not available until the vehicle_owners table exists.');
}

$vehicleId = (int) ($_POST['vehicle_id'] ?? 0);
$coOwnerEmail = trim((string) ($_POST['co_owner_email'] ?? ''));
$ownershipRole = trim((string) ($_POST['ownership_role'] ?? 'Co-owner'));

if ($vehicleId <= 0 || $coOwnerEmail === '') {
    flash_admin_vehicle('warning', 'Choose a vehicle and enter a co-owner email.');
}

try {
    $vehicleStmt = $pdo->prepare(
        'SELECT vehicle_id, plate_number
         FROM vehicles
         WHERE vehicle_id = :vehicle_id
         LIMIT 1'
    );
    $vehicleStmt->execute(['vehicle_id' => $vehicleId]);
    $vehicle = $vehicleStmt->fetch();

    if (!$vehicle) {
        flash_admin_vehicle('warning', 'That vehicle could not be found.');
    }

    $userStmt = $pdo->prepare('SELECT user_id, name, email FROM users WHERE email = :email LIMIT 1');
    $userStmt->execute(['email' => $coOwnerEmail]);
    $coOwner = $userStmt->fetch();

    if (!$coOwner) {
        flash_admin_vehicle('warning', 'No account exists for that email address.');
    }

    $roleOptions = array_values(vcs_vehicle_ownership_role_options());
    if (!in_array($ownershipRole, $roleOptions, true)) {
        $ownershipRole = 'Co-owner';
    }

    $pdo->beginTransaction();

    $insert = $pdo->prepare(
        'INSERT INTO vehicle_owners (vehicle_id, user_id, ownership_role, is_primary)
         VALUES (:vehicle_id, :user_id, :ownership_role, 0)
         ON DUPLICATE KEY UPDATE ownership_role = VALUES(ownership_role)'
    );
    $insert->execute([
        'vehicle_id' => $vehicleId,
        'user_id' => $coOwner['user_id'],
        'ownership_role' => $ownershipRole,
    ]);

    if (vcs_has_table($pdo, 'user_roles')) {
        $roleInsert = $pdo->prepare(
            'INSERT INTO user_roles (user_id, role, is_primary)
             VALUES (:user_id, "owner", 0)
             ON DUPLICATE KEY UPDATE role = role'
        );
        $roleInsert->execute([
            'user_id' => $coOwner['user_id'],
        ]);
    }

    $pdo->commit();

    flash_admin_vehicle(
        'success',
        $coOwner['name'] . ' was added as a ' . $ownershipRole . ' for ' . $vehicle['plate_number'] . '.',
    );
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log($e->getMessage());
    flash_admin_vehicle('danger', 'Database error while saving the co-owner.');
}
