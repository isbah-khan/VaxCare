-- Vaccination Management System database schema.
-- Foreign keys preserve consistency across parents, children, hospitals, and bookings.

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role ENUM('admin', 'parent', 'hospital') NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(30) DEFAULT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_role_status (role, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS parent_profiles (
    user_id BIGINT UNSIGNED PRIMARY KEY,
    address VARCHAR(255) DEFAULT NULL,
    CONSTRAINT fk_parent_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS hospitals (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    address VARCHAR(255) NOT NULL,
    city VARCHAR(100) NOT NULL,
    location_details VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_hospital_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_hospitals_city (city)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS children (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    date_of_birth DATE NOT NULL,
    gender ENUM('female', 'male', 'other') NOT NULL,
    blood_group VARCHAR(5) DEFAULT NULL,
    medical_notes VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_child_parent FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_children_parent (parent_id),
    INDEX idx_children_birth (date_of_birth)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vaccines (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    disease VARCHAR(140) NOT NULL,
    dose_label VARCHAR(60) NOT NULL,
    recommended_age_months SMALLINT UNSIGNED NOT NULL,
    description VARCHAR(500) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_vaccine_schedule (name, dose_label, recommended_age_months),
    INDEX idx_vaccine_age_active (recommended_age_months, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS hospital_vaccines (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hospital_id BIGINT UNSIGNED NOT NULL,
    vaccine_id BIGINT UNSIGNED NOT NULL,
    is_available TINYINT(1) NOT NULL DEFAULT 0,
    stock_quantity INT UNSIGNED NOT NULL DEFAULT 0,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_inventory_hospital FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE,
    CONSTRAINT fk_inventory_vaccine FOREIGN KEY (vaccine_id) REFERENCES vaccines(id) ON DELETE CASCADE,
    UNIQUE KEY uq_hospital_vaccine (hospital_id, vaccine_id),
    INDEX idx_inventory_available (vaccine_id, is_available)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS appointments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id BIGINT UNSIGNED NOT NULL,
    child_id BIGINT UNSIGNED NOT NULL,
    hospital_id BIGINT UNSIGNED NOT NULL,
    vaccine_id BIGINT UNSIGNED NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    reason VARCHAR(300) DEFAULT NULL,
    status ENUM('pending', 'approved', 'rejected', 'completed', 'not_vaccinated') NOT NULL DEFAULT 'pending',
    admin_notes VARCHAR(500) DEFAULT NULL,
    hospital_notes VARCHAR(500) DEFAULT NULL,
    approved_by BIGINT UNSIGNED DEFAULT NULL,
    approved_at DATETIME DEFAULT NULL,
    completed_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_appointment_parent FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_appointment_child FOREIGN KEY (child_id) REFERENCES children(id) ON DELETE CASCADE,
    CONSTRAINT fk_appointment_hospital FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE RESTRICT,
    CONSTRAINT fk_appointment_vaccine FOREIGN KEY (vaccine_id) REFERENCES vaccines(id) ON DELETE RESTRICT,
    CONSTRAINT fk_appointment_admin FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_appointments_parent (parent_id, appointment_date),
    INDEX idx_appointments_hospital (hospital_id, status, appointment_date),
    INDEX idx_appointments_status_date (status, appointment_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vaccination_records (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    appointment_id BIGINT UNSIGNED NOT NULL UNIQUE,
    child_id BIGINT UNSIGNED NOT NULL,
    vaccine_id BIGINT UNSIGNED NOT NULL,
    hospital_id BIGINT UNSIGNED NOT NULL,
    administered_at DATETIME NOT NULL,
    batch_number VARCHAR(80) DEFAULT NULL,
    notes VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_record_appointment FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
    CONSTRAINT fk_record_child FOREIGN KEY (child_id) REFERENCES children(id) ON DELETE CASCADE,
    CONSTRAINT fk_record_vaccine FOREIGN KEY (vaccine_id) REFERENCES vaccines(id) ON DELETE RESTRICT,
    CONSTRAINT fk_record_hospital FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE RESTRICT,
    INDEX idx_records_child_date (child_id, administered_at),
    INDEX idx_records_date (administered_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
