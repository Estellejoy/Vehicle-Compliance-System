-- Demo data for the seeded owner and officer accounts.
-- Joy Gatiti owns the sample vehicle.
-- Jemima Moye is recorded as the officer who inspected and uploaded the service entry.

INSERT INTO vehicles (
    vehicle_id,
    owner_id,
    plate_number,
    make,
    model,
    year,
    inspection_status,
    inspection_checked_at,
    inspection_checked_by
)
VALUES
    (
        101,
        54,
        'KDH 201A',
        'Toyota',
        'Corolla Axio',
        2021,
        'Inspected',
        NOW(),
        55
    )
ON DUPLICATE KEY UPDATE
    owner_id = VALUES(owner_id),
    plate_number = VALUES(plate_number),
    make = VALUES(make),
    model = VALUES(model),
    year = VALUES(year),
    inspection_status = VALUES(inspection_status),
    inspection_checked_at = VALUES(inspection_checked_at),
    inspection_checked_by = VALUES(inspection_checked_by);

INSERT INTO compliance_records (
    compliance_id,
    vehicle_id,
    insurance_expiry,
    insurance_status,
    licence_expiry,
    licence_status,
    registration_expiry,
    registration_status
)
VALUES
    (
        101,
        101,
        DATE_ADD(CURDATE(), INTERVAL 180 DAY),
        'Valid',
        DATE_ADD(CURDATE(), INTERVAL 365 DAY),
        'Valid',
        DATE_ADD(CURDATE(), INTERVAL 210 DAY),
        'Valid'
    )
ON DUPLICATE KEY UPDATE
    vehicle_id = VALUES(vehicle_id),
    insurance_expiry = VALUES(insurance_expiry),
    insurance_status = VALUES(insurance_status),
    licence_expiry = VALUES(licence_expiry),
    licence_status = VALUES(licence_status),
    registration_expiry = VALUES(registration_expiry),
    registration_status = VALUES(registration_status);

INSERT INTO service_records (
    service_id,
    vehicle_id,
    service_details,
    service_report_path,
    service_report_name,
    last_service_date,
    next_service_date,
    uploaded_by,
    uploaded_at
)
VALUES
    (
        101,
        101,
        'Full service and inspection demo record',
        NULL,
        NULL,
        CURDATE(),
        DATE_ADD(CURDATE(), INTERVAL 180 DAY),
        55,
        NOW()
    )
ON DUPLICATE KEY UPDATE
    vehicle_id = VALUES(vehicle_id),
    service_details = VALUES(service_details),
    service_report_path = VALUES(service_report_path),
    service_report_name = VALUES(service_report_name),
    last_service_date = VALUES(last_service_date),
    next_service_date = VALUES(next_service_date),
    uploaded_by = VALUES(uploaded_by),
    uploaded_at = VALUES(uploaded_at);

INSERT INTO notifications (
    notification_id,
    user_id,
    notification_type,
    message,
    status,
    date_sent
)
VALUES
    (
        41,
        54,
        'Inspection',
        'Your demo vehicle KDH 201A has been inspected by OFF-DEMO-001.',
        'Unread',
        CURDATE()
    ),
    (
        42,
        55,
        'Assignment',
        'Demo inspection data has been seeded for Joy Gatiti.',
        'Unread',
        CURDATE()
    )
ON DUPLICATE KEY UPDATE
    user_id = VALUES(user_id),
    notification_type = VALUES(notification_type),
    message = VALUES(message),
    status = VALUES(status),
    date_sent = VALUES(date_sent);
