-- Add the staff identifier displayed for officers and administrators.
ALTER TABLE users
    ADD COLUMN staff_id VARCHAR(50) NULL UNIQUE AFTER role;
