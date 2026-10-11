-- ============================================================
-- UPTM SMART LIBRARY SYSTEM (UPTM-SLS)
-- Database schema + sample data for XAMPP / phpMyAdmin
-- ============================================================

CREATE DATABASE IF NOT EXISTS uptm_sls
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE uptm_sls;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS borrowings;
DROP TABLE IF EXISTS books;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(30) NOT NULL UNIQUE,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE books (
    book_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(150) NOT NULL,
    category VARCHAR(80) NOT NULL,
    isbn VARCHAR(30) NOT NULL UNIQUE,
    publication_year SMALLINT UNSIGNED NULL,
    shelf_location VARCHAR(60) NOT NULL,
    availability_status ENUM('Available', 'Borrowed') NOT NULL DEFAULT 'Available',
    description TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_books_title (title),
    INDEX idx_books_author (author),
    INDEX idx_books_category (category),
    INDEX idx_books_availability (availability_status)
) ENGINE=InnoDB;

CREATE TABLE borrowings (
    borrowing_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    book_id INT UNSIGNED NOT NULL,
    borrow_date DATE NOT NULL,
    due_date DATE NOT NULL,
    return_date DATE NULL,
    status ENUM('Borrowed', 'Returned') NOT NULL DEFAULT 'Borrowed',
    rating TINYINT UNSIGNED NULL,
    feedback TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_borrowings_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_borrowings_book
        FOREIGN KEY (book_id)
        REFERENCES books(book_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    INDEX idx_borrowings_user (user_id),
    INDEX idx_borrowings_book (book_id),
    INDEX idx_borrowings_status (status),
    INDEX idx_borrowings_due_date (due_date)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- DEMO USER
-- Student ID: STU001
-- Email: student1@uptm.edu.my
-- Password: Student@123
-- Password is stored as a secure bcrypt hash.
-- ------------------------------------------------------------

INSERT INTO users (student_id, full_name, email, password) VALUES
(
    'STU001',
    'Demo UPTM Student',
    'student1@uptm.edu.my',
    '$2y$12$tOzJ2rXPlay3ahlsrciL1eViaAKDcYh6YQ0zrOylQoaMf8VCMSQ1G'
);

-- ------------------------------------------------------------
-- SAMPLE BOOKS
-- ------------------------------------------------------------

INSERT INTO books
(title, author, category, isbn, publication_year, shelf_location, availability_status, description)
VALUES
(
    'Database System Concepts',
    'Abraham Silberschatz, Henry F. Korth, S. Sudarshan',
    'Database',
    '9780078022159',
    2019,
    'A-01-03',
    'Available',
    'A foundation text covering relational databases, SQL, database design, transactions, and data management concepts.'
),
(
    'Computer Networks',
    'Andrew S. Tanenbaum, David J. Wetherall',
    'Networking',
    '9780132126953',
    2011,
    'B-02-01',
    'Borrowed',
    'An introduction to computer networking, network protocols, architecture, and communication technologies.'
),
(
    'Human-Computer Interaction',
    'Alan Dix, Janet Finlay, Gregory Abowd, Russell Beale',
    'HCI',
    '9780130461094',
    2004,
    'C-03-02',
    'Available',
    'Covers the principles of human-computer interaction, interface design, usability, and user-centred systems.'
),
(
    'Clean Code',
    'Robert C. Martin',
    'Programming',
    '9780132350884',
    2008,
    'D-01-04',
    'Available',
    'A practical guide to writing code that is readable, maintainable, and easier to improve.'
),
(
    'Operating System Concepts',
    'Abraham Silberschatz, Peter B. Galvin, Greg Gagne',
    'Operating Systems',
    '9781119800361',
    2021,
    'E-02-05',
    'Borrowed',
    'Introduces operating-system structure, processes, memory management, storage, protection, and modern OS concepts.'
),
(
    'Artificial Intelligence: A Modern Approach',
    'Stuart Russell, Peter Norvig',
    'Artificial Intelligence',
    '9780134610993',
    2020,
    'F-01-01',
    'Available',
    'A broad introduction to artificial intelligence, intelligent agents, search, learning, reasoning, and related topics.'
),
(
    'PHP and MySQL Web Development',
    'Luke Welling, Laura Thomson',
    'Web Development',
    '9780321833891',
    2016,
    'G-03-06',
    'Available',
    'Introduces server-side web application development using PHP and MySQL.'
),
(
    'Introduction to Algorithms',
    'Thomas H. Cormen, Charles E. Leiserson, Ronald L. Rivest, Clifford Stein',
    'Algorithms',
    '9780262046305',
    2022,
    'H-02-02',
    'Available',
    'A comprehensive reference for algorithm design, analysis, and common data structures.'
),
(
    'Cybersecurity Essentials',
    'Charles J. Brooks, Christopher Grow, Philip Craig, Donald Short',
    'Cybersecurity',
    '9781119362395',
    2018,
    'I-01-07',
    'Available',
    'A practical overview of information security, threats, controls, and cybersecurity fundamentals.'
),
(
    'Software Engineering',
    'Ian Sommerville',
    'Software Engineering',
    '9780133943030',
    2015,
    'J-04-02',
    'Available',
    'Explains software processes, requirements, design, testing, project management, and software evolution.'
);

-- ------------------------------------------------------------
-- SAMPLE BORROWING HISTORY FOR STU001
-- ------------------------------------------------------------

INSERT INTO borrowings
(user_id, book_id, borrow_date, due_date, return_date, status)
VALUES
(
    1,
    2,
    DATE_SUB(CURDATE(), INTERVAL 7 DAY),
    DATE_ADD(CURDATE(), INTERVAL 7 DAY),
    NULL,
    'Borrowed'
),
(
    1,
    5,
    DATE_SUB(CURDATE(), INTERVAL 20 DAY),
    DATE_SUB(CURDATE(), INTERVAL 6 DAY),
    NULL,
    'Borrowed'
),
(
    1,
    4,
    DATE_SUB(CURDATE(), INTERVAL 40 DAY),
    DATE_SUB(CURDATE(), INTERVAL 26 DAY),
    DATE_SUB(CURDATE(), INTERVAL 28 DAY),
    'Returned'
);
