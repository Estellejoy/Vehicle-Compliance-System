ALTER TABLE service_records
    ADD COLUMN service_report_path VARCHAR(255) NULL AFTER service_details,
    ADD COLUMN service_report_name VARCHAR(255) NULL AFTER service_report_path,
    ADD COLUMN uploaded_by INT NULL AFTER next_service_date,
    ADD COLUMN uploaded_at DATETIME NULL AFTER uploaded_by,
    ADD CONSTRAINT fk_service_uploaded_by
        FOREIGN KEY (uploaded_by) REFERENCES users(user_id)
        ON DELETE SET NULL;
