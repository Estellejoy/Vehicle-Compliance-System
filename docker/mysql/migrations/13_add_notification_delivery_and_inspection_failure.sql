-- Store the reason when an officer records a failed inspection.
ALTER TABLE vehicles
    ADD COLUMN inspection_failure_reason VARCHAR(255) NULL AFTER inspection_checked_by;

-- Keep email delivery separate so failed messages can be retried.
CREATE TABLE IF NOT EXISTS notification_deliveries (
    delivery_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    notification_id INT NOT NULL,
    channel VARCHAR(20) NOT NULL,
    recipient_email VARCHAR(150) NOT NULL,
    recipient_name VARCHAR(100) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    html_body MEDIUMTEXT NOT NULL,
    text_body MEDIUMTEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'Pending',
    attempts INT NOT NULL DEFAULT 0,
    last_error VARCHAR(500) NULL,
    next_attempt_at DATETIME NULL,
    sent_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_notification_delivery_channel (notification_id, channel),
    CONSTRAINT fk_notification_deliveries_notification
        FOREIGN KEY (notification_id) REFERENCES notifications(notification_id)
        ON DELETE CASCADE
);
