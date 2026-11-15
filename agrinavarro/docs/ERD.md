# Growthtopia Entity-Relationship Diagram

The diagram below expresses the relationships in the supplied normalized 3NF schema. The core operational chain is **FARMER 1:M FARM 1:M PLANTING 1:M HARVEST**; each planting also references one crop. Lookup entities store reusable descriptions. This editable Mermaid diagram is based on the submitted schema image and the migration scripts in `supabase/migrations/`.

```mermaid
erDiagram
    farmers ||--o{ farms : owns
    farms ||--o{ plantings : hosts
    crops ||--o{ plantings : selected_for
    plantings ||--o{ harvests : produces

    soil_types ||--o{ farms : classifies
    irrigation_types ||--o{ farms : irrigates
    crop_categories ||--o{ crops : categorizes
    seasons o|--o{ crops : recommends
    planting_statuses ||--o{ plantings : tracks
    quality_grades ||--o{ harvests : grades
    harvest_remarks ||--o{ harvests : describes

    roles ||--o{ users : assigns
    users o|--o{ activity_logs : creates

    farmers {
        int farmer_id PK
        varchar first_name
        varchar last_name
        varchar contact_number
        varchar address
        date date_registered
        boolean is_deleted
    }
    farms {
        int farm_id PK
        int farmer_id FK
        varchar farm_name
        varchar location
        numeric area_hectares
        int soil_id FK
        int irrigation_id FK
        boolean is_deleted
    }
    crops {
        int crop_id PK
        varchar crop_name
        varchar variety
        int category_id FK
        int growing_period_days
        int season_id FK
        boolean is_deleted
    }
    plantings {
        int planting_id PK
        int farm_id FK
        int crop_id FK
        date planting_date
        numeric area_planted
        numeric seed_quantity
        date expected_harvest_date
        int status_id FK
        boolean is_deleted
    }
    harvests {
        int harvest_id PK
        int planting_id FK
        date harvest_date
        numeric quantity_kg
        numeric selling_price_per_kg
        int grade_id FK
        int remark_id FK
        boolean is_deleted
    }
    soil_types {
        int soil_id PK
        varchar soil_name
    }
    irrigation_types {
        int irrigation_id PK
        varchar irrigation_name
    }
    crop_categories {
        int category_id PK
        varchar category_name
    }
    seasons {
        int season_id PK
        varchar season_name
    }
    planting_statuses {
        int status_id PK
        varchar status_name
    }
    quality_grades {
        int grade_id PK
        varchar grade_name
    }
    harvest_remarks {
        int remark_id PK
        varchar remark_description
    }
    roles {
        int role_id PK
        varchar role_name
    }
    users {
        int user_id PK
        varchar username UK
        varchar password_hash
        int role_id FK
        bigint last_notification_read_id
    }
    activity_logs {
        int log_id PK
        int user_id FK
        text log_action
        timestamp created_at
    }
    system_settings {
        int setting_id PK
        varchar setting_key
        text setting_value
    }
```

`season_id` is optional in the supplied normalized schema; the other required relationship IDs are made non-null by the lookup-normalization migration. The `system_settings` key/value table is not connected to the operational record graph.

## Relationship summary

| Parent | Child | Cardinality | Meaning |
|---|---|---:|---|
| `farmers` | `farms` | 1:M | One farmer may own multiple farms; each farm belongs to one farmer. |
| `farms` | `plantings` | 1:M | A farm can host multiple plantings; each planting belongs to one farm. |
| `crops` | `plantings` | 1:M | A crop may be used in multiple plantings; each planting selects one crop. |
| `plantings` | `harvests` | 1:M | One planting can produce multiple harvest records; each harvest refers to one planting. |
| Lookup tables | Operational tables | 1:M | Each selected soil, irrigation, category, season, status, grade, or remark is stored by its lookup ID. |
| `roles` | `users` | 1:M | A role can be assigned to multiple user accounts. |
| `users` | `activity_logs` | 1:M | An account can create multiple activity entries; historical entries may have a null user reference. |

Confirm exact column types, nullability, and constraints against the current Supabase catalog before submitting a database snapshot. See the [data dictionary](DATA_DICTIONARY.md).
