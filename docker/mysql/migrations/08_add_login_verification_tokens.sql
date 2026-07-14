-- Store one-time login codes as hashes with an expiry time.
CREATE TABLE IF NOT EXISTS login_verification_tokens (
    token_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    selected_role ENUM('admin', 'officer', 'owner') NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_login_verification_token_hash (token_hash),
    CONSTRAINT fk_login_verification_tokens_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE
);
