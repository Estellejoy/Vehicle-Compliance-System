<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

require_once '../config/db.php';
require_once __DIR__ . '/auth_helpers.php';

$userId = (int) $_SESSION['user_id'];
$currentPassword = (string) ($_POST['current_password'] ?? '');
$newPassword = (string) ($_POST['new_password'] ?? '');
$confirmPassword = (string) ($_POST['confirm_password'] ?? '');

function setFlash(string $type, string $message): void
{
    $_SESSION['flash_type'] = $type;
    $_SESSION['flash_message'] = $message;
}

function redirectBack(): void
{
    header('Location: /views/change_password.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectBack();
}

if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
    setFlash('warning', 'Fill in all password fields.');
    redirectBack();
}

if ($newPassword !== $confirmPassword) {
    setFlash('warning', 'New password and confirmation do not match.');
    redirectBack();
}

if ($strengthErrors = vcs_validate_password_strength($newPassword)) {
    setFlash('warning', $strengthErrors[0]);
    redirectBack();
}

try {
    $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE user_id = :user_id AND COALESCE(is_active, 1) = 1 LIMIT 1');
    $stmt->execute(['user_id' => $userId]);
    $user = $stmt->fetch();

    if (!$user || empty($user['password_hash'])) {
        setFlash('danger', 'Your account does not have a valid password record.');
        redirectBack();
    }

    if (!password_verify($currentPassword, $user['password_hash'])) {
        setFlash('danger', 'Current password is incorrect.');
        redirectBack();
    }

    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $update = $pdo->prepare('UPDATE users SET password_hash = :password_hash WHERE user_id = :user_id');
    $update->execute([
        'password_hash' => $newHash,
        'user_id' => $userId,
    ]);

    session_regenerate_id(true);
    setFlash('success', 'Password updated successfully.');
    redirectBack();
} catch (PDOException $e) {
    error_log($e->getMessage());
    setFlash('danger', 'Database error while updating your password.');
    redirectBack();
}
