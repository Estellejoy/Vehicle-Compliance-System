<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'officer') {
    header('Location: ../views/login.php');
    exit;
}

require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/officer_dashboard.php');
    exit;
}

$vehicleId = (int) ($_POST['vehicle_id'] ?? 0);
$plateNumber = trim($_POST['plate_number'] ?? '');
$action = trim($_POST['action'] ?? '');
$checkedBy = (int) ($_SESSION['user_id'] ?? 0);

if ($vehicleId <= 0 || $action !== 'mark_inspected') {
    header('Location: ../views/officer_dashboard.php?error=invalid_request');
    exit;
}

try {
    $statusColumn = $pdo->query("SHOW COLUMNS FROM vehicles LIKE 'inspection_status'")->fetch();
    $checkedAtColumn = $pdo->query("SHOW COLUMNS FROM vehicles LIKE 'inspection_checked_at'")->fetch();
    $checkedByColumn = $pdo->query("SHOW COLUMNS FROM vehicles LIKE 'inspection_checked_by'")->fetch();

    if (!$statusColumn || !$checkedAtColumn || !$checkedByColumn) {
        $query = http_build_query([
            'plate_number' => $plateNumber,
            'error' => 'inspection_columns_missing',
        ]);
        header('Location: ../views/officer_dashboard.php?' . $query);
        exit;
    }

    $stmt = $pdo->prepare(
        "UPDATE vehicles
         SET inspection_status = 'Checked',
             inspection_checked_at = :checked_at,
             inspection_checked_by = :checked_by
         WHERE vehicle_id = :vehicle_id"
    );
    $stmt->execute([
        'vehicle_id' => $vehicleId,
        'checked_at' => date('Y-m-d H:i:s'),
        'checked_by' => $checkedBy > 0 ? $checkedBy : null,
    ]);

    $query = http_build_query([
        'plate_number' => $plateNumber,
        'updated' => '1',
    ]);

    header('Location: ../views/officer_dashboard.php?' . $query);
    exit;
} catch (PDOException $e) {
    error_log($e->getMessage());
    $query = http_build_query([
        'plate_number' => $plateNumber,
        'error' => 'update_failed',
    ]);
    header('Location: ../views/officer_dashboard.php?' . $query);
    exit;
}
