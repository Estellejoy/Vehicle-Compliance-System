CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    role ENUM('admin', 'officer', 'owner') NOT NULL,
    password_hash VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS vehicles (
    vehicle_id INT AUTO_INCREMENT PRIMARY KEY,
    owner_id INT NOT NULL,
    plate_number VARCHAR(50) NOT NULL UNIQUE,
    model VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_vehicles_owner
        FOREIGN KEY (owner_id) REFERENCES users(user_id)
        ON DELETE CASCADE
);

INSERT INTO users (name, email, role, password_hash) VALUES
('System Admin', 'admin@example.com', 'admin', NULL),
('Traffic Officer', 'officer@example.com', 'officer', NULL),
('Vehicle Owner', 'owner@example.com', 'owner', NULL)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    role = VALUES(role);

INSERT INTO vehicles (owner_id, plate_number, model) VALUES
(3, 'KAA 123A', 'Toyota Corolla')
ON DUPLICATE KEY UPDATE
    model = VALUES(model);
