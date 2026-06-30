<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: /login');
    exit;
}

require_once '../config/db.php';
require_once __DIR__ . '/auth_helpers.php';

function flash_admin(string $type, string $message): void
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

if (!vcs_has_table($pdo, 'user_roles')) {
    flash_admin('warning', 'User role management is not available until the user_roles table exists.');
}

$targetUserId = (int) ($_POST['user_id'] ?? 0);
$selectedRoles = vcs_normalized_role_set($_POST['roles'] ?? []);
$primaryRole = vcs_normalize_role($_POST['primary_role'] ?? '');

if ($targetUserId <= 0) {
    flash_admin('warning', 'Select a user first.');
}

if ($targetUserId === (int) $_SESSION['user_id']) {
    flash_admin('warning', 'You cannot change your own account roles from this screen.');
}

if (!$selectedRoles) {
    flash_admin('warning', 'Select at least one role.');
}

$allowedRoles = array_keys(vcs_supported_roles());
$selectedRoles = array_values(array_intersect($selectedRoles, $allowedRoles));

if (!$selectedRoles) {
    flash_admin('warning', 'Select at least one valid role.');
}

if ($primaryRole === '' || !in_array($primaryRole, $selectedRoles, true)) {
    $primaryRole = $selectedRoles[0];
}

try {
    $stmt = $pdo->prepare('SELECT user_id, name, email FROM users WHERE user_id = :user_id LIMIT 1');
    $stmt->execute(['user_id' => $targetUserId]);
    $user = $stmt->fetch();

    if (!$user) {
        flash_admin('warning', 'That user could not be found.');
    }

    $pdo->beginTransaction();

    $delete = $pdo->prepare(
        'DELETE FROM user_roles
         WHERE user_id = :user_id
           AND role NOT IN (' . implode(',', array_fill(0, count($selectedRoles), '?')) . ')'
    );
    $delete->execute(array_merge([$targetUserId], $selectedRoles));

    $upsert = $pdo->prepare(
        'INSERT INTO user_roles (user_id, role, is_primary)
         VALUES (:user_id, :role, :is_primary)
         ON DUPLICATE KEY UPDATE is_primary = VALUES(is_primary)'
    );

    foreach ($selectedRoles as $role) {
        $upsert->execute([
            'user_id' => $targetUserId,
            'role' => $role,
            'is_primary' => $role === $primaryRole ? 1 : 0,
        ]);
    }

    $updateUser = $pdo->prepare('UPDATE users SET role = :role WHERE user_id = :user_id');
    $updateUser->execute([
        'role' => $primaryRole,
        'user_id' => $targetUserId,
    ]);

    $pdo->commit();

    flash_admin(
        'success',
        'Roles updated for ' . $user['name'] . ' (' . $user['email'] . '): ' . implode(', ', array_map('vcs_role_label', $selectedRoles))
    );
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log($e->getMessage());
    flash_admin('danger', 'Database error while updating user roles.');
}
