# Vehicle Compliance System Presentation Guide

## 1. What This System Does

Vehicle Compliance System is a PHP and MySQL application for managing:

- vehicle compliance records
- inspection updates
- owner and officer accounts
- password reset and verification flows
- notifications and reminders

The project is split into:

- UI pages in the project root and `views/`
- backend processing in `backend/`
- shared configuration in `config/`
- database schema and migrations in `docker/mysql/`

## 2. How The Code Is Organized

### UI Pages

These files mostly display forms and dashboards:

- `index.php`
- `login.php`
- `register.php`
- `verify.php`
- `verify_login.php`
- `forgot_password.php`
- `reset_password.php`
- files in `views/`

### Backend Handlers

These files receive form submissions, validate input, write to the database, and redirect the user:

- `backend/auth.php`
- `backend/register.php`
- `backend/forgot_password.php`
- `backend/reset_password.php`
- `backend/update_record.php`
- `backend/admin_actions.php`
- `backend/feedback.php`

### Shared Helpers

These files hold reusable logic used across the app:

- `backend/auth_helpers.php`
- `backend/password_reset_service.php`
- `backend/notification_service.php`

### Configuration

These files set up the database and email delivery:

- `config/db.php`
- `config/mail.php`

### Database

- `docker/mysql/init/01-schema.sql` creates the base tables
- `docker/mysql/init/02-seeds.sql` loads sample data
- `docker/mysql/migrations/` adds later features

## 3. What The Helpers Do

`backend/auth_helpers.php` is the central utility file for authentication and role logic.

It handles:

- supported roles
- password strength rules
- normalizing role names
- mapping roles to dashboards
- creating and consuming login verification tokens
- storing session data after login
- checking whether a table or column exists
- providing default vehicle values when data is missing

### What To Say

- "The helper file centralizes shared auth logic so the same rules are used everywhere."
- "It also makes the app schema-aware, so it can work with older and newer migrations."

## 4. Login Flow

Main files:

- `login.php`
- `backend/auth.php`
- `verify_login.php`
- `backend/auth_helpers.php`

### Flow

1. The user enters email, password, and role on `login.php`.
2. `backend/auth.php` loads the account from the database.
3. The password is verified.
4. The system checks which roles are available for that account.
5. If the account is one of the protected demo accounts, the system creates a 6-digit login code.
6. The code is emailed to the user.
7. The user is redirected to `verify_login.php`.
8. The code is checked.
9. If valid, the session is created and the user is redirected to the correct dashboard.

### What To Say

- "We validate the password first, then send a 6-digit email code for step-up verification."
- "The session is only created after the verification code is confirmed."
- "This is email-based 2FA-style verification, not an authenticator app."

## 5. Registration Flow

Main files:

- `register.php`
- `backend/register.php`
- `verify.php`

### Flow

1. The user fills the registration form.
2. `backend/register.php` validates the name, email, and password.
3. A new user record is created with `is_active = 0`.
4. A verification token is generated and stored as a hash.
5. A verification link is emailed to the user.
6. The user opens `verify.php?token=...`.
7. The token is checked against the stored hash and expiry time.
8. If valid, the account is activated.

### What To Say

- "New accounts are inactive until the email verification link is used."
- "We store only a hash of the verification token, not the raw token."

## 6. Password Reset Flow

Main files:

- `forgot_password.php`
- `backend/forgot_password.php`
- `backend/password_reset_service.php`
- `reset_password.php`
- `backend/reset_password.php`

### Flow

1. The user requests a reset link.
2. The backend checks whether the email belongs to an active account.
3. A reset token is generated.
4. Only the hashed token is stored in the database.
5. A reset link is emailed.
6. The user opens the reset page.
7. The token is checked for validity and expiry.
8. The user enters a new password.
9. The password is updated and the token is marked as used.

### What To Say

- "The system does not store the raw reset token."
- "It stores a hash, checks expiry, and consumes the token after use."

## 7. Notifications And Scheduled Jobs

Main files:

- `backend/notification_service.php`
- `backend/update_record.php`
- `tools/send_expiry_notifications.php`
- `docker/cron/vcs-notifications`

### What It Does

The system sends two main notification types:

- daily expiry reminders
- inspection status updates

### Expiry Reminders

1. A scheduled job runs every day.
2. It finds vehicles with insurance or licence expiry exactly 14 days away.
3. It saves an in-app notification.
4. It sends an email reminder.
5. It prevents duplicates using event metadata.

### Inspection Updates

1. An officer marks a vehicle as inspected.
2. The vehicle record is updated.
3. The owner receives an in-app notification.
4. The owner also receives an email.

### What To Say

- "Notification logic is centralized so email and database records stay in sync."
- "The expiry reminder job is scheduled and de-duplicated."

## 8. Admin And Officer Responsibilities

### Admin

File:

- `backend/admin_actions.php`

Admin can:

- create users
- update roles
- activate or deactivate accounts
- delete non-admin accounts
- reset passwords
- send password reset links

### Officer

File:

- `backend/update_record.php`

Officer can:

- update inspection status
- trigger owner notifications
- record who performed the check

### What To Say

- "Admin actions are separate from officer actions."
- "Officers handle inspection workflows, while admins manage user accounts and recovery."

## 9. Migrations

Migrations are the scripts that evolve the database after the base schema was created.

Base schema:

- `docker/mysql/init/01-schema.sql`

Later feature migrations add things like:

- inspection fields
- checked-by officer tracking
- staff IDs
- service report uploads
- login verification tokens
- feedback messages
- notification metadata

### Why They Matter

The code checks whether some migration-added tables or columns exist before using them. That is why helper functions like `vcs_has_table()` and `vcs_has_column()` exist.

### What To Say

- "The application is designed to work with evolving database structure."
- "Migrations add new features without breaking the older base schema."

## 10. Simple Defence Script

If you are asked to explain the system quickly, say:

"This is a PHP and MySQL vehicle compliance system. The UI pages collect input, the backend scripts validate and process it, and the helper files hold shared logic like roles, sessions, verification tokens, and notifications. Login uses password verification plus an email code for protected accounts, registration uses email verification, password reset uses hashed reset tokens, and migrations extend the database as new features are added."

## 11. Where To Point If Asked To Show Something

- Login logic: `backend/auth.php`
- Login 2FA token logic: `backend/auth_helpers.php`
- Login verification page: `verify_login.php`
- Registration logic: `backend/register.php`
- Email verification page: `verify.php`
- Password reset logic: `backend/password_reset_service.php`
- Reset form handler: `backend/reset_password.php`
- Notifications: `backend/notification_service.php`
- Officer inspection update: `backend/update_record.php`
- Admin actions: `backend/admin_actions.php`
- Base database structure: `docker/mysql/init/01-schema.sql`
- Later migrations: `docker/mysql/migrations/`

