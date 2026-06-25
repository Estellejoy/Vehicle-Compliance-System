<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'officer') {
    header('Location: /login');
    exit;
}

require_once '../config/db.php';

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function badgeClass($status)
{
    return strtolower((string) $status) === 'valid' ? 'bg-success' : 'bg-danger';
}

function inspectionBadgeClass($status)
{
    $normalized = strtolower(trim((string) $status));

    if ($normalized === 'checked') {
        return 'bg-success';
    }

    if ($normalized === 'pending police check') {
        return 'bg-warning text-dark';
    }

    return 'bg-secondary';
}

$plateNumber = trim($_POST['plate_number'] ?? $_GET['plate_number'] ?? '');
$vehicle = null;
$message = null;
$messageType = 'info';
$inspectionFeatureEnabled = false;

if (isset($_GET['updated'])) {
    $message = 'Inspection status updated successfully.';
    $messageType = 'success';
}

if (isset($_GET['error']) && $_GET['error'] === 'inspection_columns_missing') {
    $message = 'This database does not have the inspection columns yet. Run the migration in the README.';
    $messageType = 'warning';
}

try {
    $inspectionStatusColumn = $pdo->query("SHOW COLUMNS FROM vehicles LIKE 'inspection_status'")->fetch();
    $inspectionCheckedAtColumn = $pdo->query("SHOW COLUMNS FROM vehicles LIKE 'inspection_checked_at'")->fetch();
    $inspectionFeatureEnabled = (bool) $inspectionStatusColumn && (bool) $inspectionCheckedAtColumn;
} catch (PDOException $e) {
    error_log($e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' || $plateNumber !== '') {

    if ($plateNumber === '') {
        $message = 'Enter a plate number to look up a record.';
        $messageType = 'warning';
    } else {
        try {
            $inspectionSelect = $inspectionFeatureEnabled
                ? "v.inspection_status,
                    v.inspection_checked_at,"
                : "";

            $stmt = $pdo->prepare(
                "SELECT
                    v.vehicle_id,
                    v.owner_id,
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
                    $inspectionSelect
                    s.service_details,
                    s.last_service_date,
                    s.next_service_date
                FROM vehicles v
                INNER JOIN users u ON u.user_id = v.owner_id
                LEFT JOIN compliance_records c ON c.vehicle_id = v.vehicle_id
                LEFT JOIN service_records s ON s.vehicle_id = v.vehicle_id
                WHERE REPLACE(UPPER(v.plate_number), ' ', '') = REPLACE(UPPER(:plate_number), ' ', '')
                LIMIT 1"
            );
            $stmt->execute(['plate_number' => $plateNumber]);
            $vehicle = $stmt->fetch();

            if ($vehicle) {
                $message = 'Record loaded successfully.';
                $messageType = 'success';
            } else {
                $message = 'No record found for that plate number.';
                $messageType = 'warning';
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $message = 'Database error while searching for that plate number.';
            $messageType = 'danger';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Officer Dashboard - VCVS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="dashboard-page">
    <nav class="navbar navbar-expand-lg navbar-light vcs-navbar border-bottom sticky-top">
        <div class="container py-2">
            <a class="navbar-brand d-flex align-items-center gap-2 fw-bold text-success" href="../index.php">
                <span class="brand-mark"><i class="bi bi-shield-check"></i></span>
                <span>VCVS</span>
            </a>
            <div class="ms-auto d-flex align-items-center gap-2">
                <span class="text-secondary small d-none d-md-inline">
                    <i class="bi bi-person-badge me-1 text-success"></i><?php echo h($_SESSION['name'] ?? 'Officer'); ?>
                </span>
                <a href="/login" class="btn btn-outline-success btn-sm">
                    <i class="bi bi-box-arrow-right me-1"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <main class="officer-dashboard">
        <section class="section-band bg-white">
            <div class="container py-5">
                <div class="row align-items-center g-4">
                    <div class="col-lg-6">
                        <span class="eyebrow text-success fw-semibold">Officer dashboard</span>
                        <h1 class="section-title mt-2">Search a plate number and pull the vehicle record from the database.</h1>
                        <p class="text-secondary mt-3 mb-0">
                            Enter the plate number during roadside checks and the system will return the owner details, compliance status, and service history.
                        </p>
                    </div>
                    <div class="col-lg-6">
                        <div class="dashboard-hero shadow-sm">
                            <img
                                src="https://commons.wikimedia.org/wiki/Special:FilePath/Section%2058%20Bypass.jpg"
                                class="img-fluid rounded-4"
                                alt="Nairobi Expressway"
                            >
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="section-band bg-light">
            <div class="container py-5">
                <div class="search-card shadow-sm">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div>
                            <span class="text-uppercase small fw-semibold text-success">Plate lookup</span>
                            <h2 class="h4 fw-bold mb-0">Vehicle verification search</h2>
                        </div>
                        <i class="bi bi-search text-success fs-3"></i>
                    </div>

                    <form method="POST" class="mt-4">
                        <label for="plate_number" class="form-label fw-semibold text-secondary">Plate Number</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-white border-success"><i class="bi bi-car-front text-success"></i></span>
                            <input
                                type="text"
                                id="plate_number"
                                name="plate_number"
                                class="form-control border-success"
                                placeholder="e.g. KDB 101D"
                                value="<?php echo h($plateNumber); ?>"
                                required
                            >
                            <button class="btn btn-success px-4" type="submit">
                                <i class="bi bi-search me-1"></i> Search
                            </button>
                        </div>
                    </form>

                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo h($messageType); ?> mt-4 mb-0" role="alert">
                            <?php echo h($message); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <?php if ($vehicle): ?>
            <section class="section-band bg-white">
                <div class="container py-5">
                    <div class="row g-4">
                        <div class="col-md-6 col-xl-3">
                            <div class="detail-card h-100">
                                <div class="small text-secondary text-uppercase fw-semibold">Vehicle</div>
                                <h3 class="h4 fw-bold mt-2 mb-1"><?php echo h($vehicle['plate_number']); ?></h3>
                                <p class="text-secondary mb-0"><?php echo h($vehicle['make']); ?> <?php echo h($vehicle['model']); ?></p>
                                <hr>
                                <div class="small text-secondary">Year</div>
                                <div class="fw-semibold"><?php echo h($vehicle['year']); ?></div>
                            </div>
                        </div>

                        <div class="col-md-6 col-xl-3">
                            <div class="detail-card h-100">
                                <div class="small text-secondary text-uppercase fw-semibold">Owner</div>
                                <h3 class="h4 fw-bold mt-2 mb-1"><?php echo h($vehicle['owner_name']); ?></h3>
                                <p class="text-secondary mb-0"><?php echo h($vehicle['owner_email']); ?></p>
                                <hr>
                                <div class="small text-secondary">Role</div>
                                <div class="fw-semibold text-capitalize"><?php echo h($vehicle['owner_role']); ?></div>
                            </div>
                        </div>

                        <div class="col-md-6 col-xl-3">
                            <div class="detail-card h-100">
                                <div class="small text-secondary text-uppercase fw-semibold">Compliance</div>
                                <div class="mt-2 d-flex flex-wrap gap-2">
                                    <span class="badge <?php echo badgeClass($vehicle['insurance_status']); ?> px-3 py-2">Insurance: <?php echo h($vehicle['insurance_status']); ?></span>
                                    <span class="badge <?php echo badgeClass($vehicle['licence_status']); ?> px-3 py-2">Licence: <?php echo h($vehicle['licence_status']); ?></span>
                                    <span class="badge <?php echo badgeClass($vehicle['registration_status']); ?> px-3 py-2">Registration: <?php echo h($vehicle['registration_status']); ?></span>
                                </div>
                                <hr>
                                <div class="small text-secondary">Compliance record</div>
                                <div class="fw-semibold">Vehicle ID <?php echo h($vehicle['vehicle_id']); ?></div>
                            </div>
                        </div>

                        <div class="col-md-6 col-xl-3">
                            <div class="detail-card h-100">
                                <div class="small text-secondary text-uppercase fw-semibold">Service</div>
                                <h3 class="h5 fw-bold mt-2 mb-1"><?php echo h($vehicle['service_details'] ?? 'N/A'); ?></h3>
                                <p class="text-secondary mb-0">Last service: <?php echo h($vehicle['last_service_date'] ?? 'N/A'); ?></p>
                                <hr>
                                <div class="small text-secondary">Next service</div>
                                <div class="fw-semibold"><?php echo h($vehicle['next_service_date'] ?? 'N/A'); ?></div>
                            </div>
                        </div>
                    </div>

                    <?php if ($inspectionFeatureEnabled): ?>
                        <div class="detail-card mt-4">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                                <div>
                                    <div class="small text-secondary text-uppercase fw-semibold">Police inspection</div>
                                    <h3 class="h4 fw-bold mt-2 mb-0">Update inspection status</h3>
                                </div>
                                <span class="badge <?php echo inspectionBadgeClass($vehicle['inspection_status'] ?? 'Pending Police Check'); ?> px-3 py-2">
                                    <?php echo h($vehicle['inspection_status'] ?? 'Pending Police Check'); ?>
                                </span>
                            </div>

                            <div class="row g-3 mt-3 align-items-end">
                                <div class="col-md-6">
                                    <div class="small text-secondary">Last checked</div>
                                    <div class="fw-semibold"><?php echo h($vehicle['inspection_checked_at'] ?? 'Not checked yet'); ?></div>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <form method="POST" action="../backend/update_record.php" class="d-inline-block no-print">
                                        <input type="hidden" name="action" value="mark_inspected">
                                        <input type="hidden" name="vehicle_id" value="<?php echo h($vehicle['vehicle_id']); ?>">
                                        <input type="hidden" name="plate_number" value="<?php echo h($vehicle['plate_number']); ?>">
                                        <button type="submit" class="btn btn-success">
                                            <i class="bi bi-check2-circle me-1"></i> Mark as Checked
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning mt-4 no-print" role="alert">
                            The current database is missing the inspection columns. Apply the migration in the README to enable inspection updates.
                        </div>
                    <?php endif; ?>

                    <div class="d-flex justify-content-end mt-4 no-print">
                        <button type="button" class="btn btn-outline-success" onclick="window.print()">
                            <i class="bi bi-printer me-1"></i> Print Compliance Report
                        </button>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
