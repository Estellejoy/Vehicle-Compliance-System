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

For hot reload during development, use Compose Watch if your Docker Compose version supports it:

```bash
docker compose watch
```

Open:

- `http://localhost:8080`
- `http://localhost:8081` for phpMyAdmin

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

The first database start loads the schema from `docker/mysql/init/01-schema.sql`.

If you already have an existing MySQL volume, apply the inspection migration once:

```cmd
type docker\mysql\migrations\01_add_vehicle_inspection.sql | docker exec -i vehicle-compliance-db mysql -uvcs_user -pvcs_password -D vehicle_compliance
```

Then apply the inspection audit migration so the system stores who performed the last check:

```cmd
type docker\mysql\migrations\03_add_vehicle_inspection_checked_by.sql | docker exec -i vehicle-compliance-db mysql -uvcs_user -pvcs_password -D vehicle_compliance
```

### Seeded Tables

The init schema creates these tables:

- `users`
- `vehicles`
- `compliance_records`
- `service_records`
- `notifications`

It also loads the CSV-backed seed rows so the app can be tested immediately.

Seeded user passwords are generated from each email's local part plus `@123`.
For example, `brian.mwangi@gmail.com` uses `brian.mwangi@123`.

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
<<<<<<< Updated upstream
- The current login flow still checks email + role; password verification has not been implemented yet.
- The `app` service uses `develop.watch` rules in `docker-compose.yml` for file sync and rebuilds during development.
=======
- The login flow now requires a valid password hash, so `NULL` passwords no longer bypass authentication.
>>>>>>> Stashed changes
