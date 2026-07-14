<?php
session_start();

// Build a printable report containing the owner's fleet and inspection details.
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'owner') {
    header('Location: /login');
    exit;
}

require_once '../config/db.php';

$ownerId = (int) $_SESSION['user_id'];
$vehicles = [];

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

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
            checker.badge_number AS inspection_checked_by_badge" . ($usersStaffIdEnabled ? ",
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
} catch (PDOException $e) {
    error_log($e->getMessage());
    header('Location: /views/citizen_portal.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicle Summary Report - VCS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            background: #f5f7f9;
        }
        .report-shell {
            max-width: 1200px;
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
                <h1 class="h3 fw-bold mb-0">Vehicle Summary Report</h1>
            </div>
            <div class="d-flex gap-2">
                <a href="/views/citizen_portal.php" class="btn btn-outline-secondary">Back</a>
                <button type="button" class="btn btn-success" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i> Print
                </button>
            </div>
        </div>

        <div class="report-card p-4 p-md-5 mb-4">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="border rounded-3 p-3 h-100">
                        <div class="text-secondary small text-uppercase">Vehicles</div>
                        <div class="display-6 fw-bold text-success"><?php echo count($vehicles); ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border rounded-3 p-3 h-100">
                        <div class="text-secondary small text-uppercase">Checked</div>
                        <div class="display-6 fw-bold text-success">
                            <?php echo count(array_filter($vehicles, static fn ($vehicle) => strtolower(trim((string) ($vehicle['inspection_status'] ?? ''))) === 'checked')); ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border rounded-3 p-3 h-100">
                        <div class="text-secondary small text-uppercase">Pending</div>
                        <div class="display-6 fw-bold text-warning">
                            <?php echo count($vehicles) - count(array_filter($vehicles, static fn ($vehicle) => strtolower(trim((string) ($vehicle['inspection_status'] ?? ''))) === 'checked')); ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border rounded-3 p-3 h-100">
                        <div class="text-secondary small text-uppercase">Owner</div>
                        <div class="fw-semibold mt-2"><?php echo h($_SESSION['name'] ?? 'Citizen'); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="report-card p-4 p-md-5">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Plate</th>
                            <th>Make &amp; Model</th>
                            <th>Inspection</th>
                            <th>Checked By</th>
                            <th>Service</th>
                            <th>Last Service</th>
                            <th>Next Service</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vehicles as $vehicle): ?>
                            <tr>
                                <td class="fw-semibold"><?php echo h($vehicle['plate_number']); ?></td>
                                <td><?php echo h($vehicle['make'] . ' ' . $vehicle['model']); ?></td>
                                <td>
                                    <div class="fw-semibold"><?php echo h($vehicle['inspection_status'] ?? 'Pending Police Check'); ?></div>
                                    <div class="text-secondary small"><?php echo h($vehicle['inspection_checked_at'] ?? 'Not checked yet'); ?></div>
                                </td>
                                <td>
                                    <div class="fw-semibold"><?php echo h($vehicle['inspection_checked_by_badge'] ?? 'N/A'); ?></div>
                                </td>
                                <td><?php echo h($vehicle['service_details'] ?? 'N/A'); ?></td>
                                <td><?php echo h($vehicle['last_service_date'] ?? 'N/A'); ?></td>
                                <td><?php echo h($vehicle['next_service_date'] ?? 'N/A'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
