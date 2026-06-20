-- MyEduConnect Database Schema
-- Learning Management System Database

-- Create Database
CREATE DATABASE IF NOT EXISTS myeduconnect CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE myeduconnect;

-- Drop existing tables (for clean setup)
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS course_materials;
DROP TABLE IF EXISTS enrollments;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS announcements;
DROP TABLE IF EXISTS courses;
DROP TABLE IF EXISTS admins;
DROP TABLE IF EXISTS teachers;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS users;

-- Users Table (Base table for all user types)
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    about_me TEXT,
    profile_picture VARCHAR(255),
    role ENUM('student', 'teacher', 'admin') NOT NULL,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Students Table
CREATE TABLE students (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE NOT NULL,
    student_id_number VARCHAR(50) UNIQUE NOT NULL,
    date_of_birth DATE,
    grade_level VARCHAR(50),
    parent_name VARCHAR(100),
    parent_email VARCHAR(255),
    parent_phone VARCHAR(20),
    enrollment_date DATE DEFAULT (CURRENT_DATE),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_student_id_number (student_id_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Teachers Table
CREATE TABLE teachers (
    teacher_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE NOT NULL,
    teacher_id_number VARCHAR(50) UNIQUE NOT NULL,
    department VARCHAR(100),
    specialization VARCHAR(255),
    qualification VARCHAR(255),
    hire_date DATE,
    salary DECIMAL(10, 2),
    bio TEXT,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_teacher_id_number (teacher_id_number),
    INDEX idx_department (department)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admins Table
CREATE TABLE admins (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE NOT NULL,
    admin_id_number VARCHAR(50) UNIQUE NOT NULL,
    department VARCHAR(100),
    permissions TEXT,
    hire_date DATE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_admin_id_number (admin_id_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Courses Table
CREATE TABLE courses (
    course_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    instructor_id INT NOT NULL,
    category VARCHAR(100),
    thumbnail VARCHAR(255),
    price DECIMAL(10, 2) DEFAULT 0.00,
    duration_weeks INT DEFAULT 8,
    max_students INT DEFAULT 50,
    enrollment_count INT DEFAULT 0,
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (instructor_id) REFERENCES teachers(teacher_id) ON DELETE CASCADE,
    INDEX idx_instructor (instructor_id),
    INDEX idx_category (category),
    INDEX idx_status (status),
    INDEX idx_title (title)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Enrollments Table
CREATE TABLE enrollments (
    enrollment_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'completed', 'dropped') DEFAULT 'active',
    progress INT DEFAULT 0,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE,
    UNIQUE KEY unique_enrollment (student_id, course_id),
    INDEX idx_student (student_id),
    INDEX idx_course (course_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payments Table
CREATE TABLE payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT,
    amount DECIMAL(10, 2) NOT NULL,
    payment_method VARCHAR(50),
    transaction_id VARCHAR(255) UNIQUE,
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE SET NULL,
    INDEX idx_student (student_id),
    INDEX idx_course (course_id),
    INDEX idx_status (status),
    INDEX idx_transaction_id (transaction_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Announcements Table
CREATE TABLE announcements (
    announcement_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    author_id INT NOT NULL,
    author_type ENUM('admin', 'teacher') NOT NULL,
    target_audience ENUM('all', 'students', 'teachers', 'specific_course') DEFAULT 'all',
    course_id INT NULL,
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE,
    INDEX idx_author (author_id),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Course Materials Table
CREATE TABLE course_materials (
    material_id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(512) NOT NULL,
    file_type VARCHAR(50),
    file_size BIGINT,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    uploaded_by INT NOT NULL,
    material_type ENUM('note', 'assignment', 'video', 'resource', 'other') DEFAULT 'resource',
    status ENUM('active', 'inactive') DEFAULT 'active',
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES teachers(teacher_id) ON DELETE CASCADE,
    INDEX idx_course (course_id),
    INDEX idx_material_type (material_type),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Audit Logs Table
CREATE TABLE audit_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    user_type ENUM('student', 'teacher', 'admin', 'system'),
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    old_values TEXT,
    new_values TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_table (table_name),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Sample Data

-- Insert Admin User
INSERT INTO users (email, password, first_name, last_name, phone, role, status) VALUES
('admin@myeduconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', '+1234567890', 'admin', 'active');

INSERT INTO admins (user_id, admin_id_number, department, hire_date) VALUES
(1, 'ADM001', 'Administration', '2024-01-01');

-- Insert Teacher Users
INSERT INTO users (email, password, first_name, last_name, phone, role, status) VALUES
('teacher1@myeduconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John', 'Smith', '+1234567891', 'teacher', 'active'),
('teacher2@myeduconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sarah', 'Johnson', '+1234567892', 'teacher', 'active');

INSERT INTO teachers (user_id, teacher_id_number, department, specialization, qualification, hire_date, bio) VALUES
(2, 'TCH001', 'Computer Science', 'Cybersecurity', 'PhD in Computer Science', '2024-01-15', 'Expert in cybersecurity with 10 years of experience'),
(3, 'TCH002', 'Mathematics', 'Applied Mathematics', 'MSc in Mathematics', '2024-02-01', 'Mathematics specialist focusing on data science');

-- Insert Student Users
INSERT INTO users (email, password, first_name, last_name, phone, role, status) VALUES
('student1@myeduconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Alice', 'Williams', '+1234567893', 'student', 'active'),
('student2@myeduconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Bob', 'Brown', '+1234567894', 'student', 'active'),
('student3@myeduconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Charlie', 'Davis', '+1234567895', 'student', 'active');

INSERT INTO students (user_id, student_id_number, date_of_birth, grade_level, parent_name, parent_email, parent_phone) VALUES
(4, 'STU001', '2005-05-15', 'Grade 12', 'Parent Williams', 'parent1@email.com', '+1234567896'),
(5, 'STU002', '2004-08-20', 'Grade 12', 'Parent Brown', 'parent2@email.com', '+1234567897'),
(6, 'STU003', '2005-03-10', 'Grade 11', 'Parent Davis', 'parent3@email.com', '+1234567898');

-- Insert Sample Courses
INSERT INTO courses (title, description, instructor_id, category, price, duration_weeks, max_students, status) VALUES
('Introduction to Cybersecurity', 'Learn the fundamentals of cybersecurity including network security, encryption, and threat analysis.', 1, 'Cybersecurity', 199.99, 8, 50, 'published'),
('Advanced Web Development', 'Master modern web development with HTML5, CSS3, JavaScript, and PHP.', 1, 'Web Development', 149.99, 10, 40, 'published'),
('Data Science Fundamentals', 'Introduction to data science, statistics, and machine learning basics.', 2, 'Data Science', 249.99, 12, 30, 'published'),
('Network Security Essentials', 'Deep dive into network security protocols and best practices.', 1, 'Cybersecurity', 179.99, 6, 35, 'published');

-- Insert Sample Enrollments
INSERT INTO enrollments (student_id, course_id, status, progress) VALUES
(1, 1, 'active', 25),
(1, 2, 'active', 10),
(2, 1, 'active', 40),
(2, 3, 'active', 5),
(3, 2, 'active', 15);

-- Update course enrollment counts
UPDATE courses SET enrollment_count = (
    SELECT COUNT(*) FROM enrollments WHERE courses.course_id = enrollments.course_id
);

-- Insert Sample Payments
INSERT INTO payments (student_id, course_id, amount, payment_method, transaction_id, status) VALUES
(1, 1, 199.99, 'Credit Card', 'TXN001', 'completed'),
(1, 2, 149.99, 'Credit Card', 'TXN002', 'completed'),
(2, 1, 199.99, 'PayPal', 'TXN003', 'completed'),
(2, 3, 249.99, 'Credit Card', 'TXN004', 'completed'),
(3, 2, 149.99, 'PayPal', 'TXN005', 'completed');

-- Insert Sample Announcements
INSERT INTO announcements (title, content, author_id, author_type, target_audience, priority, status) VALUES
('Welcome to MyEduConnect', 'We are excited to welcome you to our new learning platform. Explore our courses and start your learning journey today!', 1, 'admin', 'all', 'high', 'published'),
('New Cybersecurity Course Available', 'Our new Introduction to Cybersecurity course is now available for enrollment. Limited spots available!', 1, 'admin', 'students', 'medium', 'published'),
('Teacher Training Session', 'All teachers are required to attend the upcoming training session on new platform features.', 1, 'admin', 'teachers', 'high', 'published');

-- Insert Sample Course Materials
INSERT INTO course_materials (course_id, title, description, file_name, file_path, file_type, file_size, uploaded_by, material_type) VALUES
(1, 'Course Syllabus', 'Complete syllabus for the cybersecurity course', 'syllabus.pdf', '/uploads/course1/syllabus.pdf', 'application/pdf', 524288, 1, 'note'),
(1, 'Week 1 Notes', 'Introduction to network security', 'week1_notes.pdf', '/uploads/course1/week1_notes.pdf', 'application/pdf', 1048576, 1, 'note'),
(1, 'Assignment 1', 'Network security analysis assignment', 'assignment1.pdf', '/uploads/course1/assignment1.pdf', 'application/pdf', 262144, 1, 'assignment'),
(2, 'HTML5 Basics', 'Introduction to HTML5', 'html5_basics.pdf', '/uploads/course2/html5_basics.pdf', 'application/pdf', 786432, 1, 'note'),
(2, 'CSS3 Tutorial', 'CSS3 styling guide', 'css3_tutorial.pdf', '/uploads/course2/css3_tutorial.pdf', 'application/pdf', 524288, 1, 'note');

-- Insert Sample Audit Logs
INSERT INTO audit_logs (user_id, user_type, action, table_name, record_id, ip_address) VALUES
(1, 'admin', 'CREATE', 'users', 1, '127.0.0.1'),
(2, 'teacher', 'CREATE', 'courses', 1, '127.0.0.1'),
(4, 'student', 'LOGIN', 'users', 4, '127.0.0.1'),
(1, 'admin', 'UPDATE', 'announcements', 1, '127.0.0.1');

-- Create Views for Statistics

-- View: User Statistics
CREATE VIEW user_statistics AS
SELECT 
    role,
    status,
    COUNT(*) as count
FROM users
GROUP BY role, status;

-- View: Course Statistics
CREATE VIEW course_statistics AS
SELECT 
    c.course_id,
    c.title,
    c.category,
    c.status,
    COUNT(e.enrollment_id) as total_enrollments,
    SUM(CASE WHEN e.status = 'active' THEN 1 ELSE 0 END) as active_enrollments,
    SUM(CASE WHEN e.status = 'completed' THEN 1 ELSE 0 END) as completed_enrollments
FROM courses c
LEFT JOIN enrollments e ON c.course_id = e.course_id
GROUP BY c.course_id, c.title, c.category, c.status;

-- View: Payment Statistics
CREATE VIEW payment_statistics AS
SELECT 
    status,
    COUNT(*) as count,
    SUM(amount) as total_amount
FROM payments
GROUP BY status;

-- Create Stored Procedures

-- Procedure: Add Audit Log
DELIMITER //
CREATE PROCEDURE AddAuditLog(
    IN p_user_id INT,
    IN p_user_type VARCHAR(20),
    IN p_action VARCHAR(100),
    IN p_table_name VARCHAR(50),
    IN p_record_id INT,
    IN p_ip_address VARCHAR(45)
)
BEGIN
    INSERT INTO audit_logs (user_id, user_type, action, table_name, record_id, ip_address)
    VALUES (p_user_id, p_user_type, p_action, p_table_name, p_record_id, p_ip_address);
END //
DELIMITER ;

-- Procedure: Update Course Enrollment Count
DELIMITER //
CREATE PROCEDURE UpdateCourseEnrollment(IN p_course_id INT)
BEGIN
    UPDATE courses 
    SET enrollment_count = (
        SELECT COUNT(*) FROM enrollments WHERE course_id = p_course_id
    )
    WHERE course_id = p_course_id;
END //
DELIMITER ;

-- Create Triggers

-- Trigger: Log User Changes
DELIMITER //
CREATE TRIGGER after_user_update
AFTER UPDATE ON users
FOR EACH ROW
BEGIN
    INSERT INTO audit_logs (user_id, user_type, action, table_name, record_id, old_values, new_values)
    VALUES (
        NEW.user_id,
        NEW.role,
        'UPDATE',
        'users',
        NEW.user_id,
        CONCAT('status:', OLD.status, ', email:', OLD.email),
        CONCAT('status:', NEW.status, ', email:', NEW.email)
    );
END //
DELIMITER ;

-- Trigger: Update Course Enrollment on Enrollment
DELIMITER //
CREATE TRIGGER after_enrollment_insert
AFTER INSERT ON enrollments
FOR EACH ROW
BEGIN
    CALL UpdateCourseEnrollment(NEW.course_id);
END //
DELIMITER ;

-- Trigger: Update Course Enrollment on Enrollment Delete
DELIMITER //
CREATE TRIGGER after_enrollment_delete
AFTER DELETE ON enrollments
FOR EACH ROW
BEGIN
    CALL UpdateCourseEnrollment(OLD.course_id);
END //
DELIMITER ;

DROP TABLE IF EXISTS security_settings;
CREATE TABLE security_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vulnerability_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NOT NULL,
    enabled TINYINT(1) NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO security_settings (vulnerability_name, description, enabled) VALUES
('sql_injection', 'Allows vulnerable SQL query concatenation and login bypass behavior.', 0),
('stored_xss', 'Allows unescaped storage/rendering of attacker-controlled HTML/JS.', 0),
('idor', 'Allows direct object access without ownership checks for non-admin users.', 0),
('weak_ssh_credentials', 'Uses predictable SSH credentials for demonstration.', 0),
('backup_file_exposure', 'Exposes database backup file from web-accessible path.', 0),
('weak_password_hashing', 'Stores passwords in plaintext instead of using bcrypt hashing.', 0),
('http_api_communication', 'Uses HTTP API URL instead of HTTPS for traffic visibility.', 0),
('exposed_database', 'Exposes MySQL database port and phpMyAdmin to the host machine without authentication.', 0);