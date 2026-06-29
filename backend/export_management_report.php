<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: /login');
    exit;
}

require_once '../config/db.php';

$year = (int) ($_GET['year'] ?? date('Y'));
if ($year < 2000 || $year > 2100) {
    $year = (int) date('Y');
}

try {
    $summary = [
        'year' => $year,
        'total_users' => (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
        'active_users' => (int) $pdo->query('SELECT COUNT(*) FROM users WHERE COALESCE(is_active, 1) = 1')->fetchColumn(),
        'inactive_users' => (int) $pdo->query('SELECT COUNT(*) FROM users WHERE COALESCE(is_active, 1) = 0')->fetchColumn(),
        'admins' => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn(),
        'officers' => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'officer'")->fetchColumn(),
        'owners' => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'owner'")->fetchColumn(),
        'total_vehicles' => (int) $pdo->query('SELECT COUNT(*) FROM vehicles')->fetchColumn(),
        'compliance_valid' => (int) $pdo->query("SELECT COUNT(*) FROM compliance_records WHERE insurance_status = 'Valid' AND licence_status = 'Valid' AND registration_status = 'Valid'")->fetchColumn(),
        'compliance_expired' => (int) $pdo->query("SELECT COUNT(*) FROM compliance_records WHERE insurance_status = 'Expired' OR licence_status = 'Expired' OR registration_status = 'Expired'")->fetchColumn(),
        'service_records' => (int) $pdo->query('SELECT COUNT(*) FROM service_records')->fetchColumn(),
        'inspections_checked' => (int) $pdo->query("SELECT COUNT(*) FROM vehicles WHERE inspection_status = 'Checked'")->fetchColumn(),
        'inspections_pending' => (int) $pdo->query("SELECT COUNT(*) FROM vehicles WHERE inspection_status <> 'Checked'")->fetchColumn(),
    ];

    $monthly = $pdo->prepare(
        "SELECT
            MONTH(insurance_expiry) AS month_no,
            COUNT(*) AS total_records,
            SUM(CASE WHEN insurance_status = 'Valid' THEN 1 ELSE 0 END) AS valid_count,
            SUM(CASE WHEN insurance_status = 'Expired' THEN 1 ELSE 0 END) AS expired_count
        FROM compliance_records
        WHERE YEAR(insurance_expiry) = :report_year
        GROUP BY MONTH(insurance_expiry)
        ORDER BY MONTH(insurance_expiry)"
    );
    $monthly->execute(['report_year' => $year]);
    $monthlyRows = $monthly->fetchAll();

    $filename = 'management_report_' . $year . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'wb');
    fputcsv($output, ['Management Report', $year]);
    fputcsv($output, []);
    fputcsv($output, ['Metric', 'Value']);

    foreach ($summary as $label => $value) {
        if ($label === 'year') {
            continue;
        }
        fputcsv($output, [str_replace('_', ' ', ucwords($label, '_')), $value]);
    }

    fputcsv($output, []);
    fputcsv($output, ['Monthly Insurance Expiry Summary', $year]);
    fputcsv($output, ['Month', 'Total Records', 'Valid', 'Expired']);

    $monthNames = [1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'];
    foreach ($monthlyRows as $row) {
        $monthNo = (int) $row['month_no'];
        fputcsv($output, [
            $monthNames[$monthNo] ?? (string) $monthNo,
            $row['total_records'],
            $row['valid_count'],
            $row['expired_count'],
        ]);
    }

    fclose($output);
    exit;
} catch (PDOException $e) {
    error_log($e->getMessage());
    header('Location: /views/admin_panel.php');
    exit;
}
