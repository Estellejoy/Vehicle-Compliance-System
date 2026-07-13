-- Joy's demo vehicle is used to demonstrate the 14-day daily reminder window.
INSERT INTO vehicles (
    vehicle_id, owner_id, plate_number, make, model, year, inspection_status
)
VALUES (
    102, 54, 'KDJ 102B', 'Mazda', 'Demio', 2020, 'Pending Police Check'
)
ON DUPLICATE KEY UPDATE
    owner_id = VALUES(owner_id), plate_number = VALUES(plate_number),
    make = VALUES(make), model = VALUES(model), year = VALUES(year),
    inspection_status = VALUES(inspection_status);

INSERT INTO compliance_records (
    compliance_id, vehicle_id, insurance_expiry, insurance_status,
    licence_expiry, licence_status, registration_expiry, registration_status
)
VALUES (
    102, 102,
    DATE_ADD(CURDATE(), INTERVAL 14 DAY), 'Valid',
    DATE_ADD(CURDATE(), INTERVAL 14 DAY), 'Valid',
    DATE_ADD(CURDATE(), INTERVAL 60 DAY), 'Valid'
)
ON DUPLICATE KEY UPDATE
    vehicle_id = VALUES(vehicle_id),
    insurance_expiry = VALUES(insurance_expiry), insurance_status = VALUES(insurance_status),
    licence_expiry = VALUES(licence_expiry), licence_status = VALUES(licence_status),
    registration_expiry = VALUES(registration_expiry), registration_status = VALUES(registration_status);
