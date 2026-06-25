<?php
session_start();

// 1. Security Check: Lock the door if they aren't logged in as an owner
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header("Location: login.php");
    exit;
}

// 2. Bring in your database connection 
require_once '../config/db.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];

// Placeholders for database data arrays
$vehicles = [];
$total_vehicles = 0;
$pending_fines = 0;

function inspectionBadgeClass($status)
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

try {
    // 3. Fetch real vehicles belonging to this logged-in user ID
    $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE owner_id = :user_id");
    $stmt->execute(['user_id' => $user_id]);
    $vehicles = $stmt->fetchAll();
    $total_vehicles = count($vehicles);
    
    // (Optional placeholder query example for tracking fines)
    // $fine_stmt = $pdo->prepare("SELECT SUM(amount) as total FROM fines WHERE owner_id = :user_id AND status = 'unpaid'");
    // $fine_stmt->execute(['user_id' => $user_id]);
    // $pending_fines = $fine_stmt->fetch()['total'] ?? 0;

} catch (\PDOException $e) {
    // Graceful fallback if your database tables are still being structured
    $vehicles = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Citizen Dashboard - VCS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .navbar-brand { font-weight: 700; }
        .stat-card { border-left: 4px solid #198754; }
        .fine-card { border-left: 4px solid #dc3545; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm">
        <div class="container">
            <a class="navbar-brand text-white" href="#"><i class="bi bi-car-front-fill me-2"></i>VCS Portal</a>
            <button class="navbar-expand-lg border-0 bg-transparent text-white d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <i class="bi bi-list fs-3"></i>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <div class="d-flex align-items-center text-white gap-3">
                    <span class="small"><i class="bi bi-person-circle me-1"></i> <?php echo htmlspecialchars($user_name); ?> (Citizen)</span>
                    <a href="login.php" class="btn btn-outline-light btn-sm"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        
        <div class="row mb-4">
            <div class="col-12">
                <div class="p-4 bg-white shadow-sm rounded-3 border-start border-success border-4">
                    <h2 class="h4 text-dark fw-bold mb-1">Welcome back, <?php echo htmlspecialchars($user_name); ?>!</h2>
                    <p class="text-muted small mb-0">Check your active vehicle registries, track statutory inspection timelines, and manage traffic citation profiles.</p>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6 col-lg-4">
                <div class="card stat-card shadow-sm p-3 bg-white h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted text-uppercase small fw-bold">Registered Vehicles</span>
                            <h3 class="display-6 fw-bold text-success mb-0 mt-1"><?php echo $total_vehicles; ?></h3>
                        </div>
                        <div class="bg-success bg-opacity-10 p-3 rounded-3 text-success fs-3">
                            <i class="bi bi-truck"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="card shadow-sm p-3 bg-white h-100 border-start border-4 border-info">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted text-uppercase small fw-bold">Overall Status</span>
                            <h3 class="h4 fw-bold text-info mb-0 mt-2">
                                <?php echo ($pending_fines > 0) ? 'Action Required' : 'Fully Compliant ✅'; ?>
                            </h3>
                        </div>
                        <div class="bg-info bg-opacity-10 p-3 rounded-3 text-info fs-3">
                            <i class="bi bi-shield-check"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="card fine-card shadow-sm p-3 bg-white h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted text-uppercase small fw-bold">Unpaid Penalties</span>
                            <h3 class="display-6 fw-bold text-danger mb-0 mt-1">KES <?php echo number_format($pending_fines); ?></h3>
                        </div>
                        <div class="bg-danger bg-opacity-10 p-3 rounded-3 text-danger fs-3">
                            <i class="bi bi-exclamation-octagon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
                    <div class="card-header bg-white py-3 border-bottom border-light">
                        <h5 class="mb-0 fw-bold text-success"><i class="bi bi-journal-text me-2"></i>My Registered Fleet Records</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if ($total_vehicles > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0 text-nowrap">
                                    <thead class="table-light text-secondary small text-uppercase">
                                        <tr>
                                            <th class="ps-4">License Plate</th>
                                            <th>Make & Model</th>
                                            <th>Insurance Policy</th>
                                            <th>Inspection Status</th>
                                            <th class="pe-4 text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($vehicles as $vehicle): ?>
                                            <tr>
                                                <td class="ps-4 fw-bold text-dark"><?php echo htmlspecialchars($vehicle['plate_number']); ?></td>
                                                <td><?php echo htmlspecialchars($vehicle['model']); ?></td>
                                                <td>
                                                    <span class="badge bg-success-subtle text-success border border-success border-opacity-25 px-2.5 py-1.5 rounded-pill">
                                                        Active
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge <?php echo inspectionBadgeClass($vehicle['inspection_status'] ?? 'Pending Police Check'); ?> px-2.5 py-1.5 rounded-pill">
                                                        <?php echo htmlspecialchars($vehicle['inspection_status'] ?? 'Pending Police Check'); ?>
                                                    </span>
                                                    <div class="text-muted small mt-1">
                                                        <?php if (!empty($vehicle['inspection_checked_at'])): ?>
                                                            Checked on <?php echo htmlspecialchars($vehicle['inspection_checked_at']); ?>
                                                        <?php else: ?>
                                                            Not checked yet
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td class="pe-4 text-end">
                                                    <button class="btn btn-light btn-sm border fw-semibold text-secondary"><i class="bi bi-eye"></i> Details</button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5 px-4">
                                <div class="text-muted display-4 mb-3"><i class="bi bi-folder-x"></i></div>
                                <h5 class="fw-semibold text-dark">No Vehicle Records Found</h5>
                                <p class="text-muted small mb-0">There are currently no active asset configurations matching user account identity code: <?php echo $user_id; ?>.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
