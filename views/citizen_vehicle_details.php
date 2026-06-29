<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'owner') {
    header('Location: /login');
    exit;
}

require_once '../config/db.php';

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function inspectionBadgeClass($status): string
{
    $normalized = strtolower(trim((string) $status));

    if ($normalized === 'checked') {
        return 'bg-success-subtle text-success border border-success border-opacity-25';
    }

    if ($normalized === 'pending police check') {
        return 'bg-warning-subtle text-warning border border-warning border-opacity-25';
    }

    return 'bg-secondary-subtle text-secondary border border-secondary border-opacity-25';
}

$userId = (int) $_SESSION['user_id'];
$vehicleId = (int) ($_GET['vehicle_id'] ?? 0);
$vehicle = null;
$message = null;
$messageType = 'info';

$dashboardUrl = '/views/citizen_portal.php';

if ($vehicleId <= 0) {
    $message = 'Select a vehicle first.';
    $messageType = 'warning';
} else {
    try {
        $stmt = $pdo->prepare(
            "SELECT
                u.user_id,
                u.name AS owner_name,
                u.email AS owner_email,
                u.role AS owner_role,
                v.vehicle_id,
                v.plate_number,
                v.make,
                v.model,
                v.year,
                v.inspection_status,
                v.inspection_checked_at,
                v.inspection_checked_by,
                checker.name AS inspection_checked_by_name,
                checker.staff_id AS inspection_checked_by_staff_id,
                v.created_at AS vehicle_created_at,
                c.insurance_expiry,
                c.insurance_status,
                c.licence_expiry,
                c.licence_status,
                c.registration_expiry,
                c.registration_status,
                s.service_details,
                s.last_service_date,
                s.next_service_date
            FROM vehicles v
            INNER JOIN users u ON u.user_id = v.owner_id
            LEFT JOIN compliance_records c ON c.vehicle_id = v.vehicle_id
            LEFT JOIN service_records s ON s.vehicle_id = v.vehicle_id
            LEFT JOIN users checker ON checker.user_id = v.inspection_checked_by
            WHERE v.vehicle_id = :vehicle_id
              AND v.owner_id = :owner_id
            LIMIT 1"
        );
        $stmt->execute([
            'vehicle_id' => $vehicleId,
            'owner_id' => $userId,
        ]);
        $vehicle = $stmt->fetch();

        if (!$vehicle) {
            $message = 'That vehicle does not belong to your account or could not be found.';
            $messageType = 'warning';
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $message = 'Database error while loading vehicle details.';
        $messageType = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicle Details - VCS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="dashboard-page citizen-page">
    <nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm">
        <div class="container">
            <a class="navbar-brand text-white" href="<?php echo h($dashboardUrl); ?>">
                <i class="bi bi-car-front-fill me-2"></i>VCS Portal
            </a>
            <div class="d-flex align-items-center text-white gap-2">
                <span class="small d-none d-md-inline"><i class="bi bi-person-circle me-1"></i> <?php echo h($_SESSION['name'] ?? 'Citizen'); ?></span>
                <a href="<?php echo h($dashboardUrl); ?>" class="btn btn-outline-light btn-sm">Back</a>
                <a href="/backend/logout.php" class="btn btn-light btn-sm text-success">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <main>
        <section class="section-band bg-white">
            <div class="container py-5">
                <div class="row align-items-center g-4">
                    <div class="col-lg-7">
                        <h1 class="section-title mt-2 mb-3">Vehicle Profile</h1>
                        <p class="text-secondary mb-0">
                            Manage compliance, registration status, and maintenance logs for this vehicle.
                        </p>
                    </div>
                    <div class="col-lg-5">
                        <div class="detail-card shadow-sm">
                            <div class="small text-secondary text-uppercase fw-semibold">Account summary</div>
                            <div class="fw-bold mt-2"><?php echo h($_SESSION['name'] ?? 'Citizen'); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="section-band bg-light">
            <div class="container py-5">
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo h($messageType); ?> shadow-sm" role="alert">
                        <?php echo h($message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($vehicle): ?>
                    <div class="row g-4">
                        <div class="col-lg-4">
                            <div class="detail-card h-100 shadow-sm">
                                <div class="small text-secondary text-uppercase fw-semibold">Vehicle</div>
                                <h2 class="h3 fw-bold mt-2 mb-1"><?php echo h($vehicle['plate_number']); ?></h2>
                                <p class="text-secondary mb-0"><?php echo h($vehicle['make']); ?> <?php echo h($vehicle['model']); ?></p>
                                <hr>
                                <div class="small text-secondary">Year</div>
                                <div class="fw-semibold"><?php echo h($vehicle['year']); ?></div>
                                <div class="small text-secondary mt-3">Vehicle ID</div>
                                <div class="fw-semibold"><?php echo h($vehicle['vehicle_id']); ?></div>
                                <div class="small text-secondary mt-3">Added to system</div>
                                <div class="fw-semibold"><?php echo h($vehicle['vehicle_created_at']); ?></div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="detail-card h-100 shadow-sm">
                                <div class="small text-secondary text-uppercase fw-semibold">Compliance</div>
                                <div class="mt-2 d-grid gap-2">
                                    <span class="badge bg-success px-3 py-2 text-start">Insurance: <?php echo h($vehicle['insurance_status'] ?? 'N/A'); ?></span>
                                    <span class="badge bg-success px-3 py-2 text-start">Licence: <?php echo h($vehicle['licence_status'] ?? 'N/A'); ?></span>
                                    <span class="badge bg-success px-3 py-2 text-start">Registration: <?php echo h($vehicle['registration_status'] ?? 'N/A'); ?></span>
                                </div>
                                <hr>
                                <div class="small text-secondary">Insurance expiry</div>
                                <div class="fw-semibold"><?php echo h($vehicle['insurance_expiry'] ?? 'N/A'); ?></div>
                                <div class="small text-secondary mt-3">Licence expiry</div>
                                <div class="fw-semibold"><?php echo h($vehicle['licence_expiry'] ?? 'N/A'); ?></div>
                                <div class="small text-secondary mt-3">Registration expiry</div>
                                <div class="fw-semibold"><?php echo h($vehicle['registration_expiry'] ?? 'N/A'); ?></div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="detail-card h-100 shadow-sm">
                                <div class="small text-secondary text-uppercase fw-semibold">Inspection and service</div>
                                <div class="mt-2">
                                    <span class="badge <?php echo inspectionBadgeClass($vehicle['inspection_status'] ?? 'Pending Police Check'); ?> px-3 py-2">
                                        <?php echo h($vehicle['inspection_status'] ?? 'Pending Police Check'); ?>
                                    </span>
                                </div>
                                <div class="small text-secondary mt-3">Checked at</div>
                                <div class="fw-semibold"><?php echo h($vehicle['inspection_checked_at'] ?? 'Not checked yet'); ?></div>
                                <div class="small text-secondary mt-3">Checked by</div>
                                <div class="fw-semibold"><?php echo h($vehicle['inspection_checked_by_name'] ?? 'Officer not recorded'); ?></div>
                                <div class="small text-secondary mt-2">Officer staff ID</div>
                                <div class="fw-semibold"><?php echo h($vehicle['inspection_checked_by_staff_id'] ?? 'N/A'); ?></div>
                                <hr>
                                <div class="small text-secondary text-uppercase fw-semibold">Latest service</div>
                                <div class="fw-semibold mt-2"><?php echo h($vehicle['service_details'] ?? 'N/A'); ?></div>
                                <div class="small text-secondary mt-3">Last service date</div>
                                <div class="fw-semibold"><?php echo h($vehicle['last_service_date'] ?? 'N/A'); ?></div>
                                <div class="small text-secondary mt-3">Next service date</div>
                                <div class="fw-semibold"><?php echo h($vehicle['next_service_date'] ?? 'N/A'); ?></div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="d-flex justify-content-end flex-wrap gap-2 no-print">
                                <a href="/backend/export_record.php?vehicle_id=<?php echo urlencode((string) $vehicle['vehicle_id']); ?>" class="btn btn-outline-secondary">
                                    <i class="bi bi-download me-1"></i> Download Report
                                </a>
                                <button type="button" class="btn btn-success" onclick="window.print()">
                                    <i class="bi bi-printer me-1"></i> Print Record
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
