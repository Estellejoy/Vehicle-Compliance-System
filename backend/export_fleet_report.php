<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'owner') {
    header('Location: /login');
    exit;
}

require_once '../config/db.php';

$ownerId = (int) $_SESSION['user_id'];
$usersStaffIdEnabled = false;

try {
    $usersStaffIdEnabled = (bool) $pdo->query("SHOW COLUMNS FROM users LIKE 'staff_id'")->fetch();
    $stmt = $pdo->prepare(
        "SELECT
            v.vehicle_id,
            v.plate_number,
            v.make,
            v.model,
            v.year,
            v.inspection_status,
            v.inspection_checked_at,
            checker.name AS inspection_checked_by_name" . ($usersStaffIdEnabled ? ",
            checker.staff_id AS inspection_checked_by_staff_id" : "") . ",
            c.insurance_status,
            c.insurance_expiry,
            c.licence_status,
            c.licence_expiry,
            c.registration_status,
            c.registration_expiry,
            s.service_details,
            s.service_report_name,
            s.last_service_date,
            s.next_service_date
        FROM vehicles v
        LEFT JOIN compliance_records c ON c.vehicle_id = v.vehicle_id
        LEFT JOIN service_records s ON s.vehicle_id = v.vehicle_id
        LEFT JOIN users checker ON checker.user_id = v.inspection_checked_by
        WHERE v.owner_id = :owner_id
        ORDER BY v.vehicle_id ASC"
    );
    $stmt->execute(['owner_id' => $ownerId]);
    $vehicles = $stmt->fetchAll();

    $ownerName = (string) ($_SESSION['name'] ?? 'Citizen');
    $safeName = preg_replace('/[^A-Za-z0-9_-]+/', '_', trim($ownerName));
    $safeName = trim($safeName, '_');
    if ($safeName === '') {
        $safeName = 'citizen';
    }

    $filename = $safeName . '_fleet_report_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'wb');
    fputcsv($output, ['Fleet Report', $ownerName]);
    fputcsv($output, ['Generated At', date('Y-m-d H:i:s')]);
    fputcsv($output, []);
    fputcsv($output, ['Plate Number', 'Make', 'Model', 'Year', 'Inspection Status', 'Checked At', 'Checked By', 'Inspector / Officer Staff ID', 'Insurance Status', 'Insurance Expiry', 'Licence Status', 'Licence Expiry', 'Registration Status', 'Registration Expiry', 'Service Details', 'Service Report Name', 'Last Service Date', 'Next Service Date']);

    foreach ($vehicles as $vehicle) {
        fputcsv($output, [
            $vehicle['plate_number'],
            $vehicle['make'],
            $vehicle['model'],
            $vehicle['year'],
            $vehicle['inspection_status'] ?? 'Pending Police Check',
            $vehicle['inspection_checked_at'] ?? 'N/A',
            $vehicle['inspection_checked_by_name'] ?? 'Officer not recorded',
            $vehicle['inspection_checked_by_staff_id'] ?? 'N/A',
            $vehicle['insurance_status'] ?? 'N/A',
            $vehicle['insurance_expiry'] ?? 'N/A',
            $vehicle['licence_status'] ?? 'N/A',
            $vehicle['licence_expiry'] ?? 'N/A',
            $vehicle['registration_status'] ?? 'N/A',
            $vehicle['registration_expiry'] ?? 'N/A',
            $vehicle['service_details'] ?? 'N/A',
            $vehicle['service_report_name'] ?? 'N/A',
            $vehicle['last_service_date'] ?? 'N/A',
            $vehicle['next_service_date'] ?? 'N/A',
        ]);
    }

    fclose($output);
    exit;
} catch (PDOException $e) {
    error_log($e->getMessage());
    header('Location: /views/citizen_portal.php');
    exit;
}
