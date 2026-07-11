# Defence Notes

## 1. Email-Based Login Verification

This system uses email-based step-up verification for protected accounts.

Flow:
- The user enters email and password on the login page.
- The backend verifies the password first.
- For protected accounts, the system creates a 6-digit verification code.
- The code is emailed to the user.
- The code is stored as a short-lived token in the database.
- The user enters the code on the verification page.
- If the code matches and has not expired, the session is created and the user is redirected to the dashboard.

Key files:
- [backend/auth.php](./backend/auth.php)
- [backend/auth_helpers.php](./backend/auth_helpers.php)
- [verify_login.php](./verify_login.php)
- [config/mail.php](./config/mail.php)
- [docker/mysql/init/01-schema.sql](./docker/mysql/init/01-schema.sql)

What to say:
- "We validate the password first, then send a 6-digit code to email for step-up verification."
- "The code is stored as a short-lived token and must be verified before the session is created."
- "This is an email-based 2FA-style flow, implemented for accounts that require extra verification."

## 2. Password Reset

The forgot-password flow sends a reset link to the user's email.

Flow:
- The user clicks Forgot Password and enters their email.
- The backend creates a password reset token and stores a SHA-256 hash of it.
- A reset link is emailed to the user.
- The link opens the reset password page.
- The reset page validates the token and expiry.
- If valid, the user sets a new password.
- The token is marked used after the password change.

Admin support:
- Admins can send the same reset link from the admin panel.
- Admins can also set a new password directly from the admin panel.

Key files:
- [forgot_password.php](./forgot_password.php)
- [backend/forgot_password.php](./backend/forgot_password.php)
- [backend/password_reset_service.php](./backend/password_reset_service.php)
- [reset_password.php](./reset_password.php)
- [backend/reset_password.php](./backend/reset_password.php)
- [backend/admin_actions.php](./backend/admin_actions.php)
- [views/admin_panel.php](./views/admin_panel.php)
- [docker/mysql/init/01-schema.sql](./docker/mysql/init/01-schema.sql)

What to say:
- "The system does not store the raw reset token."
- "It stores a hash of the token, checks expiry, and marks the token used after a successful reset."
- "Admins can either send a reset link or set a password directly, depending on the support case."

## 3. Email Notifications

The system sends two main kinds of notifications:
- Daily expiry reminders
- Inspection status updates

Expiry reminders:
- A scheduled job runs every day at 10:00 AM Africa/Nairobi time.
- It scans compliance records for insurance or driving licence expiry exactly 14 days away.
- It inserts an in-app notification.
- It sends one email per event.
- It de-duplicates by vehicle, event type, and expiry date.

Inspection updates:
- When an officer marks a vehicle as inspected, the owner gets an email and an in-app notification.
- Inspection status changes are handled in the inspection update backend.

Key files:
- [config/mail.php](./config/mail.php)
- [backend/notification_service.php](./backend/notification_service.php)
- [backend/update_record.php](./backend/update_record.php)
- [tools/send_expiry_notifications.php](./tools/send_expiry_notifications.php)
- [docker/cron/vcs-notifications](./docker/cron/vcs-notifications)
- [docker-compose.yml](./docker-compose.yml)
- [Dockerfile](./Dockerfile)
- [docker/mysql/init/01-schema.sql](./docker/mysql/init/01-schema.sql)
- [docker/mysql/migrations/10_add_notification_event_metadata.sql](./docker/mysql/migrations/10_add_notification_event_metadata.sql)

What to say:
- "We centralized all mail templates and SMTP configuration in one mail helper."
- "Owners receive both email and in-app notifications for expiry reminders and inspection updates."
- "The daily reminder job is scheduled, repeatable, and safe from duplicates."

## 4. Suggested File Order For Defence

1. [backend/auth.php](./backend/auth.php)
2. [verify_login.php](./verify_login.php)
3. [backend/forgot_password.php](./backend/forgot_password.php)
4. [backend/password_reset_service.php](./backend/password_reset_service.php)
5. [reset_password.php](./reset_password.php)
6. [backend/admin_actions.php](./backend/admin_actions.php)
7. [backend/notification_service.php](./backend/notification_service.php)
8. [backend/update_record.php](./backend/update_record.php)
9. [tools/send_expiry_notifications.php](./tools/send_expiry_notifications.php)
10. [docker/cron/vcs-notifications](./docker/cron/vcs-notifications)

## 5. Short Speaking Script

- "For login verification, we implemented an email-based step-up verification flow. After the password is accepted, the system sends a 6-digit code to the user's email, stores a short-lived token, and only creates the session after the code is verified."
- "For password reset, the user requests a link, the system generates a hashed token, sends the reset link to email, and the reset page validates the token before allowing a new password to be saved."
- "For notifications, we centralized email delivery and in-app notification creation. Expiry reminders run daily at 10:00 AM, and inspection status changes notify the owner immediately."

## 6. Demo Pointers

- Use `joy.gatiti@strathmore.edu` for the presentation demo account.
- Show the email in Mailpit.
- Show the notification rows in MySQL.
- Show the scheduled job output for expiry reminders.

