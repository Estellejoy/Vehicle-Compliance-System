<?php
session_start();

require_once 'config/db.php';

$title = 'Email Verification';
$message = 'Verification link is invalid or expired.';
$messageType = 'danger';

$token = trim($_GET['token'] ?? '');

if ($token !== '') {
    try {
        $tokenHash = hash('sha256', $token);
        $stmt = $pdo->prepare(
            "SELECT user_id, name, email
             FROM users
             WHERE email_verification_token_hash = :token_hash
               AND email_verification_expires_at > NOW()
               AND is_active = 0
             LIMIT 1"
        );
        $stmt->execute(['token_hash' => $tokenHash]);
        $user = $stmt->fetch();

        if ($user) {
            $update = $pdo->prepare(
                "UPDATE users
                 SET is_active = 1,
                     email_verified_at = NOW(),
                     email_verification_token_hash = NULL,
                     email_verification_expires_at = NULL
                 WHERE user_id = :user_id"
            );
            $update->execute(['user_id' => $user['user_id']]);

            header('Location: /login?verified=1');
            exit;
        }

        $message = 'Verification link is invalid or expired.';
        $messageType = 'danger';
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $message = 'Database error while verifying your account.';
        $messageType = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="login-page">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="card shadow-lg p-4">
                    <h1 class="h3 fw-bold mb-3"><?php echo htmlspecialchars($title); ?></h1>
                    <div class="alert alert-<?php echo htmlspecialchars($messageType); ?>" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                    <a href="/login" class="btn btn-success">Go to Login</a>
                    <a href="/register" class="btn btn-outline-success ms-2">Register Again</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
