-- Christmas Toy Appeal Referral System Database Schema

-- Create database
CREATE DATABASE IF NOT EXISTS toy_appeal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE toy_appeal;

-- Admin users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_username (username)
) ENGINE=InnoDB;

-- Warehouse zones table
CREATE TABLE IF NOT EXISTS zones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    zone_name VARCHAR(50) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_zone_name (zone_name)
) ENGINE=InnoDB;

-- Households table (to group children from same family)
CREATE TABLE IF NOT EXISTS households (
    id INT AUTO_INCREMENT PRIMARY KEY,
    referrer_name VARCHAR(100) NOT NULL,
    referrer_organisation VARCHAR(150) NOT NULL,
    referrer_team VARCHAR(100) NULL,
    secondary_contact VARCHAR(100) NULL,
    referrer_phone VARCHAR(20) NOT NULL,
    referrer_email VARCHAR(100) NOT NULL,
    postcode VARCHAR(10) NOT NULL,
    duration_known ENUM('<1 month', '1-6 months', '6-12 months', '1-2 years', '2+ years') NOT NULL,
    additional_notes TEXT,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_postcode (postcode),
    INDEX idx_organisation (referrer_organisation),
    INDEX idx_submitted (submitted_at)
) ENGINE=InnoDB;

-- Referrals table (one per child)
CREATE TABLE IF NOT EXISTS referrals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reference_number VARCHAR(20) UNIQUE NOT NULL,
    household_id INT NOT NULL,

    -- Child information
    child_initials VARCHAR(10) NOT NULL,
    child_age INT NOT NULL,
    child_gender ENUM('Male', 'Female', 'Other', 'Prefer not to say') NOT NULL,
    special_requirements TEXT,

    -- Label printing tracking
    label_printed BOOLEAN DEFAULT FALSE,
    label_printed_at TIMESTAMP NULL,
    label_printed_by INT NULL,

    -- Status tracking
    status ENUM('pending', 'fulfilled', 'located', 'ready_for_collection', 'collected') DEFAULT 'pending',
    zone_id INT NULL,

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fulfilled_at TIMESTAMP NULL,
    located_at TIMESTAMP NULL,
    ready_at TIMESTAMP NULL,
    collected_at TIMESTAMP NULL,

    -- Tracking
    fulfilled_by INT NULL,
    collected_by INT NULL,
    notes TEXT,

    FOREIGN KEY (household_id) REFERENCES households(id) ON DELETE CASCADE,
    FOREIGN KEY (zone_id) REFERENCES zones(id) ON DELETE SET NULL,
    FOREIGN KEY (label_printed_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (fulfilled_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (collected_by) REFERENCES users(id) ON DELETE SET NULL,

    INDEX idx_reference (reference_number),
    INDEX idx_status (status),
    INDEX idx_household (household_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- Settings table for application configuration
CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value TEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Activity log for audit trail
CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    referral_id INT NOT NULL,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    old_value VARCHAR(255),
    new_value VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (referral_id) REFERENCES referrals(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,

    INDEX idx_referral (referral_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- Insert default admin user (password: admin123 - CHANGE THIS!)
INSERT INTO users (username, password_hash, full_name, email) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin@example.com');

-- Insert default zones
INSERT INTO zones (zone_name, description) VALUES
('Zone A', 'Main warehouse area'),
('Zone B', 'Secondary storage'),
('Zone C', 'Overflow area');

-- Insert default settings
INSERT INTO settings (setting_key, setting_value) VALUES
('site_name', 'Christmas Toy Appeal'),
('smtp_host', 'localhost'),
('smtp_port', '587'),
('smtp_username', ''),
('smtp_password', ''),
('smtp_from_email', 'noreply@toyappeal.org'),
('smtp_from_name', 'Christmas Toy Appeal'),
('collection_location', 'Main Warehouse, Norfolk'),
('collection_hours', 'Monday-Friday 9am-5pm'),
('current_year', '2024'),
('enable_referrals', '1');
