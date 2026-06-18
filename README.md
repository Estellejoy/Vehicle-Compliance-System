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

It also inserts sample rows so the app can be tested immediately.

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
