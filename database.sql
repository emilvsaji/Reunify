

-- Create Database
CREATE DATABASE IF NOT EXISTS reunify;
USE reunify;

-- =============================================
-- Table: departments
-- =============================================
CREATE TABLE IF NOT EXISTS departments (
    department_id INT AUTO_INCREMENT PRIMARY KEY,
    department_name VARCHAR(100) NOT NULL UNIQUE,
    department_code VARCHAR(20) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Table: users
-- =============================================
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    user_role ENUM('admin', 'student', 'faculty') NOT NULL DEFAULT 'student',
    department_id INT,
    student_id VARCHAR(50),
    employee_id VARCHAR(50),
    profile_image VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE SET NULL,
    INDEX idx_user_role (user_role),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Table: categories
-- =============================================
CREATE TABLE IF NOT EXISTS categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(50) NOT NULL UNIQUE,
    category_icon VARCHAR(50),
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Table: items
-- =============================================
CREATE TABLE IF NOT EXISTS items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    item_type ENUM('lost', 'found') NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    category_id INT NOT NULL,
    location VARCHAR(200) NOT NULL,
    date_lost_found DATE NOT NULL,
    time_lost_found TIME,
    reported_by INT NOT NULL,
    contact_info VARCHAR(100),
    item_image VARCHAR(255),
    additional_images TEXT,
    status ENUM('pending', 'approved', 'matched', 'claimed', 'rejected') DEFAULT 'pending',
    is_equipment TINYINT(1) DEFAULT 0,
    department_id INT,
    matched_item_id INT,
    views_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE RESTRICT,
    FOREIGN KEY (reported_by) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE SET NULL,
    FOREIGN KEY (matched_item_id) REFERENCES items(item_id) ON DELETE SET NULL,
    INDEX idx_item_type (item_type),
    INDEX idx_status (status),
    INDEX idx_category (category_id),
    INDEX idx_date (date_lost_found)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Table: claims
-- =============================================
CREATE TABLE IF NOT EXISTS claims (
    claim_id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    claimed_by INT NOT NULL,
    claim_description TEXT NOT NULL,
    claimer_name VARCHAR(100),
    claimer_phone VARCHAR(20),
    claimer_email VARCHAR(100),
    proof_image VARCHAR(255),
    identification_proof VARCHAR(255),
    claim_status ENUM('pending', 'under_review', 'approved', 'rejected', 'completed') DEFAULT 'pending',
    reviewed_by INT,
    review_notes TEXT,
    claimed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL,
    FOREIGN KEY (item_id) REFERENCES items(item_id) ON DELETE CASCADE,
    FOREIGN KEY (claimed_by) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_claim_status (claim_status),
    INDEX idx_claimed_by (claimed_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Table: notifications
-- =============================================
CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    notification_type ENUM('claim_update', 'item_matched', 'item_approved', 'item_rejected', 'new_claim', 'system') NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    related_item_id INT,
    related_claim_id INT,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (related_item_id) REFERENCES items(item_id) ON DELETE CASCADE,
    FOREIGN KEY (related_claim_id) REFERENCES claims(claim_id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Table: activity_logs
-- =============================================
CREATE TABLE IF NOT EXISTS activity_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action_type VARCHAR(50) NOT NULL,
    action_description TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_user_action (user_id, action_type),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Table: messages
-- =============================================
CREATE TABLE IF NOT EXISTS messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    item_id INT,
    message_text TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(item_id) ON DELETE SET NULL,
    INDEX idx_conversation (sender_id, receiver_id),
    INDEX idx_unread (receiver_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Table: feedback
-- =============================================
CREATE TABLE IF NOT EXISTS feedback (
    feedback_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    category ENUM('application', 'features', 'performance', 'user_experience', 'documentation', 'other') NOT NULL DEFAULT 'other',
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    subject VARCHAR(200) NOT NULL,
    feedback_text TEXT NOT NULL,
    status ENUM('new', 'reviewed', 'in_progress', 'resolved') DEFAULT 'new',
    reviewed_by INT,
    admin_response TEXT,
    faculty_response TEXT,
    is_anonymous TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_student (student_id),
    INDEX idx_status (status),
    INDEX idx_category (category),
    INDEX idx_rating (rating),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Insert Default Data
-- =============================================

-- Insert Departments
INSERT INTO departments (department_name, department_code) VALUES
('Computer Science', 'CS'),
('Information Technology', 'IT'),
('Electronics Engineering', 'EC'),
('Mechanical Engineering', 'ME'),
('Civil Engineering', 'CE'),
('Business Administration', 'BA'),
('General', 'GEN');

-- Insert Default Admin User (Password: admin123)
INSERT INTO users (username, email, password_hash, full_name, phone, user_role, is_active) VALUES
('admin', 'admin@reunify.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', '1234567890', 'admin', 1);

-- Insert Categories
INSERT INTO categories (category_name, category_icon, description) VALUES
('Electronics', 'fa-mobile-alt', 'Mobile phones, tablets, laptops, chargers, earphones'),
('Documents', 'fa-file-alt', 'ID cards, certificates, books, notebooks'),
('Accessories', 'fa-glasses', 'Glasses, watches, jewelry, wallets'),
('Clothing', 'fa-tshirt', 'Jackets, bags, umbrellas, caps'),
('Keys', 'fa-key', 'Keys, keychains'),
('Sports Equipment', 'fa-futbol', 'Sports items, gym equipment'),
('Lab Equipment', 'fa-flask', 'Laboratory instruments, equipment'),
('Stationery', 'fa-pen', 'Pens, pencils, calculators'),
('Others', 'fa-box', 'Other miscellaneous items');

-- =============================================
-- Create Views for Analytics
-- =============================================

-- View: Daily Statistics
CREATE OR REPLACE VIEW daily_statistics AS
SELECT 
    DATE(created_at) as date,
    COUNT(CASE WHEN item_type = 'lost' THEN 1 END) as lost_items,
    COUNT(CASE WHEN item_type = 'found' THEN 1 END) as found_items,
    COUNT(CASE WHEN status = 'matched' THEN 1 END) as matched_items,
    COUNT(CASE WHEN status = 'claimed' THEN 1 END) as claimed_items
FROM items
GROUP BY DATE(created_at)
ORDER BY date DESC;

-- View: Category Statistics
CREATE OR REPLACE VIEW category_statistics AS
SELECT 
    c.category_name,
    COUNT(i.item_id) as total_items,
    COUNT(CASE WHEN i.item_type = 'lost' THEN 1 END) as lost_count,
    COUNT(CASE WHEN i.item_type = 'found' THEN 1 END) as found_count,
    COUNT(CASE WHEN i.status = 'matched' THEN 1 END) as matched_count
FROM categories c
LEFT JOIN items i ON c.category_id = i.category_id
GROUP BY c.category_id, c.category_name
ORDER BY total_items DESC;

-- View: User Statistics
CREATE OR REPLACE VIEW user_statistics AS
SELECT 
    u.user_id,
    u.full_name,
    u.user_role,
    COUNT(DISTINCT i.item_id) as items_reported,
    COUNT(DISTINCT cl.claim_id) as claims_made,
    COUNT(CASE WHEN cl.claim_status = 'approved' THEN 1 END) as approved_claims
FROM users u
LEFT JOIN items i ON u.user_id = i.reported_by
LEFT JOIN claims cl ON u.user_id = cl.claimed_by
GROUP BY u.user_id, u.full_name, u.user_role;

-- =============================================
-- Stored Procedures
-- =============================================

DELIMITER //

-- Procedure: Match Lost and Found Items
CREATE PROCEDURE match_items(IN lost_id INT, IN found_id INT)
BEGIN
    UPDATE items SET matched_item_id = found_id, status = 'matched' WHERE item_id = lost_id;
    UPDATE items SET matched_item_id = lost_id, status = 'matched' WHERE item_id = found_id;
END//

-- Procedure: Approve Claim
CREATE PROCEDURE approve_claim(IN claim_id_param INT, IN admin_id INT, IN notes TEXT)
BEGIN
    DECLARE item_id_var INT;
    
    UPDATE claims 
    SET claim_status = 'approved', 
        reviewed_by = admin_id, 
        review_notes = notes,
        reviewed_at = CURRENT_TIMESTAMP
    WHERE claim_id = claim_id_param;
    
    SELECT item_id INTO item_id_var FROM claims WHERE claim_id = claim_id_param;
    UPDATE items SET status = 'claimed' WHERE item_id = item_id_var;
END//

-- Procedure: Get Unread Notification Count
CREATE PROCEDURE get_unread_count(IN user_id_param INT)
BEGIN
    SELECT COUNT(*) as unread_count 
    FROM notifications 
    WHERE user_id = user_id_param AND is_read = 0;
END//

DELIMITER ;

-- =============================================
-- Triggers
-- =============================================

DELIMITER //

-- Trigger: Create notification when claim is made
CREATE TRIGGER after_claim_insert
AFTER INSERT ON claims
FOR EACH ROW
BEGIN
    DECLARE item_owner INT;
    DECLARE item_title VARCHAR(200);
    
    SELECT reported_by, title INTO item_owner, item_title 
    FROM items WHERE item_id = NEW.item_id;
    
    -- Notify item reporter
    INSERT INTO notifications (user_id, notification_type, title, message, related_item_id, related_claim_id)
    VALUES (item_owner, 'new_claim', 'New Claim Request', 
            CONCAT('Someone has claimed your item: ', item_title), NEW.item_id, NEW.claim_id);
    
    -- Notify claimer
    INSERT INTO notifications (user_id, notification_type, title, message, related_item_id, related_claim_id)
    VALUES (NEW.claimed_by, 'claim_update', 'Claim Submitted', 
            CONCAT('Your claim for "', item_title, '" has been submitted successfully'), NEW.item_id, NEW.claim_id);
END//

-- Trigger: Create notification when claim status changes
CREATE TRIGGER after_claim_update
AFTER UPDATE ON claims
FOR EACH ROW
BEGIN
    DECLARE item_title VARCHAR(200);
    
    IF NEW.claim_status != OLD.claim_status THEN
        SELECT title INTO item_title FROM items WHERE item_id = NEW.item_id;
        
        INSERT INTO notifications (user_id, notification_type, title, message, related_item_id, related_claim_id)
        VALUES (NEW.claimed_by, 'claim_update', 'Claim Status Updated', 
                CONCAT('Your claim for "', item_title, '" status: ', NEW.claim_status), NEW.item_id, NEW.claim_id);
    END IF;
END//

-- Trigger: Create notification when item is approved/rejected
CREATE TRIGGER after_item_status_update
AFTER UPDATE ON items
FOR EACH ROW
BEGIN
    IF NEW.status != OLD.status THEN
        IF NEW.status = 'approved' THEN
            INSERT INTO notifications (user_id, notification_type, title, message, related_item_id)
            VALUES (NEW.reported_by, 'item_approved', 'Item Approved', 
                    CONCAT('Your reported item "', NEW.title, '" has been approved'), NEW.item_id);
        ELSEIF NEW.status = 'rejected' THEN
            INSERT INTO notifications (user_id, notification_type, title, message, related_item_id)
            VALUES (NEW.reported_by, 'item_rejected', 'Item Rejected', 
                    CONCAT('Your reported item "', NEW.title, '" has been rejected'), NEW.item_id);
        END IF;
    END IF;
END//

DELIMITER ;

-- =============================================
-- Indexes for Performance Optimization
-- =============================================
CREATE INDEX idx_items_search ON items(title, description(100));
CREATE INDEX idx_notifications_unread ON notifications(user_id, is_read, created_at);
CREATE INDEX idx_claims_item ON claims(item_id, claim_status);

-- =============================================
-- Grant Permissions (Optional - Adjust as needed)
-- =============================================
-- CREATE USER 'reunify_user'@'localhost' IDENTIFIED BY 'your_password_here';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON reunify.* TO 'reunify_user'@'localhost';
-- FLUSH PRIVILEGES;

-- =============================================
-- End of Database Schema
-- =============================================
