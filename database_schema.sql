CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    maintenance_area VARCHAR(255),
    priority VARCHAR(50),
    deadline DATE,
    details TEXT,
    status VARCHAR(50) NOT NULL DEFAULT 'pending',
    assigned_worker VARCHAR(100),
    completion_feedback TEXT,
    submission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    subject VARCHAR(255),
    details TEXT,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
