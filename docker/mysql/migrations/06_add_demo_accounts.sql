-- Demo accounts for the Vehicle Compliance System
-- Joy is the owner demo account and Jemima is the officer demo account.

INSERT INTO users (
    user_id,
    name,
    email,
    role,
    staff_id,
    password_hash,
    email_verified_at,
    email_verification_token_hash,
    email_verification_expires_at,
    is_active
)
VALUES
    (
        54,
        'Joy Gatiti',
        'joy.gatiti@strathmore.edu',
        'owner',
        NULL,
        '$2y$10$tvVEdrR/LjqhWrh6SthQmOTHno5lXQTAoiIaK7f/b0xB35cNAkUXm',
        NOW(),
        NULL,
        NULL,
        1
    ),
    (
        55,
        'Jemima Moye',
        'jemima.moye@strathmore.edu',
        'officer',
        'OFF-DEMO-001',
        '$2y$10$6a7IveO.Ql37nUK.H79Kq.5TSQPf8tIKwuevPtJxJdl64gbJlsThy',
        NOW(),
        NULL,
        NULL,
        1
    )
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    email = VALUES(email),
    role = VALUES(role),
    staff_id = VALUES(staff_id),
    password_hash = VALUES(password_hash),
    email_verified_at = VALUES(email_verified_at),
    email_verification_token_hash = VALUES(email_verification_token_hash),
    email_verification_expires_at = VALUES(email_verification_expires_at),
    is_active = VALUES(is_active);
