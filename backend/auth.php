<?php
session_start();

require_once '../config/db.php';
require_once __DIR__ . '/auth_helpers.php';
require_once __DIR__ . '/../config/mail.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $selectedRole = vcs_normalize_role($_POST['role'] ?? '');
    
    try {
        // Load the account first so we can return specific errors for inactive, unverified, or invalid credentials.
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
                // Some demo users still rely on a legacy password format; upgrade them after a successful check.
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

            // Resolve every role this account may use before choosing the dashboard.
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

            if (vcs_login_verification_required($user)) {
                // Some demo accounts must confirm a one-time code before the session is created.
                $verification = vcs_create_login_verification_token($pdo, (int) $user['user_id'], $selectedRole);
                $appUrl = rtrim(getenv('APP_URL') ?: 'http://localhost:8080', '/');
                $verifyPageUrl = $appUrl . '/verify_login.php';
                $mailSent = sendLoginVerificationEmail($user['email'], $user['name'], $verification['code'], $verifyPageUrl);

                $_SESSION['pending_login_verification'] = [
                    'token_id' => $verification['token_id'],
                    'user_id' => (int) $user['user_id'],
                    'selected_role' => $selectedRole,
                    'available_roles' => $availableRoles,
                    'email' => $user['email'],
                    'name' => $user['name'],
                    'expires_at' => $verification['expires_at'],
                ];

                $_SESSION['flash_message'] = $mailSent
                    ? 'A verification code has been sent to your email. Enter it on the next screen.'
                    : 'Verification code could not be emailed on this server.';
                $_SESSION['flash_type'] = $mailSent ? 'warning' : 'danger';
                if (!$mailSent) {
                    $_SESSION['flash_code'] = $verification['code'];
                }

                header('Location: /verify_login.php');
                exit;
            }

            // Persist the authenticated identity in the session and refresh the session ID.
            vcs_store_auth_session($user, $selectedRole, $availableRoles);

            header('Location: ' . vcs_dashboard_url_for_role($selectedRole));
            exit;

        } else {
            header("Location: /login?error=Invalid credentials or inactive account.");
            exit;
        }

    } catch (\PDOException $e) {
        header("Location: /login?error=Database_Error");
        exit;
    }
}
?>
