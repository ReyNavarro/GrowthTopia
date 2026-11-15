# Growthtopia Data Dictionary

This logical data dictionary is based on the submitted normalized 3NF schema diagram and the SQL migration files in `supabase/migrations/`. It describes the intended application model; it is **not a newly generated live Supabase catalog export**. Confirm column types, nullability, defaults, and constraint names from the current database before final submission.

## Core agricultural tables

| Table | Field | Key | Meaning |
|---|---|---|---|
| `farmers` | `farmer_id` | PK | Unique farmer identifier. |
|  | `first_name` |  | Farmer's first name. |
|  | `last_name` |  | Farmer's last name. |
|  | `contact_number` |  | Contact telephone number. |
|  | `address` |  | Farmer's address. |
|  | `date_registered` |  | Date the farmer was registered. |
|  | `is_deleted` |  | Archive flag; archived rows are excluded from normal lists. |
| `farms` | `farm_id` | PK | Unique farm identifier. |
|  | `farmer_id` | FK | Owner reference to `farmers.farmer_id`. |
|  | `farm_name` |  | Farm name. |
|  | `location` |  | Farm location. |
|  | `area_hectares` |  | Farm area in hectares. |
|  | `soil_id` | FK | Soil classification reference to `soil_types.soil_id`. |
|  | `irrigation_id` | FK | Irrigation reference to `irrigation_types.irrigation_id`. |
|  | `is_deleted` |  | Archive flag. |
| `crops` | `crop_id` | PK | Unique crop identifier. |
|  | `crop_name` |  | Crop name. |
|  | `variety` |  | Crop variety. |
|  | `category_id` | FK | Category reference to `crop_categories.category_id`. |
|  | `growing_period_days` |  | Expected growing period in days. |
|  | `season_id` | FK, optional | Recommended season reference to `seasons.season_id`. |
|  | `is_deleted` |  | Archive flag. |
| `plantings` | `planting_id` | PK | Unique planting identifier. |
|  | `farm_id` | FK | Farm reference to `farms.farm_id`. |
|  | `crop_id` | FK | Crop reference to `crops.crop_id`. |
|  | `planting_date` |  | Planting date. |
|  | `area_planted` |  | Area used for this planting. |
|  | `seed_quantity` |  | Quantity of seed used. |
|  | `expected_harvest_date` |  | Expected harvest date. |
|  | `status_id` | FK | Status reference to `planting_statuses.status_id`. |
|  | `is_deleted` |  | Archive flag. |
| `harvests` | `harvest_id` | PK | Unique harvest identifier. |
|  | `planting_id` | FK | Source planting reference to `plantings.planting_id`. |
|  | `harvest_date` |  | Date harvested. |
|  | `quantity_kg` |  | Harvest amount in kilograms. |
|  | `selling_price_per_kg` |  | Selling price for one kilogram. |
|  | `grade_id` | FK | Quality reference to `quality_grades.grade_id`. |
|  | `remark_id` | FK | Remark reference to `harvest_remarks.remark_id`. |
|  | `is_deleted` |  | Archive flag. |

## Lookup tables

| Table | Primary key | Description field | Used by |
|---|---|---|---|
| `soil_types` | `soil_id` | `soil_name` | `farms.soil_id` |
| `irrigation_types` | `irrigation_id` | `irrigation_name` | `farms.irrigation_id` |
| `crop_categories` | `category_id` | `category_name` | `crops.category_id` |
| `seasons` | `season_id` | `season_name` | `crops.season_id` |
| `planting_statuses` | `status_id` | `status_name` | `plantings.status_id` |
| `quality_grades` | `grade_id` | `grade_name` | `harvests.grade_id` |
| `harvest_remarks` | `remark_id` | `remark_description` | `harvests.remark_id` |

The supplied sample schema/screenshots show values such as Loam, Clay, and Sandy Loam; Rainfed, Irrigated, and Drip Irrigation; Cereal, Vegetable, Fruit, Legume, and Root Crop; Wet Season, Dry Season, and Year Round; Growing and Harvested; Grade A/B/C; and harvest-quality remarks. These are sample values only—confirm the current rows in Supabase before presenting them as the live lists.

Lookup tables also have generated identifiers and may include `created_at` and `updated_at` audit timestamps.

## Account, audit, and settings tables

| Table | Field | Key | Meaning |
|---|---|---|---|
| `roles` | `role_id` | PK | Unique role identifier. |
|  | `role_name` |  | Human-readable role name used for access control. |
| `users` | `user_id` | PK | Unique account identifier. |
|  | `username` | UK | Sign-in name; migration creates case-insensitive uniqueness. |
|  | `password_hash` |  | Password hash; plaintext passwords must not be stored. |
|  | `role_id` | FK | Role reference to `roles.role_id`. |
|  | `last_notification_read_id` |  | Activity-log watermark for tracking read notifications. |
| `activity_logs` | `log_id` | PK | Unique activity entry identifier. |
|  | `user_id` | FK, nullable | Actor reference to `users.user_id`; historical/system records may have no actor. |
|  | `log_action` |  | Description of the recorded action. |
|  | `created_at` |  | Time the activity entry was recorded. |
| `system_settings` | `setting_id` | PK | Unique setting identifier. |
|  | `setting_key` |  | Setting name. |
|  | `setting_value` |  | Setting value. |

Main records and user accounts receive `created_at` and `updated_at` timestamps from the versioned migration. A database trigger updates `updated_at` on changes.

## Relationships

| Parent key | Referencing field | Cardinality |
|---|---|---|
| `farmers.farmer_id` | `farms.farmer_id` | One farmer to many farms |
| `farms.farm_id` | `plantings.farm_id` | One farm to many plantings |
| `crops.crop_id` | `plantings.crop_id` | One crop to many plantings |
| `plantings.planting_id` | `harvests.planting_id` | One planting to many harvests |
| Each lookup primary key | Matching `*_id` foreign key in its operational table | One lookup value to many records |
| `roles.role_id` | `users.role_id` | One role to many users |
| `users.user_id` | `activity_logs.user_id` | One user to many activity entries |

See the [ERD](ERD.md) for the complete relationship diagram.

## Constraints documented in migrations

The lookup normalization migration makes required references non-null for `farms.farmer_id`, `farms.soil_id`, `farms.irrigation_id`, `crops.category_id`, `plantings.farm_id`, `plantings.crop_id`, `plantings.status_id`, `harvests.planting_id`, `harvests.grade_id`, `harvests.remark_id`, and `users.role_id`. `crops.season_id` is optional.

The same migration defines these checks:

- `farms.area_hectares >= 0`
- `crops.growing_period_days >= 0`
- `plantings.area_planted >= 0`
- `plantings.seed_quantity >= 0`
- `plantings.expected_harvest_date >= plantings.planting_date`
- `harvests.quantity_kg > 0`
- `harvests.selling_price_per_kg >= 0`
- Case-insensitive unique usernames via an index on `lower(username)`

The actual primary/foreign-key names and delete behavior should be read from the live catalog. Do not infer cascading deletion from this document.
