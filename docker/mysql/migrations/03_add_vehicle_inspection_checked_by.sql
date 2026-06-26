ALTER TABLE vehicles
    ADD COLUMN inspection_checked_by INT NULL AFTER inspection_checked_at,
    ADD CONSTRAINT fk_vehicles_inspection_checked_by
        FOREIGN KEY (inspection_checked_by) REFERENCES users(user_id)
        ON DELETE SET NULL;
