<?php
session_start();

require_once 'config/db.php';
require_once __DIR__ . '/backend/auth_helpers.php';

$flash = $_SESSION['flash_message'] ?? null;
$flashType = $_SESSION['flash_type'] ?? null;
$flashCode = $_SESSION['flash_code'] ?? null;
unset($_SESSION['flash_message'], $_SESSION['flash_type'], $_SESSION['flash_code']);

$pending = $_SESSION['pending_login_verification'] ?? null;
$error = null;

if (!$pending) {
    $error = 'Your login verification session has expired. Please sign in again.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pending) {
    $code = preg_replace('/\D/', '', trim((string) ($_POST['verification_code'] ?? '')));

    if (strlen($code) !== 6) {
        $error = 'Enter the 6-digit verification code from your email.';
    } else {
        try {
            $verification = vcs_consume_login_verification_token($pdo, (int) $pending['token_id'], $code);

            if ($verification && (int) $verification['user_id'] === (int) $pending['user_id']) {
                $user = [
                    'user_id' => $verification['user_id'],
                    'name' => $verification['name'],
                    'email' => $verification['email'],
                    'role' => $verification['role'],
                ];

                $availableRoles = vcs_available_roles_for_user($pdo, $user);
                if (!$availableRoles) {
                    $availableRoles = [vcs_normalize_role($verification['selected_role'])];
                }

                vcs_store_auth_session($user, (string) $verification['selected_role'], $availableRoles);
                unset($_SESSION['pending_login_verification']);

                header('Location: ' . vcs_dashboard_url_for_role((string) $verification['selected_role']));
                exit;
            }

            $error = 'The verification code is invalid or expired. Please sign in again.';
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = 'Database error while verifying your login code.';
        }
    }
}

$email = $pending['email'] ?? '';
$expiresAt = $pending['expires_at'] ?? null;
$title = 'Verify Login';
$subtitle = 'Enter the 6-digit code sent to your email.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="login-page">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="card shadow-lg p-4">
                    <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                        <div>
                            <h1 class="h3 fw-bold mb-1"><?php echo htmlspecialchars($title); ?></h1>
                            <p class="text-secondary mb-0"><?php echo htmlspecialchars($subtitle); ?></p>
                        </div>
                        <a href="/login" class="btn btn-outline-success">Back to login</a>
                    </div>

                    <?php if ($flash): ?>
                        <div class="alert alert-<?php echo htmlspecialchars($flashType ?? 'info'); ?>" role="alert">
                            <?php echo htmlspecialchars($flash); ?>
                            <?php if (!empty($flashCode)): ?>
                                <div class="mt-2">
                                    <span class="badge text-bg-light text-dark border">Code: <?php echo htmlspecialchars($flashCode); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($pending): ?>
                        <div class="alert alert-info" role="alert">
                            We sent a verification code to <strong><?php echo htmlspecialchars($email); ?></strong>.
                            <?php if (!empty($expiresAt)): ?>
                                It expires at <strong><?php echo htmlspecialchars($expiresAt); ?></strong>.
                            <?php endif; ?>
                        </div>

                        <form method="POST" action="/verify_login.php" class="login-form">
                            <div class="mb-4">
                                <label for="verification_code" class="form-label fw-semibold text-secondary">Verification Code</label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-white"><i class="bi bi-shield-lock text-success"></i></span>
                                    <input
                                        type="text"
                                        id="verification_code"
                                        name="verification_code"
                                        class="form-control"
                                        placeholder="Enter 6-digit code"
                                        inputmode="numeric"
                                        maxlength="6"
                                        pattern="[0-9]{6}"
                                        required
                                        autofocus
                                    >
                                </div>
                                <div class="form-text">Check your email and enter the code here to continue.</div>
                            </div>

                            <button type="submit" class="btn btn-success btn-lg w-100 fw-semibold">
                                <i class="bi bi-check2-circle me-2"></i> Verify and Continue
                            </button>
                        </form>
                    <?php else: ?>
                        <p class="mb-0">
                            Please sign in again to request a new verification code.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
