CREATE TABLE IF NOT EXISTS user_roles (
    user_role_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    role ENUM('admin', 'officer', 'owner') NOT NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_roles_user_role (user_id, role),
    CONSTRAINT fk_user_roles_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS password_reset_tokens (
    token_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_password_reset_token_hash (token_hash),
    CONSTRAINT fk_password_reset_tokens_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS vehicle_owners (
    vehicle_owner_id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    user_id INT NOT NULL,
    ownership_role VARCHAR(30) NOT NULL DEFAULT 'Co-owner',
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_vehicle_owner (vehicle_id, user_id),
    CONSTRAINT fk_vehicle_owners_vehicle
        FOREIGN KEY (vehicle_id) REFERENCES vehicles(vehicle_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_vehicle_owners_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE
);

ALTER TABLE vehicles
    ADD COLUMN chassis_number VARCHAR(50) NULL AFTER plate_number,
    ADD COLUMN insurance_type VARCHAR(30) NULL AFTER chassis_number,
    ADD COLUMN payment_period VARCHAR(30) NULL AFTER insurance_type,
    ADD COLUMN driver_licence_class VARCHAR(20) NULL AFTER payment_period,
    ADD COLUMN odometer_km INT NULL AFTER year,
    ADD COLUMN service_interval_km INT NULL AFTER odometer_km,
    ADD COLUMN next_probable_service_km INT NULL AFTER service_interval_km;

ALTER TABLE service_records
    ADD COLUMN service_notes TEXT NULL AFTER service_details,
    ADD COLUMN last_service_odometer_km INT NULL AFTER last_service_date,
    ADD COLUMN next_service_odometer_km INT NULL AFTER last_service_odometer_km,
    ADD COLUMN service_interval_km INT NULL AFTER next_service_odometer_km;

INSERT IGNORE INTO user_roles (user_id, role, is_primary)
SELECT user_id, role, 1
FROM users;

INSERT IGNORE INTO user_roles (user_id, role, is_primary)
VALUES (54, 'officer', 0);

INSERT IGNORE INTO vehicle_owners (vehicle_id, user_id, ownership_role, is_primary)
VALUES (1, 2, 'Co-owner', 0),
       (54, 4, 'Co-owner', 0);
