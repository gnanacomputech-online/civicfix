-- CivicFix — Municipal Complaint Reporting & SLA Escalation Platform
-- Import via phpMyAdmin or: mysql -u root -P 3306 < schema.sql
-- Creates database, schema, and demo seed data.
-- Date-relative seed rows use NOW()/DATE_SUB() so the SLA-breach / escalation
-- demo state always looks fresh, however long after import the demo happens.

CREATE DATABASE IF NOT EXISTS civicfix
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE civicfix;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS complaint_comments;
DROP TABLE IF EXISTS complaint_upvotes;
DROP TABLE IF EXISTS complaint_status_history;
DROP TABLE IF EXISTS complaints;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS wards;
DROP TABLE IF EXISTS departments;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- Schema
-- ============================================================

CREATE TABLE users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100) NOT NULL,
    email         VARCHAR(150) NOT NULL UNIQUE,
    password      VARCHAR(255) NOT NULL,
    role          ENUM('super_admin','officer','citizen') NOT NULL,
    phone         VARCHAR(20) NULL,
    department_id INT NULL,               -- only meaningful for role = 'officer'
    status        ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE departments (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(120) NOT NULL UNIQUE,
    description   VARCHAR(255) NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

ALTER TABLE users
    ADD CONSTRAINT fk_users_department FOREIGN KEY (department_id) REFERENCES departments(id);

CREATE TABLE categories (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(150) NOT NULL,
    department_id INT NOT NULL,
    sla_hours     INT NOT NULL,           -- SLA target for this category, in hours
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id),
    KEY idx_categories_department (department_id)
) ENGINE=InnoDB;

CREATE TABLE wards (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100) NOT NULL UNIQUE,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE complaints (
    id                      INT AUTO_INCREMENT PRIMARY KEY,
    citizen_id              INT NOT NULL,
    category_id             INT NOT NULL,
    department_id           INT NOT NULL,   -- snapshot of categories.department_id at filing time (auto-routing)
    ward_id                 INT NOT NULL,
    address_line            VARCHAR(255) NOT NULL,
    description             TEXT NOT NULL,
    photo_path              VARCHAR(255) NULL,
    status                  ENUM('new','assigned','in_progress','resolved','closed') NOT NULL DEFAULT 'new',
    assigned_officer_id     INT NULL,
    resolution_photo_path   VARCHAR(255) NULL,
    resolved_at             DATETIME NULL,
    closed_at               DATETIME NULL,
    citizen_rating          TINYINT UNSIGNED NULL,          -- 1-5, set when the citizen confirms & closes a Resolved complaint
    citizen_rating_comment  VARCHAR(500) NULL,
    reopen_count            TINYINT UNSIGNED NOT NULL DEFAULT 0, -- capped at 1 by app logic
    created_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (citizen_id) REFERENCES users(id),
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (department_id) REFERENCES departments(id),
    FOREIGN KEY (ward_id) REFERENCES wards(id),
    FOREIGN KEY (assigned_officer_id) REFERENCES users(id),
    KEY idx_complaints_department_status (department_id, status),
    KEY idx_complaints_citizen (citizen_id),
    KEY idx_complaints_ward (ward_id),
    KEY idx_complaints_status (status)
) ENGINE=InnoDB;

CREATE TABLE complaint_status_history (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id  INT NOT NULL,
    status        ENUM('new','assigned','in_progress','resolved','closed') NOT NULL,
    note          VARCHAR(500) NULL,
    changed_by    INT NULL,               -- NULL only in principle (system); every seed row has an actor
    changed_at    DATETIME NOT NULL,
    FOREIGN KEY (complaint_id) REFERENCES complaints(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id),
    KEY idx_history_complaint (complaint_id)
) ENGINE=InnoDB;

CREATE TABLE complaint_comments (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id  INT NOT NULL,
    user_id       INT NOT NULL,
    body          VARCHAR(1000) NOT NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (complaint_id) REFERENCES complaints(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id),
    KEY idx_comments_complaint (complaint_id)
) ENGINE=InnoDB;

-- One citizen can upvote a given open complaint once, as "this is happening to me too"
-- instead of filing a near-duplicate report.
CREATE TABLE complaint_upvotes (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id  INT NOT NULL,
    citizen_id    INT NOT NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (complaint_id) REFERENCES complaints(id) ON DELETE CASCADE,
    FOREIGN KEY (citizen_id) REFERENCES users(id),
    UNIQUE KEY uniq_upvote (complaint_id, citizen_id)
) ENGINE=InnoDB;

-- ============================================================
-- Seed data
-- ============================================================

INSERT INTO departments (id, name, description) VALUES
(1, 'Sanitation & Waste Management', 'Garbage collection, public dustbins, illegal dumping and open-burning complaints.'),
(2, 'Water Supply & Sewerage',       'Drinking water supply, pipeline leakage, and sewage/drain overflow.'),
(3, 'Roads & Public Infrastructure', 'Potholes, damaged footpaths, and manhole covers.'),
(4, 'Street Lighting & Electrical',  'Non-functional streetlights and exposed/dangerous wiring.'),
(5, 'Parks, Horticulture & Public Spaces', 'Park upkeep, playground equipment, and tree maintenance.');

-- Passwords: Admin@123 / Officer@123 / Citizen@123 (bcrypt via password_hash)
-- Users 1 = Super Admin, 2-7 = Department Officers, 8-25 = Citizens (25 is deliberately inactive).
INSERT INTO users (id, name, email, password, role, phone, department_id, status) VALUES
(1,  'Kavitha Rao',      'admin@civicfix.test',    '$2y$12$Ww8/fnXwSQp2vQwTnkqJR.bPQb18d2mBpG8purd0bmxx5t1L0rGBC', 'super_admin', '9820000001', NULL, 'active'),
(2,  'Ramesh Gowda',     'officer1@civicfix.test', '$2y$12$EgL6PzsmVJClHHIsARg6Cu/oI48I3qdQRw9gFGmWhUsEtWRIKuR6O', 'officer', '9820000002', 1, 'active'),
(3,  'Suman Iyer',       'officer2@civicfix.test', '$2y$12$EgL6PzsmVJClHHIsARg6Cu/oI48I3qdQRw9gFGmWhUsEtWRIKuR6O', 'officer', '9820000003', 1, 'active'),
(4,  'Farhan Khan',      'officer3@civicfix.test', '$2y$12$EgL6PzsmVJClHHIsARg6Cu/oI48I3qdQRw9gFGmWhUsEtWRIKuR6O', 'officer', '9820000004', 2, 'active'),
(5,  'Meena Pillai',     'officer4@civicfix.test', '$2y$12$EgL6PzsmVJClHHIsARg6Cu/oI48I3qdQRw9gFGmWhUsEtWRIKuR6O', 'officer', '9820000005', 3, 'active'),
(6,  'Vikram Nair',      'officer5@civicfix.test', '$2y$12$EgL6PzsmVJClHHIsARg6Cu/oI48I3qdQRw9gFGmWhUsEtWRIKuR6O', 'officer', '9820000006', 4, 'active'),
(7,  'Anjali Desai',     'officer6@civicfix.test', '$2y$12$EgL6PzsmVJClHHIsARg6Cu/oI48I3qdQRw9gFGmWhUsEtWRIKuR6O', 'officer', '9820000007', 5, 'active'),
(8,  'Arjun Mehta',      'citizen1@civicfix.test',  '$2y$12$SQy8legaYX.EyTcJTj6ylu7Zee1G5lxvnMiYHpwfcNjiO4QEAuwlS', 'citizen', '9830000001', NULL, 'active'),
(9,  'Priya Sharma',     'citizen2@civicfix.test',  '$2y$12$SQy8legaYX.EyTcJTj6ylu7Zee1G5lxvnMiYHpwfcNjiO4QEAuwlS', 'citizen', '9830000002', NULL, 'active'),
(10, 'Rahul Verma',      'citizen3@civicfix.test',  '$2y$12$SQy8legaYX.EyTcJTj6ylu7Zee1G5lxvnMiYHpwfcNjiO4QEAuwlS', 'citizen', '9830000003', NULL, 'active'),
(11, 'Sneha Kulkarni',   'citizen4@civicfix.test',  '$2y$12$SQy8legaYX.EyTcJTj6ylu7Zee1G5lxvnMiYHpwfcNjiO4QEAuwlS', 'citizen', '9830000004', NULL, 'active'),
(12, 'Vikas Reddy',      'citizen5@civicfix.test',  '$2y$12$SQy8legaYX.EyTcJTj6ylu7Zee1G5lxvnMiYHpwfcNjiO4QEAuwlS', 'citizen', '9830000005', NULL, 'active'),
(13, 'Divya Nambiar',    'citizen6@civicfix.test',  '$2y$12$SQy8legaYX.EyTcJTj6ylu7Zee1G5lxvnMiYHpwfcNjiO4QEAuwlS', 'citizen', '9830000006', NULL, 'active'),
(14, 'Karthik Subramani','citizen7@civicfix.test',  '$2y$12$SQy8legaYX.EyTcJTj6ylu7Zee1G5lxvnMiYHpwfcNjiO4QEAuwlS', 'citizen', '9830000007', NULL, 'active'),
(15, 'Neha Joshi',       'citizen8@civicfix.test',  '$2y$12$SQy8legaYX.EyTcJTj6ylu7Zee1G5lxvnMiYHpwfcNjiO4QEAuwlS', 'citizen', '9830000008', NULL, 'active'),
(16, 'Aditya Bhatt',     'citizen9@civicfix.test',  '$2y$12$SQy8legaYX.EyTcJTj6ylu7Zee1G5lxvnMiYHpwfcNjiO4QEAuwlS', 'citizen', '9830000009', NULL, 'active'),
(17, 'Pooja Menon',      'citizen10@civicfix.test', '$2y$12$SQy8legaYX.EyTcJTj6ylu7Zee1G5lxvnMiYHpwfcNjiO4QEAuwlS', 'citizen', '9830000010', NULL, 'active'),
(18, 'Sanjay Kapoor',    'citizen11@civicfix.test', '$2y$12$SQy8legaYX.EyTcJTj6ylu7Zee1G5lxvnMiYHpwfcNjiO4QEAuwlS', 'citizen', '9830000011', NULL, 'active'),
(19, 'Lakshmi Narayanan','citizen12@civicfix.test', '$2y$12$SQy8legaYX.EyTcJTj6ylu7Zee1G5lxvnMiYHpwfcNjiO4QEAuwlS', 'citizen', '9830000012', NULL, 'active'),
(20, 'Rohit Chatterjee', 'citizen13@civicfix.test', '$2y$12$SQy8legaYX.EyTcJTj6ylu7Zee1G5lxvnMiYHpwfcNjiO4QEAuwlS', 'citizen', '9830000013', NULL, 'active'),
(21, 'Anita George',     'citizen14@civicfix.test', '$2y$12$SQy8legaYX.EyTcJTj6ylu7Zee1G5lxvnMiYHpwfcNjiO4QEAuwlS', 'citizen', '9830000014', NULL, 'active'),
(22, 'Manoj Tiwari',     'citizen15@civicfix.test', '$2y$12$SQy8legaYX.EyTcJTj6ylu7Zee1G5lxvnMiYHpwfcNjiO4QEAuwlS', 'citizen', '9830000015', NULL, 'active'),
(23, 'Ritu Bansal',      'citizen16@civicfix.test', '$2y$12$SQy8legaYX.EyTcJTj6ylu7Zee1G5lxvnMiYHpwfcNjiO4QEAuwlS', 'citizen', '9830000016', NULL, 'active'),
(24, 'Deepak Suresh',    'citizen17@civicfix.test', '$2y$12$SQy8legaYX.EyTcJTj6ylu7Zee1G5lxvnMiYHpwfcNjiO4QEAuwlS', 'citizen', '9830000017', NULL, 'active'),
(25, 'Farida Sheikh',    'citizen18@civicfix.test', '$2y$12$SQy8legaYX.EyTcJTj6ylu7Zee1G5lxvnMiYHpwfcNjiO4QEAuwlS', 'citizen', '9830000018', NULL, 'inactive');

INSERT INTO categories (id, name, department_id, sla_hours) VALUES
(1,  'Garbage not collected',              1, 24),
(2,  'Overflowing public dustbin',         1, 24),
(3,  'Illegal dumping / open burning',     1, 48),
(4,  'No water supply',                    2, 24),
(5,  'Water pipeline leakage',             2, 24),
(6,  'Sewage overflow / blocked drain',    2, 24),
(7,  'Pothole on road',                    3, 72),
(8,  'Damaged footpath / pavement',        3, 96),
(9,  'Broken manhole cover',               3, 24),
(10, 'Streetlight not working',            4, 72),
(11, 'Exposed / dangling electrical wire', 4, 12),
(12, 'Park maintenance / broken equipment',5, 120),
(13, 'Fallen tree / needs trimming',       5, 48);

INSERT INTO wards (id, name) VALUES
(1,  'Ward 1 - Gandhi Nagar'),
(2,  'Ward 2 - Nehru Colony'),
(3,  'Ward 3 - Ashok Vihar'),
(4,  'Ward 4 - Model Town'),
(5,  'Ward 5 - Shastri Nagar'),
(6,  'Ward 6 - Lakeview'),
(7,  'Ward 7 - Rajaji Puram'),
(8,  'Ward 8 - Sector 12'),
(9,  'Ward 9 - Old Town'),
(10, 'Ward 10 - Green Park'),
(11, 'Ward 11 - Vivekananda Colony'),
(12, 'Ward 12 - Subhash Nagar'),
(13, 'Ward 13 - Industrial Area'),
(14, 'Ward 14 - Riverside'),
(15, 'Ward 15 - Civil Lines');

-- Complaints: 25 rows spanning every status. created_at is deliberately backdated with
-- DATE_SUB(NOW(), INTERVAL ... HOUR) on several rows past their category's sla_hours so
-- the SLA-breach flag and Super Admin escalation queue are populated on first login, not
-- just theoretically implemented. Breached-and-open rows (checked against sla_hours above):
-- #1 (30h > 24h), #2 (20h > 12h), #6 (26h > 24h), #8 (30h > 24h), #10 (60h > 48h),
-- #20 (26h > 24h), #23 (100h > 96h), #25 (50h > 24h) -- 8 escalated complaints.
INSERT INTO complaints
    (id, citizen_id, category_id, department_id, ward_id, address_line, description, photo_path,
     status, assigned_officer_id, resolution_photo_path, resolved_at, closed_at, created_at) VALUES
(1,  8,  1,  1, 1,  'Near Gandhi Nagar bus stop, opposite Ward office',
     'Garbage has not been collected from our street for over a week. Bins are overflowing onto the road.',
     'complaint_photo_1.jpg', 'new', NULL, NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 30 HOUR)),
(2,  9,  11, 4, 6,  'Lakeview Main Road, near the water tank',
     'A live electrical wire is hanging low over the footpath, sparking when it rains. Extremely dangerous for pedestrians.',
     'complaint_photo_2.jpg', 'new', NULL, NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 20 HOUR)),
(3,  10, 7,  3, 3,  'Ashok Vihar 2nd Cross, near the school gate',
     'Large pothole has formed after the recent rains, several two-wheelers have skidded here already.',
     'complaint_photo_3.jpg', 'new', NULL, NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 5 HOUR)),
(4,  11, 4,  2, 9,  'Old Town Market Road, behind the post office',
     'No water supply in our block for the past two days. Neighbouring blocks are getting water as usual.',
     'complaint_photo_4.jpg', 'new', NULL, NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(5,  12, 10, 4, 7,  'Rajaji Puram Park Road, third streetlight from the junction',
     'Streetlight has been off for a week, making the road unsafe to walk on at night.',
     'complaint_photo_5.jpg', 'new', NULL, NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 40 HOUR)),
(6,  13, 2,  1, 2,  'Nehru Colony 4th Street corner',
     'The public dustbin at the street corner has been overflowing for days, attracting stray animals.',
     'complaint_photo_6.jpg', 'assigned', 2, NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 26 HOUR)),
(7,  14, 9,  3, 13, 'Industrial Area Phase 2, near gate 4',
     'Manhole cover is broken and partially open, a serious fall hazard for workers walking through at night.',
     'complaint_photo_7.jpg', 'assigned', 5, NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 10 HOUR)),
(8,  15, 5,  2, 4,  'Model Town Sector A, near the community hall',
     'Underground pipeline is leaking heavily, water is pooling on the road and wasting a lot of water.',
     'complaint_photo_8.jpg', 'in_progress', 4, NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 30 HOUR)),
(9,  16, 13, 5, 10, 'Green Park, near the children''s play area',
     'A large tree branch has fallen and is blocking half the walking path inside the park.',
     'complaint_photo_1.jpg', 'in_progress', 7, NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 20 HOUR)),
(10, 17, 3,  1, 5,  'Shastri Nagar vacant plot, behind the temple',
     'People are dumping construction debris and burning garbage on the empty plot, causing smoke and bad smell.',
     'complaint_photo_2.jpg', 'in_progress', 3, NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 60 HOUR)),
(11, 8,  6,  2, 11, 'Vivekananda Colony Lane 3',
     'Sewage is overflowing from a blocked drain and spreading onto the lane, very unhygienic.',
     'complaint_photo_3.jpg', 'resolved', 4, 'resolution_photo_1.jpg', DATE_SUB(NOW(), INTERVAL 60 HOUR), NULL, DATE_SUB(NOW(), INTERVAL 90 HOUR)),
(12, 9,  1,  1, 1,  'Gandhi Nagar 6th Cross',
     'Garbage truck has skipped our street for the second week in a row.',
     'complaint_photo_4.jpg', 'resolved', 2, 'resolution_photo_2.jpg', DATE_SUB(NOW(), INTERVAL 95 HOUR), NULL, DATE_SUB(NOW(), INTERVAL 100 HOUR)),
(13, 10, 7,  3, 14, 'Riverside Road, near the bridge',
     'Deep pothole right at the bridge approach, causing traffic to swerve dangerously.',
     'complaint_photo_5.jpg', 'resolved', 5, 'resolution_photo_3.jpg', DATE_SUB(NOW(), INTERVAL 150 HOUR), NULL, DATE_SUB(NOW(), INTERVAL 200 HOUR)),
(14, 11, 12, 5, 15, 'Civil Lines Community Park',
     'The children''s swing set is broken and has sharp edges exposed, unsafe for kids.',
     'complaint_photo_6.jpg', 'resolved', 7, 'resolution_photo_4.jpg', DATE_SUB(NOW(), INTERVAL 20 HOUR), NULL, DATE_SUB(NOW(), INTERVAL 130 HOUR)),
(15, 12, 10, 4, 8,  'Sector 12 Main Avenue',
     'Three consecutive streetlights are out, the whole stretch is pitch dark at night.',
     'complaint_photo_7.jpg', 'closed', 6, 'resolution_photo_5.jpg', DATE_SUB(NOW(), INTERVAL 290 HOUR), DATE_SUB(NOW(), INTERVAL 200 HOUR), DATE_SUB(NOW(), INTERVAL 300 HOUR)),
(16, 13, 2,  1, 12, 'Subhash Nagar Market Street',
     'Dustbin overflowing near the vegetable market, smell is unbearable during the day.',
     'complaint_photo_8.jpg', 'closed', 3, 'resolution_photo_1.jpg', DATE_SUB(NOW(), INTERVAL 240 HOUR), DATE_SUB(NOW(), INTERVAL 100 HOUR), DATE_SUB(NOW(), INTERVAL 250 HOUR)),
(17, 14, 4,  2, 2,  'Nehru Colony Water Tank Road',
     'No water supply for the whole street for three days straight.',
     'complaint_photo_1.jpg', 'closed', 4, 'resolution_photo_2.jpg', DATE_SUB(NOW(), INTERVAL 390 HOUR), DATE_SUB(NOW(), INTERVAL 380 HOUR), DATE_SUB(NOW(), INTERVAL 400 HOUR)),
(18, 15, 11, 4, 6,  'Lakeview Cross Street 2',
     'Loose wire from the electric pole is dangling close to the ground near the bus stop.',
     'complaint_photo_2.jpg', 'resolved', 6, 'resolution_photo_3.jpg', DATE_SUB(NOW(), INTERVAL 47 HOUR), NULL, DATE_SUB(NOW(), INTERVAL 50 HOUR)),
(19, 16, 3,  1, 3,  'Ashok Vihar open plot near the water tank',
     'Someone is regularly dumping household waste on the vacant plot next to the park.',
     'complaint_photo_3.jpg', 'new', NULL, NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(20, 17, 9,  3, 9,  'Old Town main drain crossing',
     'Manhole cover near the main road crossing is missing entirely, a serious hazard at night.',
     'complaint_photo_4.jpg', 'new', NULL, NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 26 HOUR)),
(21, 8,  13, 5, 10, 'Green Park north entrance',
     'A large branch is hanging precariously over the walking track after last night''s storm.',
     'complaint_photo_5.jpg', 'assigned', 7, NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 5 HOUR)),
(22, 9,  5,  2, 4,  'Model Town Sector B',
     'Small pipeline leak has started near the park entrance, getting worse through the day.',
     'complaint_photo_6.jpg', 'new', NULL, NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 3 HOUR)),
(23, 10, 8,  3, 13, 'Industrial Area Phase 1 footpath',
     'Footpath slabs are broken and uneven for almost 200 metres, hard to walk on especially at night.',
     'complaint_photo_7.jpg', 'in_progress', 5, NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 100 HOUR)),
(24, 11, 2,  1, 5,  'Shastri Nagar bus stand',
     'Dustbin near the bus stand has been overflowing since yesterday evening.',
     'complaint_photo_8.jpg', 'assigned', 2, NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 8 HOUR)),
(25, 12, 6,  2, 11, 'Vivekananda Colony Lane 7',
     'Drain is completely blocked and sewage water has been standing on the road for two days.',
     'complaint_photo_1.jpg', 'new', NULL, NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 50 HOUR));

-- Status history: one row per stage each complaint has actually passed through, timestamped
-- consistently with the complaint's own created_at / resolved_at / closed_at above.
INSERT INTO complaint_status_history (complaint_id, status, note, changed_by, changed_at) VALUES
-- #1 new only
(1,  'new', 'Complaint filed and auto-routed to department.', 8,  DATE_SUB(NOW(), INTERVAL 30 HOUR)),
-- #2 new only
(2,  'new', 'Complaint filed and auto-routed to department.', 9,  DATE_SUB(NOW(), INTERVAL 20 HOUR)),
-- #3 new only
(3,  'new', 'Complaint filed and auto-routed to department.', 10, DATE_SUB(NOW(), INTERVAL 5 HOUR)),
-- #4 new only
(4,  'new', 'Complaint filed and auto-routed to department.', 11, DATE_SUB(NOW(), INTERVAL 2 HOUR)),
-- #5 new only
(5,  'new', 'Complaint filed and auto-routed to department.', 12, DATE_SUB(NOW(), INTERVAL 40 HOUR)),
-- #6 new -> assigned
(6,  'new', 'Complaint filed and auto-routed to department.', 13, DATE_SUB(NOW(), INTERVAL 26 HOUR)),
(6,  'assigned', 'Claimed by officer.', 2, DATE_SUB(NOW(), INTERVAL 25 HOUR)),
-- #7 new -> assigned
(7,  'new', 'Complaint filed and auto-routed to department.', 14, DATE_SUB(NOW(), INTERVAL 10 HOUR)),
(7,  'assigned', 'Claimed by officer.', 5, DATE_SUB(NOW(), INTERVAL 9 HOUR)),
-- #8 new -> assigned -> in_progress
(8,  'new', 'Complaint filed and auto-routed to department.', 15, DATE_SUB(NOW(), INTERVAL 30 HOUR)),
(8,  'assigned', 'Claimed by officer.', 4, DATE_SUB(NOW(), INTERVAL 29 HOUR)),
(8,  'in_progress', 'Site visited, arranging pipeline repair crew.', 4, DATE_SUB(NOW(), INTERVAL 28 HOUR)),
-- #9 new -> assigned -> in_progress
(9,  'new', 'Complaint filed and auto-routed to department.', 16, DATE_SUB(NOW(), INTERVAL 20 HOUR)),
(9,  'assigned', 'Claimed by officer.', 7, DATE_SUB(NOW(), INTERVAL 19 HOUR)),
(9,  'in_progress', 'Team dispatched to clear the branch.', 7, DATE_SUB(NOW(), INTERVAL 18 HOUR)),
-- #10 new -> assigned -> in_progress
(10, 'new', 'Complaint filed and auto-routed to department.', 17, DATE_SUB(NOW(), INTERVAL 60 HOUR)),
(10, 'assigned', 'Claimed by officer.', 3, DATE_SUB(NOW(), INTERVAL 58 HOUR)),
(10, 'in_progress', 'Notice issued to plot owner, follow-up inspection scheduled.', 3, DATE_SUB(NOW(), INTERVAL 55 HOUR)),
-- #11 new -> assigned -> in_progress -> resolved
(11, 'new', 'Complaint filed and auto-routed to department.', 8,  DATE_SUB(NOW(), INTERVAL 90 HOUR)),
(11, 'assigned', 'Claimed by officer.', 4, DATE_SUB(NOW(), INTERVAL 88 HOUR)),
(11, 'in_progress', 'Drain-clearing crew dispatched.', 4, DATE_SUB(NOW(), INTERVAL 85 HOUR)),
(11, 'resolved', 'Blockage cleared and drain flushed. Proof photo attached.', 4, DATE_SUB(NOW(), INTERVAL 60 HOUR)),
-- #12 full cycle -> resolved
(12, 'new', 'Complaint filed and auto-routed to department.', 9,  DATE_SUB(NOW(), INTERVAL 100 HOUR)),
(12, 'assigned', 'Claimed by officer.', 2, DATE_SUB(NOW(), INTERVAL 98 HOUR)),
(12, 'in_progress', 'Collection truck rerouted to cover the street.', 2, DATE_SUB(NOW(), INTERVAL 97 HOUR)),
(12, 'resolved', 'Backlog cleared, street added back to daily route.', 2, DATE_SUB(NOW(), INTERVAL 95 HOUR)),
-- #13 full cycle -> resolved
(13, 'new', 'Complaint filed and auto-routed to department.', 10, DATE_SUB(NOW(), INTERVAL 200 HOUR)),
(13, 'assigned', 'Claimed by officer.', 5, DATE_SUB(NOW(), INTERVAL 195 HOUR)),
(13, 'in_progress', 'Material arranged for pothole filling.', 5, DATE_SUB(NOW(), INTERVAL 190 HOUR)),
(13, 'resolved', 'Pothole filled and compacted.', 5, DATE_SUB(NOW(), INTERVAL 150 HOUR)),
-- #14 full cycle -> resolved
(14, 'new', 'Complaint filed and auto-routed to department.', 11, DATE_SUB(NOW(), INTERVAL 130 HOUR)),
(14, 'assigned', 'Claimed by officer.', 7, DATE_SUB(NOW(), INTERVAL 125 HOUR)),
(14, 'in_progress', 'Replacement swing set ordered.', 7, DATE_SUB(NOW(), INTERVAL 100 HOUR)),
(14, 'resolved', 'Swing set replaced and edges checked for safety.', 7, DATE_SUB(NOW(), INTERVAL 20 HOUR)),
-- #15 full cycle -> resolved -> closed
(15, 'new', 'Complaint filed and auto-routed to department.', 12, DATE_SUB(NOW(), INTERVAL 300 HOUR)),
(15, 'assigned', 'Claimed by officer.', 6, DATE_SUB(NOW(), INTERVAL 295 HOUR)),
(15, 'in_progress', 'Faulty ballasts identified, replacements ordered.', 6, DATE_SUB(NOW(), INTERVAL 293 HOUR)),
(15, 'resolved', 'All three streetlights repaired and tested at night.', 6, DATE_SUB(NOW(), INTERVAL 290 HOUR)),
(15, 'closed', 'Citizen follow-up confirmed lights working. Closing.', 1, DATE_SUB(NOW(), INTERVAL 200 HOUR)),
-- #16 full cycle -> resolved -> closed
(16, 'new', 'Complaint filed and auto-routed to department.', 13, DATE_SUB(NOW(), INTERVAL 250 HOUR)),
(16, 'assigned', 'Claimed by officer.', 3, DATE_SUB(NOW(), INTERVAL 248 HOUR)),
(16, 'in_progress', 'Extra pickup scheduled for the market street.', 3, DATE_SUB(NOW(), INTERVAL 245 HOUR)),
(16, 'resolved', 'Dustbin emptied and area cleaned.', 3, DATE_SUB(NOW(), INTERVAL 240 HOUR)),
(16, 'closed', 'No repeat complaints since resolution. Closing.', 1, DATE_SUB(NOW(), INTERVAL 100 HOUR)),
-- #17 full cycle -> resolved -> closed
(17, 'new', 'Complaint filed and auto-routed to department.', 14, DATE_SUB(NOW(), INTERVAL 400 HOUR)),
(17, 'assigned', 'Claimed by officer.', 4, DATE_SUB(NOW(), INTERVAL 398 HOUR)),
(17, 'in_progress', 'Valve fault identified at the distribution point.', 4, DATE_SUB(NOW(), INTERVAL 395 HOUR)),
(17, 'resolved', 'Valve repaired, supply restored to the full street.', 4, DATE_SUB(NOW(), INTERVAL 390 HOUR)),
(17, 'closed', 'Confirmed stable supply for a week. Closing.', 1, DATE_SUB(NOW(), INTERVAL 380 HOUR)),
-- #18 new -> assigned -> in_progress -> resolved (slow resolution, past its 12h SLA target)
(18, 'new', 'Complaint filed and auto-routed to department.', 15, DATE_SUB(NOW(), INTERVAL 50 HOUR)),
(18, 'assigned', 'Claimed by officer.', 6, DATE_SUB(NOW(), INTERVAL 49 HOUR)),
(18, 'in_progress', 'Power shutdown coordinated with the electricity board before repair.', 6, DATE_SUB(NOW(), INTERVAL 48 HOUR)),
(18, 'resolved', 'Wire re-secured and insulated. Took longer than target due to required power shutdown coordination.', 6, DATE_SUB(NOW(), INTERVAL 47 HOUR)),
-- #19 new only
(19, 'new', 'Complaint filed and auto-routed to department.', 16, DATE_SUB(NOW(), INTERVAL 1 HOUR)),
-- #20 new only
(20, 'new', 'Complaint filed and auto-routed to department.', 17, DATE_SUB(NOW(), INTERVAL 26 HOUR)),
-- #21 new -> assigned
(21, 'new', 'Complaint filed and auto-routed to department.', 8,  DATE_SUB(NOW(), INTERVAL 5 HOUR)),
(21, 'assigned', 'Claimed by officer.', 7, DATE_SUB(NOW(), INTERVAL 4 HOUR)),
-- #22 new only
(22, 'new', 'Complaint filed and auto-routed to department.', 9,  DATE_SUB(NOW(), INTERVAL 3 HOUR)),
-- #23 new -> assigned -> in_progress
(23, 'new', 'Complaint filed and auto-routed to department.', 10, DATE_SUB(NOW(), INTERVAL 100 HOUR)),
(23, 'assigned', 'Claimed by officer.', 5, DATE_SUB(NOW(), INTERVAL 98 HOUR)),
(23, 'in_progress', 'Measuring the affected stretch for slab replacement.', 5, DATE_SUB(NOW(), INTERVAL 96 HOUR)),
-- #24 new -> assigned
(24, 'new', 'Complaint filed and auto-routed to department.', 11, DATE_SUB(NOW(), INTERVAL 8 HOUR)),
(24, 'assigned', 'Claimed by officer.', 2, DATE_SUB(NOW(), INTERVAL 7 HOUR)),
-- #25 new only
(25, 'new', 'Complaint filed and auto-routed to department.', 12, DATE_SUB(NOW(), INTERVAL 50 HOUR));

-- Citizen ratings on two already-closed complaints (the citizen-confirms-and-closes path).
-- #17 is deliberately left unrated -- its existing history above already narrates the
-- Super Admin closing it, which is now literally the "force close, no citizen response" path.
UPDATE complaints SET citizen_rating = 5, citizen_rating_comment = 'Lights are working great now, thank you!' WHERE id = 15;
UPDATE complaints SET citizen_rating = 4, citizen_rating_comment = 'Cleaned up nicely, took a little while to get to it though.' WHERE id = 16;

-- A short back-and-forth on two still-open complaints.
INSERT INTO complaint_comments (complaint_id, user_id, body, created_at) VALUES
(6, 2,  'Visited the site this morning, arranging an extra pickup for this corner.', DATE_SUB(NOW(), INTERVAL 20 HOUR)),
(6, 13, 'Thank you, please also check the bin lid -- it''s broken too.', DATE_SUB(NOW(), INTERVAL 18 HOUR)),
(8, 4,  'Repair crew has been requested, should be on site within 24 hours.', DATE_SUB(NOW(), INTERVAL 25 HOUR)),
(8, 15, 'Appreciate the update, the water is still pooling badly here.', DATE_SUB(NOW(), INTERVAL 24 HOUR));

-- Two other citizens confirming complaint #6 is affecting them too, instead of filing
-- their own near-duplicate report -- demonstrates the upvote-instead-of-duplicate flow.
INSERT INTO complaint_upvotes (complaint_id, citizen_id, created_at) VALUES
(6, 8,  DATE_SUB(NOW(), INTERVAL 15 HOUR)),
(6, 9,  DATE_SUB(NOW(), INTERVAL 12 HOUR));
