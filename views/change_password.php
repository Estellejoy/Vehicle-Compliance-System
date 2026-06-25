<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

$role = $_SESSION['role'] ?? '';
$dashboardUrl = match ($role) {
    'admin' => '/views/admin_panel.php',
    'officer' => '/views/officer_dashboard.php',
    default => '/views/citizen_portal.php',
};

$flashType = $_SESSION['flash_type'] ?? null;
$flashMessage = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_type'], $_SESSION['flash_message']);

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - VCVS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="login-page">
    <div class="login-shell">
        <div class="container py-4">
            <nav class="navbar navbar-expand-lg navbar-light vcs-navbar border-bottom rounded-4 px-3 px-md-4 mb-4 bg-white shadow-sm">
                <a class="navbar-brand d-flex align-items-center gap-2 fw-bold text-success" href="<?php echo h($dashboardUrl); ?>">
                    <span class="brand-mark"><i class="bi bi-shield-check"></i></span>
                    <span>VCVS</span>
                </a>
                <div class="ms-auto d-flex align-items-center gap-2">
                    <span class="text-secondary small d-none d-md-inline">
                        <i class="bi bi-person-circle me-1 text-success"></i><?php echo h($_SESSION['name'] ?? 'User'); ?>
                    </span>
                    <a href="<?php echo h($dashboardUrl); ?>" class="btn btn-outline-success btn-sm">
                        <i class="bi bi-house me-1"></i> Dashboard
                    </a>
                    <a href="/backend/logout.php" class="btn btn-success btn-sm">
                        <i class="bi bi-box-arrow-right me-1"></i> Logout
                    </a>
                </div>
            </nav>

            <div class="row justify-content-center align-items-center">
                <div class="col-lg-10 col-xl-8">
                    <div class="row g-0 login-card overflow-hidden shadow-lg">
                        <div class="col-lg-5 login-panel text-white p-5 d-flex flex-column justify-content-between">
                            <div>
                                <span class="badge text-bg-light text-success mb-3">Security</span>
                                <h1 class="h2 fw-bold">Change your password</h1>
                                <p class="mt-3 mb-0 opacity-75">
                                    Pick a password that is unique to you. This immediately replaces your current login password.
                                </p>
                            </div>
                            <div class="login-art mt-4">
                                <div class="rounded-4 p-4 bg-white bg-opacity-10">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="display-6"><i class="bi bi-key-fill"></i></div>
                                        <div>
                                            <div class="fw-semibold">Password tips</div>
                                            <div class="small opacity-75">Use 8+ characters and avoid reusing your old password.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-7 bg-white p-4 p-md-5">
                            <div class="mb-4">
                                <h2 class="fw-bold text-dark mb-1">Set a new password</h2>
                                <p class="text-secondary mb-0">Logged in as <?php echo h($_SESSION['role'] ?? 'user'); ?>.</p>
                            </div>

                            <?php if ($flashMessage): ?>
                                <div class="alert alert-<?php echo h($flashType ?? 'info'); ?>" role="alert">
                                    <?php echo h($flashMessage); ?>
                                </div>
                            <?php endif; ?>

                            <form action="/backend/change_password.php" method="POST" class="login-form">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label fw-semibold text-secondary">Current Password</label>
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text bg-white"><i class="bi bi-lock text-success"></i></span>
                                        <input type="password" id="current_password" name="current_password" class="form-control" placeholder="Current password" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="new_password" class="form-label fw-semibold text-secondary">New Password</label>
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text bg-white"><i class="bi bi-shield-lock text-success"></i></span>
                                        <input type="password" id="new_password" name="new_password" class="form-control" placeholder="New password" minlength="8" required>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label for="confirm_password" class="form-label fw-semibold text-secondary">Confirm New Password</label>
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text bg-white"><i class="bi bi-shield-check text-success"></i></span>
                                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Confirm new password" minlength="8" required>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-success btn-lg w-100 fw-semibold">
                                    <i class="bi bi-arrow-repeat me-2"></i> Update Password
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
