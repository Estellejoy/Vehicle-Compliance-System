<?php
session_start();

$role = $_SESSION['role'] ?? '';
if (!isset($_SESSION['user_id']) || !in_array($role, ['admin', 'officer', 'owner'], true)) {
    header('Location: /login');
    exit;
}

require_once '../config/db.php';

$vehicleId = (int) ($_GET['vehicle_id'] ?? 0);

if ($vehicleId <= 0) {
    header('Location: ' . ($role === 'officer' ? '/views/officer_dashboard.php' : '/views/citizen_portal.php'));
    exit;
}

try {
    $params = ['vehicle_id' => $vehicleId];
    $ownerFilter = '';

    if ($role === 'owner') {
        $ownerFilter = ' AND v.owner_id = :owner_id';
        $params['owner_id'] = (int) $_SESSION['user_id'];
    }

    $stmt = $pdo->prepare(
        "SELECT
            v.vehicle_id,
            v.plate_number,
            v.make,
            v.model,
            v.year,
            u.name AS owner_name,
            u.email AS owner_email,
            u.role AS owner_role,
            c.insurance_expiry,
            c.insurance_status,
            c.licence_expiry,
            c.licence_status,
            c.registration_expiry,
            c.registration_status,
            v.inspection_status,
            v.inspection_checked_at,
            checker.name AS inspection_checked_by_name,
            checker.staff_id AS inspection_checked_by_staff_id,
            s.service_details,
            s.service_report_name,
            s.service_report_path,
            s.last_service_date,
            s.next_service_date,
            uploader.name AS service_uploaded_by_name,
            uploader.staff_id AS service_uploaded_by_staff_id,
            s.uploaded_at
        FROM vehicles v
        INNER JOIN users u ON u.user_id = v.owner_id
        LEFT JOIN compliance_records c ON c.vehicle_id = v.vehicle_id
        LEFT JOIN service_records s ON s.vehicle_id = v.vehicle_id
        LEFT JOIN users checker ON checker.user_id = v.inspection_checked_by
        LEFT JOIN users uploader ON uploader.user_id = s.uploaded_by
        WHERE v.vehicle_id = :vehicle_id{$ownerFilter}
        LIMIT 1"
    );
    $stmt->execute($params);
    $vehicle = $stmt->fetch();

    if (!$vehicle) {
        header('Location: ' . ($role === 'officer' ? '/views/officer_dashboard.php' : '/views/citizen_portal.php'));
        exit;
    }

    $filename = preg_replace('/[^A-Za-z0-9_-]+/', '_', (string) $vehicle['plate_number']);
    $filename = trim($filename, '_');
    if ($filename === '') {
        $filename = 'vehicle_' . $vehicleId;
    }
    $filename .= '_record.csv';

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'wb');
    fputcsv($output, ['Field', 'Value']);

    $rows = [
        ['Vehicle ID', $vehicle['vehicle_id']],
        ['Plate Number', $vehicle['plate_number']],
        ['Make', $vehicle['make']],
        ['Model', $vehicle['model']],
        ['Year', $vehicle['year']],
        ['Owner Name', $vehicle['owner_name']],
        ['Owner Email', $vehicle['owner_email']],
        ['Owner Role', $vehicle['owner_role']],
        ['Insurance Status', $vehicle['insurance_status'] ?? 'N/A'],
        ['Insurance Expiry', $vehicle['insurance_expiry'] ?? 'N/A'],
        ['Licence Status', $vehicle['licence_status'] ?? 'N/A'],
        ['Licence Expiry', $vehicle['licence_expiry'] ?? 'N/A'],
        ['Registration Status', $vehicle['registration_status'] ?? 'N/A'],
        ['Registration Expiry', $vehicle['registration_expiry'] ?? 'N/A'],
        ['Inspection Status', $vehicle['inspection_status'] ?? 'Pending Police Check'],
        ['Inspection Checked At', $vehicle['inspection_checked_at'] ?? 'N/A'],
        ['Inspection Checked By', $vehicle['inspection_checked_by_name'] ?? 'Officer not recorded'],
        ['Inspector / Officer Staff ID', $vehicle['inspection_checked_by_staff_id'] ?? 'N/A'],
        ['Service Details', $vehicle['service_details'] ?? 'N/A'],
        ['Service Report Name', $vehicle['service_report_name'] ?? 'N/A'],
        ['Service Report Path', $vehicle['service_report_path'] ?? 'N/A'],
        ['Last Service Date', $vehicle['last_service_date'] ?? 'N/A'],
        ['Next Service Date', $vehicle['next_service_date'] ?? 'N/A'],
        ['Service Uploaded By', $vehicle['service_uploaded_by_name'] ?? 'N/A'],
        ['Service Uploaded By Staff ID', $vehicle['service_uploaded_by_staff_id'] ?? 'N/A'],
        ['Service Uploaded At', $vehicle['uploaded_at'] ?? 'N/A'],
    ];

    foreach ($rows as $row) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit;
} catch (PDOException $e) {
    error_log($e->getMessage());
    header('Location: ' . ($role === 'officer' ? '/views/officer_dashboard.php' : '/views/citizen_portal.php'));
    exit;
}
