<?php
session_start();

require_once '../config/db.php';
require_once __DIR__ . '/auth_helpers.php';

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

    $tokenHash = hash('sha256', $token);
    $stmt = $pdo->prepare(
        'SELECT prt.token_id, prt.user_id, u.email
         FROM password_reset_tokens prt
         INNER JOIN users u ON u.user_id = prt.user_id
         WHERE prt.token_hash = :token_hash
           AND prt.expires_at > NOW()
           AND prt.used_at IS NULL
         LIMIT 1'
    );
    $stmt->execute(['token_hash' => $tokenHash]);
    $reset = $stmt->fetch();

    if (!$reset) {
        flash_reset([
            'type' => 'danger',
            'message' => 'This reset link is invalid or expired.',
        ]);
    }

    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $pdo->beginTransaction();

    $update = $pdo->prepare('UPDATE users SET password_hash = :password_hash WHERE user_id = :user_id');
    $update->execute([
        'password_hash' => $newHash,
        'user_id' => $reset['user_id'],
    ]);

    $markUsed = $pdo->prepare(
        'UPDATE password_reset_tokens
         SET used_at = NOW()
         WHERE user_id = :user_id'
    );
    $markUsed->execute(['user_id' => $reset['user_id']]);

    $pdo->commit();

    $_SESSION['flash_type'] = 'success';
    $_SESSION['flash_message'] = 'Password updated successfully. Please log in again.';
    header('Location: /login');
    exit;
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log($e->getMessage());
    flash_reset([
        'type' => 'danger',
        'message' => 'Database error while updating the password.',
    ]);
}
