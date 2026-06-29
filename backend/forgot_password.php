<?php
session_start();

require_once '../config/db.php';
require_once __DIR__ . '/auth_helpers.php';

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

    $genericMessage = 'If an active account exists for that email, we have prepared a reset link.';

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

    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $expiresAt = vcs_password_reset_expiry();

    $cleanup = $pdo->prepare('DELETE FROM password_reset_tokens WHERE user_id = :user_id');
    $cleanup->execute(['user_id' => $user['user_id']]);

    $insert = $pdo->prepare(
        'INSERT INTO password_reset_tokens (user_id, token_hash, expires_at)
         VALUES (:user_id, :token_hash, :expires_at)'
    );
    $insert->execute([
        'user_id' => $user['user_id'],
        'token_hash' => $tokenHash,
        'expires_at' => $expiresAt,
    ]);

    $appUrl = rtrim(getenv('APP_URL') ?: 'http://localhost:8080', '/');
    $resetLink = $appUrl . '/reset-password?token=' . urlencode($token);

    $fromAddress = getenv('MAIL_FROM_ADDRESS') ?: 'no-reply@localhost';
    $fromName = getenv('MAIL_FROM_NAME') ?: 'Vehicle Compliance System';
    $subject = 'Reset your Vehicle Compliance System password';
    $body = "Hello {$user['name']},\n\n";
    $body .= "Use the link below to set a new password. This link expires in 1 hour:\n{$resetLink}\n\n";
    $body .= "If you did not request this, you can ignore this message.";
    $headers = [
        'From: ' . $fromName . ' <' . $fromAddress . '>',
        'Reply-To: ' . $fromAddress,
        'Content-Type: text/plain; charset=UTF-8',
    ];

    $mailSent = @mail($user['email'], $subject, $body, implode("\r\n", $headers));

    flash_forgot([
        'type' => 'success',
        'message' => $mailSent
            ? 'Password reset link sent to your email address.'
            : 'Password reset link generated, but email delivery is not configured on this server.',
        'reset_link' => $resetLink,
    ]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    flash_forgot([
        'type' => 'danger',
        'message' => 'Database error while preparing the password reset link.',
    ]);
}
