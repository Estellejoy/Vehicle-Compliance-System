# Vehicle-Compliance-System

## Overview

Vehicle Compliance System is a PHP application backed by MySQL. The local development
setup now runs with Docker Compose so the app and database start together.

## Project Structure

- `backend/` - request handlers and server-side logic
- `config/` - database connection bootstrap
- `views/` - login page and role-based dashboards
- `assets/` - static CSS and JavaScript
- `docker/` - MySQL initialization scripts
- `Dockerfile` - PHP-Apache image definition
- `docker-compose.yml` - local multi-service setup

## Docker Setup

This project can run with PHP-Apache and MySQL through Docker Compose.

### Services

- `app`: PHP 8.2 + Apache
- `db`: MySQL 8.0
- `phpmyadmin`: Browser-based MySQL management

### Run

```bash
docker compose up --build
```

For everyday development, the `app` service bind-mounts the repository into the container,
so PHP, view, and asset changes are picked up immediately without rebuilding the image.

Open:

- `http://localhost:8080`
- `http://localhost:8081` for phpMyAdmin

Registration:

- Open `http://localhost:8080/register`
- Register as a vehicle owner
- Check your email for the verification link
- If local mail is not configured, the page will show a clickable verification link
- After verification, log in at `http://localhost:8080/login`

## Database Setup

The database connection is configured through environment variables in `config/db.php`.

Default Compose values:

- `DB_HOST=db`
- `DB_NAME=vehicle_compliance`
- `DB_USER=vcs_user`
- `DB_PASSWORD=vcs_password`
- Host MySQL port: `3307`
- phpMyAdmin port: `8081`
- App timestamps are stored in `Africa/Nairobi` time.

Local environment files:

- `.env` contains your machine-specific Compose values and seeded app account details.
- `.env.example` shows the same keys with placeholder values.
- `APP_URL`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`, and `MAIL_REPLY_TO_ADDRESS` support the registration email flow.

The first database start loads the schema from `docker/mysql/init/01-schema.sql`.

If you already have an existing MySQL volume, apply the inspection migration once:

```cmd
type docker\mysql\migrations\01_add_vehicle_inspection.sql | docker exec -i vehicle-compliance-db mysql -uvcs_user -pvcs_password -D vehicle_compliance
```

Then apply the inspection audit migration so the system stores who performed the last check:

```cmd
type docker\mysql\migrations\03_add_vehicle_inspection_checked_by.sql | docker exec -i vehicle-compliance-db mysql -uvcs_user -pvcs_password -D vehicle_compliance
```

Then add the officer staff ID and service report upload columns used by the admin and officer dashboards:

```cmd
type docker\mysql\migrations\04_add_officer_staff_id.sql | docker exec -i vehicle-compliance-db mysql -uvcs_user -pvcs_password -D vehicle_compliance
type docker\mysql\migrations\05_add_service_report_upload.sql | docker exec -i vehicle-compliance-db mysql -uvcs_user -pvcs_password -D vehicle_compliance
```

To add the demo owner and officer accounts used for testing, apply:

```cmd
type docker\mysql\migrations\06_add_demo_accounts.sql | docker exec -i vehicle-compliance-db mysql -uvcs_user -pvcs_password -D vehicle_compliance
```

To add demo vehicle and workflow data for those accounts, apply:

```cmd
type docker\mysql\migrations\07_add_demo_account_data.sql | docker exec -i vehicle-compliance-db mysql -uvcs_user -pvcs_password -D vehicle_compliance
```

Demo login details:

- Owner: `joy.gatiti@strathmore.edu`
- Owner password: `joy.gatiti@123`
- Officer: `jemima.moye@strathmore.edu`
- Officer password: `jemima.moye@123`

### Seeded Tables

The init schema creates these tables:

- `users`
- `vehicles`
- `compliance_records`
- `service_records`
- `notifications`

The `users` table also includes an optional `staff_id` column for officers and administrators, while `service_records` stores uploaded service report file details.

It also loads the CSV-backed seed rows so the app can be tested immediately.

Seeded user passwords are generated from each email's local part plus `@123`.
For example, `brian.mwangi@gmail.com` uses `brian.mwangi@123`.

Default login passwords:

- Seeded owner and officer accounts use `<email-local-part>@123`.
- The seeded app account `joy.gatiti@strathmore.edu` uses the password in your local `.env` file.
- New registrations use the password entered during signup after email verification.

If you already have a database with `NULL` password hashes, run the backfill script once:

```bash
php tools/backfill-passwords.php
```

After logging in, users can open `Change Password` from any dashboard to replace their temporary password with a personal one.
Police inspection updates now record the officer name and the Nairobi-local timestamp of the last check.

### Run Schema and Seeds

The recommended way to initialize everything is to start Compose with a fresh MySQL volume:

```bash
docker compose down -v
docker compose up --build
```

If the database container is already running and you want to apply the SQL files manually,
run the schema first and then the seed file:

```bash
type docker\mysql\init\01-schema.sql | docker exec -i vehicle-compliance-db mysql -uvcs_user -pvcs_password -D vehicle_compliance
type docker\mysql\init\02-seeds.sql | docker exec -i vehicle-compliance-db mysql -uvcs_user -pvcs_password -D vehicle_compliance
```

### Verify Load

Use these commands to confirm the tables and row counts:

```bash
docker exec -it vehicle-compliance-db mysql -uvcs_user -pvcs_password -D vehicle_compliance -e "SHOW TABLES;"
docker exec -it vehicle-compliance-db mysql -uvcs_user -pvcs_password -D vehicle_compliance -e "SELECT COUNT(*) AS users_count FROM users; SELECT COUNT(*) AS vehicles_count FROM vehicles; SELECT COUNT(*) AS compliance_count FROM compliance_records; SELECT COUNT(*) AS service_count FROM service_records; SELECT COUNT(*) AS notifications_count FROM notifications;"
```

### View Tables

To see the table names in Command Prompt:

```cmd
docker exec -it vehicle-compliance-db mysql -uvcs_user -pvcs_password -D vehicle_compliance -e "SHOW TABLES;"
```

To inspect the seeded data:

```cmd
docker exec -it vehicle-compliance-db mysql -uvcs_user -pvcs_password -D vehicle_compliance -e "SELECT * FROM users; SELECT * FROM vehicles; SELECT * FROM compliance_records; SELECT * FROM service_records; SELECT * FROM notifications;"
```

### CSV Seed Source

The seed data is generated from:

- `C:\Users\gatit\Desktop\CSV FOLDER\users.csv`
- `C:\Users\gatit\Desktop\CSV FOLDER\vehicles.csv`
- `C:\Users\gatit\Desktop\CSV FOLDER\compliance_records.csv`
- `C:\Users\gatit\Desktop\CSV FOLDER\service_records.csv`
- `C:\Users\gatit\Desktop\CSV FOLDER\notifications.csv`

The generator script is:

- `tools/generate-seeds.ps1`

Run it with:

```bash
powershell -ExecutionPolicy Bypass -File .\tools\generate-seeds.ps1 -SourceFolder "C:\Users\gatit\Desktop\CSV FOLDER"
```

Seeded app account:

- Email: `joy.gatiti@strathmore.edu`
- Role: `owner`
- Password: use the value stored in your local `.env`

### Useful Commands

Show tables:

```bash
docker exec -it vehicle-compliance-db mysql -uvcs_user -pvcs_password -D vehicle_compliance -e "SHOW TABLES;"
```

Inspect data:

```bash
docker exec -it vehicle-compliance-db mysql -uvcs_user -pvcs_password -D vehicle_compliance -e "SELECT * FROM users; SELECT * FROM vehicles;"
```

Open an interactive MySQL shell:

```bash
docker exec -it vehicle-compliance-db mysql -uvcs_user -pvcs_password -D vehicle_compliance
```

### Reset Database

If you need to rerun the init schema from scratch, remove the volume and start again:

```bash
docker compose down -v
docker compose up --build
```

### Notes

- The MySQL host port is mapped to `3307` to avoid collisions with local MySQL installs.
- The login backend now loads `config/db.php`, which reads database values from environment variables.
- The login flow now verifies email/password and requires account activation.
- The `app` service bind-mounts the repository in `docker-compose.yml`, so code changes appear in the running container immediately.
