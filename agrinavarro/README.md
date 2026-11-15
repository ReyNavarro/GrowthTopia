# Growthtopia Application

Growthtopia is a PHP Agriculture Information System for managing farmers, farms, crops, plantings, harvests, reports, and related administrative workflows. It uses Supabase PostgreSQL for application data.

**Application version:** v2.5.2 — prepared locally; not yet released.
**Project use:** Educational and demonstration purposes. Use test data; the application is not verified for production use.

For the feature overview and repository information, see the [main README](../README.md).

## Requirements

- PHP 8.1 or later with `pdo_pgsql` enabled
- A Supabase PostgreSQL project with the required schema and lookup records
- XAMPP on Windows, or another local PHP environment

## Configure the database

The application reads the `SUPABASE_DB_*` environment variables. For local development, create `includes/db.local.php` using `includes/db.local.php.example` as a reference, or provide the environment variables listed in [`.env.example`](.env.example).

Use SSL for the database connection. Keep credentials private: `includes/db.local.php` and real `.env` files must not be committed. Set deployment secrets through Render's private environment settings.

The application does not automatically apply database migrations. SQL files under [`supabase/migrations/`](supabase/migrations/) document schema changes and must be reviewed and applied manually in Supabase when appropriate. Back up the database and verify migration status before applying changes.

## Run locally on port 8094

Open PowerShell in the repository root and start the PHP server:

```powershell
Set-Location .\agrinavarro
& 'C:\xampp\php\php.exe' -S 127.0.0.1:8094 -t .
```

If PHP is available on your `PATH`, you can use `php -S 127.0.0.1:8094 -t .` instead. Update the executable path if XAMPP is installed elsewhere.

Open [http://127.0.0.1:8094/login.php](http://127.0.0.1:8094/login.php). Keep the terminal open while testing and press **Ctrl+C** to stop the server. PHP's built-in server is for local development only and must not be exposed publicly.

The application requires a valid database configuration, schema, and existing account. The previous Render URL returned HTTP 503 on 2026-11-15; check the Render Dashboard for the current service status before sharing a public demo link.

## Application modules

- **Dashboard:** operational summaries, charts, recent activity, and links to the record modules.
- **Farmers and Farms:** maintain farmer records and associated farm information.
- **Crops:** manage crop varieties, categories, growing periods, and seasons.
- **Plantings:** connect crops and farms, and record planting details and status.
- **Harvests:** record harvests against plantings, including date, quantity, price, grade, and remarks.
- **Reports:** review date-range results and print reports from the browser.
- **Users (administrators):** create and update accounts and assign roles.
- **Archived Records (administrators):** restore archived records or permanently delete eligible records.
- **Activity Logs (administrators):** search account and record activity.
- **Notifications:** view recent activity, with non-administrator access limited to the signed-in user's activity.

Record lists include live search; harvest records also support grade filtering and pagination. The application includes a saved light/dark preference, a pixel-inspired agricultural theme, loading skeletons, a Philippines-time clock, and Asia/Manila formatting for activity timestamps.

Archived farmers, farms, crops, plantings, and harvests remain in their original tables and are marked with `is_deleted`. Normal lists exclude archived rows. Administrators can restore eligible rows; permanent deletion may be blocked while related active records still reference them. Use disposable records when demonstrating these actions.

## Project and database documentation

- [Project description, requirements, normalization, and user guide](PROJECT_DOCUMENTATION.md)
- [ERD and relationships](docs/ERD.md)
- [Data dictionary and constraints](docs/DATA_DICTIONARY.md)
- [Normalization review](docs/NORMALIZATION_REVIEW.md)
- [Verification record and outstanding tests](docs/TEST_RESULTS.md)
- [Render deployment and Supabase migration guidance](docs/DEPLOYMENT.md)
- [Release notes](../CHANGELOG.md)
- [Versioning and rollback guide](docs/VERSIONING.md)

## Deployment

The repository root contains a Dockerfile for Render using PHP 8.3, Apache, and `pdo_pgsql` on port `10000`. Follow the [deployment guide](docs/DEPLOYMENT.md) to configure the service and private database environment variables. The previously listed public URL returned HTTP 503 on 2026-11-15; deployment readiness must be rechecked in Render.
