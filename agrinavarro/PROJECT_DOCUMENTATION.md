# Growthtopia Agriculture Information System

**Project documentation**

**Prepared by:** Navarro, Rey Anderson D.

**Program/section:** BSIS 3-1
**Application:** Growthtopia, PHP and Supabase PostgreSQL

> This is a school project for educational and demonstration purposes. Use test data only; the application is not verified for production use.

## 1. Project title, description, and objectives

### Project title

**Growthtopia Agriculture Information System**

### Description

Growthtopia is a web-based system for organizing farmer, farm, crop, planting, and harvest information in one Supabase PostgreSQL database. Users can maintain related agricultural records, review dashboard summaries, search records, and prepare date-range reports. Administrator tools provide role and account management, activity history, notifications, and archived-record recovery.

### Objectives

1. Centralize agricultural records in a structured relational database.
2. Link farmers to their farms, farms and crops to planting records, and planting records to harvests.
3. Use lookup tables for reusable classifications and statuses.
4. Provide authorized users with search, summary, reporting, and record-maintenance tools.
5. Preserve recoverable operational records through archive and restore workflows.
6. Record relevant account and data-management activity for administrator review.

## 2. Functional requirements

### Core records

- **FR-01 — Authentication:** Users sign in with an account and assigned role. Passwords are checked using PHP password-hashing functions.
- **FR-02 — Dashboard:** The application presents operational counts, charts, recent activity, and navigation to its modules.
- **FR-03 — Farmers:** Authorized users can add, view, update, and archive farmer records.
- **FR-04 — Farms:** Authorized users can associate a farm with a farmer and record farm name, location, area, soil, and irrigation information.
- **FR-05 — Crops:** Authorized users can maintain crop name, variety, category, growing period, and optional season.
- **FR-06 — Plantings:** Authorized users can record a crop on a farm, planting date, planted area, seed quantity, expected harvest date, and status.
- **FR-07 — Harvests:** Authorized users can record one or more harvest entries for a planting, with harvest date, quantity, selling price, quality grade, and remark.
- **FR-08 — Search and reports:** Users can search record lists; harvests also support grade filtering and pagination. Reports can be filtered by date range and printed from the browser.
- **FR-09 — Archive and restore:** Operational records can be archived without being immediately removed. Administrators can restore eligible records or permanently delete archived records when relationship constraints permit.

### Administration and supporting functions

- **FR-10 — User management:** Administrators can create accounts, update usernames and roles, optionally set a new password, and delete eligible accounts. The active account cannot delete itself; accounts with activity history and the final administrator account are protected from deletion.
- **FR-11 — Roles:** Access to administrator functions is restricted according to the signed-in user's role.
- **FR-12 — Notifications:** Users can see recent activity subject to role-based visibility; administrators can review all activity.
- **FR-13 — Activity history:** The system records selected sign-in, account, and record-management actions for administrator review.
- **FR-14 — Interface preferences:** Users can switch between light and dark themes. The preference is saved in browser storage.
- **FR-15 — Validation:** Required fields and browser-supported value constraints provide immediate feedback. Server-side validation remains authoritative for database writes.

### Quality and operational requirements

- The application requires PHP 8.1+ with `pdo_pgsql` and a configured Supabase PostgreSQL database.
- Protected form submissions use CSRF tokens, and database access uses parameterized queries where applicable.
- Credentials must be supplied through ignored local configuration or private hosting environment variables.
- The application uses Asia/Manila for the displayed clock and activity timestamp formatting.
- The sign-in background video is an existing design element and is not part of the database or business workflows.

## 3. Entity-Relationship Diagram

The editable [ERD](docs/ERD.md) is based on the submitted normalized schema diagram. Its central record chain is:

**FARMER 1:M FARM 1:M PLANTING 1:M HARVEST**

A planting also references one crop. Lookup entities connect to farms, crops, plantings, and harvests to keep reusable labels in one place. The ERD includes the account, role, activity-log, and system-settings tables as well as the agricultural modules.

## 4. Database normalization: UNF, 1NF, 2NF, and 3NF

The submitted `1st NORMAL FORM.docx` contains example table screenshots and a normalized database diagram. This summary describes the design progression without reproducing example personal data.

### Unnormalized form (UNF)

A single agricultural worksheet could repeat farmer and farm details for every planting and repeat planting and crop details for every harvest. A record could contain multiple harvest values or repeating classification text. This causes duplicate data and makes updates inconsistent: changing a farmer's contact information, for example, would require changing multiple worksheet rows.

### First normal form (1NF)

Separate repeating values into individual records and ensure each field stores one value. Assign a primary key to each record so each row is uniquely addressable. Growthtopia uses identifiers such as `farmer_id`, `farm_id`, `crop_id`, `planting_id`, and `harvest_id`. A harvest is recorded as its own row rather than as a repeating group inside a planting row.

### Second normal form (2NF)

The core entities use single-column primary keys. Therefore there are no partial dependencies on part of a composite key in those entities. Attributes are stored with the entity they describe: farmer details with `farmers`, farm details with `farms`, crop details with `crops`, planting details with `plantings`, and harvest details with `harvests`.

### Third normal form (3NF)

Move reusable descriptive values into lookup tables and reference them by key. The 3NF model uses `soil_types`, `irrigation_types`, `crop_categories`, `seasons`, `planting_statuses`, `quality_grades`, and `harvest_remarks`. Operational rows store the corresponding numeric foreign keys rather than repeating the descriptive labels. The relationship structure and lookup constraints are documented in the [ERD](docs/ERD.md) and [data dictionary](docs/DATA_DICTIONARY.md).

The supplied migration `supabase/migrations/20261105_normalize_lookup_fields.sql` describes the lookup normalization and its validation checks. Confirm the current Supabase schema before treating this documentation as a live schema export.

## 5. Data dictionary

The [Growthtopia data dictionary](docs/DATA_DICTIONARY.md) describes the core entities, supporting account and audit tables, field meanings, foreign keys, and lookup values. The dictionary is based on the supplied 3NF schema diagram and repository migration scripts; regenerate it from the current Supabase catalog before final submission if the database has since changed.

## 6. Table relationships, constraints, and lookup data

### Main relationships

| Parent table | Child table | Relationship | Foreign key |
|---|---|---:|---|
| `farmers` | `farms` | 1:M | `farms.farmer_id` |
| `farms` | `plantings` | 1:M | `plantings.farm_id` |
| `crops` | `plantings` | 1:M | `plantings.crop_id` |
| `plantings` | `harvests` | 1:M | `harvests.planting_id` |
| `soil_types` | `farms` | 1:M | `farms.soil_id` |
| `irrigation_types` | `farms` | 1:M | `farms.irrigation_id` |
| `crop_categories` | `crops` | 1:M | `crops.category_id` |
| `seasons` | `crops` | 1:M, optional for crop | `crops.season_id` |
| `planting_statuses` | `plantings` | 1:M | `plantings.status_id` |
| `quality_grades` | `harvests` | 1:M | `harvests.grade_id` |
| `harvest_remarks` | `harvests` | 1:M | `harvests.remark_id` |
| `roles` | `users` | 1:M | `users.role_id` |
| `users` | `activity_logs` | 1:M, user reference may be null | `activity_logs.user_id` |

### Key constraints and rules

- Each entity uses its own primary key.
- Required lookup relationships are non-null in the normalization migration: farms require a farmer, soil type, and irrigation type; crops require a category; plantings require farm, crop, and status; harvests require planting, grade, and remark; users require a role. A crop's season is optional.
- Usernames have a case-insensitive unique index.
- Check constraints reject negative farm area, growing period, planting area, seed quantity, and selling price; harvest quantity must be greater than zero; expected harvest date must not precede planting date.
- Farmers, farms, crops, plantings, and harvests use the `is_deleted` flag for archiving. Permanent deletion remains subject to foreign-key and application dependency checks.
- Main records and user accounts receive creation/update timestamps through migration-managed columns and triggers.
- Migrations are not run automatically by the application. Review and apply them manually after a database backup.

### Lookup data

The submitted sample screenshots show examples such as:

| Lookup table | Example labels in the supplied sample |
|---|---|
| `soil_types` | Loam, Clay Loam, Sandy Loam, Clay |
| `irrigation_types` | Rainfed, Irrigated, Drip Irrigation |
| `crop_categories` | Cereal, Vegetable, Fruit, Legume, Root Crop |
| `seasons` | Wet Season, Dry Season, Year Round |
| `planting_statuses` | Growing, Harvested |
| `quality_grades` | Grade A, Grade B, Grade C |
| `harvest_remarks` | High quality harvest, Good harvest, Acceptable quality |
| `roles` | Role names are maintained in the database; verify the live values before documenting them. |

These are illustrative values visible in the supplied project material, not a guarantee of current live lookup rows.

## 7. User guide

### Sign in and navigate

1. Open the local site at <http://127.0.0.1:8094/login.php> after starting the PHP server, or use the deployed site URL if available.
2. Enter the username and password for an existing account.
3. Use the sidebar to open Dashboard, Farmers, Farms, Crops, Plantings, Harvests, and Reports.
4. Administrators also have Users, Archived Records, and Activity Logs.
5. Use the theme button in the header to change the saved light/dark preference.

### Enter agricultural records

1. Create a farmer before creating that farmer's farm.
2. Create the required farmer, farm, and crop records before entering a planting.
3. In Plantings, select the farm, crop, and status; enter planting and expected harvest details.
4. In Harvests, select a planting and choose a quality grade and remark from the reference lists.
5. Use each module's search controls to locate records. Use the report date filters to review activity over a period.

### Edit, archive, and restore

- Use the **Edit** action on a record to open its form, change the necessary values, and save.
- Use an archive action to remove a record from active lists while retaining it for recovery.
- Administrators can open **Archived Records** to restore an entry or permanently delete an eligible record. A record with dependent records may not be permanently deletable.
- In **Users**, administrators can edit an account, leave the new password blank to keep the current password, or request deletion of an eligible account. The active account, the last administrator, and accounts with activity history are protected.
- Use **Activity Logs** and notifications to review recorded actions.

### Sign out and data safety

- Use **Logout** and confirm the sign-out.
- Use test or anonymized data for demonstrations. Do not publish screenshots that expose real farmer names, phone numbers, addresses, passwords, or database credentials.
- Keep database credentials in ignored local configuration or private Render environment settings.

## 8. Screenshots and evidence

**To be added by the project owner.** Insert privacy-safe screenshots for the sign-in page, dashboard, farmer and farm records, crop and planting records, harvests, reports, and administrator modules. Remove or mask real personal data before printing or publishing.

## 9. Test cases and results

**To be completed by the project owner.** Attach the test cases and actual results for required sign-in, role access, create/edit/search/archive/restore, reporting, validation, and account-management workflows. Record the test date and use disposable or anonymized records.
