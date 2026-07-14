<?php
session_start();

// Show login errors, verification links, and one-time codes from the session.
$flash = $_SESSION['flash_message'] ?? null;
$flashType = $_SESSION['flash_type'] ?? null;
$flashLink = $_SESSION['flash_link'] ?? null;
$flashLinkLabel = $_SESSION['flash_link_label'] ?? 'Open link';
$flashCode = $_SESSION['flash_code'] ?? null;
unset(
    $_SESSION['flash_message'],
    $_SESSION['flash_type'],
    $_SESSION['flash_link'],
    $_SESSION['flash_link_label'],
    $_SESSION['flash_code']
);

require_once __DIR__ . '/backend/auth_helpers.php';
$supportedRoles = vcs_supported_roles();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VCVS Login</title>
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
                                    <span class="badge text-bg-light text-success mb-3">Secure VCS Portal</span>
                                    <h1 class="h2 fw-bold">WELCOME BACK!</h1>
                                    <p class="mt-3 mb-0 opacity-75">
                                        Sign in to access compliance records, inspection tools, and account controls in one secure space.
                                    </p>
                                </div>
                            </div>
                            <div class="login-art mt-3">
                                <img
                                    src="https://commons.wikimedia.org/wiki/Special:FilePath/A%20car%20on%20Nairobi-Nakuru%20highway.jpg"
                                    class="img-fluid rounded-4"
                                    alt="Car on the Nairobi-Nakuru highway"
                                >
                            </div>
                        </div>
                        <div class="col-lg-7 bg-white p-4 p-md-5">
                                <div class="mb-4">
                                    <h2 class="fw-bold text-dark mb-1">Login</h2>
                                    <p class="text-secondary mb-0">Enter the email registered to your account.</p>
                             
                                </div>

                            <?php if ($flash): ?>
                                <div class="alert alert-<?php echo htmlspecialchars($flashType ?? 'info'); ?>" role="alert">
                                    <?php echo htmlspecialchars($flash); ?>
                                    <?php if (!empty($flashCode)): ?>
                                        <div class="mt-2">
                                            <span class="badge text-bg-light text-dark border">Code: <?php echo htmlspecialchars($flashCode); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($flashLink)): ?>
                                        <div class="mt-2">
                                            <a href="<?php echo htmlspecialchars($flashLink); ?>" class="link-success fw-semibold text-decoration-none">
                                                <?php echo htmlspecialchars($flashLinkLabel); ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <?php if (isset($_GET['error'])): ?>
                                <div class="alert alert-danger" role="alert">
                                    <?php echo htmlspecialchars($_GET['error']); ?>
                                </div>
                            <?php endif; ?>

                            <form action="backend/auth.php" method="POST" class="login-form">
                                <div class="mb-4">
                                    <label for="username" class="form-label fw-semibold text-secondary">Email Address</label>
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text bg-white"><i class="bi bi-envelope text-success"></i></span>
                                        <input type="email" id="username" name="username" class="form-control" placeholder="name@example.com" required>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label for="password" class="form-label fw-semibold text-secondary">Password</label>
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text bg-white"><i class="bi bi-lock text-success"></i></span>
                                        <input type="password" id="password" name="password" class="form-control" placeholder="Password" required>
                                        <button
                                            type="button"
                                            class="btn btn-outline-secondary"
                                            id="togglePassword"
                                            aria-label="Show password"
                                            aria-pressed="false"
                                        >
                                            <i class="bi bi-eye" id="togglePasswordIcon"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label for="role" class="form-label fw-semibold text-secondary">Sign in as</label>
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text bg-white"><i class="bi bi-person-badge text-success"></i></span>
                                        <select id="role" name="role" class="form-select">
                                            <?php foreach ($supportedRoles as $value => $label): ?>
                                                <option value="<?php echo htmlspecialchars($value); ?>"><?php echo htmlspecialchars($label); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-text">Choose the role you want to access for this session.</div>
                                </div>

                                <button type="submit" class="btn btn-success btn-lg w-100 fw-semibold">
                                    <i class="bi bi-box-arrow-in-right me-2"></i> Login 
                                </button>
                            </form>

                            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2 mt-4">
                                <a href="/forgot-password" class="link-success fw-semibold text-decoration-none">Forgot Password?</a>
                                <span class="text-secondary">Need an account?</span>
                                <a href="/register" class="link-success fw-semibold text-decoration-none">Register</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function () {
            const passwordInput = document.getElementById('password');
            const toggleButton = document.getElementById('togglePassword');
            const toggleIcon = document.getElementById('togglePasswordIcon');

            if (!passwordInput || !toggleButton || !toggleIcon) {
                return;
            }

            toggleButton.addEventListener('click', () => {
                const isHidden = passwordInput.type === 'password';
                passwordInput.type = isHidden ? 'text' : 'password';
                toggleIcon.className = isHidden ? 'bi bi-eye-slash' : 'bi bi-eye';
                toggleButton.setAttribute('aria-pressed', String(isHidden));
                toggleButton.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
            });
        })();
    </script>
</body>
</html>
