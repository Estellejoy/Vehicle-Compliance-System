<?php

require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/auth_helpers.php';

function vcs_notification_now(): DateTimeImmutable
{
    return new DateTimeImmutable('now', new DateTimeZone('Africa/Nairobi'));
}

function vcs_notification_today(): string
{
    return vcs_notification_now()->format('Y-m-d');
}

function vcs_notification_timestamp(?DateTimeImmutable $moment = null): string
{
    return ($moment ?? vcs_notification_now())->format('Y-m-d H:i:s');
}

function vcs_notification_event_code(string $kind, int $vehicleId, string $eventDate): string
{
    return strtolower(trim($kind)) . ':' . $vehicleId . ':' . $eventDate;
}

function vcs_notification_message_for_reminder(array $vehicle, string $field, string $expiryDate, int $daysRemaining): string
{
    $plateNumber = trim((string) ($vehicle['plate_number'] ?? 'this vehicle'));
    $label = strtolower(trim($field)) === 'licence' ? 'driving licence' : 'insurance';

    return "Your {$label} for vehicle {$plateNumber} expires in {$daysRemaining} days on {$expiryDate}.";
}

function vcs_notification_message_for_inspection(array $vehicle, string $inspectionStatus, string $checkedAt): string
{
    $plateNumber = trim((string) ($vehicle['plate_number'] ?? 'this vehicle'));

    return "Your vehicle {$plateNumber} inspection status has been updated to {$inspectionStatus} on {$checkedAt}.";
}

function vcs_notification_has_schema(PDO $pdo): bool
{
    return vcs_has_column($pdo, 'notifications', 'vehicle_id')
        && vcs_has_column($pdo, 'notifications', 'event_code')
        && vcs_has_column($pdo, 'notifications', 'event_date');
}

function vcs_insert_notification(PDO $pdo, array $data): bool
{
    $hasSchema = vcs_notification_has_schema($pdo);
    $baseColumns = [
        'user_id' => (int) ($data['user_id'] ?? 0),
        'notification_type' => trim((string) ($data['notification_type'] ?? 'Notification')),
        'message' => trim((string) ($data['message'] ?? '')),
        'status' => trim((string) ($data['status'] ?? 'Unread')),
        'date_sent' => trim((string) ($data['date_sent'] ?? vcs_notification_today())),
    ];

    if ($baseColumns['user_id'] <= 0 || $baseColumns['message'] === '') {
        return false;
    }

    if (!$hasSchema) {
        $stmt = $pdo->prepare(
            'INSERT INTO notifications (user_id, notification_type, message, status, date_sent)
             VALUES (:user_id, :notification_type, :message, :status, :date_sent)'
        );
        $stmt->execute($baseColumns);

        return $stmt->rowCount() > 0;
    }

    $columns = $baseColumns + [
        'vehicle_id' => array_key_exists('vehicle_id', $data) && $data['vehicle_id'] !== null ? (int) $data['vehicle_id'] : null,
        'event_code' => trim((string) ($data['event_code'] ?? '')),
        'event_date' => array_key_exists('event_date', $data) && $data['event_date'] !== null ? trim((string) $data['event_date']) : null,
    ];

    $stmt = $pdo->prepare(
        'INSERT IGNORE INTO notifications (
            user_id,
            vehicle_id,
            notification_type,
            event_code,
            event_date,
            message,
            status,
            date_sent
        ) VALUES (
            :user_id,
            :vehicle_id,
            :notification_type,
            :event_code,
            :event_date,
            :message,
            :status,
            :date_sent
        )'
    );
    $stmt->execute($columns);

    return $stmt->rowCount() > 0;
}

function vcs_fetch_active_users_by_role(PDO $pdo, string $role): array
{
    $stmt = $pdo->prepare(
        'SELECT user_id, name, email, role, badge_number, staff_id
         FROM users
         WHERE role = :role AND COALESCE(is_active, 1) = 1
         ORDER BY user_id ASC'
    );
    $stmt->execute(['role' => vcs_normalize_role($role)]);

    return $stmt->fetchAll();
}

function vcs_fetch_vehicle_owner(PDO $pdo, int $vehicleId): ?array
{
    $stmt = $pdo->prepare(
        'SELECT v.vehicle_id,
                v.plate_number,
                v.inspection_status,
                v.inspection_checked_at,
                v.inspection_checked_by,
                owner.user_id,
                owner.name,
                owner.email
         FROM vehicles v
         INNER JOIN users owner ON owner.user_id = v.owner_id
         WHERE v.vehicle_id = :vehicle_id
         LIMIT 1'
    );
    $stmt->execute(['vehicle_id' => $vehicleId]);

    $row = $stmt->fetch();

    return $row ?: null;
}

function vcs_send_admin_exception(PDO $pdo, string $subject, string $details): bool
{
    $admins = vcs_fetch_active_users_by_role($pdo, 'admin');
    $sent = false;

    foreach ($admins as $admin) {
        if (empty($admin['email']) || !filter_var($admin['email'], FILTER_VALIDATE_EMAIL)) {
            continue;
        }

        $sent = sendExceptionAlertEmail(
            (string) $admin['email'],
            (string) $admin['name'],
            $subject,
            $details
        ) || $sent;
    }

    return $sent;
}

function vcs_send_officer_exception(PDO $pdo, int $officerId, string $subject, string $details): bool
{
    $stmt = $pdo->prepare(
        'SELECT user_id, name, email
         FROM users
         WHERE user_id = :user_id AND role = :role
         LIMIT 1'
    );
    $stmt->execute([
        'user_id' => $officerId,
        'role' => 'officer',
    ]);

    $officer = $stmt->fetch();

    if (!$officer || empty($officer['email']) || !filter_var($officer['email'], FILTER_VALIDATE_EMAIL)) {
        return vcs_send_admin_exception($pdo, $subject, $details);
    }

    return sendExceptionAlertEmail(
        (string) $officer['email'],
        (string) $officer['name'],
        $subject,
        $details
    );
}

function vcs_send_owner_expiry_notification(
    PDO $pdo,
    array $owner,
    array $vehicle,
    array $compliance,
    string $field,
    int $daysRemaining
): array {
    $field = strtolower(trim($field));
    $expiryDate = (string) ($compliance[$field . '_expiry'] ?? '');
    $vehicleId = (int) ($vehicle['vehicle_id'] ?? 0);
    $notificationType = ucfirst($field) . ' Reminder';
    $eventCode = vcs_notification_event_code($field . '_expiry_reminder', $vehicleId, $expiryDate);
    $message = vcs_notification_message_for_reminder($vehicle, $field, $expiryDate, $daysRemaining);

    $saved = vcs_insert_notification($pdo, [
        'user_id' => (int) ($owner['user_id'] ?? 0),
        'vehicle_id' => $vehicleId,
        'notification_type' => $notificationType,
        'event_code' => $eventCode,
        'event_date' => $expiryDate,
        'message' => $message,
        'status' => 'Unread',
        'date_sent' => vcs_notification_today(),
    ]);

    if (!$saved) {
        return [
            'saved' => false,
            'email_sent' => false,
            'duplicate' => true,
        ];
    }

    $emailSent = false;
    if (!empty($owner['email']) && filter_var($owner['email'], FILTER_VALIDATE_EMAIL)) {
        $emailSent = sendVehicleExpiryReminderEmail(
            (string) $owner['email'],
            (string) $owner['name'],
            (string) ($vehicle['plate_number'] ?? ''),
            $field,
            $expiryDate,
            $daysRemaining
        );
    }

    if (!$emailSent) {
        vcs_send_admin_exception(
            $pdo,
            'Vehicle Compliance reminder delivery failure',
            sprintf(
                'Reminder delivery failed for user #%d, vehicle #%d, reminder type %s, expiry date %s.',
                (int) ($owner['user_id'] ?? 0),
                $vehicleId,
                $field,
                $expiryDate
            )
        );
    }

    return [
        'saved' => true,
        'email_sent' => $emailSent,
        'duplicate' => false,
    ];
}

function vcs_send_owner_inspection_notification(
    PDO $pdo,
    array $owner,
    array $vehicle,
    string $inspectionStatus,
    string $checkedAt,
    string $checkedBy = ''
): array {
    $vehicleId = (int) ($vehicle['vehicle_id'] ?? 0);
    $eventDate = substr($checkedAt, 0, 10);
    $eventCode = vcs_notification_event_code('inspection_status_update', $vehicleId, $eventDate);
    $message = vcs_notification_message_for_inspection($vehicle, $inspectionStatus, $checkedAt);

    $saved = vcs_insert_notification($pdo, [
        'user_id' => (int) ($owner['user_id'] ?? 0),
        'vehicle_id' => $vehicleId,
        'notification_type' => 'Inspection Status',
        'event_code' => $eventCode,
        'event_date' => $eventDate,
        'message' => $message,
        'status' => 'Unread',
        'date_sent' => $eventDate,
    ]);

    if (!$saved) {
        return [
            'saved' => false,
            'email_sent' => false,
            'duplicate' => true,
        ];
    }

    $emailSent = false;
    if (!empty($owner['email']) && filter_var($owner['email'], FILTER_VALIDATE_EMAIL)) {
        $emailSent = sendVehicleInspectionStatusEmail(
            (string) $owner['email'],
            (string) $owner['name'],
            (string) ($vehicle['plate_number'] ?? ''),
            $inspectionStatus,
            $checkedAt,
            $checkedBy
        );
    }

    if (!$emailSent) {
        vcs_send_admin_exception(
            $pdo,
            'Vehicle inspection notification delivery failure',
            sprintf(
                'Inspection status mail failed for user #%d, vehicle #%d at %s.',
                (int) ($owner['user_id'] ?? 0),
                $vehicleId,
                $checkedAt
            )
        );
    }

    return [
        'saved' => true,
        'email_sent' => $emailSent,
        'duplicate' => false,
    ];
}

function vcs_send_expiry_notifications(PDO $pdo): array
{
    $results = [
        'insurance' => 0,
        'licence' => 0,
        'skipped' => 0,
        'failed' => 0,
    ];

    $targetDate = (new DateTimeImmutable('+14 days', new DateTimeZone('Africa/Nairobi')))->format('Y-m-d');
    $stmt = $pdo->prepare(
        'SELECT v.vehicle_id,
                v.plate_number,
                owner.user_id,
                owner.name,
                owner.email,
                c.insurance_expiry,
                c.licence_expiry
         FROM compliance_records c
         INNER JOIN vehicles v ON v.vehicle_id = c.vehicle_id
         INNER JOIN users owner ON owner.user_id = v.owner_id
         WHERE (c.insurance_expiry = :insurance_target_date OR c.licence_expiry = :licence_target_date)
           AND COALESCE(owner.is_active, 1) = 1
         ORDER BY v.vehicle_id ASC'
    );
    $stmt->execute([
        'insurance_target_date' => $targetDate,
        'licence_target_date' => $targetDate,
    ]);

    foreach ($stmt->fetchAll() as $row) {
        foreach (['insurance', 'licence'] as $field) {
            if ((string) ($row[$field . '_expiry'] ?? '') !== $targetDate) {
                continue;
            }

            try {
                $response = vcs_send_owner_expiry_notification($pdo, $row, $row, $row, $field, 14);
                if ($response['duplicate']) {
                    $results['skipped']++;
                    continue;
                }

                if ($response['saved']) {
                    $results[$field]++;
                    if (!$response['email_sent']) {
                        $results['failed']++;
                    }
                }
            } catch (Throwable $e) {
                $results['failed']++;
                error_log('Expiry notification dispatch failed: ' . $e->getMessage());
                vcs_send_admin_exception(
                    $pdo,
                    'Expiry reminder job failure',
                    sprintf(
                        'Reminder dispatch failed for vehicle ID %d, reminder type %s, expiry date %s. Error: %s',
                        (int) ($row['vehicle_id'] ?? 0),
                        $field,
                        $targetDate,
                        $e->getMessage()
                    )
                );
            }
        }
    }

    return $results;
}
