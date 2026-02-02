-- AAC Assist Database Schema
-- Augmentative and Alternative Communication Application

-- Create database
CREATE DATABASE IF NOT EXISTS aac_assist
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE aac_assist;

-- =============================================================================
-- USERS TABLE
-- Stores user accounts and their personalized settings
-- =============================================================================
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    settings JSON DEFAULT NULL COMMENT 'User preferences: voice, speed, grid size, theme',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- CATEGORIES TABLE
-- Organizes phrases into logical groups (e.g., Greetings, Needs, Feelings)
-- =============================================================================
CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    color_code VARCHAR(7) NOT NULL DEFAULT '#4A90D9' COMMENT 'Hex color for category styling',
    icon_url VARCHAR(255) DEFAULT NULL COMMENT 'Path or URL to category icon',
    sort_order INT UNSIGNED DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sort (sort_order),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- PHRASES TABLE
-- Individual communication phrases/words with speech output
-- =============================================================================
CREATE TABLE IF NOT EXISTS phrases (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    text_label VARCHAR(100) NOT NULL COMMENT 'Display text on button',
    speech_output VARCHAR(500) NOT NULL COMMENT 'Text that is actually spoken',
    icon_url VARCHAR(255) DEFAULT NULL COMMENT 'Path or URL to phrase icon',
    sort_order INT UNSIGNED DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    INDEX idx_category (category_id),
    INDEX idx_sort (sort_order),
    INDEX idx_label (text_label),
    FULLTEXT idx_search (text_label, speech_output)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- QUICK ACCESS TABLE
-- Join table for user's frequently used/favorite phrases
-- =============================================================================
CREATE TABLE IF NOT EXISTS quick_access (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    phrase_id INT UNSIGNED NOT NULL,
    use_count INT UNSIGNED DEFAULT 1 COMMENT 'Track frequency of use',
    last_used TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (phrase_id) REFERENCES phrases(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_phrase (user_id, phrase_id),
    INDEX idx_user (user_id),
    INDEX idx_frequency (user_id, use_count DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- SEED DATA - Categories
-- =============================================================================
INSERT INTO categories (name, color_code, icon_url, sort_order) VALUES
    ('Greetings', '#4CAF50', 'icons/wave.svg', 1),
    ('Needs', '#F44336', 'icons/hand.svg', 2),
    ('Feelings', '#9C27B0', 'icons/heart.svg', 3),
    ('Questions', '#2196F3', 'icons/question.svg', 4),
    ('Responses', '#FF9800', 'icons/chat.svg', 5),
    ('People', '#00BCD4', 'icons/people.svg', 6),
    ('Places', '#795548', 'icons/location.svg', 7),
    ('Actions', '#607D8B', 'icons/run.svg', 8),
    ('Time', '#E91E63', 'icons/clock.svg', 9),
    ('Food & Drink', '#8BC34A', 'icons/food.svg', 10);

-- =============================================================================
-- SEED DATA - Phrases
-- =============================================================================

-- Greetings (category_id = 1)
INSERT INTO phrases (category_id, text_label, speech_output, icon_url, sort_order) VALUES
    (1, 'Hello', 'Hello', 'icons/phrases/hello.svg', 1),
    (1, 'Hi', 'Hi there', 'icons/phrases/hi.svg', 2),
    (1, 'Good morning', 'Good morning', 'icons/phrases/morning.svg', 3),
    (1, 'Good afternoon', 'Good afternoon', 'icons/phrases/afternoon.svg', 4),
    (1, 'Good night', 'Good night', 'icons/phrases/night.svg', 5),
    (1, 'Goodbye', 'Goodbye', 'icons/phrases/bye.svg', 6),
    (1, 'See you later', 'See you later', 'icons/phrases/later.svg', 7),
    (1, 'How are you?', 'How are you?', 'icons/phrases/howareyou.svg', 8),
    (1, 'Nice to meet you', 'Nice to meet you', 'icons/phrases/meet.svg', 9),
    (1, 'Thank you', 'Thank you', 'icons/phrases/thanks.svg', 10);

-- Needs (category_id = 2)
INSERT INTO phrases (category_id, text_label, speech_output, icon_url, sort_order) VALUES
    (2, 'I need', 'I need', 'icons/phrases/need.svg', 1),
    (2, 'I want', 'I want', 'icons/phrases/want.svg', 2),
    (2, 'Help', 'I need help', 'icons/phrases/help.svg', 3),
    (2, 'Water', 'I would like some water', 'icons/phrases/water.svg', 4),
    (2, 'Food', 'I am hungry', 'icons/phrases/hungry.svg', 5),
    (2, 'Bathroom', 'I need to use the bathroom', 'icons/phrases/bathroom.svg', 6),
    (2, 'Rest', 'I need to rest', 'icons/phrases/rest.svg', 7),
    (2, 'Medicine', 'I need my medicine', 'icons/phrases/medicine.svg', 8),
    (2, 'Stop', 'Please stop', 'icons/phrases/stop.svg', 9),
    (2, 'More', 'I want more', 'icons/phrases/more.svg', 10);

-- Feelings (category_id = 3)
INSERT INTO phrases (category_id, text_label, speech_output, icon_url, sort_order) VALUES
    (3, 'Happy', 'I am happy', 'icons/phrases/happy.svg', 1),
    (3, 'Sad', 'I am feeling sad', 'icons/phrases/sad.svg', 2),
    (3, 'Tired', 'I am tired', 'icons/phrases/tired.svg', 3),
    (3, 'Angry', 'I am feeling angry', 'icons/phrases/angry.svg', 4),
    (3, 'Scared', 'I am scared', 'icons/phrases/scared.svg', 5),
    (3, 'Excited', 'I am excited', 'icons/phrases/excited.svg', 6),
    (3, 'Pain', 'I am in pain', 'icons/phrases/pain.svg', 7),
    (3, 'Sick', 'I feel sick', 'icons/phrases/sick.svg', 8),
    (3, 'Cold', 'I am cold', 'icons/phrases/cold.svg', 9),
    (3, 'Hot', 'I am hot', 'icons/phrases/hot.svg', 10);

-- Questions (category_id = 4)
INSERT INTO phrases (category_id, text_label, speech_output, icon_url, sort_order) VALUES
    (4, 'What?', 'What?', 'icons/phrases/what.svg', 1),
    (4, 'Where?', 'Where?', 'icons/phrases/where.svg', 2),
    (4, 'When?', 'When?', 'icons/phrases/when.svg', 3),
    (4, 'Who?', 'Who?', 'icons/phrases/who.svg', 4),
    (4, 'Why?', 'Why?', 'icons/phrases/why.svg', 5),
    (4, 'How?', 'How?', 'icons/phrases/how.svg', 6),
    (4, 'Can I?', 'Can I?', 'icons/phrases/cani.svg', 7),
    (4, 'What time?', 'What time is it?', 'icons/phrases/whattime.svg', 8),
    (4, 'How much?', 'How much does it cost?', 'icons/phrases/howmuch.svg', 9),
    (4, 'What is this?', 'What is this?', 'icons/phrases/whatisthis.svg', 10);

-- Responses (category_id = 5)
INSERT INTO phrases (category_id, text_label, speech_output, icon_url, sort_order) VALUES
    (5, 'Yes', 'Yes', 'icons/phrases/yes.svg', 1),
    (5, 'No', 'No', 'icons/phrases/no.svg', 2),
    (5, 'Maybe', 'Maybe', 'icons/phrases/maybe.svg', 3),
    (5, 'Please', 'Please', 'icons/phrases/please.svg', 4),
    (5, 'Sorry', 'I am sorry', 'icons/phrases/sorry.svg', 5),
    (5, 'Okay', 'Okay', 'icons/phrases/okay.svg', 6),
    (5, 'I don''t know', 'I don''t know', 'icons/phrases/dontknow.svg', 7),
    (5, 'I understand', 'I understand', 'icons/phrases/understand.svg', 8),
    (5, 'Repeat please', 'Can you repeat that please?', 'icons/phrases/repeat.svg', 9),
    (5, 'Wait', 'Please wait', 'icons/phrases/wait.svg', 10);

-- People (category_id = 6)
INSERT INTO phrases (category_id, text_label, speech_output, icon_url, sort_order) VALUES
    (6, 'I', 'I', 'icons/phrases/i.svg', 1),
    (6, 'You', 'You', 'icons/phrases/you.svg', 2),
    (6, 'Mom', 'Mom', 'icons/phrases/mom.svg', 3),
    (6, 'Dad', 'Dad', 'icons/phrases/dad.svg', 4),
    (6, 'Family', 'My family', 'icons/phrases/family.svg', 5),
    (6, 'Friend', 'My friend', 'icons/phrases/friend.svg', 6),
    (6, 'Doctor', 'The doctor', 'icons/phrases/doctor.svg', 7),
    (6, 'Teacher', 'My teacher', 'icons/phrases/teacher.svg', 8),
    (6, 'Everyone', 'Everyone', 'icons/phrases/everyone.svg', 9),
    (6, 'Someone', 'Someone', 'icons/phrases/someone.svg', 10);

-- Places (category_id = 7)
INSERT INTO phrases (category_id, text_label, speech_output, icon_url, sort_order) VALUES
    (7, 'Home', 'Home', 'icons/phrases/home.svg', 1),
    (7, 'School', 'School', 'icons/phrases/school.svg', 2),
    (7, 'Work', 'Work', 'icons/phrases/work.svg', 3),
    (7, 'Hospital', 'The hospital', 'icons/phrases/hospital.svg', 4),
    (7, 'Store', 'The store', 'icons/phrases/store.svg', 5),
    (7, 'Outside', 'Outside', 'icons/phrases/outside.svg', 6),
    (7, 'Here', 'Here', 'icons/phrases/here.svg', 7),
    (7, 'There', 'Over there', 'icons/phrases/there.svg', 8),
    (7, 'Bathroom', 'The bathroom', 'icons/phrases/bathroom.svg', 9),
    (7, 'Bedroom', 'My bedroom', 'icons/phrases/bedroom.svg', 10);

-- Actions (category_id = 8)
INSERT INTO phrases (category_id, text_label, speech_output, icon_url, sort_order) VALUES
    (8, 'Go', 'Go', 'icons/phrases/go.svg', 1),
    (8, 'Come', 'Come', 'icons/phrases/come.svg', 2),
    (8, 'Eat', 'Eat', 'icons/phrases/eat.svg', 3),
    (8, 'Drink', 'Drink', 'icons/phrases/drink.svg', 4),
    (8, 'Sleep', 'Sleep', 'icons/phrases/sleep.svg', 5),
    (8, 'Play', 'Play', 'icons/phrases/play.svg', 6),
    (8, 'Read', 'Read', 'icons/phrases/read.svg', 7),
    (8, 'Watch', 'Watch', 'icons/phrases/watch.svg', 8),
    (8, 'Listen', 'Listen', 'icons/phrases/listen.svg', 9),
    (8, 'Call', 'Call', 'icons/phrases/call.svg', 10);

-- Time (category_id = 9)
INSERT INTO phrases (category_id, text_label, speech_output, icon_url, sort_order) VALUES
    (9, 'Now', 'Now', 'icons/phrases/now.svg', 1),
    (9, 'Later', 'Later', 'icons/phrases/later.svg', 2),
    (9, 'Today', 'Today', 'icons/phrases/today.svg', 3),
    (9, 'Tomorrow', 'Tomorrow', 'icons/phrases/tomorrow.svg', 4),
    (9, 'Yesterday', 'Yesterday', 'icons/phrases/yesterday.svg', 5),
    (9, 'Morning', 'In the morning', 'icons/phrases/morning.svg', 6),
    (9, 'Afternoon', 'In the afternoon', 'icons/phrases/afternoon.svg', 7),
    (9, 'Night', 'At night', 'icons/phrases/night.svg', 8),
    (9, 'Soon', 'Soon', 'icons/phrases/soon.svg', 9),
    (9, 'Always', 'Always', 'icons/phrases/always.svg', 10);

-- Food & Drink (category_id = 10)
INSERT INTO phrases (category_id, text_label, speech_output, icon_url, sort_order) VALUES
    (10, 'Water', 'Water', 'icons/phrases/water.svg', 1),
    (10, 'Juice', 'Juice', 'icons/phrases/juice.svg', 2),
    (10, 'Milk', 'Milk', 'icons/phrases/milk.svg', 3),
    (10, 'Coffee', 'Coffee', 'icons/phrases/coffee.svg', 4),
    (10, 'Breakfast', 'Breakfast', 'icons/phrases/breakfast.svg', 5),
    (10, 'Lunch', 'Lunch', 'icons/phrases/lunch.svg', 6),
    (10, 'Dinner', 'Dinner', 'icons/phrases/dinner.svg', 7),
    (10, 'Snack', 'A snack', 'icons/phrases/snack.svg', 8),
    (10, 'Fruit', 'Fruit', 'icons/phrases/fruit.svg', 9),
    (10, 'Vegetables', 'Vegetables', 'icons/phrases/vegetables.svg', 10);

-- =============================================================================
-- SEED DATA - Sample User
-- =============================================================================
INSERT INTO users (username, password_hash, email, settings) VALUES
    ('demo_user', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'demo@example.com',
    '{"voice": "default", "rate": 1.0, "pitch": 1.0, "gridSize": "medium", "theme": "light", "highContrast": false}');

-- Add some quick access phrases for demo user
INSERT INTO quick_access (user_id, phrase_id, use_count) VALUES
    (1, 1, 50),   -- Hello
    (1, 11, 45),  -- I need
    (1, 13, 40),  -- Help
    (1, 41, 38),  -- Yes
    (1, 42, 35),  -- No
    (1, 14, 30),  -- Water
    (1, 46, 28),  -- Okay
    (1, 21, 25),  -- Happy
    (1, 6, 20),   -- Goodbye
    (1, 44, 18);  -- Please
