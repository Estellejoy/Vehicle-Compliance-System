<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: /login');
    exit;
}

require_once '../config/db.php';

function admin_flash(string $type, string $message, array $extra = []): void
{
    $_SESSION['admin_flash'] = array_merge([
        'type' => $type,
        'message' => $message,
    ], $extra);
}

function redirect_admin(): void
{
    header('Location: /views/admin_panel.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_admin();
}

$action = (string) ($_POST['action'] ?? '');
$targetUserId = (int) ($_POST['user_id'] ?? 0);
$currentAdminId = (int) $_SESSION['user_id'];

try {
    if ($action === 'create_user') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $role = trim((string) ($_POST['role'] ?? 'owner'));
        $staffId = trim((string) ($_POST['staff_id'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($name === '' || $email === '' || $password === '') {
            admin_flash('warning', 'Name, email, and password are required to create an account.');
            redirect_admin();
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            admin_flash('warning', 'Enter a valid email address.');
            redirect_admin();
        }

        if (strlen($password) < 8) {
            admin_flash('warning', 'Use at least 8 characters for the password.');
            redirect_admin();
        }

        $allowedRoles = ['admin', 'officer', 'owner'];
        if (!in_array($role, $allowedRoles, true)) {
            admin_flash('warning', 'Select a valid role.');
            redirect_admin();
        }

        if ($role !== 'officer') {
            $staffId = null;
        } elseif ($staffId === '') {
            $staffId = 'OFF-' . strtoupper(bin2hex(random_bytes(2)));
        }

        if ($role === 'officer' && $staffId !== null) {
            $staffExists = $pdo->prepare('SELECT user_id FROM users WHERE staff_id = :staff_id LIMIT 1');
            $staffExists->execute(['staff_id' => $staffId]);
            if ($staffExists->fetch()) {
                admin_flash('warning', 'That officer staff ID is already in use.');
                redirect_admin();
            }
        }

        $exists = $pdo->prepare('SELECT user_id FROM users WHERE email = :email LIMIT 1');
        $exists->execute(['email' => $email]);
        if ($exists->fetch()) {
            admin_flash('warning', 'A user already exists with that email address.');
            redirect_admin();
        }

        $insert = $pdo->prepare(
            "INSERT INTO users (
                name,
                email,
                role,
                staff_id,
                password_hash,
                email_verified_at,
                is_active
            ) VALUES (
                :name,
                :email,
                :role,
                :staff_id,
                :password_hash,
                NOW(),
                1
            )"
        );
        $insert->execute([
            'name' => $name,
            'email' => $email,
            'role' => $role,
            'staff_id' => $staffId,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ]);

        admin_flash('success', 'Account created successfully.', [
            'generated_password' => $password,
        ]);
        redirect_admin();
    }

    if ($targetUserId <= 0) {
        admin_flash('warning', 'Select a user first.');
        redirect_admin();
    }

    if ($targetUserId === $currentAdminId && in_array($action, ['toggle_status', 'delete_user', 'update_role', 'reset_password'], true)) {
        admin_flash('warning', 'You cannot change your own account from this screen.');
        redirect_admin();
    }

    if ($action === 'toggle_status') {
        $nextStatus = (int) ($_POST['is_active'] ?? 0) === 1 ? 0 : 1;

        $stmt = $pdo->prepare('SELECT role FROM users WHERE user_id = :user_id LIMIT 1');
        $stmt->execute(['user_id' => $targetUserId]);
        $target = $stmt->fetch();

        if (!$target) {
            admin_flash('warning', 'That account could not be found.');
            redirect_admin();
        }

        if (($target['role'] ?? '') === 'admin') {
            admin_flash('warning', 'Admin accounts cannot be deactivated from this screen.');
            redirect_admin();
        }

        $update = $pdo->prepare('UPDATE users SET is_active = :is_active WHERE user_id = :user_id');
        $update->execute([
            'is_active' => $nextStatus,
            'user_id' => $targetUserId,
        ]);

        admin_flash('success', $nextStatus === 1 ? 'Account reactivated.' : 'Account deactivated.');
        redirect_admin();
    }

    if ($action === 'delete_user') {
        $stmt = $pdo->prepare('SELECT role FROM users WHERE user_id = :user_id LIMIT 1');
        $stmt->execute(['user_id' => $targetUserId]);
        $target = $stmt->fetch();

        if (!$target) {
            admin_flash('warning', 'That account could not be found.');
            redirect_admin();
        }

        if (($target['role'] ?? '') === 'admin') {
            admin_flash('warning', 'Admin accounts cannot be removed from this screen.');
            redirect_admin();
        }

        $delete = $pdo->prepare('DELETE FROM users WHERE user_id = :user_id');
        $delete->execute(['user_id' => $targetUserId]);

        admin_flash('success', 'User removed from the system.');
        redirect_admin();
    }

    if ($action === 'update_role') {
        $role = trim((string) ($_POST['role'] ?? ''));
        $staffId = trim((string) ($_POST['staff_id'] ?? ''));
        $allowedRoles = ['admin', 'officer', 'owner'];

        if (!in_array($role, $allowedRoles, true)) {
            admin_flash('warning', 'Select a valid role.');
            redirect_admin();
        }

        if ($role !== 'officer') {
            $staffId = null;
        } elseif ($staffId === '') {
            $staffId = 'OFF-' . strtoupper(bin2hex(random_bytes(2)));
        }

        if ($role === 'officer' && $staffId !== null) {
            $staffExists = $pdo->prepare('SELECT user_id FROM users WHERE staff_id = :staff_id AND user_id <> :user_id LIMIT 1');
            $staffExists->execute([
                'staff_id' => $staffId,
                'user_id' => $targetUserId,
            ]);
            if ($staffExists->fetch()) {
                admin_flash('warning', 'That officer staff ID is already in use.');
                redirect_admin();
            }
        }

        if ($role !== 'admin') {
            $stmt = $pdo->prepare('SELECT role FROM users WHERE user_id = :user_id LIMIT 1');
            $stmt->execute(['user_id' => $targetUserId]);
            $target = $stmt->fetch();
            if (($target['role'] ?? '') === 'admin') {
                admin_flash('warning', 'Admin accounts must remain admin accounts.');
                redirect_admin();
            }
        }

        $update = $pdo->prepare(
            'UPDATE users
             SET role = :role, staff_id = :staff_id
             WHERE user_id = :user_id'
        );
        $update->execute([
            'role' => $role,
            'staff_id' => $staffId,
            'user_id' => $targetUserId,
        ]);

        admin_flash('success', 'User role updated successfully.');
        redirect_admin();
    }

    if ($action === 'reset_password') {
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        if ($newPassword === '') {
            $newPassword = 'Temp@' . strtoupper(bin2hex(random_bytes(3)));
        }

        if (strlen($newPassword) < 8) {
            admin_flash('warning', 'Use at least 8 characters for the new password.');
            redirect_admin();
        }

        if ($confirmPassword !== '' && !hash_equals($newPassword, $confirmPassword)) {
            admin_flash('warning', 'Password confirmation does not match.');
            redirect_admin();
        }

        $update = $pdo->prepare(
            'UPDATE users
             SET password_hash = :password_hash,
                 email_verified_at = COALESCE(email_verified_at, NOW())
             WHERE user_id = :user_id'
        );
        $update->execute([
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
            'user_id' => $targetUserId,
        ]);

        admin_flash('success', 'Password reset successfully.', [
            'generated_password' => $newPassword,
        ]);
        redirect_admin();
    }

    admin_flash('warning', 'Unknown admin action.');
    redirect_admin();
} catch (PDOException $e) {
    error_log($e->getMessage());
    admin_flash('danger', 'Database error while updating the account.');
    redirect_admin();
}
