<?php
session_start();

require_once '../config/db.php';
require_once __DIR__ . '/auth_helpers.php';
require_once __DIR__ . '/../config/mail.php';

function register_wants_json(): bool
{
    // The register page supports both normal form posts and AJAX submissions.
    $requestedWith = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
    $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));

    return $requestedWith === 'xmlhttprequest' || str_contains($accept, 'application/json');
}

function register_respond(array $payload, int $statusCode = 200): void
{
    // Keep browser redirects and JSON responses in one place.
    if (register_wants_json()) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
        exit;
    }

    $_SESSION['register_flash'] = $payload;
    header('Location: /register');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /register');
    exit;
}

// Validate everything before touching the database.
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = (string) ($_POST['password'] ?? '');
$confirmPassword = (string) ($_POST['confirm_password'] ?? '');

if ($name === '' || $email === '' || $password === '' || $confirmPassword === '') {
    register_respond([
        'type' => 'danger',
        'message' => 'All fields are required.',
    ], 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    register_respond([
        'type' => 'danger',
        'message' => 'Enter a valid email address.',
    ], 422);
}

if ($password !== $confirmPassword) {
    register_respond([
        'type' => 'danger',
        'message' => 'Passwords do not match.',
    ], 422);
}

foreach (vcs_validate_password_strength($password) as $passwordError) {
    register_respond([
        'type' => 'danger',
        'message' => $passwordError,
    ], 422);
}

try {
    $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    if ($stmt->fetch()) {
        register_respond([
            'type' => 'warning',
            'message' => 'An account already exists for that email address.',
        ], 409);
    }

    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $tokenExpiresAt = (new DateTimeImmutable('+24 hours'))->format('Y-m-d H:i:s');
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // New accounts start inactive until the email verification link is used.
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

    if (vcs_has_table($pdo, 'user_roles')) {
        // Mirror the primary owner role in the normalized role table when present.
        $rolesInsert = $pdo->prepare(
            'INSERT INTO user_roles (user_id, role, is_primary)
             VALUES (:user_id, :role, 1)
             ON DUPLICATE KEY UPDATE is_primary = VALUES(is_primary)'
        );
        $rolesInsert->execute([
            'user_id' => (int) $pdo->lastInsertId(),
            'role' => 'owner',
        ]);
    }

    $appUrl = rtrim(getenv('APP_URL') ?: 'http://localhost:8080', '/');
    $verifyLink = $appUrl . '/verify.php?token=' . urlencode($token);

    $mailSent = sendVerificationEmail($email, $name, $verifyLink);

    $message = $mailSent
        ? 'Registration successful. Check your email for the verification link.'
        : 'Registration successful, but email delivery is not configured on this server. Use the verification link below.';

    $response = [
        'type' => 'success',
        'message' => $message,
        'verification_link' => $verifyLink,
        'email_sent' => $mailSent,
    ];

    if (register_wants_json()) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
        exit;
    }

    $_SESSION['register_flash'] = $response;
    header('Location: /register');
    exit;
} catch (PDOException $e) {
    error_log($e->getMessage());
    register_respond([
        'type' => 'danger',
        'message' => 'Database error while creating the account.',
    ], 500);
}
