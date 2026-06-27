<?php
session_start();

require_once '../config/db.php';

function flash_register(array $payload): void
{
    $_SESSION['register_flash'] = $payload;
    header('Location: /register');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /register');
    exit;
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = (string) ($_POST['password'] ?? '');
$confirmPassword = (string) ($_POST['confirm_password'] ?? '');

if ($name === '' || $email === '' || $password === '' || $confirmPassword === '') {
    flash_register([
        'type' => 'danger',
        'message' => 'All fields are required.',
    ]);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    flash_register([
        'type' => 'danger',
        'message' => 'Enter a valid email address.',
    ]);
}

if ($password !== $confirmPassword) {
    flash_register([
        'type' => 'danger',
        'message' => 'Passwords do not match.',
    ]);
}

if (strlen($password) < 8) {
    flash_register([
        'type' => 'danger',
        'message' => 'Password must be at least 8 characters long.',
    ]);
}

try {
    $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    if ($stmt->fetch()) {
        flash_register([
            'type' => 'warning',
            'message' => 'An account already exists for that email address.',
        ]);
    }

    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $tokenExpiresAt = (new DateTimeImmutable('+24 hours'))->format('Y-m-d H:i:s');
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $insert = $pdo->prepare(
        "INSERT INTO users (
            name,
            email,
            role,
            password_hash,
            is_active,
            email_verified_at,
            email_verification_token_hash,
            email_verification_expires_at
        ) VALUES (
            :name,
            :email,
            'owner',
            :password_hash,
            0,
            NULL,
            :token_hash,
            :token_expires_at
        )"
    );
    $insert->execute([
        'name' => $name,
        'email' => $email,
        'password_hash' => $passwordHash,
        'token_hash' => $tokenHash,
        'token_expires_at' => $tokenExpiresAt,
    ]);

    $appUrl = rtrim(getenv('APP_URL') ?: 'http://localhost:8080', '/');
    $verifyLink = $appUrl . '/verify.php?token=' . urlencode($token);

    $fromAddress = getenv('MAIL_FROM_ADDRESS') ?: 'no-reply@localhost';
    $fromName = getenv('MAIL_FROM_NAME') ?: 'Vehicle Compliance System';
    $subject = 'Verify your Vehicle Compliance System account';
    $body = "Hello {$name},\n\n";
    $body .= "Please verify your account by opening this link within 24 hours:\n{$verifyLink}\n\n";
    $body .= "If you did not create this account, you can ignore this message.";
    $headers = [
        'From: ' . $fromName . ' <' . $fromAddress . '>',
        'Reply-To: ' . $fromAddress,
        'Content-Type: text/plain; charset=UTF-8',
    ];

    $mailSent = @mail($email, $subject, $body, implode("\r\n", $headers));

    $message = $mailSent
        ? 'Registration successful. Check your email for the verification link.'
        : 'Registration successful, but email delivery is not configured on this server. Use the verification link below.';

    $_SESSION['register_flash'] = [
        'type' => 'success',
        'message' => $message,
        'verification_link' => $verifyLink,
        'email_sent' => $mailSent,
    ];

    header('Location: /register');
    exit;
} catch (PDOException $e) {
    error_log($e->getMessage());
    flash_register([
        'type' => 'danger',
        'message' => 'Database error while creating the account.',
    ]);
}
