<?php
session_start();

$flash = $_SESSION['forgot_password_flash'] ?? null;
unset($_SESSION['forgot_password_flash']);

require_once __DIR__ . '/backend/auth_helpers.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - VCS</title>
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
                                    <h1 class="h2 fw-bold">Reset your password</h1>
                                    <p class="mt-3 mb-0 opacity-75">
                                        We’ll send a password reset link to the email address on your account.
                                    </p>
                                </div>
                            </div>
                            <div class="login-art mt-4">
                                <div class="rounded-4 p-4 bg-white bg-opacity-10">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="display-6"><i class="bi bi-envelope-paper-heart"></i></div>
                                        <div>
                                            <div class="fw-semibold">Secure reset</div>
                                            <div class="small opacity-75">Links expire after 1 hour for safety.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-7 bg-white p-4 p-md-5">
                            <div class="mb-4">
                                <h2 class="fw-bold text-dark mb-1">Forgot password</h2>
                                <p class="text-secondary mb-0">Enter the email connected to your account.</p>
                            </div>

                            <?php if ($flash): ?>
                                <div class="alert alert-<?php echo htmlspecialchars($flash['type'] ?? 'info'); ?>" role="alert">
                                    <?php echo htmlspecialchars($flash['message'] ?? ''); ?>
                                    <?php if (!empty($flash['reset_link'])): ?>
                                        <div class="mt-2">
                                            <a href="<?php echo htmlspecialchars($flash['reset_link']); ?>" class="link-success fw-semibold">
                                                Open reset link
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <form action="/backend/forgot_password.php" method="POST" class="login-form">
                                <div class="mb-4">
                                    <label for="email" class="form-label fw-semibold text-secondary">Email Address</label>
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text bg-white"><i class="bi bi-envelope text-success"></i></span>
                                        <input type="email" id="email" name="email" class="form-control" placeholder="name@example.com" required>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-success btn-lg w-100 fw-semibold">
                                    <i class="bi bi-send me-2"></i> Send Reset Link
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
