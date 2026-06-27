<?php
session_start();

$flash = $_SESSION['register_flash'] ?? null;
unset($_SESSION['register_flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VCVS Register</title>
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
                                <a href="/" class="text-white text-decoration-none small fw-semibold">
                                    <i class="bi bi-arrow-left me-1"></i> Back to home
                                </a>
                                <div class="mt-4">
                                    <span class="badge text-bg-light text-success mb-3">VCS Portal</span>
                                    <h1 class="h2 fw-bold">CREATE ACCOUNT</h1>
                                    <p class="mt-3 mb-0 opacity-75">
                                        Register as a vehicle owner, then verify your email address before logging in.
                                    </p>
                                </div>
                            </div>
                            <div class="login-art mt-4">
                                <img
                                    src="https://commons.wikimedia.org/wiki/Special:FilePath/Section%2058%20Bypass.jpg"
                                    class="img-fluid rounded-4"
                                    alt="Nairobi Expressway"
                                >
                            </div>
                        </div>
                        <div class="col-lg-7 bg-white p-4 p-md-5">
                            <div class="mb-4">
                                <h2 class="fw-bold text-dark mb-1">Register</h2>
                                <p class="text-secondary mb-0">Create your owner account and verify your email to activate it.</p>
                            </div>

                            <?php if ($flash): ?>
                                <div class="alert alert-<?php echo htmlspecialchars($flash['type'] ?? 'info'); ?>" role="alert">
                                    <?php echo htmlspecialchars($flash['message'] ?? ''); ?>
                                    <?php if (!empty($flash['verification_link'])): ?>
                                        <div class="mt-2">
                                            <a href="<?php echo htmlspecialchars($flash['verification_link']); ?>" class="link-success fw-semibold">
                                                Open verification link
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <?php if (isset($_GET['verified']) && $_GET['verified'] === '1'): ?>
                                <div class="alert alert-success" role="alert">
                                    Email verified successfully. You can now log in.
                                </div>
                            <?php endif; ?>

                            <?php if (isset($_GET['error'])): ?>
                                <div class="alert alert-danger" role="alert">
                                    <?php echo htmlspecialchars($_GET['error']); ?>
                                </div>
                            <?php endif; ?>

                            <form action="backend/register.php" method="POST" class="login-form">
                                <div class="mb-3">
                                    <label for="name" class="form-label fw-semibold text-secondary">Full Name</label>
                                    <input type="text" id="name" name="name" class="form-control form-control-lg" placeholder="Joy Gatiti" required>
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label fw-semibold text-secondary">Email Address</label>
                                    <input type="email" id="email" name="email" class="form-control form-control-lg" placeholder="name@example.com" required>
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label fw-semibold text-secondary">Password</label>
                                    <input type="password" id="password" name="password" class="form-control form-control-lg" placeholder="Create a password" required>
                                </div>

                                <div class="mb-4">
                                    <label for="confirm_password" class="form-label fw-semibold text-secondary">Confirm Password</label>
                                    <input type="password" id="confirm_password" name="confirm_password" class="form-control form-control-lg" placeholder="Repeat password" required>
                                </div>

                                <button type="submit" class="btn btn-success btn-lg w-100 fw-semibold">
                                    <i class="bi bi-person-plus me-2"></i> Register
                                </button>
                            </form>

                            <div class="text-center mt-4">
                                <span class="text-secondary">Already have an account?</span>
                                <a href="/login" class="link-success fw-semibold text-decoration-none">Login</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
