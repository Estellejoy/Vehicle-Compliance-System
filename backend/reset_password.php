<?php
session_start();

require_once '../config/db.php';
require_once __DIR__ . '/auth_helpers.php';
require_once __DIR__ . '/password_reset_service.php';

function flash_reset(array $payload): void
{
    $_SESSION['reset_password_flash'] = $payload;
    $redirect = '/reset-password';
    if (!empty($_POST['token'])) {
        $redirect .= '?token=' . urlencode((string) $_POST['token']);
    }
    header('Location: ' . $redirect);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /login');
    exit;
}

$token = trim((string) ($_POST['token'] ?? ''));
$newPassword = (string) ($_POST['new_password'] ?? '');
$confirmPassword = (string) ($_POST['confirm_password'] ?? '');

if ($token === '') {
    flash_reset([
        'type' => 'danger',
        'message' => 'The reset link is missing a token.',
    ]);
}

if ($newPassword === '' || $confirmPassword === '') {
    flash_reset([
        'type' => 'warning',
        'message' => 'Fill in both password fields.',
    ]);
}

if ($newPassword !== $confirmPassword) {
    flash_reset([
        'type' => 'warning',
        'message' => 'Passwords do not match.',
    ]);
}

$strengthErrors = vcs_validate_password_strength($newPassword);
if ($strengthErrors) {
    flash_reset([
        'type' => 'warning',
        'message' => $strengthErrors[0],
    ]);
}

try {
    if (!vcs_has_table($pdo, 'password_reset_tokens')) {
        flash_reset([
            'type' => 'danger',
            'message' => 'Password reset is not available on this database yet.',
        ]);
    }

    $reset = vcs_find_password_reset_request($pdo, $token);

    if (!$reset) {
        flash_reset([
            'type' => 'danger',
            'message' => 'This reset link is invalid or expired.',
        ]);
    }

    vcs_apply_password_reset($pdo, (int) $reset['token_id'], (int) $reset['user_id'], $newPassword);

    $_SESSION['flash_type'] = 'success';
    $_SESSION['flash_message'] = 'Password updated successfully. Please log in again.';
    header('Location: /login');
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log($e->getMessage());
    flash_reset([
        'type' => 'danger',
        'message' => 'Database error while updating the password.',
    ]);
}
