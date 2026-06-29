<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'officer') {
    header('Location: /login');
    exit;
}

require_once '../config/db.php';

function officer_redirect(array $query = []): void
{
    $suffix = $query ? ('?' . http_build_query($query)) : '';
    header('Location: /views/officer_dashboard.php' . $suffix);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    officer_redirect();
}

$vehicleId = (int) ($_POST['vehicle_id'] ?? 0);
$plateNumber = trim((string) ($_POST['plate_number'] ?? ''));
$serviceDetails = trim((string) ($_POST['service_details'] ?? ''));
$lastServiceDate = trim((string) ($_POST['last_service_date'] ?? ''));
$nextServiceDate = trim((string) ($_POST['next_service_date'] ?? ''));
$allowedExtensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'txt'];
$maxUploadSize = 5 * 1024 * 1024;

if ($vehicleId <= 0 || $serviceDetails === '' || $lastServiceDate === '' || $nextServiceDate === '') {
    officer_redirect([
        'plate_number' => $plateNumber,
        'error' => 'missing_service_fields',
    ]);
}

if (!isset($_FILES['service_report']) || ($_FILES['service_report']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    officer_redirect([
        'plate_number' => $plateNumber,
        'error' => 'missing_service_file',
    ]);
}

$uploadName = basename((string) $_FILES['service_report']['name']);
$uploadSize = (int) ($_FILES['service_report']['size'] ?? 0);
$extension = strtolower(pathinfo($uploadName, PATHINFO_EXTENSION));

if ($uploadName === '' || !in_array($extension, $allowedExtensions, true)) {
    officer_redirect([
        'plate_number' => $plateNumber,
        'error' => 'invalid_service_file',
    ]);
}

if ($uploadSize <= 0 || $uploadSize > $maxUploadSize) {
    officer_redirect([
        'plate_number' => $plateNumber,
        'error' => 'service_file_too_large',
    ]);
}

try {
    $statusColumns = [
        $pdo->query("SHOW COLUMNS FROM service_records LIKE 'service_report_path'")->fetch(),
        $pdo->query("SHOW COLUMNS FROM service_records LIKE 'service_report_name'")->fetch(),
        $pdo->query("SHOW COLUMNS FROM service_records LIKE 'uploaded_by'")->fetch(),
        $pdo->query("SHOW COLUMNS FROM service_records LIKE 'uploaded_at'")->fetch(),
    ];

    foreach ($statusColumns as $column) {
        if (!$column) {
            officer_redirect([
                'plate_number' => $plateNumber,
                'error' => 'service_columns_missing',
            ]);
        }
    }

    $uploadDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'service-reports';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
        throw new RuntimeException('Unable to create upload directory.');
    }

    $originalName = $uploadName;
    $safeName = preg_replace('/[^A-Za-z0-9_.-]+/', '_', pathinfo($originalName, PATHINFO_FILENAME));
    $storedName = sprintf(
        '%s_%s.%s',
        $safeName !== '' ? $safeName : 'service_report',
        date('Ymd_His'),
        $extension !== '' ? $extension : 'dat'
    );
    $storedPath = $uploadDir . DIRECTORY_SEPARATOR . $storedName;

    if (!move_uploaded_file($_FILES['service_report']['tmp_name'], $storedPath)) {
        throw new RuntimeException('Failed to store uploaded file.');
    }

    $webPath = '/assets/uploads/service-reports/' . rawurlencode($storedName);
    $now = date('Y-m-d H:i:s');

    $existing = $pdo->prepare('SELECT service_id FROM service_records WHERE vehicle_id = :vehicle_id ORDER BY service_id DESC LIMIT 1');
    $existing->execute(['vehicle_id' => $vehicleId]);
    $row = $existing->fetch();

    if ($row) {
        $update = $pdo->prepare(
            "UPDATE service_records
             SET service_details = :service_details,
                 service_report_path = :service_report_path,
                 service_report_name = :service_report_name,
                 last_service_date = :last_service_date,
                 next_service_date = :next_service_date,
                 uploaded_by = :uploaded_by,
                 uploaded_at = :uploaded_at
             WHERE service_id = :service_id"
        );
        $update->execute([
            'service_details' => $serviceDetails,
            'service_report_path' => $webPath,
            'service_report_name' => $originalName,
            'last_service_date' => $lastServiceDate,
            'next_service_date' => $nextServiceDate,
            'uploaded_by' => (int) $_SESSION['user_id'],
            'uploaded_at' => $now,
            'service_id' => $row['service_id'],
        ]);
    } else {
        $insert = $pdo->prepare(
            "INSERT INTO service_records (
                vehicle_id,
                service_details,
                service_report_path,
                service_report_name,
                last_service_date,
                next_service_date,
                uploaded_by,
                uploaded_at
            ) VALUES (
                :vehicle_id,
                :service_details,
                :service_report_path,
                :service_report_name,
                :last_service_date,
                :next_service_date,
                :uploaded_by,
                :uploaded_at
            )"
        );
        $insert->execute([
            'vehicle_id' => $vehicleId,
            'service_details' => $serviceDetails,
            'service_report_path' => $webPath,
            'service_report_name' => $originalName,
            'last_service_date' => $lastServiceDate,
            'next_service_date' => $nextServiceDate,
            'uploaded_by' => (int) $_SESSION['user_id'],
            'uploaded_at' => $now,
        ]);
    }

    officer_redirect([
        'plate_number' => $plateNumber,
        'updated' => 'service_uploaded',
    ]);
} catch (Throwable $e) {
    error_log($e->getMessage());
    officer_redirect([
        'plate_number' => $plateNumber,
        'error' => 'service_upload_failed',
    ]);
}
