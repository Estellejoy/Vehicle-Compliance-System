# Vehicle Compliance System Defence Q&A

Use this as a rehearsal sheet before the presentation. Keep answers short and direct.

## 1. What is this system?

**Answer:**
It is a PHP and MySQL-based Vehicle Compliance System for managing vehicle records, compliance data, inspections, notifications, and user accounts.

## 2. How is the project structured?

**Answer:**
- UI pages are in the project root and `views/`
- backend logic is in `backend/`
- shared configuration is in `config/`
- database schema and migrations are in `docker/mysql/`

## 3. What do the helper files do?

**Answer:**
The helper files contain reusable logic such as roles, password rules, session storage, token handling, and schema checks. The main helper file is `backend/auth_helpers.php`.

## 4. Why do you have helper functions instead of repeating logic?

**Answer:**
To keep the code consistent, reduce duplication, and make the system easier to maintain.

## 5. How does registration work?

**Answer:**
The user submits the form on `register.php`. The backend validates the input, creates an inactive account, generates an email verification token, and sends a verification link. The account becomes active only after `verify.php` confirms the token.

## 6. Why does a new account start inactive?

**Answer:**
To make sure the user owns the email address before the account becomes usable.

## 7. How does login work?

**Answer:**
The user submits email, password, and role on `login.php`. `backend/auth.php` checks the password and role. If the account requires extra verification, the system sends a 6-digit email code and redirects to `verify_login.php`. Otherwise, it creates the session and opens the correct dashboard.

## 8. Is your 2FA an authenticator-app based 2FA?

**Answer:**
No. It is email-based step-up verification using a 6-digit one-time code for selected accounts.

## 9. Why did you implement login verification?

**Answer:**
To add an extra security step for protected accounts and demonstrate a stronger login flow.

## 10. How is the login code stored?

**Answer:**
The system stores a hashed token in the database, not the raw code.

## 11. How does the system know when the login code expires?

**Answer:**
The token has an expiry timestamp, and `backend/auth_helpers.php` checks that it has not expired before accepting it.

## 12. How does password reset work?

**Answer:**
The user requests a reset link, the backend creates a hashed token, emails the reset link, and `backend/reset_password.php` validates the token before updating the password.

## 13. Why store reset tokens as hashes?

**Answer:**
To protect the token if the database is exposed. Only the hash is stored.

## 14. What happens after a password reset?

**Answer:**
The password is updated and the token is marked as used so it cannot be reused.

## 15. What does the officer module do?

**Answer:**
Officers update inspection status. The action is processed in `backend/update_record.php`, which updates the vehicle record and notifies the owner.

## 16. What does the admin module do?

**Answer:**
Admins can create users, update roles, reset passwords, send reset links, and activate or deactivate accounts. This is handled in `backend/admin_actions.php`.

## 17. How do notifications work?

**Answer:**
Notification logic is centralized in `backend/notification_service.php`. It creates in-app notifications and sends emails for expiry reminders and inspection updates.

## 18. How do scheduled expiry reminders work?

**Answer:**
A cron job runs `tools/send_expiry_notifications.php`, which calls the notification service to find records expiring in 14 days and send reminders.

## 19. How do you prevent duplicate reminders?

**Answer:**
The notification table stores event metadata, and the insert logic uses that metadata to avoid creating duplicate reminders.

## 20. Why do you have migrations?

**Answer:**
Migrations let the database evolve without rewriting the entire schema. The base tables are in `docker/mysql/init/01-schema.sql`, and later features are added through migration files.

## 21. Why does the code check whether tables or columns exist?

**Answer:**
Because the app supports older and newer database states. The helper functions `vcs_has_table()` and `vcs_has_column()` make the code schema-aware.

## 22. What is the role of `config/db.php`?

**Answer:**
It creates the PDO connection to MySQL and sets the timezone and connection options.

## 23. What is the role of `config/mail.php`?

**Answer:**
It configures SMTP and contains the email template functions used for verification, reset links, login codes, reminders, and alerts.

## 24. Why did you use Docker?

**Answer:**
To make the app and database run consistently in development with the same environment setup.

## 25. What is the main design pattern in the code?

**Answer:**
The system follows a request-handler pattern: the UI submits a form, the backend validates and processes it, then the user is redirected or shown a flash message.

## 26. How do roles work?

**Answer:**
Roles determine which dashboard a user sees and which actions they can perform. The main roles are owner, officer, and admin.

## 27. Why can one email have more than one role?

**Answer:**
The system supports multi-role accounts through the `user_roles` table and role-selection during login.

## 28. What is the difference between `users` and `user_roles`?

**Answer:**
`users` stores the main account record, while `user_roles` stores role assignments for accounts that can use more than one role.

## 29. What should you say if they ask about security?

**Answer:**
Passwords are hashed, reset and verification tokens are hashed, sessions are regenerated on login, and protected accounts require an extra email verification step.

## 30. What are the limitations of the current system?

**Answer:**
The login verification is email-based rather than authenticator-app-based, and some demo behavior is tied to seeded accounts and migrations.

## 31. If they ask you to show the login flow, where do you go?

**Answer:**
- `login.php`
- `backend/auth.php`
- `verify_login.php`
- `backend/auth_helpers.php`

## 32. If they ask you to show registration, where do you go?

**Answer:**
- `register.php`
- `backend/register.php`
- `verify.php`

## 33. If they ask you to show password reset, where do you go?

**Answer:**
- `forgot_password.php`
- `backend/forgot_password.php`
- `backend/password_reset_service.php`
- `reset_password.php`
- `backend/reset_password.php`

## 34. If they ask you to show notifications, where do you go?

**Answer:**
- `backend/notification_service.php`
- `backend/update_record.php`
- `tools/send_expiry_notifications.php`

## 35. If they ask you to show the database structure, where do you go?

**Answer:**
- `docker/mysql/init/01-schema.sql`
- `docker/mysql/migrations/`

## 36. Short defence summary

**Answer:**
This system is a PHP and MySQL vehicle compliance application. The frontend collects user input, backend handlers process the requests, helper files provide shared logic, and the database schema is extended through migrations. It includes registration verification, email-based login verification, password reset, role-based dashboards, officer inspections, admin controls, and scheduled compliance reminders.

