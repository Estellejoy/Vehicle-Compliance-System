-- Give Jemima an owner role for portal testing while preserving her officer role.
INSERT INTO user_roles (user_id, role, is_primary)
VALUES (55, 'owner', 0)
ON DUPLICATE KEY UPDATE is_primary = VALUES(is_primary);

-- Jemima's first vehicle is compliant and already inspected.
INSERT INTO vehicles (
    vehicle_id, owner_id, plate_number, make, model, year,
    inspection_status, inspection_checked_at, inspection_checked_by, inspection_failure_reason
)
VALUES (
    103, 55, 'KDJ 203B', 'Toyota', 'Corolla Axio', 2021,
    'Checked', NOW(), 55, NULL
)
ON DUPLICATE KEY UPDATE
    owner_id = VALUES(owner_id), plate_number = VALUES(plate_number),
    make = VALUES(make), model = VALUES(model), year = VALUES(year),
    inspection_status = VALUES(inspection_status),
    inspection_checked_at = VALUES(inspection_checked_at),
    inspection_checked_by = VALUES(inspection_checked_by),
    inspection_failure_reason = VALUES(inspection_failure_reason);

-- The second vehicle gives the portal a visible non-compliant/failed-inspection case.
INSERT INTO vehicles (
    vehicle_id, owner_id, plate_number, make, model, year,
    inspection_status, inspection_checked_at, inspection_checked_by, inspection_failure_reason
)
VALUES (
    104, 55, 'KDJ 204C', 'Mazda', 'Demio', 2020,
    'Failed', NOW(), 55, 'Defective brake lights and worn front tyres'
)
ON DUPLICATE KEY UPDATE
    owner_id = VALUES(owner_id), plate_number = VALUES(plate_number),
    make = VALUES(make), model = VALUES(model), year = VALUES(year),
    inspection_status = VALUES(inspection_status),
    inspection_checked_at = VALUES(inspection_checked_at),
    inspection_checked_by = VALUES(inspection_checked_by),
    inspection_failure_reason = VALUES(inspection_failure_reason);

INSERT INTO compliance_records (
    compliance_id, vehicle_id, insurance_expiry, insurance_status,
    licence_expiry, licence_status, registration_expiry, registration_status
)
VALUES
    (103, 103, DATE_ADD(CURDATE(), INTERVAL 180 DAY), 'Valid',
           DATE_ADD(CURDATE(), INTERVAL 365 DAY), 'Valid',
           DATE_ADD(CURDATE(), INTERVAL 210 DAY), 'Valid'),
    (104, 104, DATE_SUB(CURDATE(), INTERVAL 20 DAY), 'Expired',
           DATE_ADD(CURDATE(), INTERVAL 120 DAY), 'Valid',
           DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'Expired')
ON DUPLICATE KEY UPDATE
    vehicle_id = VALUES(vehicle_id),
    insurance_expiry = VALUES(insurance_expiry), insurance_status = VALUES(insurance_status),
    licence_expiry = VALUES(licence_expiry), licence_status = VALUES(licence_status),
    registration_expiry = VALUES(registration_expiry), registration_status = VALUES(registration_status);

INSERT INTO service_records (
    service_id, vehicle_id, service_details, last_service_date, next_service_date,
    uploaded_by, uploaded_at
)
VALUES
    (103, 103, 'Full service and inspection record', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 180 DAY), 55, NOW()),
    (104, 104, 'Brake and tyre inspection required', DATE_SUB(CURDATE(), INTERVAL 30 DAY), CURDATE(), 55, NOW())
ON DUPLICATE KEY UPDATE
    vehicle_id = VALUES(vehicle_id), service_details = VALUES(service_details),
    last_service_date = VALUES(last_service_date), next_service_date = VALUES(next_service_date),
    uploaded_by = VALUES(uploaded_by), uploaded_at = VALUES(uploaded_at);

INSERT INTO notifications (
    user_id, vehicle_id, notification_type, event_code, event_date,
    message, status, date_sent
)
VALUES
    (55, 103, 'Inspection Status', CONCAT('inspection_status_update:103:', CURDATE()), CURDATE(),
     'Your vehicle KDJ 203B inspection status has been updated to Checked.', 'Unread', CURDATE()),
    (55, 104, 'Compliance Alert', CONCAT('compliance_daily_alert:104:', CURDATE()), CURDATE(),
     'Vehicle KDJ 204C requires attention: Insurance expired; Registration expired; Inspection failed: Defective brake lights and worn front tyres.', 'Unread', CURDATE())
ON DUPLICATE KEY UPDATE
    message = VALUES(message), status = VALUES(status), date_sent = VALUES(date_sent);
