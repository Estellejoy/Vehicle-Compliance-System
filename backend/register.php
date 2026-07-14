<?php
session_start();

require_once '../config/db.php';
require_once __DIR__ . '/auth_helpers.php';
require_once __DIR__ . '/../config/mail.php';

function register_wants_json(): bool
{
    // This supports both the normal form and the AJAX version of registration.
    $requestedWith = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
    $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));

    return $requestedWith === 'xmlhttprequest' || str_contains($accept, 'application/json');
}

function register_respond(array $payload, int $statusCode = 200): void
{
    // Send the response in the format the page requested.
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

// Check the form first so invalid data is never saved.
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

    $pdo->beginTransaction();

    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $tokenExpiresAt = (new DateTimeImmutable('+24 hours'))->format('Y-m-d H:i:s');
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // The account stays inactive until the owner verifies the email.
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

    // Save the new ID before creating the matching owner role.
    $registeredUserId = (int) $pdo->lastInsertId();
    if ($registeredUserId <= 0) {
        throw new RuntimeException('The database did not return the new user ID.');
    }

    if (vcs_has_table($pdo, 'user_roles')) {
        // Keep the user's role in the separate role table as well.
        $rolesInsert = $pdo->prepare(
            'INSERT INTO user_roles (user_id, role, is_primary)
             VALUES (:user_id, :role, 1)
             ON DUPLICATE KEY UPDATE is_primary = VALUES(is_primary)'
        );
        $rolesInsert->execute([
            'user_id' => $registeredUserId,
            'role' => 'owner',
        ]);
    }

    $pdo->commit();

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
} catch (Throwable $e) {
    if ($pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());
    $duplicateAccount = $e instanceof PDOException && (int) ($e->errorInfo[1] ?? 0) === 1062;
    register_respond([
        'type' => $duplicateAccount ? 'warning' : 'danger',
        'message' => $duplicateAccount
            ? 'An account already exists for that email address.'
            : 'Database error while creating the account.',
    ], $duplicateAccount ? 409 : 500);
}
