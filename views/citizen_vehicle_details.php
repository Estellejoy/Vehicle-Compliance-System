<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'owner') {
    header('Location: /login');
    exit;
}

require_once '../config/db.php';
require_once __DIR__ . '/../backend/auth_helpers.php';

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
$coOwners = [];
$message = null;
$messageType = 'info';
$inspectionCheckedByBadgeColumn = vcs_has_column($pdo, 'users', 'badge_number');
$flash = $_SESSION['vehicle_flash'] ?? null;
unset($_SESSION['vehicle_flash']);

$dashboardUrl = '/views/citizen_portal.php';

if ($vehicleId <= 0) {
    $message = 'Select a vehicle first.';
    $messageType = 'warning';
} else {
    try {
        $inspectionSelect = '';
        if ($inspectionCheckedByBadgeColumn) {
            $inspectionSelect = "checker.badge_number AS inspection_checked_by_badge,";
        }

        $stmt = $pdo->prepare(
            "SELECT
                u.user_id,
                u.name AS owner_name,
                u.email AS owner_email,
                u.role AS owner_role,
                v.*,
                v.created_at AS vehicle_created_at,
                $inspectionSelect
                c.insurance_expiry,
                c.insurance_status,
                c.licence_expiry,
                c.licence_status,
                c.registration_expiry,
                c.registration_status,
                s.*
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
        } elseif (vcs_has_table($pdo, 'vehicle_owners')) {
            $ownersStmt = $pdo->prepare(
                'SELECT u.name, u.email, vo.ownership_role, vo.is_primary
                 FROM vehicle_owners vo
                 INNER JOIN users u ON u.user_id = vo.user_id
                 WHERE vo.vehicle_id = :vehicle_id
                 ORDER BY vo.is_primary DESC, u.name ASC'
            );
            $ownersStmt->execute(['vehicle_id' => $vehicleId]);
            $coOwners = $ownersStmt->fetchAll();
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
<body class="dashboard-page">
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
                        <div class="col-lg-6">
                            <div class="detail-card h-100 shadow-sm">
                                <div class="small text-secondary text-uppercase fw-semibold">Vehicle</div>
                                <h2 class="h3 fw-bold mt-2 mb-1"><?php echo h($vehicle['plate_number']); ?></h2>
                                <p class="text-secondary mb-0"><?php echo h($vehicle['make']); ?> <?php echo h($vehicle['model']); ?></p>
                                <hr>
                                <div class="small text-secondary">Chassis / VIN</div>
                                <div class="fw-semibold"><?php echo h(vcs_vehicle_vin($vehicle)); ?></div>
                                <div class="small text-secondary mt-3">Year</div>
                                <div class="fw-semibold"><?php echo h($vehicle['year']); ?></div>
                                <div class="small text-secondary mt-3">Vehicle ID</div>
                                <div class="fw-semibold"><?php echo h($vehicle['vehicle_id']); ?></div>
                                <div class="small text-secondary mt-3">Added to system</div>
                                <div class="fw-semibold"><?php echo h($vehicle['vehicle_created_at']); ?></div>
                                <div class="small text-secondary mt-3">Ownership</div>
                                <div class="fw-semibold">
                                    <?php echo h($vehicle['owner_name']); ?>
                                    <?php if (!empty($coOwners)): ?>
                                        <span class="text-secondary small d-block">Joint ownership enabled</span>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($coOwners)): ?>
                                    <div class="mt-3 small text-secondary text-uppercase fw-semibold">Co-owners</div>
                                    <div class="d-grid gap-2 mt-2">
                                        <?php foreach ($coOwners as $coOwner): ?>
                                            <div class="border rounded-3 p-2 bg-light">
                                                <div class="fw-semibold"><?php echo h($coOwner['name']); ?></div>
                                                <div class="small text-secondary"><?php echo h($coOwner['email']); ?></div>
                                                <div class="small text-secondary text-capitalize"><?php echo h($coOwner['ownership_role']); ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="detail-card h-100 shadow-sm">
                                <div class="small text-secondary text-uppercase fw-semibold">Compliance</div>
                                <div class="mt-2 d-grid gap-2">
                                    <span class="badge bg-success px-3 py-2 text-start">Insurance: <?php echo h($vehicle['insurance_status'] ?? 'N/A'); ?></span>
                                    <span class="badge bg-success px-3 py-2 text-start">Licence: <?php echo h($vehicle['licence_status'] ?? 'N/A'); ?></span>
                                    <span class="badge bg-success px-3 py-2 text-start">Registration: <?php echo h($vehicle['registration_status'] ?? 'N/A'); ?></span>
                                </div>
                                <hr>
                                <div class="small text-secondary">Insurance type</div>
                                <div class="fw-semibold"><?php echo h(vcs_vehicle_insurance_type($vehicle)); ?></div>
                                <div class="small text-secondary mt-3">Payment period</div>
                                <div class="fw-semibold"><?php echo h(vcs_vehicle_payment_period($vehicle)); ?></div>
                                <div class="small text-secondary">Insurance expiry</div>
                                <div class="fw-semibold"><?php echo h($vehicle['insurance_expiry'] ?? 'N/A'); ?></div>
                                <div class="small text-secondary mt-3">Driver's licence class</div>
                                <div class="fw-semibold"><?php echo h(vcs_vehicle_licence_class($vehicle)); ?></div>
                                <div class="small text-secondary mt-3">Licence expiry</div>
                                <div class="fw-semibold"><?php echo h($vehicle['licence_expiry'] ?? 'N/A'); ?></div>
                                <div class="small text-secondary mt-3">Registration expiry</div>
                                <div class="fw-semibold"><?php echo h($vehicle['registration_expiry'] ?? 'N/A'); ?></div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="detail-card h-100 shadow-sm">
                                <div class="small text-secondary text-uppercase fw-semibold">Service planning</div>
                                <div class="fw-semibold mt-2">Next probable service</div>
                                <div class="h4 text-success fw-bold mb-0"><?php echo h(vcs_vehicle_next_probable_service_km($vehicle)); ?> km</div>
                                <div class="small text-secondary mt-2">Based on a <?php echo h(vcs_vehicle_service_interval_km($vehicle)); ?> km interval.</div>
                                <hr>
                                <div class="small text-secondary">Current odometer</div>
                                <div class="fw-semibold"><?php echo h(vcs_vehicle_odometer_km($vehicle)); ?> km</div>
                                <div class="small text-secondary mt-3">Latest service record</div>
                                <div class="fw-semibold"><?php echo h($vehicle['service_details'] ?? 'Full service'); ?></div>
                                <div class="small text-secondary mt-3">Last service odometer</div>
                                <div class="fw-semibold"><?php echo h($vehicle['last_service_odometer_km'] ?? vcs_vehicle_odometer_km($vehicle) - vcs_vehicle_service_interval_km($vehicle)); ?> km</div>
                                <div class="small text-secondary mt-3">Service notes</div>
                                <div class="fw-semibold"><?php echo h(vcs_vehicle_service_notes($vehicle)); ?></div>
                            </div>
                        </div>

                        <div class="col-lg-6">
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
                                <div class="fw-semibold"><?php echo h(vcs_inspector_badge_label($vehicle)); ?></div>
                                <hr>
                                <div class="small text-secondary text-uppercase fw-semibold">Recent service date</div>
                                <div class="fw-semibold mt-2"><?php echo h($vehicle['last_service_date'] ?? '2025-10-24'); ?></div>
                                <div class="small text-secondary mt-3">Date-based next service</div>
                                <div class="fw-semibold"><?php echo h($vehicle['next_service_date'] ?? '2026-04-22'); ?></div>
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
