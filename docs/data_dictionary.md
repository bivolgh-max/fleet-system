# MySQL data dictionary

This dictionary is generated from the supplied MySQL schema. Application validation adds business rules; an unsigned SQL type alone does not implement all those rules.

## Users

| Field | SQL type | Constraint | Purpose |
|---|---|---|---|
| id | INT UNSIGNED | Primary key; Auto increment; Required | Record identifier |
| name | VARCHAR(100) | Required | Administrator or driver name |
| email | VARCHAR(190) | Unique; Required | Normalised contact or login email |
| password_hash | VARCHAR(255) | Required | One-way PHP password hash; never plain text |
| role | VARCHAR(20) | Required | Administrator role for this prototype |
| created_at | DATETIME | Required | Timestamp when the record was created |

## Vehicles

| Field | SQL type | Constraint | Purpose |
|---|---|---|---|
| id | INT UNSIGNED | Primary key; Auto increment; Required | Record identifier |
| registration | VARCHAR(12) | Unique; Required | Normalised fictional vehicle registration |
| make | VARCHAR(60) | Required | Vehicle manufacturer |
| model | VARCHAR(80) | Required | Vehicle model |
| model_year | SMALLINT UNSIGNED | Required | Model year from 1980 to next year |
| mileage | INT UNSIGNED | Required | Current or historical odometer reading in miles |
| mot_expiry | DATE | Nullable | Manually recorded MOT expiry date |
| insurance_expiry | DATE | Nullable | Manually recorded insurance expiry date |
| next_service_date | DATE | Nullable | Next agreed service date |
| next_service_mileage | INT UNSIGNED | Nullable | Next agreed service mileage threshold |
| status | VARCHAR(20) | Required | Available, In Use or Repair |
| archived | TINYINT(1) | Required | 1 removes the vehicle from the active fleet |
| created_at | DATETIME | Required | Timestamp when the record was created |

## Drivers

| Field | SQL type | Constraint | Purpose |
|---|---|---|---|
| id | INT UNSIGNED | Primary key; Auto increment; Required | Record identifier |
| name | VARCHAR(100) | Required | Administrator or driver name |
| email | VARCHAR(190) | Required | Normalised contact or login email |
| phone | VARCHAR(25) | Required | Contact number with basic format validation |
| licence_number | VARCHAR(20) | Unique; Required | Unique licence string; not externally verified |
| licence_expiry | DATE | Required | Manually recorded licence expiry date |
| active | TINYINT(1) | Required | 1 allows assignment if other rules pass |
| created_at | DATETIME | Required | Timestamp when the record was created |

## Assignments

| Field | SQL type | Constraint | Purpose |
|---|---|---|---|
| id | INT UNSIGNED | Primary key; Auto increment; Required | Record identifier |
| vehicle_id | INT UNSIGNED | Foreign key; Required | Parent vehicle reference |
| driver_id | INT UNSIGNED | Foreign key; Required | Parent driver reference |
| started_at | DATETIME | Required | Assignment start timestamp |
| ended_at | DATETIME | Nullable | Null for an active assignment |
| active_vehicle_id | INT UNSIGNED | Unique; Generated stored | Generated vehicle ID while active; otherwise null |
| active_driver_id | INT UNSIGNED | Unique; Generated stored | Generated driver ID while active; otherwise null |

## Maintenance

| Field | SQL type | Constraint | Purpose |
|---|---|---|---|
| id | INT UNSIGNED | Primary key; Auto increment; Required | Record identifier |
| vehicle_id | INT UNSIGNED | Foreign key; Required | Parent vehicle reference |
| service_date | DATE | Required | Date of completed service, not future work |
| mileage | INT UNSIGNED | Required | Current or historical odometer reading in miles |
| description | TEXT | Required | Completed work description, up to 2000 characters in the app |
| supplier | VARCHAR(120) | Required | Garage or supplier name, up to 120 characters |
| cost_pence | BIGINT UNSIGNED | Required | Non-negative exact cost stored as integer pence |
| created_at | DATETIME | Required | Timestamp when the record was created |

## Repairs

| Field | SQL type | Constraint | Purpose |
|---|---|---|---|
| id | INT UNSIGNED | Primary key; Auto increment; Required | Record identifier |
| vehicle_id | INT UNSIGNED | Foreign key; Required | Parent vehicle reference |
| repair_date | DATE | Required | Date of completed repair, not future work |
| description | TEXT | Required | Completed work description, up to 2000 characters in the app |
| supplier | VARCHAR(120) | Required | Garage or supplier name, up to 120 characters |
| cost_pence | BIGINT UNSIGNED | Required | Non-negative exact cost stored as integer pence |
| created_at | DATETIME | Required | Timestamp when the record was created |

## Login Attempts

| Field | SQL type | Constraint | Purpose |
|---|---|---|---|
| email_key | CHAR(64) | Primary key; Required | SHA-256 of a submitted normalised email |
| failures | INT UNSIGNED | Required | Number of failed login attempts |
| locked_until | DATETIME | Nullable | Null or the account throttle end timestamp |
