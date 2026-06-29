<?php
// backend/auth.php
session_start();

// 1. Import database connection
require_once '../config/db.php';
require_once __DIR__ . '/auth_helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $selectedRole = vcs_normalize_role($_POST['role'] ?? '');
    
    try {
        // 2. Load the account first so we can distinguish verification and password errors
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([
            'email' => $email,
        ]);
        
        $user = $stmt->fetch();
        
        if ($user) {
            if ((int)($user['is_active'] ?? 0) !== 1) {
                if (empty($user['email_verified_at'])) {
                    header("Location: /login?error=Please verify your email before logging in.");
                } else {
                    header("Location: /login?error=Account is inactive. Contact an administrator.");
                }
                exit;
            }

            if (empty($user['password_hash'])) {
                $legacyPassword = explode('@', $email, 2)[0] . '@123';

                if (!hash_equals($legacyPassword, $password)) {
                    header("Location: /login?error=Invalid email or password.");
                    exit;
                }

                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $update = $pdo->prepare(
                    "UPDATE users
                     SET password_hash = :password_hash
                     WHERE user_id = :user_id"
                );
                $update->execute([
                    'password_hash' => $passwordHash,
                    'user_id' => $user['user_id'],
                ]);
                $user['password_hash'] = $passwordHash;
            }

            if (!password_verify($password, $user['password_hash'])) {
                header("Location: /login?error=Invalid email or password.");
                exit;
            }

            $availableRoles = vcs_available_roles_for_user($pdo, $user);

            if (!$availableRoles) {
                $availableRoles = [vcs_normalize_role($user['role'] ?? 'owner')];
            }

            if (count($availableRoles) === 1) {
                $selectedRole = $availableRoles[0];
            } elseif ($selectedRole === '') {
                $query = http_build_query([
                    'error' => 'Choose the role you want to use for this account.',
                ]);
                header("Location: /login?{$query}");
                exit;
            }

            if (!in_array($selectedRole, $availableRoles, true)) {
                header("Location: /login?error=That role is not assigned to this email address.");
                exit;
            }

            // 3. Save details safely to session string variables
            vcs_store_auth_session($user, $selectedRole, $availableRoles);
            
            // 4. Redirect smoothly based on role
            header('Location: ' . vcs_dashboard_url_for_role($selectedRole));
            exit;
            
        } else {
            // Mismatch redirect
            header("Location: /login?error=Invalid credentials or inactive account.");
            exit;
        }
        
    } catch (\PDOException $e) {
        header("Location: /login?error=Database_Error");
        exit;
    }
}
?>
