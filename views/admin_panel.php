<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: /login');
    exit;
}

require_once '../config/db.php';
require_once __DIR__ . '/../backend/auth_helpers.php';

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$currentAdminId = (int) $_SESSION['user_id'];
$flash = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']);
$flashType = is_array($flash) ? ($flash['type'] ?? 'info') : 'info';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        $targetUserId = (int) ($_POST['user_id'] ?? 0);

        if ($targetUserId <= 0) {
            $flash = 'Select a user first.';
            $flashType = 'warning';
        } elseif ($targetUserId === $currentAdminId) {
            $flash = 'You cannot change your own account from this screen.';
            $flashType = 'warning';
        } elseif ($action === 'deactivate') {
            $stmt = $pdo->prepare("UPDATE users SET is_active = 0 WHERE user_id = :user_id AND role <> 'admin'");
            $stmt->execute(['user_id' => $targetUserId]);
            $flash = $stmt->rowCount() > 0 ? 'Account deactivated.' : 'That account could not be deactivated.';
            $flashType = $stmt->rowCount() > 0 ? 'success' : 'warning';
        } elseif ($action === 'activate') {
            $stmt = $pdo->prepare("UPDATE users SET is_active = 1 WHERE user_id = :user_id");
            $stmt->execute(['user_id' => $targetUserId]);
            $flash = $stmt->rowCount() > 0 ? 'Account reactivated.' : 'That account could not be reactivated.';
            $flashType = $stmt->rowCount() > 0 ? 'success' : 'warning';
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = :user_id AND role <> 'admin'");
            $stmt->execute(['user_id' => $targetUserId]);
            $flash = $stmt->rowCount() > 0 ? 'User removed from the system.' : 'That account could not be removed.';
            $flashType = $stmt->rowCount() > 0 ? 'success' : 'warning';
        }
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    $flash = 'Database error while updating the account.';
    $flashType = 'danger';
}

$users = [];
$userRolesByUserId = [];
$report = [
    'total_users' => 0,
    'active_users' => 0,
    'inactive_users' => 0,
    'admins' => 0,
    'officers' => 0,
    'owners' => 0,
    'vehicles' => 0,
    'valid_insurance' => 0,
    'expired_insurance' => 0,
    'valid_licence' => 0,
    'expired_licence' => 0,
    'valid_registration' => 0,
    'expired_registration' => 0,
];

try {
    $users = $pdo->query(
        "SELECT user_id, name, email, role, COALESCE(is_active, 1) AS is_active, created_at
         FROM users
         ORDER BY created_at DESC, user_id DESC"
    )->fetchAll();

    if (vcs_has_table($pdo, 'user_roles')) {
        $userRoles = $pdo->query(
            "SELECT user_id, role, is_primary
             FROM user_roles
             ORDER BY user_id ASC, is_primary DESC, role ASC"
        )->fetchAll();

        foreach ($userRoles as $userRole) {
            $userId = (int) $userRole['user_id'];
            $userRolesByUserId[$userId][] = [
                'role' => $userRole['role'],
                'is_primary' => (int) $userRole['is_primary'] === 1,
            ];
        }
    }

    $report['total_users'] = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $report['active_users'] = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE COALESCE(is_active, 1) = 1")->fetchColumn();
    $report['inactive_users'] = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE COALESCE(is_active, 1) = 0")->fetchColumn();
    $report['admins'] = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    $report['officers'] = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'officer'")->fetchColumn();
    $report['owners'] = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'owner'")->fetchColumn();
    $report['vehicles'] = (int) $pdo->query("SELECT COUNT(*) FROM vehicles")->fetchColumn();
    $report['valid_insurance'] = (int) $pdo->query("SELECT COUNT(*) FROM compliance_records WHERE insurance_status = 'Valid'")->fetchColumn();
    $report['expired_insurance'] = (int) $pdo->query("SELECT COUNT(*) FROM compliance_records WHERE insurance_status = 'Expired'")->fetchColumn();
    $report['valid_licence'] = (int) $pdo->query("SELECT COUNT(*) FROM compliance_records WHERE licence_status = 'Valid'")->fetchColumn();
    $report['expired_licence'] = (int) $pdo->query("SELECT COUNT(*) FROM compliance_records WHERE licence_status = 'Expired'")->fetchColumn();
    $report['valid_registration'] = (int) $pdo->query("SELECT COUNT(*) FROM compliance_records WHERE registration_status = 'Valid'")->fetchColumn();
    $report['expired_registration'] = (int) $pdo->query("SELECT COUNT(*) FROM compliance_records WHERE registration_status = 'Expired'")->fetchColumn();
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
                        <h1 class="section-title mt-2">Manage accounts and review system reports.</h1>
                        <p class="text-secondary mb-0">
                            Use this panel to deactivate accounts, remove users who should no longer have access, and generate a quick operational report for the system.
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
                            <div class="small text-secondary text-uppercase fw-semibold">Inactive</div>
                            <div class="display-6 fw-bold text-danger mt-1"><?php echo h($report['inactive_users']); ?></div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="value-card h-100">
                            <div class="small text-secondary text-uppercase fw-semibold">Vehicles</div>
                            <div class="display-6 fw-bold text-success mt-1"><?php echo h($report['vehicles']); ?></div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 search-card shadow-sm">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div>
                            <span class="text-uppercase small fw-semibold text-success">Generate system reports</span>
                            <h2 class="h4 fw-bold mb-0">Operational summary</h2>
                        </div>
                        <button class="btn btn-outline-success" type="button" onclick="window.print()">
                            <i class="bi bi-printer me-1"></i> Print Report
                        </button>
                    </div>
                    <div class="row g-3 mt-3">
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
                                <div class="small text-secondary mt-2">Deactivate accounts that should no longer have access.</div>
                                <div class="small text-secondary">Remove users from the system when needed.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($flash): ?>
                    <div class="alert alert-<?php echo h($flashType); ?> mt-4" role="alert">
                        <?php echo h(is_array($flash) ? ($flash['message'] ?? '') : $flash); ?>
                    </div>
                <?php endif; ?>

                <div class="mt-4 search-card shadow-sm">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div>
                            <span class="text-uppercase small fw-semibold text-success">Role assignment</span>
                            <h2 class="h4 fw-bold mb-0">Give one account multiple roles</h2>
                        </div>
                        <i class="bi bi-person-badge text-success fs-3"></i>
                    </div>

                    <form action="/backend/manage_user_roles.php" method="POST" class="mt-4">
                        <div class="row g-3">
                            <div class="col-lg-4">
                                <label for="user_id" class="form-label fw-semibold text-secondary">User</label>
                                <select id="user_id" name="user_id" class="form-select form-select-lg" required>
                                    <option value="">Select a user</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo h($user['user_id']); ?>">
                                            <?php echo h($user['name']); ?> (<?php echo h($user['email']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-lg-5">
                                <label class="form-label fw-semibold text-secondary">Roles</label>
                                <div class="d-flex flex-wrap gap-3">
                                    <?php foreach (vcs_admin_role_labels() as $roleValue => $roleLabel): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="roles[]" value="<?php echo h($roleValue); ?>" id="role_<?php echo h($roleValue); ?>" checked>
                                            <label class="form-check-label" for="role_<?php echo h($roleValue); ?>"><?php echo h($roleLabel); ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <label for="primary_role" class="form-label fw-semibold text-secondary">Primary role</label>
                                <select id="primary_role" name="primary_role" class="form-select form-select-lg" required>
                                    <?php foreach (vcs_admin_role_labels() as $roleValue => $roleLabel): ?>
                                        <option value="<?php echo h($roleValue); ?>"><?php echo h($roleLabel); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-3">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-save me-1"></i> Save Roles
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </section>

        <section class="section-band bg-white">
            <div class="container py-5">
                <div class="d-flex align-items-end justify-content-between flex-wrap gap-3 mb-4">
                    <div>
                        <span class="eyebrow text-success fw-semibold">User management</span>
                        <h2 class="section-title mt-2 mb-0">Deactivate or remove accounts.</h2>
                    </div>
                </div>

                <div class="table-responsive admin-table-wrap">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Assigned Roles</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td class="fw-semibold"><?php echo h($user['name']); ?></td>
                                    <td><?php echo h($user['email']); ?></td>
                                    <td class="text-capitalize"><?php echo h($user['role']); ?></td>
                                    <td>
                                        <?php if (!empty($userRolesByUserId[(int) $user['user_id']])): ?>
                                            <div class="d-flex flex-wrap gap-2">
                                                <?php foreach ($userRolesByUserId[(int) $user['user_id']] as $roleItem): ?>
                                                    <span class="badge <?php echo $roleItem['is_primary'] ? 'bg-success' : 'bg-secondary'; ?>">
                                                        <?php echo h(vcs_role_label($roleItem['role'])); ?><?php echo $roleItem['is_primary'] ? ' (Primary)' : ''; ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-secondary small">No role records</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ((int) $user['is_active'] === 1): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-secondary small"><?php echo h($user['created_at']); ?></td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-2 flex-wrap justify-content-end">
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="user_id" value="<?php echo h($user['user_id']); ?>">
                                                <input type="hidden" name="action" value="<?php echo ((int) $user['is_active'] === 1) ? 'deactivate' : 'activate'; ?>">
                                                <button
                                                    type="submit"
                                                    class="btn btn-sm <?php echo ((int) $user['is_active'] === 1) ? 'btn-outline-warning' : 'btn-outline-success'; ?>"
                                                    onclick="return confirm('Update this account status?');"
                                                >
                                                    <?php echo ((int) $user['is_active'] === 1) ? 'Deactivate' : 'Activate'; ?>
                                                </button>
                                            </form>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="user_id" value="<?php echo h($user['user_id']); ?>">
                                                <input type="hidden" name="action" value="delete">
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
