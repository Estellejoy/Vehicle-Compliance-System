<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'officer') {
    header('Location: ../views/login.php');
    exit;
}

require_once '../config/db.php';
require_once __DIR__ . '/auth_helpers.php';
require_once __DIR__ . '/notification_service.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/officer_dashboard.php');
    exit;
}

$vehicleId = (int) ($_POST['vehicle_id'] ?? 0);
$plateNumber = trim($_POST['plate_number'] ?? '');
$action = trim($_POST['action'] ?? '');
$checkedBy = (int) ($_SESSION['user_id'] ?? 0);

if ($vehicleId <= 0 || $action !== 'mark_inspected') {
    header('Location: ../views/officer_dashboard.php?error=invalid_request');
    exit;
}

function vcs_redirect_officer_dashboard(array $params = []): void
{
    $query = http_build_query(array_filter($params, static fn ($value) => $value !== ''));
    $url = '../views/officer_dashboard.php';
    if ($query !== '') {
        $url .= '?' . $query;
    }

    header('Location: ' . $url);
    exit;
}

try {
    $statusColumn = $pdo->query("SHOW COLUMNS FROM vehicles LIKE 'inspection_status'")->fetch();
    $checkedAtColumn = $pdo->query("SHOW COLUMNS FROM vehicles LIKE 'inspection_checked_at'")->fetch();
    $checkedByColumn = $pdo->query("SHOW COLUMNS FROM vehicles LIKE 'inspection_checked_by'")->fetch();

    if (!$statusColumn || !$checkedAtColumn || !$checkedByColumn) {
        vcs_send_officer_exception(
            $pdo,
            $checkedBy,
            'Inspection update blocked by a schema problem',
            'The vehicles table is missing one or more inspection columns. Apply the inspection migrations before retrying the inspection update.'
        );

        vcs_redirect_officer_dashboard([
            'plate_number' => $plateNumber,
            'error' => 'inspection_columns_missing',
        ]);
    }

    $pdo->beginTransaction();

    $vehicleStmt = $pdo->prepare(
        "SELECT v.vehicle_id,
                v.plate_number,
                v.inspection_status,
                owner.user_id,
                owner.name,
                owner.email
         FROM vehicles v
         INNER JOIN users owner ON owner.user_id = v.owner_id
         WHERE v.vehicle_id = :vehicle_id
         LIMIT 1
         FOR UPDATE"
    );
    $vehicleStmt->execute(['vehicle_id' => $vehicleId]);
    $vehicle = $vehicleStmt->fetch();

    if (!$vehicle) {
        throw new RuntimeException('Vehicle not found.');
    }

    $checkedAt = vcs_notification_timestamp();
    $previousStatus = trim((string) ($vehicle['inspection_status'] ?? ''));

    $stmt = $pdo->prepare(
        "UPDATE vehicles
         SET inspection_status = 'Checked',
             inspection_checked_at = :checked_at,
             inspection_checked_by = :checked_by
         WHERE vehicle_id = :vehicle_id"
    );
    $stmt->execute([
        'vehicle_id' => $vehicleId,
        'checked_at' => $checkedAt,
        'checked_by' => $checkedBy > 0 ? $checkedBy : null,
    ]);

    $pdo->commit();

    if (strcasecmp($previousStatus, 'Checked') !== 0) {
        try {
            $officerStmt = $pdo->prepare(
                'SELECT user_id, name, email, badge_number, staff_id
                 FROM users
                 WHERE user_id = :user_id
                 LIMIT 1'
            );
            $officerStmt->execute(['user_id' => $checkedBy]);
            $officer = $officerStmt->fetch() ?: [];
            $checkedByLabel = trim((string) ($officer['badge_number'] ?? $officer['staff_id'] ?? $officer['name'] ?? ''));

            vcs_send_owner_inspection_notification(
                $pdo,
                $vehicle,
                $vehicle,
                'Checked',
                $checkedAt,
                $checkedByLabel
            );
        } catch (Throwable $notificationError) {
            error_log('Inspection notification failed: ' . $notificationError->getMessage());
            vcs_send_admin_exception(
                $pdo,
                'Inspection notification failure',
                sprintf(
                    'Inspection update for vehicle ID %d succeeded, but the notification step failed. Error: %s',
                    $vehicleId,
                    $notificationError->getMessage()
                )
            );
        }
    }

    vcs_redirect_officer_dashboard([
        'plate_number' => $plateNumber,
        'updated' => '1',
    ]);
} catch (Throwable $e) {
    if ($pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());
    vcs_send_officer_exception(
        $pdo,
        $checkedBy,
        'Inspection update failed',
        sprintf(
            'Inspection update failed for vehicle ID %d (%s). Error: %s',
            $vehicleId,
            $plateNumber !== '' ? $plateNumber : 'unknown plate',
            $e->getMessage()
        )
    );

    vcs_redirect_officer_dashboard([
        'plate_number' => $plateNumber,
        'error' => 'update_failed',
    ]);
}
