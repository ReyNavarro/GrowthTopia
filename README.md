# Growthtopia

**Agriculture Information System** for managing farmers, farms, crops, plantings, and harvests. Growthtopia is built with PHP and Supabase PostgreSQL.

| | |
|---|---|
| Current app version | **v2.5.2** — prepared locally; not yet released |
| Latest published release | **v2.5.1** |
| GitHub repository | [ReyNavarro/GrowthTopia](https://github.com/ReyNavarro/GrowthTopia) |
| Project use | Educational and demonstration purposes |

> **Use test data only.** This project has not been verified for production use. Avoid entering real or sensitive personal information.

## Features

- **Farmers and farms:** maintain farmer details and associate farms with owners, locations, area, soil type, and irrigation method.
- **Crops:** organize crop varieties, categories, growing periods, and seasons.
- **Plantings and harvests:** track crop planting activity, status, expected dates, harvest quantities, prices, grades, and remarks.
- **Lookup data:** use reference tables for soil and irrigation types, crop categories, seasons, planting statuses, quality grades, and harvest remarks.
- **Dashboard and reports:** review operational summaries, charts, recent activity, date-range reports, and printable reports.
- **Record workflows:** search records, archive them, restore eligible records, and permanently delete archived items when relationships permit.
- **Administration:** manage accounts and roles, control staff add/edit/delete access, view notifications, and search activity logs.
- **Interface:** persistent light/dark mode, a pixel-inspired agricultural theme, page loading skeletons, and Philippines-time display.
- **Safeguards:** password hashing, role checks, CSRF protection on protected forms, database relationships, and account/record activity logging.

## Technology

- PHP 8.1+ and Apache
- Supabase PostgreSQL with PHP `pdo_pgsql`
- Bootstrap, Bootstrap Icons, and Tailwind CDN
- Docker-based Render deployment configuration

## Run locally

### Requirements

- Windows with XAMPP, or PHP 8.1+ installed
- PHP `pdo_pgsql` extension enabled
- A Supabase PostgreSQL project with the application schema and lookup records
- Git

### Setup

1. Clone the repository and enter the application folder:

   ```powershell
   git clone https://github.com/ReyNavarro/GrowthTopia.git
   Set-Location .\GrowthTopia\agrinavarro
   ```

   If you already have the project checked out, open PowerShell in its root and run `Set-Location .\agrinavarro`.

2. Configure a local database connection. Use `includes/db.local.php.example` as a reference to create `includes/db.local.php`, or provide the `SUPABASE_DB_*` variables listed in [`.env.example`](agrinavarro/.env.example). Keep credentials private; local database configuration is not meant to be committed.

3. Start the PHP development server:

   ```powershell
   & 'C:\xampp\php\php.exe' -S 127.0.0.1:8094 -t .
   ```

   If PHP is on your `PATH`, you can use `php -S 127.0.0.1:8094 -t .`. Adjust the executable path if XAMPP is installed elsewhere.

4. Open [http://127.0.0.1:8094/login.php](http://127.0.0.1:8094/login.php). Keep the terminal open while using the app and press **Ctrl+C** to stop the server.

The PHP development server is for local testing only; do not expose it directly to the internet. An existing database schema and sign-in account are required. Before using user permissions, run `agrinavarro/supabase/migrations/20261115_user_action_permissions.sql` in the Supabase SQL Editor; migrations are not applied automatically.

## Deployment

The repository includes a Dockerfile for Render using PHP 8.3, Apache, and `pdo_pgsql` on port `10000`. Configure database values and `APP_HTTPS` as private service environment variables; never commit secrets.

The Render URL listed for the previous project name, <https://datacorepoint.onrender.com/login.php>, returned HTTP 503 when checked on 2026-11-15. It is not currently confirmed as an available demo. Check the Render Dashboard for the current service status before sharing a public link.

## Releases

**v2.5.1** is the latest published release. **v2.5.2** is committed locally, but this checkout has no v2.5.2 tag. See [CHANGELOG.md](CHANGELOG.md) for release notes.
