<?php
// backend/auth.php
session_start();

// 1. Import database connection
require_once '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['username']);
    $selected_role = trim($_POST['role']);
    
    try {
        // 2. Exact match check
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email AND role = :role LIMIT 1");
        $stmt->execute([
            'email' => $email,
            'role'  => $selected_role
        ]);
        
        $user = $stmt->fetch();
        
        if ($user) {
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
            header("Location: ../views/login.php?error=Invalid email or role selection.");
            exit;
        }
        
    } catch (\PDOException $e) {
        header("Location: ../views/login.php?error=Database_Error");
        exit;
    }
}
?>