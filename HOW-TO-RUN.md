# How to Run — CivicFix

Assumes prerequisites in `PREREQUISITES.md` are already done. No Composer step — this project has no dependencies.

## One-time setup

```
mysql -u root -P 3306 < database/schema.sql
```
(or import `database/schema.sql` via phpMyAdmin)

## Start MySQL

Open **XAMPP Control Panel** and make sure **MySQL** is running.

## Run the app

From this project's folder:
```
php -S localhost:8021 -t public public/index.php
```
(`-t public` is required — without it, assets under `public/assets` and
uploaded photos under `public/uploads` 404. Port **8021** is this project's
assigned slot in the suite's dev-server port convention: project 22 →
8000 + 22 - 1.)

## Open it

- Log in: `http://localhost:8021/index.php?route=login` — any of the demo
  credentials in `README.md` under "Default Login Credentials".
- Public dashboard (no login): `http://localhost:8021/index.php?route=stats`

## Stop the app

`Ctrl+C` in the terminal running the `php -S` command.
