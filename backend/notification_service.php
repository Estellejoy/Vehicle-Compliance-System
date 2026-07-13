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
    $label = strtolower(trim($field)) === 'licence' ? 'driving licence' : $field;

    return "Your {$label} for vehicle {$plateNumber} expires in {$daysRemaining} days on {$expiryDate}.";
}

function vcs_notification_message_for_inspection(
    array $vehicle,
    string $inspectionStatus,
    string $checkedAt,
    string $failureReason = ''
): string {
    $plateNumber = trim((string) ($vehicle['plate_number'] ?? 'this vehicle'));
    $message = "Your vehicle {$plateNumber} inspection status has been updated to {$inspectionStatus} on {$checkedAt}.";

    if ($failureReason !== '') {
        $message .= " Reason: {$failureReason}";
    }

    return $message;
}

function vcs_notification_has_schema(PDO $pdo): bool
{
    return vcs_has_column($pdo, 'notifications', 'vehicle_id')
        && vcs_has_column($pdo, 'notifications', 'event_code')
        && vcs_has_column($pdo, 'notifications', 'event_date');
}

function vcs_insert_notification(PDO $pdo, array $data): int
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
        return 0;
    }

    try {
        if (!$hasSchema) {
            $stmt = $pdo->prepare(
                'INSERT INTO notifications (user_id, notification_type, message, status, date_sent)
                 VALUES (:user_id, :notification_type, :message, :status, :date_sent)'
            );
            $stmt->execute($baseColumns);

            return (int) $pdo->lastInsertId();
        }

        $columns = $baseColumns + [
            'vehicle_id' => array_key_exists('vehicle_id', $data) && $data['vehicle_id'] !== null ? (int) $data['vehicle_id'] : null,
            'event_code' => trim((string) ($data['event_code'] ?? '')) ?: null,
            'event_date' => array_key_exists('event_date', $data) && $data['event_date'] !== null ? trim((string) $data['event_date']) : null,
        ];

        $stmt = $pdo->prepare(
            'INSERT INTO notifications (
                user_id, vehicle_id, notification_type, event_code, event_date,
                message, status, date_sent
             ) VALUES (
                :user_id, :vehicle_id, :notification_type, :event_code, :event_date,
                :message, :status, :date_sent
             )'
        );
        $stmt->execute($columns);

        return (int) $pdo->lastInsertId();
    } catch (PDOException $e) {
        // The event uniqueness key is an intentional idempotency guard.
        $mysqlErrorCode = (int) ($e->errorInfo[1] ?? 0);
        if ($mysqlErrorCode === 1062) {
            return 0;
        }

        throw $e;
    }
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
        'SELECT v.vehicle_id, v.plate_number, v.inspection_status,
                v.inspection_checked_at, v.inspection_checked_by,
                v.inspection_failure_reason,
                owner.user_id, owner.name, owner.email
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
    $stmt->execute(['user_id' => $officerId, 'role' => 'officer']);
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

function vcs_notification_delivery_available(PDO $pdo): bool
{
    return vcs_has_table($pdo, 'notification_deliveries');
}

function vcs_queue_email_delivery(
    PDO $pdo,
    int $notificationId,
    string $toEmail,
    string $toName,
    string $subject,
    string $htmlBody,
    string $textBody
): ?int {
    if (!vcs_notification_delivery_available($pdo)) {
        return null;
    }

    $stmt = $pdo->prepare(
        'INSERT IGNORE INTO notification_deliveries
            (notification_id, channel, recipient_email, recipient_name, subject, html_body, text_body, status, next_attempt_at)
         VALUES
            (:notification_id, :channel, :recipient_email, :recipient_name, :subject, :html_body, :text_body, :status, NULL)'
    );
    $stmt->execute([
        'notification_id' => $notificationId,
        'channel' => 'email',
        'recipient_email' => $toEmail,
        'recipient_name' => $toName,
        'subject' => $subject,
        'html_body' => $htmlBody,
        'text_body' => $textBody,
        'status' => 'Pending',
    ]);

    $lookup = $pdo->prepare(
        'SELECT delivery_id
         FROM notification_deliveries
         WHERE notification_id = :notification_id AND channel = :channel
         LIMIT 1'
    );
    $lookup->execute(['notification_id' => $notificationId, 'channel' => 'email']);

    $deliveryId = $lookup->fetchColumn();

    return $deliveryId === false ? null : (int) $deliveryId;
}

function vcs_attempt_email_delivery(PDO $pdo, int $deliveryId): bool
{
    $stmt = $pdo->prepare(
        'SELECT delivery_id, recipient_email, recipient_name, subject, html_body, text_body,
                status, attempts
         FROM notification_deliveries
         WHERE delivery_id = :delivery_id
         LIMIT 1'
    );
    $stmt->execute(['delivery_id' => $deliveryId]);
    $delivery = $stmt->fetch();

    if (!$delivery || $delivery['status'] === 'Sent' || $delivery['status'] === 'Sending') {
        return $delivery && $delivery['status'] === 'Sent';
    }

    $claim = $pdo->prepare(
        "UPDATE notification_deliveries
         SET status = 'Sending'
         WHERE delivery_id = :delivery_id AND status IN ('Pending', 'Failed')"
    );
    $claim->execute(['delivery_id' => $deliveryId]);

    if ($claim->rowCount() !== 1) {
        return false;
    }

    $sent = false;
    if (filter_var((string) $delivery['recipient_email'], FILTER_VALIDATE_EMAIL)) {
        $sent = sendMailMessage(
            (string) $delivery['recipient_email'],
            (string) $delivery['recipient_name'],
            (string) $delivery['subject'],
            (string) $delivery['html_body'],
            (string) $delivery['text_body']
        );
    }

    $attempts = (int) $delivery['attempts'] + 1;
    if ($sent) {
        $update = $pdo->prepare(
            "UPDATE notification_deliveries
             SET status = 'Sent', attempts = :attempts, last_error = NULL,
                 sent_at = :sent_at, next_attempt_at = NULL
             WHERE delivery_id = :delivery_id"
        );
        $update->execute([
            'attempts' => $attempts,
            'sent_at' => vcs_notification_timestamp(),
            'delivery_id' => $deliveryId,
        ]);

        return true;
    }

    $retryMinutes = min(1440, 5 * (2 ** min($attempts - 1, 8)));
    $nextAttempt = vcs_notification_now()->modify('+' . $retryMinutes . ' minutes')->format('Y-m-d H:i:s');
    $update = $pdo->prepare(
        "UPDATE notification_deliveries
         SET status = 'Failed', attempts = :attempts,
             last_error = :last_error, next_attempt_at = :next_attempt_at
         WHERE delivery_id = :delivery_id"
    );
    $update->execute([
        'attempts' => $attempts,
        'last_error' => 'Email provider rejected or could not deliver the message. See application logs.',
        'next_attempt_at' => $nextAttempt,
        'delivery_id' => $deliveryId,
    ]);

    error_log(sprintf('Notification email delivery %d failed; retry scheduled for %s.', $deliveryId, $nextAttempt));

    return false;
}

function vcs_send_notification_email(
    PDO $pdo,
    int $notificationId,
    string $toEmail,
    string $toName,
    string $subject,
    string $htmlBody,
    string $textBody
): bool {
    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    if (!vcs_notification_delivery_available($pdo)) {
        return sendMailMessage($toEmail, $toName, $subject, $htmlBody, $textBody);
    }

    $deliveryId = vcs_queue_email_delivery(
        $pdo,
        $notificationId,
        $toEmail,
        $toName,
        $subject,
        $htmlBody,
        $textBody
    );

    return $deliveryId !== null && vcs_attempt_email_delivery($pdo, $deliveryId);
}

function vcs_process_pending_email_deliveries(PDO $pdo, int $limit = 100): array
{
    if (!vcs_notification_delivery_available($pdo)) {
        return ['processed' => 0, 'sent' => 0, 'failed' => 0];
    }

    $limit = max(1, min($limit, 500));
    $now = vcs_notification_timestamp();
    $stmt = $pdo->prepare(
        "SELECT delivery_id
         FROM notification_deliveries
         WHERE status IN ('Pending', 'Failed')
           AND attempts < 8
           AND (next_attempt_at IS NULL OR next_attempt_at <= :now)
         ORDER BY delivery_id ASC
         LIMIT {$limit}"
    );
    $stmt->execute(['now' => $now]);

    $result = ['processed' => 0, 'sent' => 0, 'failed' => 0];
    foreach ($stmt->fetchAll() as $row) {
        $result['processed']++;
        if (vcs_attempt_email_delivery($pdo, (int) $row['delivery_id'])) {
            $result['sent']++;
        } else {
            $result['failed']++;
        }
    }

    return $result;
}

function vcs_send_owner_inspection_notification(
    PDO $pdo,
    array $owner,
    array $vehicle,
    string $inspectionStatus,
    string $checkedAt,
    string $checkedBy = '',
    string $failureReason = ''
): array {
    $vehicleId = (int) ($vehicle['vehicle_id'] ?? 0);
    $eventCode = vcs_notification_event_code('inspection_status_update', $vehicleId, $checkedAt);
    $message = vcs_notification_message_for_inspection($vehicle, $inspectionStatus, $checkedAt, $failureReason);

    $notificationId = vcs_insert_notification($pdo, [
        'user_id' => (int) ($owner['user_id'] ?? 0),
        'vehicle_id' => $vehicleId,
        'notification_type' => 'Inspection Status',
        'event_code' => $eventCode,
        'event_date' => substr($checkedAt, 0, 10),
        'message' => substr($message, 0, 255),
        'status' => 'Unread',
        'date_sent' => substr($checkedAt, 0, 10),
    ]);

    if ($notificationId <= 0) {
        return ['saved' => false, 'email_sent' => false, 'duplicate' => true];
    }

    $emailSent = false;
    if (!empty($owner['email']) && filter_var($owner['email'], FILTER_VALIDATE_EMAIL)) {
        $emailSent = vcs_send_notification_email(
            $pdo,
            $notificationId,
            (string) $owner['email'],
            (string) ($owner['name'] ?? ''),
            'Inspection status updated for ' . (string) ($vehicle['plate_number'] ?? ''),
            buildVehicleInspectionStatusHtml(
                (string) ($owner['name'] ?? ''),
                (string) ($vehicle['plate_number'] ?? ''),
                $inspectionStatus,
                $checkedAt,
                $checkedBy,
                $failureReason
            ),
            buildVehicleInspectionStatusText(
                (string) ($owner['name'] ?? ''),
                (string) ($vehicle['plate_number'] ?? ''),
                $inspectionStatus,
                $checkedAt,
                $checkedBy,
                $failureReason
            )
        );
    }

    return [
        'saved' => true,
        'email_sent' => $emailSent,
        'duplicate' => false,
        'notification_id' => $notificationId,
    ];
}

function vcs_compliance_issues(array $vehicle, string $today): array
{
    $issues = [];
    if (empty($vehicle['compliance_vehicle_id'])) {
        $issues[] = 'compliance record is missing';
    }

    foreach ([
        'insurance' => 'Insurance',
        'licence' => 'Driving licence',
        'registration' => 'Registration',
    ] as $field => $label) {
        $status = trim((string) ($vehicle[$field . '_status'] ?? ''));
        $expiry = trim((string) ($vehicle[$field . '_expiry'] ?? ''));

        if ($status === '' || strcasecmp($status, 'Valid') !== 0) {
            $issues[] = $label . ' status: ' . ($status !== '' ? $status : 'Unknown');
        }
        if ($expiry !== '' && $expiry <= $today) {
            $issues[] = $label . ' expired on ' . $expiry;
        } elseif ($expiry !== '') {
            $fourteenDaysFromNow = (new DateTimeImmutable($today, new DateTimeZone('Africa/Nairobi')))
                ->modify('+14 days')
                ->format('Y-m-d');
            if ($expiry === $fourteenDaysFromNow) {
                $issues[] = $label . ' expires in 14 days on ' . $expiry;
            }
        }
    }

    $inspectionStatus = strtolower(trim((string) ($vehicle['inspection_status'] ?? '')));
    if (in_array($inspectionStatus, ['failed', 'fail', 'non-compliant', 'non compliant', 'requires reinspection'], true)) {
        $reason = trim((string) ($vehicle['inspection_failure_reason'] ?? ''));
        $issues[] = 'Inspection failed' . ($reason !== '' ? ': ' . $reason : '');
    }

    return array_values(array_unique($issues));
}

function vcs_send_owner_compliance_notification(PDO $pdo, array $vehicle, array $issues, string $today): array
{
    $vehicleId = (int) ($vehicle['vehicle_id'] ?? 0);
    $message = 'Vehicle ' . (string) ($vehicle['plate_number'] ?? $vehicleId) . ' requires attention: ' . implode('; ', $issues);
    $eventCode = vcs_notification_event_code('compliance_daily_alert', $vehicleId, $today);
    $notificationId = vcs_insert_notification($pdo, [
        'user_id' => (int) ($vehicle['user_id'] ?? 0),
        'vehicle_id' => $vehicleId,
        'notification_type' => 'Compliance Alert',
        'event_code' => $eventCode,
        'event_date' => $today,
        'message' => substr($message, 0, 255),
        'status' => 'Unread',
        'date_sent' => $today,
    ]);

    if ($notificationId <= 0) {
        return ['saved' => false, 'email_sent' => false, 'duplicate' => true];
    }

    $plate = (string) ($vehicle['plate_number'] ?? $vehicleId);
    $emailSent = false;
    if (!empty($vehicle['email']) && filter_var($vehicle['email'], FILTER_VALIDATE_EMAIL)) {
        $emailSent = vcs_send_notification_email(
            $pdo,
            $notificationId,
            (string) $vehicle['email'],
            (string) ($vehicle['name'] ?? ''),
            'Vehicle compliance alert for ' . $plate,
            buildVehicleComplianceAlertHtml((string) ($vehicle['name'] ?? ''), $plate, $issues),
            buildVehicleComplianceAlertText((string) ($vehicle['name'] ?? ''), $plate, $issues)
        );
    }

    return [
        'saved' => true,
        'email_sent' => $emailSent,
        'duplicate' => false,
        'notification_id' => $notificationId,
    ];
}

function vcs_send_expiry_notifications(PDO $pdo): array
{
    // The command name is retained for compatibility; it now sends all daily compliance alerts.
    $today = vcs_notification_today();
    $results = [
        'scanned' => 0,
        'alerts' => 0,
        'skipped' => 0,
        'failed' => 0,
    ];

    $stmt = $pdo->query(
        'SELECT v.vehicle_id, v.plate_number, v.inspection_status, v.inspection_failure_reason,
                owner.user_id, owner.name, owner.email,
                c.vehicle_id AS compliance_vehicle_id,
                c.insurance_expiry, c.insurance_status,
                c.licence_expiry, c.licence_status,
                c.registration_expiry, c.registration_status
         FROM vehicles v
         INNER JOIN users owner ON owner.user_id = v.owner_id
         LEFT JOIN compliance_records c ON c.vehicle_id = v.vehicle_id
         WHERE COALESCE(owner.is_active, 1) = 1
         ORDER BY v.vehicle_id ASC'
    );

    foreach ($stmt->fetchAll() as $row) {
        $results['scanned']++;
        $issues = vcs_compliance_issues($row, $today);
        if (!$issues) {
            continue;
        }

        try {
            $response = vcs_send_owner_compliance_notification($pdo, $row, $issues, $today);
            if ($response['duplicate']) {
                $results['skipped']++;
            } elseif ($response['saved']) {
                $results['alerts']++;
                if (!$response['email_sent']) {
                    $results['failed']++;
                }
            }
        } catch (Throwable $e) {
            $results['failed']++;
            error_log('Compliance notification dispatch failed: ' . $e->getMessage());
        }
    }

    return $results;
}
