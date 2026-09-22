-- =====================================================================
-- Startup Idea Collaboration Portal - Database Schema
-- Engine: InnoDB (for FK support), Charset: utf8mb4
-- =====================================================================

-- ---------------------------------------------------------------------
-- USERS
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('student','admin') NOT NULL DEFAULT 'student',
    status ENUM('active','banned') NOT NULL DEFAULT 'active',
    email_verified TINYINT(1) NOT NULL DEFAULT 0,
    verification_token VARCHAR(100) DEFAULT NULL,
    reset_token VARCHAR(100) DEFAULT NULL,
    reset_token_expires DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- PROFILES
-- ---------------------------------------------------------------------
CREATE TABLE profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    photo VARCHAR(255) DEFAULT 'default.png',
    college VARCHAR(150) DEFAULT NULL,
    department VARCHAR(150) DEFAULT NULL,
    skills VARCHAR(255) DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    linkedin VARCHAR(255) DEFAULT NULL,
    github VARCHAR(255) DEFAULT NULL,
    twitter VARCHAR(255) DEFAULT NULL,
    portfolio_link VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- CATEGORIES
-- ---------------------------------------------------------------------
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- STARTUP IDEAS
-- ---------------------------------------------------------------------
CREATE TABLE startup_ideas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    problem_statement TEXT NOT NULL,
    proposed_solution TEXT NOT NULL,
    description TEXT NOT NULL,
    category_id INT DEFAULT NULL,
    required_skills VARCHAR(255) DEFAULT NULL,
    current_stage ENUM('Idea','Prototype','MVP','Launched','Scaling') NOT NULL DEFAULT 'Idea',
    team_size_needed INT NOT NULL DEFAULT 1,
    image VARCHAR(255) DEFAULT NULL,
    document VARCHAR(255) DEFAULT NULL,
    status ENUM('active','removed') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FULLTEXT KEY ft_search (title, problem_statement, proposed_solution, description, required_skills)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- COMMENTS (supports nested replies via parent_id)
-- ---------------------------------------------------------------------
CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    idea_id INT NOT NULL,
    user_id INT NOT NULL,
    parent_id INT DEFAULT NULL,
    comment TEXT NOT NULL,
    status ENUM('active','removed') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (idea_id) REFERENCES startup_ideas(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- COMMENT LIKES
-- ---------------------------------------------------------------------
CREATE TABLE comment_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    comment_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_comment_like (comment_id, user_id),
    FOREIGN KEY (comment_id) REFERENCES comments(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- LIKES (on ideas)
-- ---------------------------------------------------------------------
CREATE TABLE likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    idea_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_like (idea_id, user_id),
    FOREIGN KEY (idea_id) REFERENCES startup_ideas(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- BOOKMARKS
-- ---------------------------------------------------------------------
CREATE TABLE bookmarks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    idea_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_bookmark (idea_id, user_id),
    FOREIGN KEY (idea_id) REFERENCES startup_ideas(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- TEAMS (accepted collaborators per idea)
-- ---------------------------------------------------------------------
CREATE TABLE teams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    idea_id INT NOT NULL,
    user_id INT NOT NULL,
    role_in_team VARCHAR(100) DEFAULT 'Member',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_team_member (idea_id, user_id),
    FOREIGN KEY (idea_id) REFERENCES startup_ideas(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- COLLABORATION REQUESTS
-- ---------------------------------------------------------------------
CREATE TABLE collaboration_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    idea_id INT NOT NULL,
    sender_id INT NOT NULL,       -- user requesting to join
    receiver_id INT NOT NULL,     -- idea owner
    message TEXT DEFAULT NULL,
    status ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_request (idea_id, sender_id),
    FOREIGN KEY (idea_id) REFERENCES startup_ideas(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- NOTIFICATIONS
-- ---------------------------------------------------------------------
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,          -- recipient
    actor_id INT DEFAULT NULL,     -- who triggered it
    type ENUM('collab_request','collab_accepted','collab_rejected','comment','like','new_team_member','report','system') NOT NULL,
    idea_id INT DEFAULT NULL,
    message VARCHAR(255) NOT NULL,
    link VARCHAR(255) DEFAULT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (idea_id) REFERENCES startup_ideas(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- REPORTS (for comments or ideas)
-- ---------------------------------------------------------------------
CREATE TABLE reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reporter_id INT NOT NULL,
    target_type ENUM('idea','comment') NOT NULL,
    target_id INT NOT NULL,
    reason VARCHAR(255) NOT NULL,
    status ENUM('pending','reviewed','dismissed') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- SEED DATA
-- =====================================================================

-- Default admin account. The password column below is a PLACEHOLDER and will
-- NOT work as-is. After importing this file, generate a real bcrypt hash and
-- update the row (see database/generate_admin_hash.php and the README):
--   php database/generate_admin_hash.php YourStrongPassword123
--   UPDATE users SET password = '<generated_hash>' WHERE email = 'admin@startupportal.test';
INSERT INTO users (name, email, password, role, email_verified) VALUES
('Platform Admin', 'admin@startupportal.test', 'CHANGE_ME_RUN_generate_admin_hash.php', 'admin', 1);

INSERT INTO categories (name, slug) VALUES
('Technology', 'technology'),
('Healthcare', 'healthcare'),
('Education', 'education'),
('Finance', 'finance'),
('E-commerce', 'e-commerce'),
('Sustainability', 'sustainability'),
('Food & Beverage', 'food-beverage'),
('Social Impact', 'social-impact'),
('Agriculture', 'agriculture'),
('Entertainment', 'entertainment');
