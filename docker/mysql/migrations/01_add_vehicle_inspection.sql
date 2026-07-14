-- Add the fields used to record the latest police inspection.
ALTER TABLE vehicles
    ADD COLUMN inspection_status VARCHAR(30) NOT NULL DEFAULT 'Pending Police Check' AFTER year,
    ADD COLUMN inspection_checked_at DATETIME NULL AFTER inspection_status;
