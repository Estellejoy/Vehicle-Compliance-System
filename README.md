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

### Run

```bash
docker compose up --build
```

Open:

- `http://localhost:8080`

## Database Setup

The database connection is configured through environment variables in `config/db.php`.

Default Compose values:

- `DB_HOST=db`
- `DB_NAME=vehicle_compliance`
- `DB_USER=vcs_user`
- `DB_PASSWORD=vcs_password`
- Host MySQL port: `3307`

The first database start loads the schema from `docker/mysql/init/01-schema.sql`.

### Seeded Tables

The init schema creates these tables:

- `users`
- `vehicles`
- `compliance_records`
- `service_records`
- `notifications`

It also loads the CSV-backed seed rows so the app can be tested immediately.

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
- The current login flow still checks email + role; password verification has not been implemented yet.
