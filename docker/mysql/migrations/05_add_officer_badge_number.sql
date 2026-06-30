ALTER TABLE users
    ADD COLUMN badge_number VARCHAR(30) NULL UNIQUE AFTER role;

UPDATE users
SET badge_number = CASE
    WHEN role = 'officer' THEN CONCAT('OFF-', LPAD(user_id, 4, '0'))
    WHEN role = 'admin' THEN CONCAT('ADM-', LPAD(user_id, 4, '0'))
    ELSE badge_number
END
WHERE badge_number IS NULL;
