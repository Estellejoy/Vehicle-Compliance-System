ALTER TABLE users
    ADD COLUMN email_verified_at DATETIME NULL AFTER password_hash,
    ADD COLUMN email_verification_token_hash CHAR(64) NULL AFTER email_verified_at,
    ADD COLUMN email_verification_expires_at DATETIME NULL AFTER email_verification_token_hash;
