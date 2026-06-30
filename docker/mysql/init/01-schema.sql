CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    role ENUM('admin', 'officer', 'owner') NOT NULL,
    badge_number VARCHAR(30) NULL UNIQUE,
    password_hash VARCHAR(255) NULL,
    email_verified_at DATETIME NULL,
    email_verification_token_hash CHAR(64) NULL,
    email_verification_expires_at DATETIME NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

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

CREATE TABLE IF NOT EXISTS vehicles (
    vehicle_id INT AUTO_INCREMENT PRIMARY KEY,
    owner_id INT NOT NULL,
    plate_number VARCHAR(50) NOT NULL,
    chassis_number VARCHAR(50) NULL,
    insurance_type VARCHAR(30) NULL,
    payment_period VARCHAR(30) NULL,
    driver_licence_class VARCHAR(20) NULL,
    make VARCHAR(100) NOT NULL,
    model VARCHAR(100) NOT NULL,
    year INT NOT NULL,
    odometer_km INT NULL,
    service_interval_km INT NULL,
    next_probable_service_km INT NULL,
    inspection_status VARCHAR(30) NOT NULL DEFAULT 'Pending Police Check',
    inspection_checked_at DATETIME NULL,
    inspection_checked_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_vehicles_owner
        FOREIGN KEY (owner_id) REFERENCES users(user_id)
        ON DELETE CASCADE
    ,
    CONSTRAINT fk_vehicles_inspection_checked_by
        FOREIGN KEY (inspection_checked_by) REFERENCES users(user_id)
        ON DELETE SET NULL
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

CREATE TABLE IF NOT EXISTS compliance_records (
    compliance_id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    insurance_expiry DATE NOT NULL,
    insurance_status VARCHAR(20) NOT NULL,
    licence_expiry DATE NOT NULL,
    licence_status VARCHAR(20) NOT NULL,
    registration_expiry DATE NOT NULL,
    registration_status VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_compliance_vehicle
        FOREIGN KEY (vehicle_id) REFERENCES vehicles(vehicle_id)
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS service_records (
    service_id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    service_details VARCHAR(255) NOT NULL,
    service_notes TEXT NULL,
    last_service_date DATE NOT NULL,
    next_service_date DATE NOT NULL,
    last_service_odometer_km INT NULL,
    next_service_odometer_km INT NULL,
    service_interval_km INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_service_vehicle
        FOREIGN KEY (vehicle_id) REFERENCES vehicles(vehicle_id)
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

CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    notification_type VARCHAR(50) NOT NULL,
    message VARCHAR(255) NOT NULL,
    status VARCHAR(20) NOT NULL,
    date_sent DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notifications_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE
);
