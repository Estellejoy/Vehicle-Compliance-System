<?php
session_start();

$flash = $_SESSION['reset_password_flash'] ?? null;
unset($_SESSION['reset_password_flash']);

require_once __DIR__ . '/backend/auth_helpers.php';

$token = trim($_GET['token'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - VCS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="login-page">
    <div class="login-shell">
        <div class="container">
            <div class="row justify-content-center align-items-center min-vh-100 py-4">
                <div class="col-lg-10 col-xl-9">
                    <div class="row g-0 login-card overflow-hidden shadow-lg">
                        <div class="col-lg-5 login-panel text-white p-5 d-flex flex-column justify-content-between">
                            <div>
                                <a href="/login" class="text-white text-decoration-none small fw-semibold">
                                    <i class="bi bi-arrow-left me-1"></i> Back to login
                                </a>
                                <div class="mt-4">
                                    <span class="badge text-bg-light text-success mb-3">Account Access</span>
                                    <h1 class="h2 fw-bold">Choose a new password</h1>
                                    <p class="mt-3 mb-0 opacity-75">
                                        Enter a strong password to secure your account again.
                                    </p>
                                </div>
                            </div>
                            <div class="login-art mt-4">
                                <div class="rounded-4 p-4 bg-white bg-opacity-10">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="display-6"><i class="bi bi-shield-lock"></i></div>
                                        <div>
                                            <div class="fw-semibold">Password rules</div>
                                            <div class="small opacity-75">10+ characters, upper, lower, number, and special character.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-7 bg-white p-4 p-md-5">
                            <div class="mb-4">
                                <h2 class="fw-bold text-dark mb-1">Reset password</h2>
                                <p class="text-secondary mb-0">Set a new password for your account.</p>
                            </div>

                            <?php if ($flash): ?>
                                <div class="alert alert-<?php echo htmlspecialchars($flash['type'] ?? 'info'); ?>" role="alert">
                                    <?php echo htmlspecialchars($flash['message'] ?? ''); ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($token === ''): ?>
                                <div class="alert alert-danger" role="alert">
                                    This reset link is missing a token.
                                </div>
                            <?php else: ?>
                                <form action="/backend/reset_password.php" method="POST" class="login-form">
                                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                                    <div class="mb-3">
                                        <label for="new_password" class="form-label fw-semibold text-secondary">New Password</label>
                                        <div class="input-group input-group-lg">
                                            <span class="input-group-text bg-white"><i class="bi bi-lock text-success"></i></span>
                                            <input type="password" id="new_password" name="new_password" class="form-control" placeholder="New password" minlength="10" required>
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label for="confirm_password" class="form-label fw-semibold text-secondary">Confirm New Password</label>
                                        <div class="input-group input-group-lg">
                                            <span class="input-group-text bg-white"><i class="bi bi-shield-check text-success"></i></span>
                                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Confirm new password" minlength="10" required>
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-success btn-lg w-100 fw-semibold">
                                        <i class="bi bi-arrow-repeat me-2"></i> Update Password
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
