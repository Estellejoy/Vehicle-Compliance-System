<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: /login');
    exit;
}

require_once '../config/db.php';

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$currentAdminId = (int) $_SESSION['user_id'];
$reportYear = (int) ($_GET['year'] ?? date('Y'));
if ($reportYear < 2000 || $reportYear > 2100) {
    $reportYear = (int) date('Y');
}

$flash = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']);

$staffIdEnabled = false;
try {
    $staffIdEnabled = (bool) $pdo->query("SHOW COLUMNS FROM users LIKE 'staff_id'")->fetch();
} catch (PDOException $e) {
    error_log($e->getMessage());
}

$users = [];
$report = [
    'total_users' => 0,
    'active_users' => 0,
    'inactive_users' => 0,
    'admins' => 0,
    'officers' => 0,
    'owners' => 0,
    'vehicles' => 0,
    'service_records' => 0,
    'inspections_checked' => 0,
    'inspections_pending' => 0,
    'valid_insurance' => 0,
    'expired_insurance' => 0,
    'valid_licence' => 0,
    'expired_licence' => 0,
    'valid_registration' => 0,
    'expired_registration' => 0,
    'annual_insurance_due' => 0,
    'annual_licence_due' => 0,
    'annual_registration_due' => 0,
];

try {
    $userSelect = $staffIdEnabled
        ? "SELECT user_id, name, email, role, staff_id, COALESCE(is_active, 1) AS is_active, created_at"
        : "SELECT user_id, name, email, role, NULL AS staff_id, COALESCE(is_active, 1) AS is_active, created_at";

    $users = $pdo->query(
        $userSelect . "
         FROM users
         ORDER BY created_at DESC, user_id DESC"
    )->fetchAll();

    $report['total_users'] = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $report['active_users'] = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE COALESCE(is_active, 1) = 1")->fetchColumn();
    $report['inactive_users'] = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE COALESCE(is_active, 1) = 0")->fetchColumn();
    $report['admins'] = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    $report['officers'] = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'officer'")->fetchColumn();
    $report['owners'] = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'owner'")->fetchColumn();
    $report['vehicles'] = (int) $pdo->query("SELECT COUNT(*) FROM vehicles")->fetchColumn();
    $report['service_records'] = (int) $pdo->query("SELECT COUNT(*) FROM service_records")->fetchColumn();
    $report['inspections_checked'] = (int) $pdo->query("SELECT COUNT(*) FROM vehicles WHERE inspection_status = 'Checked'")->fetchColumn();
    $report['inspections_pending'] = (int) $pdo->query("SELECT COUNT(*) FROM vehicles WHERE inspection_status <> 'Checked'")->fetchColumn();
    $report['valid_insurance'] = (int) $pdo->query("SELECT COUNT(*) FROM compliance_records WHERE insurance_status = 'Valid'")->fetchColumn();
    $report['expired_insurance'] = (int) $pdo->query("SELECT COUNT(*) FROM compliance_records WHERE insurance_status = 'Expired'")->fetchColumn();
    $report['valid_licence'] = (int) $pdo->query("SELECT COUNT(*) FROM compliance_records WHERE licence_status = 'Valid'")->fetchColumn();
    $report['expired_licence'] = (int) $pdo->query("SELECT COUNT(*) FROM compliance_records WHERE licence_status = 'Expired'")->fetchColumn();
    $report['valid_registration'] = (int) $pdo->query("SELECT COUNT(*) FROM compliance_records WHERE registration_status = 'Valid'")->fetchColumn();
    $report['expired_registration'] = (int) $pdo->query("SELECT COUNT(*) FROM compliance_records WHERE registration_status = 'Expired'")->fetchColumn();

    $annual = $pdo->prepare(
        "SELECT
            COUNT(*) AS annual_insurance_due,
            SUM(CASE WHEN licence_status = 'Expired' THEN 1 ELSE 0 END) AS annual_licence_due,
            SUM(CASE WHEN registration_status = 'Expired' THEN 1 ELSE 0 END) AS annual_registration_due
         FROM compliance_records
         WHERE YEAR(insurance_expiry) = :report_year
            OR YEAR(licence_expiry) = :report_year
            OR YEAR(registration_expiry) = :report_year"
    );
    $annual->execute(['report_year' => $reportYear]);
    $annualSummary = $annual->fetch() ?: [];
    $report['annual_insurance_due'] = (int) ($annualSummary['annual_insurance_due'] ?? 0);
    $report['annual_licence_due'] = (int) ($annualSummary['annual_licence_due'] ?? 0);
    $report['annual_registration_due'] = (int) ($annualSummary['annual_registration_due'] ?? 0);
} catch (PDOException $e) {
    error_log($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - VCVS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="admin-page">
    <nav class="navbar navbar-expand-lg navbar-light vcs-navbar border-bottom sticky-top">
        <div class="container py-2">
            <a class="navbar-brand d-flex align-items-center gap-2 fw-bold text-success" href="../index.php">
                <span class="brand-mark"><i class="bi bi-shield-check"></i></span>
                <span>VCVS</span>
            </a>
            <div class="ms-auto d-flex align-items-center gap-2">
                <span class="text-secondary small d-none d-md-inline">
                    <i class="bi bi-person-gear me-1 text-success"></i><?php echo h($_SESSION['name'] ?? 'Admin'); ?>
                    <?php if (!empty($_SESSION['staff_id'])): ?>
                        <span class="badge bg-success-subtle text-success border border-success border-opacity-25 ms-2"><?php echo h($_SESSION['staff_id']); ?></span>
                    <?php endif; ?>
                </span>
                <a href="/views/change_password.php" class="btn btn-outline-success btn-sm">
                    <i class="bi bi-key me-1"></i> Change Password
                </a>
                <a href="/backend/logout.php" class="btn btn-success btn-sm">
                    <i class="bi bi-box-arrow-right me-1"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <main>
        <section class="section-band bg-white">
            <div class="container py-5">
                <div class="row align-items-center g-4">
                    <div class="col-lg-7">
                        <span class="eyebrow text-success fw-semibold">System administrator</span>
                        <h1 class="section-title mt-2">Manage accounts, officer records, and management reports.</h1>
                        <p class="text-secondary mb-0">
                            Create users, reset passwords, adjust roles, and export annual compliance summaries from one control panel.
                        </p>
                    </div>
                    <div class="col-lg-5">
                        <div class="report-hero shadow-sm">
                            <img
                                src="https://commons.wikimedia.org/wiki/Special:FilePath/Kenyatta%20International%20Convention%20Centre,%20Nairobi,%20by%20Karl%20Henrik%20N%C3%B8stvik%20architect,%20entrance.jpg"
                                class="img-fluid rounded-4"
                                alt="KICC Nairobi"
                            >
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="section-band bg-light">
            <div class="container py-5">
                <div class="row g-3 g-lg-4">
                    <div class="col-6 col-lg-3">
                        <div class="value-card h-100">
                            <div class="small text-secondary text-uppercase fw-semibold">Total users</div>
                            <div class="display-6 fw-bold text-success mt-1"><?php echo h($report['total_users']); ?></div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="value-card h-100">
                            <div class="small text-secondary text-uppercase fw-semibold">Active</div>
                            <div class="display-6 fw-bold text-success mt-1"><?php echo h($report['active_users']); ?></div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="value-card h-100">
                            <div class="small text-secondary text-uppercase fw-semibold">Vehicles</div>
                            <div class="display-6 fw-bold text-success mt-1"><?php echo h($report['vehicles']); ?></div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="value-card h-100">
                            <div class="small text-secondary text-uppercase fw-semibold">Service records</div>
                            <div class="display-6 fw-bold text-success mt-1"><?php echo h($report['service_records']); ?></div>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mt-1">
                    <div class="col-lg-7">
                        <div class="search-card shadow-sm h-100">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                                <div>
                                    <span class="text-uppercase small fw-semibold text-success">Create user accounts</span>
                                    <h2 class="h4 fw-bold mb-0">Add a user directly from the admin panel</h2>
                                </div>
                                <i class="bi bi-person-plus text-success fs-3"></i>
                            </div>
                            <form action="/backend/admin_actions.php" method="POST" class="row g-3 mt-3">
                                <input type="hidden" name="action" value="create_user">
                                <div class="col-md-6">
                                    <label for="create_name" class="form-label fw-semibold text-secondary">Full Name</label>
                                    <input type="text" id="create_name" name="name" class="form-control" placeholder="e.g. Jane Wambui" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="create_email" class="form-label fw-semibold text-secondary">Email</label>
                                    <input type="email" id="create_email" name="email" class="form-control" placeholder="name@example.com" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="create_role" class="form-label fw-semibold text-secondary">Role</label>
                                    <select id="create_role" name="role" class="form-select">
                                        <option value="owner">Owner</option>
                                        <option value="officer">Officer</option>
                                        <option value="admin">Admin</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="create_staff_id" class="form-label fw-semibold text-secondary">Officer Staff ID</label>
                                    <input type="text" id="create_staff_id" name="staff_id" class="form-control" placeholder="OFF-003">
                                </div>
                                <div class="col-md-4">
                                    <label for="create_password" class="form-label fw-semibold text-secondary">Initial Password</label>
                                    <input type="password" id="create_password" name="password" class="form-control" placeholder="Temp@1234" required>
                                </div>
                                <div class="col-12 text-end">
                                    <button type="submit" class="btn btn-success">
                                        <i class="bi bi-plus-circle me-1"></i> Create Account
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="search-card shadow-sm h-100">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                                <div>
                                    <span class="text-uppercase small fw-semibold text-success">Annual compliance report</span>
                                    <h2 class="h4 fw-bold mb-0">Yearly management summary</h2>
                                </div>
                                <i class="bi bi-graph-up-arrow text-success fs-3"></i>
                            </div>
                            <form class="row g-3 mt-3" method="GET">
                                <div class="col-7">
                                    <label for="year" class="form-label fw-semibold text-secondary">Report Year</label>
                                    <input type="number" id="year" name="year" class="form-control" value="<?php echo h($reportYear); ?>" min="2000" max="2100">
                                </div>
                                <div class="col-5 d-flex align-items-end">
                                    <button type="submit" class="btn btn-outline-success w-100">
                                        <i class="bi bi-funnel me-1"></i> Load
                                    </button>
                                </div>
                            </form>
                            <div class="mini-result mt-3">
                                <div class="fw-semibold">For <?php echo h($reportYear); ?></div>
                                <div class="small text-secondary mt-2">Insurance due: <?php echo h($report['annual_insurance_due']); ?></div>
                                <div class="small text-secondary">Licence due: <?php echo h($report['annual_licence_due']); ?></div>
                                <div class="small text-secondary">Registration due: <?php echo h($report['annual_registration_due']); ?></div>
                            </div>
                            <div class="d-flex gap-2 flex-wrap mt-3">
                                <a href="/backend/export_management_report.php?year=<?php echo urlencode((string) $reportYear); ?>" class="btn btn-success">
                                    <i class="bi bi-download me-1"></i> Download CSV
                                </a>
                                <button type="button" class="btn btn-outline-success" onclick="window.print()">
                                    <i class="bi bi-printer me-1"></i> Print
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 g-lg-4 mt-1">
                    <div class="col-md-4">
                        <div class="mini-result h-100">
                            <div class="fw-semibold">Users by role</div>
                            <div class="small text-secondary mt-2">Admins: <?php echo h($report['admins']); ?></div>
                            <div class="small text-secondary">Officers: <?php echo h($report['officers']); ?></div>
                            <div class="small text-secondary">Owners: <?php echo h($report['owners']); ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mini-result h-100">
                            <div class="fw-semibold">Compliance status</div>
                            <div class="small text-secondary mt-2">Insurance valid: <?php echo h($report['valid_insurance']); ?></div>
                            <div class="small text-secondary">Insurance expired: <?php echo h($report['expired_insurance']); ?></div>
                            <div class="small text-secondary">Licence valid: <?php echo h($report['valid_licence']); ?></div>
                            <div class="small text-secondary">Registration valid: <?php echo h($report['valid_registration']); ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mini-result h-100">
                            <div class="fw-semibold">Account control</div>
                            <div class="small text-secondary mt-2">Deactivate, re-enable, or remove accounts that should not remain active.</div>
                            <div class="small text-secondary">Use the role and password fields in the user table to manage access.</div>
                        </div>
                    </div>
                </div>

                <?php if ($flash): ?>
                    <div class="alert alert-<?php echo h($flash['type'] ?? 'info'); ?> mt-4" role="alert">
                        <?php echo h($flash['message'] ?? ''); ?>
                        <?php if (!empty($flash['generated_password'])): ?>
                            <div class="small mt-2"><strong>Temporary password:</strong> <?php echo h($flash['generated_password']); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="section-band bg-white">
            <div class="container py-5">
                <div class="d-flex align-items-end justify-content-between flex-wrap gap-3 mb-4">
                    <div>
                        <span class="eyebrow text-success fw-semibold">User management</span>
                        <h2 class="section-title mt-2 mb-0">Update roles, reset passwords, and manage officer accounts.</h2>
                    </div>
                    <div class="text-secondary small">
                        Current admin staff ID:
                        <span class="badge bg-success-subtle text-success border border-success border-opacity-25 ms-1"><?php echo h($_SESSION['staff_id'] ?? 'N/A'); ?></span>
                    </div>
                </div>

                <div class="table-responsive admin-table-wrap">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Staff ID</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Reset Password</th>
                                <th>Created</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td class="fw-semibold"><?php echo h($user['name']); ?></td>
                                    <td><?php echo h($user['email']); ?></td>
                                    <td>
                                        <?php if (!empty($user['staff_id'])): ?>
                                            <span class="badge bg-success-subtle text-success border border-success border-opacity-25"><?php echo h($user['staff_id']); ?></span>
                                        <?php else: ?>
                                            <span class="text-secondary small">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form action="/backend/admin_actions.php" method="POST" class="d-flex gap-2 flex-wrap align-items-center">
                                            <input type="hidden" name="action" value="update_role">
                                            <input type="hidden" name="user_id" value="<?php echo h($user['user_id']); ?>">
                                            <select name="role" class="form-select form-select-sm" style="min-width: 7rem;">
                                                <option value="owner" <?php echo $user['role'] === 'owner' ? 'selected' : ''; ?>>Owner</option>
                                                <option value="officer" <?php echo $user['role'] === 'officer' ? 'selected' : ''; ?>>Officer</option>
                                                <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                            </select>
                                            <input
                                                type="text"
                                                name="staff_id"
                                                class="form-control form-control-sm"
                                                style="max-width: 8.5rem;"
                                                placeholder="Staff ID"
                                                value="<?php echo h($user['staff_id'] ?? ''); ?>"
                                            >
                                            <button type="submit" class="btn btn-sm btn-outline-success">Save</button>
                                        </form>
                                    </td>
                                    <td>
                                        <?php if ((int) $user['is_active'] === 1): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="min-width: 18rem;">
                                        <form action="/backend/admin_actions.php" method="POST" class="d-flex gap-2 flex-wrap align-items-center">
                                            <input type="hidden" name="action" value="reset_password">
                                            <input type="hidden" name="user_id" value="<?php echo h($user['user_id']); ?>">
                                            <input type="password" name="new_password" class="form-control form-control-sm" placeholder="New password">
                                            <input type="password" name="confirm_password" class="form-control form-control-sm" placeholder="Confirm">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">Reset</button>
                                        </form>
                                    </td>
                                    <td class="text-secondary small"><?php echo h($user['created_at']); ?></td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-2 flex-wrap justify-content-end">
                                            <form method="POST" action="/backend/admin_actions.php" class="d-inline">
                                                <input type="hidden" name="user_id" value="<?php echo h($user['user_id']); ?>">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="is_active" value="<?php echo h($user['is_active']); ?>">
                                                <button
                                                    type="submit"
                                                    class="btn btn-sm <?php echo ((int) $user['is_active'] === 1) ? 'btn-outline-warning' : 'btn-outline-success'; ?>"
                                                    onclick="return confirm('Update this account status?');"
                                                >
                                                    <?php echo ((int) $user['is_active'] === 1) ? 'Deactivate' : 'Activate'; ?>
                                                </button>
                                            </form>
                                            <form method="POST" action="/backend/admin_actions.php" class="d-inline">
                                                <input type="hidden" name="user_id" value="<?php echo h($user['user_id']); ?>">
                                                <input type="hidden" name="action" value="delete_user">
                                                <button
                                                    type="submit"
                                                    class="btn btn-sm btn-outline-danger"
                                                    onclick="return confirm('Remove this user from the system? This will also remove linked records through database rules.');"
                                                >
                                                    Remove
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
