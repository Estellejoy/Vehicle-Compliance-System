<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicle Compliance System - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f7f5;
        }
        .login-card {
            border-top: 5px solid #198754; /* Clean Bootstrap Green border line */
            border-radius: 8px;
        }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center vh-100">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            
            <div class="text-center mb-4">
                <h2 class="text-success fw-bold">🚗 VCS Portal</h2>
                <p class="text-muted small">Vehicle Compliance & Enforcement System</p>
            </div>

            <div class="card login-card shadow-sm p-4 bg-white">
                
                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger p-2 text-center small" role="alert">
                        <?php echo htmlspecialchars($_GET['error']); ?>
                    </div>
                <?php endif; ?>

                <form action="../backend/auth.php" method="POST">
                    
                    <div class="mb-3">
                        <label for="username" class="form-label fw-semibold text-secondary small">Email Address</label>
                        <input type="email" id="username" name="username" class="form-control" placeholder="name@example.com" required>
                    </div>

                    <div class="mb-3">
                        <label for="role" class="form-label fw-semibold text-secondary small">System Role</label>
                        <select id="role" name="role" class="form-select" required>
                            <option value="" disabled selected>-- Choose your role --</option>
                            <option value="owner">Vehicle Owner / Citizen</option>
                            <option value="officer">Traffic Officer</option>
                            <option value="admin">System Administrator</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label fw-semibold text-secondary small">Password</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
                    </div>

                    <button type="submit" class="btn btn-success w-100 fw-bold py-2">Sign In</button>
                    
                </form>
            </div>

            <div class="text-center mt-4 text-muted style small" style="font-size: 12px;">
                &copy; 2026 Vehicle Compliance System
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>