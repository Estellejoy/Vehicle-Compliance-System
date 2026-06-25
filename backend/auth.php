<?php
// backend/auth.php
session_start();

// 1. Import database connection
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    
    try {
        // 2. Exact match check
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email AND is_active = 1 LIMIT 1");
        $stmt->execute([
            'email' => $email,
        ]);
        
        $user = $stmt->fetch();
        
        if ($user) {
            if (empty($user['password_hash'])) {
                header("Location: /login?error=Account has no password set. Contact an administrator.");
                exit;
            }

            if (!password_verify($password, $user['password_hash'])) {
                header("Location: /login?error=Invalid email or password.");
                exit;
            }

            session_regenerate_id(true);

            // 3. Save details safely to session string variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role']; 
            
            // 4. Redirect smoothly based on role
            if ($user['role'] === 'admin') {
                header("Location: ../views/admin_panel.php");
            } elseif ($user['role'] === 'officer') {
                header("Location: ../views/officer_dashboard.php");
            } else {
                header("Location: ../views/citizen_portal.php");
            }
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
