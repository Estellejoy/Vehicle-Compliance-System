<?php
session_start();

// Load one vehicle record for viewing or printing by an authorized user.
$role = $_SESSION['role'] ?? '';
if (!isset($_SESSION['user_id']) || !in_array($role, ['admin', 'officer', 'owner'], true)) {
    header('Location: /login');
    exit;
}

require_once '../config/db.php';

$vehicleId = (int) ($_GET['vehicle_id'] ?? 0);
$usersStaffIdEnabled = false;
$vehicle = null;

if ($vehicleId <= 0) {
    header('Location: ' . ($role === 'officer' ? '/views/officer_dashboard.php' : '/views/citizen_portal.php'));
    exit;
}

try {
    $usersStaffIdEnabled = (bool) $pdo->query("SHOW COLUMNS FROM users LIKE 'staff_id'")->fetch();
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
            checker.name AS inspection_checked_by_name" . ($usersStaffIdEnabled ? ",
            checker.staff_id AS inspection_checked_by_staff_id" : "") . ",
            s.service_details,
            s.service_report_name,
            s.service_report_path,
            s.last_service_date,
            s.next_service_date,
            uploader.name AS service_uploaded_by_name" . ($usersStaffIdEnabled ? ",
            uploader.staff_id AS service_uploaded_by_staff_id" : "") . ",
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
} catch (PDOException $e) {
    error_log($e->getMessage());
    header('Location: ' . ($role === 'officer' ? '/views/officer_dashboard.php' : '/views/citizen_portal.php'));
    exit;
}

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicle Report - VCS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            background: #f5f7f9;
        }
        .report-shell {
            max-width: 1100px;
        }
        .report-card {
            border: 1px solid rgba(0,0,0,.08);
            border-radius: 1rem;
            background: #fff;
            box-shadow: 0 12px 30px rgba(0,0,0,.06);
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: #fff;
            }
            .report-card {
                box-shadow: none;
                border: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container report-shell py-4 py-md-5">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4 no-print">
            <div>
                <div class="text-uppercase text-success fw-semibold small">Vehicle Compliance System</div>
                <h1 class="h3 fw-bold mb-0">Vehicle Report</h1>
            </div>
            <div class="d-flex gap-2">
                <a href="<?php echo h($role === 'officer' ? '/views/officer_dashboard.php' : '/views/citizen_portal.php'); ?>" class="btn btn-outline-secondary">
                    Back
                </a>
                <button type="button" class="btn btn-success" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i> Print
                </button>
            </div>
        </div>

        <div class="report-card p-4 p-md-5">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="text-uppercase text-secondary small fw-semibold">Vehicle</div>
                    <h2 class="h4 fw-bold mt-2 mb-1"><?php echo h($vehicle['plate_number']); ?></h2>
                    <div class="text-secondary"><?php echo h($vehicle['make']); ?> <?php echo h($vehicle['model']); ?></div>
                    <hr>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="text-secondary small">Year</div>
                            <div class="fw-semibold"><?php echo h($vehicle['year']); ?></div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-secondary small">Vehicle ID</div>
                            <div class="fw-semibold"><?php echo h($vehicle['vehicle_id']); ?></div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-secondary small">Owner</div>
                            <div class="fw-semibold"><?php echo h($vehicle['owner_name']); ?></div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-secondary small">Owner Email</div>
                            <div class="fw-semibold"><?php echo h($vehicle['owner_email']); ?></div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="text-uppercase text-secondary small fw-semibold">Inspection</div>
                    <div class="mt-2 d-flex flex-wrap gap-2">
                        <span class="badge bg-success px-3 py-2">Status: <?php echo h($vehicle['inspection_status'] ?? 'Pending Police Check'); ?></span>
                    </div>
                    <hr>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="text-secondary small">Checked At</div>
                            <div class="fw-semibold"><?php echo h($vehicle['inspection_checked_at'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-secondary small">Checked By</div>
                            <div class="fw-semibold"><?php echo h($vehicle['inspection_checked_by_name'] ?? 'Officer not recorded'); ?></div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-secondary small">Officer Badge</div>
                            <div class="fw-semibold"><?php echo h($vehicle['inspection_checked_by_staff_id'] ?? 'N/A'); ?></div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="text-uppercase text-secondary small fw-semibold">Compliance</div>
                    <div class="row g-3 mt-1">
                        <div class="col-md-4">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="text-secondary small">Insurance</div>
                                <div class="fw-semibold"><?php echo h($vehicle['insurance_status'] ?? 'N/A'); ?></div>
                                <div class="text-secondary small mt-2">Expiry</div>
                                <div class="fw-semibold"><?php echo h($vehicle['insurance_expiry'] ?? 'N/A'); ?></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="text-secondary small">Licence</div>
                                <div class="fw-semibold"><?php echo h($vehicle['licence_status'] ?? 'N/A'); ?></div>
                                <div class="text-secondary small mt-2">Expiry</div>
                                <div class="fw-semibold"><?php echo h($vehicle['licence_expiry'] ?? 'N/A'); ?></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="text-secondary small">Registration</div>
                                <div class="fw-semibold"><?php echo h($vehicle['registration_status'] ?? 'N/A'); ?></div>
                                <div class="text-secondary small mt-2">Expiry</div>
                                <div class="fw-semibold"><?php echo h($vehicle['registration_expiry'] ?? 'N/A'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="text-uppercase text-secondary small fw-semibold">Service</div>
                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="text-secondary small">Service Details</div>
                                <div class="fw-semibold"><?php echo h($vehicle['service_details'] ?? 'N/A'); ?></div>
                                <div class="text-secondary small mt-2">Last Service Date</div>
                                <div class="fw-semibold"><?php echo h($vehicle['last_service_date'] ?? 'N/A'); ?></div>
                                <div class="text-secondary small mt-2">Next Service Date</div>
                                <div class="fw-semibold"><?php echo h($vehicle['next_service_date'] ?? 'N/A'); ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="text-secondary small">Uploaded By</div>
                                <div class="fw-semibold"><?php echo h($vehicle['service_uploaded_by_name'] ?? 'N/A'); ?></div>
                                <div class="text-secondary small mt-2">Officer Badge</div>
                                <div class="fw-semibold"><?php echo h($vehicle['service_uploaded_by_staff_id'] ?? 'N/A'); ?></div>
                                <div class="text-secondary small mt-2">Uploaded At</div>
                                <div class="fw-semibold"><?php echo h($vehicle['uploaded_at'] ?? 'N/A'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
