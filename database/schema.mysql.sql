-- Fleet System: import into a NEW database called fleet_system using phpMyAdmin.
-- MySQL 8+ or MariaDB 10.4+ (the database supplied with XAMPP).
-- No DROP, DELETE or default administrator password is included.
CREATE TABLE IF NOT EXISTS users (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(100) NOT NULL,
 email VARCHAR(190) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 role VARCHAR(20) NOT NULL DEFAULT 'admin',
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vehicles (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 registration VARCHAR(12) NOT NULL UNIQUE,
 make VARCHAR(60) NOT NULL,
 model VARCHAR(80) NOT NULL,
 model_year SMALLINT UNSIGNED NOT NULL,
 mileage INT UNSIGNED NOT NULL DEFAULT 0,
 mot_expiry DATE NULL,
 insurance_expiry DATE NULL,
 next_service_date DATE NULL,
 next_service_mileage INT UNSIGNED NULL,
 status VARCHAR(20) NOT NULL DEFAULT 'Available',
 archived TINYINT(1) NOT NULL DEFAULT 0,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_vehicle_expiry (mot_expiry, insurance_expiry),
 INDEX idx_vehicle_status (archived, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS drivers (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(100) NOT NULL,
 email VARCHAR(190) NOT NULL,
 phone VARCHAR(25) NOT NULL,
 licence_number VARCHAR(20) NOT NULL UNIQUE,
 licence_expiry DATE NOT NULL,
 active TINYINT(1) NOT NULL DEFAULT 1,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS assignments (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 vehicle_id INT UNSIGNED NOT NULL,
 driver_id INT UNSIGNED NOT NULL,
 started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 ended_at DATETIME NULL,
 -- Unique indexes allow multiple historical rows but only one active row per vehicle/driver.
 active_vehicle_id INT UNSIGNED GENERATED ALWAYS AS (CASE WHEN ended_at IS NULL THEN vehicle_id ELSE NULL END) STORED,
 active_driver_id INT UNSIGNED GENERATED ALWAYS AS (CASE WHEN ended_at IS NULL THEN driver_id ELSE NULL END) STORED,
 UNIQUE KEY uq_active_vehicle (active_vehicle_id),
 UNIQUE KEY uq_active_driver (active_driver_id),
 CONSTRAINT fk_assignment_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE RESTRICT,
 CONSTRAINT fk_assignment_driver FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS maintenance (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 vehicle_id INT UNSIGNED NOT NULL,
 service_date DATE NOT NULL,
 mileage INT UNSIGNED NOT NULL,
 description TEXT NOT NULL,
 supplier VARCHAR(120) NOT NULL,
 cost_pence BIGINT UNSIGNED NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_service_vehicle_date (vehicle_id, service_date),
 CONSTRAINT fk_service_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS repairs (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 vehicle_id INT UNSIGNED NOT NULL,
 repair_date DATE NOT NULL,
 description TEXT NOT NULL,
 supplier VARCHAR(120) NOT NULL,
 cost_pence BIGINT UNSIGNED NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_repair_vehicle_date (vehicle_id, repair_date),
 CONSTRAINT fk_repair_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
 email_key CHAR(64) PRIMARY KEY,
 failures INT UNSIGNED NOT NULL DEFAULT 0,
 locked_until DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
