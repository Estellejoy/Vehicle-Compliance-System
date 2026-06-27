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
                                    <span class="badge text-bg-light text-success mb-3">VCS Portal</span>
                                    <h1 class="h2 fw-bold">WELCOME BACK!</h1>
                                    <p class="mt-3 mb-0 opacity-75">
                                        Sign in with your email address and password to access your dashboard.
                                    </p>
                                </div>
                            </div>
                            <div class="login-art mt-4">
                                <img
                                    src="https://commons.wikimedia.org/wiki/Special:FilePath/Kenyatta%20International%20Convention%20Centre,%20Nairobi,%20by%20Karl%20Henrik%20N%C3%B8stvik%20architect,%20entrance.jpg"
                                    class="img-fluid rounded-4"
                                    alt="KICC Nairobi"
                                >
                            </div>
                        </div>
                        <div class="col-lg-7 bg-white p-4 p-md-5">
                                <div class="mb-4">
                                    <h2 class="fw-bold text-dark mb-1">Login</h2>
                                    <p class="text-secondary mb-0">Enter the email registered to your account.</p>
                             
                                </div>

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
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-success btn-lg w-100 fw-semibold">
                                    <i class="bi bi-box-arrow-in-right me-2"></i> Login 
                                </button>
                            </form>

                            <div class="text-center mt-4">
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
</body>
</html>
