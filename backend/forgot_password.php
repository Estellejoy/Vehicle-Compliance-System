<?php
session_start();

require_once '../config/db.php';
require_once __DIR__ . '/password_reset_service.php';

function flash_forgot(array $payload): void
{
    $_SESSION['forgot_password_flash'] = $payload;
    header('Location: /forgot-password');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /forgot-password');
    exit;
}

$email = trim((string) ($_POST['email'] ?? ''));

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    flash_forgot([
        'type' => 'warning',
        'message' => 'Enter a valid email address.',
    ]);
}

try {
    $stmt = $pdo->prepare('SELECT user_id, name, email FROM users WHERE email = :email AND COALESCE(is_active, 1) = 1 LIMIT 1');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    $genericMessage = 'If an active account exists for that email, we have sent a reset link.';

    if (!$user) {
        flash_forgot([
            'type' => 'success',
            'message' => $genericMessage,
        ]);
    }

    if (!vcs_has_table($pdo, 'password_reset_tokens')) {
        flash_forgot([
            'type' => 'warning',
            'message' => 'Password reset is not available until the reset-token migration has been applied.',
        ]);
    }

    $request = vcs_send_password_reset_link($pdo, $user);

    $payload = [
        'type' => $request['mail_sent'] ? 'success' : 'warning',
        'message' => $request['mail_sent']
            ? 'Password reset link sent to your email address.'
            : 'Password reset link generated, but email delivery is not configured on this server.',
    ];

    if (!$request['mail_sent']) {
        $payload['reset_link'] = $request['reset_link'];
    }

    flash_forgot($payload);
} catch (Throwable $e) {
    error_log($e->getMessage());
    flash_forgot([
        'type' => 'danger',
        'message' => 'Database error while preparing the password reset link.',
    ]);
}
