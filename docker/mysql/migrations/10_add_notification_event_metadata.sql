-- Add the vehicle and event fields used to prevent duplicate alerts.
ALTER TABLE notifications
    ADD COLUMN vehicle_id INT NULL AFTER user_id,
    ADD COLUMN event_code VARCHAR(100) NULL AFTER notification_type,
    ADD COLUMN event_date DATE NULL AFTER event_code,
    ADD UNIQUE KEY uq_notifications_user_event (user_id, event_code, vehicle_id, event_date),
    ADD CONSTRAINT fk_notifications_vehicle
        FOREIGN KEY (vehicle_id) REFERENCES vehicles(vehicle_id)
        ON DELETE CASCADE;
