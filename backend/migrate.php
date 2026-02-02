<?php
/**
 * Database Migration Script
 * Run this script to initialize the database schema and seed data
 *
 * Usage:
 *   php migrate.php              - Run migrations
 *   php migrate.php --fresh      - Drop all tables and re-run migrations
 *   php migrate.php --seed-only  - Only run seed data (tables must exist)
 */

declare(strict_types=1);

echo "AAC Assist Database Migration\n";
echo "==============================\n\n";

// Parse command line arguments
$fresh = in_array('--fresh', $argv);
$seedOnly = in_array('--seed-only', $argv);

// Load database configuration
$config = require __DIR__ . '/config/database.php';

try {
    // Connect without database first (to create database if needed)
    $dsn = sprintf(
        'mysql:host=%s;port=%s;charset=%s',
        $config['host'],
        $config['port'],
        $config['charset']
    );

    $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
    echo "Connected to MySQL server.\n";

    // Create database if it doesn't exist
    $dbName = $config['database'];
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database '{$dbName}' ready.\n";

    // Switch to the database
    $pdo->exec("USE `{$dbName}`");

    if ($fresh) {
        echo "Dropping existing tables...\n";
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        $pdo->exec("DROP TABLE IF EXISTS quick_access");
        $pdo->exec("DROP TABLE IF EXISTS phrases");
        $pdo->exec("DROP TABLE IF EXISTS categories");
        $pdo->exec("DROP TABLE IF EXISTS users");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        echo "Tables dropped.\n";
    }

    if (!$seedOnly) {
        // Create tables
        echo "Creating tables...\n";

        // Users table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                email VARCHAR(100),
                settings JSON DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_username (username)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "  - users table created\n";

        // Categories table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS categories (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                color_code VARCHAR(7) NOT NULL DEFAULT '#4A90D9',
                icon_url VARCHAR(255) DEFAULT NULL,
                sort_order INT UNSIGNED DEFAULT 0,
                is_active TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_sort (sort_order),
                INDEX idx_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "  - categories table created\n";

        // Phrases table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS phrases (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                category_id INT UNSIGNED NOT NULL,
                text_label VARCHAR(100) NOT NULL,
                speech_output VARCHAR(500) NOT NULL,
                icon_url VARCHAR(255) DEFAULT NULL,
                sort_order INT UNSIGNED DEFAULT 0,
                is_active TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
                INDEX idx_category (category_id),
                INDEX idx_sort (sort_order),
                INDEX idx_label (text_label),
                FULLTEXT idx_search (text_label, speech_output)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "  - phrases table created\n";

        // Quick access table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS quick_access (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                phrase_id INT UNSIGNED NOT NULL,
                use_count INT UNSIGNED DEFAULT 1,
                last_used TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (phrase_id) REFERENCES phrases(id) ON DELETE CASCADE,
                UNIQUE KEY unique_user_phrase (user_id, phrase_id),
                INDEX idx_user (user_id),
                INDEX idx_frequency (user_id, use_count DESC)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "  - quick_access table created\n";
    }

    // Check if data exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM categories");
    $categoryCount = $stmt->fetchColumn();

    if ($categoryCount == 0 || $fresh) {
        echo "Seeding data...\n";

        // Seed categories
        $categories = [
            ['Greetings', '#4CAF50', 1],
            ['Needs', '#F44336', 2],
            ['Feelings', '#9C27B0', 3],
            ['Questions', '#2196F3', 4],
            ['Responses', '#FF9800', 5],
            ['People', '#00BCD4', 6],
            ['Places', '#795548', 7],
            ['Actions', '#607D8B', 8],
            ['Time', '#E91E63', 9],
            ['Food & Drink', '#8BC34A', 10],
        ];

        $stmt = $pdo->prepare("INSERT INTO categories (name, color_code, sort_order) VALUES (?, ?, ?)");
        foreach ($categories as $cat) {
            $stmt->execute($cat);
        }
        echo "  - Categories seeded\n";

        // Seed phrases
        $phrases = [
            // Greetings (1)
            [1, 'Hello', 'Hello', 1],
            [1, 'Hi', 'Hi there', 2],
            [1, 'Good morning', 'Good morning', 3],
            [1, 'Good afternoon', 'Good afternoon', 4],
            [1, 'Good night', 'Good night', 5],
            [1, 'Goodbye', 'Goodbye', 6],
            [1, 'See you later', 'See you later', 7],
            [1, 'How are you?', 'How are you?', 8],
            [1, 'Nice to meet you', 'Nice to meet you', 9],
            [1, 'Thank you', 'Thank you', 10],

            // Needs (2)
            [2, 'I need', 'I need', 1],
            [2, 'I want', 'I want', 2],
            [2, 'Help', 'I need help', 3],
            [2, 'Water', 'I would like some water', 4],
            [2, 'Food', 'I am hungry', 5],
            [2, 'Bathroom', 'I need to use the bathroom', 6],
            [2, 'Rest', 'I need to rest', 7],
            [2, 'Medicine', 'I need my medicine', 8],
            [2, 'Stop', 'Please stop', 9],
            [2, 'More', 'I want more', 10],

            // Feelings (3)
            [3, 'Happy', 'I am happy', 1],
            [3, 'Sad', 'I am feeling sad', 2],
            [3, 'Tired', 'I am tired', 3],
            [3, 'Angry', 'I am feeling angry', 4],
            [3, 'Scared', 'I am scared', 5],
            [3, 'Excited', 'I am excited', 6],
            [3, 'Pain', 'I am in pain', 7],
            [3, 'Sick', 'I feel sick', 8],
            [3, 'Cold', 'I am cold', 9],
            [3, 'Hot', 'I am hot', 10],

            // Questions (4)
            [4, 'What?', 'What?', 1],
            [4, 'Where?', 'Where?', 2],
            [4, 'When?', 'When?', 3],
            [4, 'Who?', 'Who?', 4],
            [4, 'Why?', 'Why?', 5],
            [4, 'How?', 'How?', 6],
            [4, 'Can I?', 'Can I?', 7],
            [4, 'What time?', 'What time is it?', 8],
            [4, 'How much?', 'How much does it cost?', 9],
            [4, 'What is this?', 'What is this?', 10],

            // Responses (5)
            [5, 'Yes', 'Yes', 1],
            [5, 'No', 'No', 2],
            [5, 'Maybe', 'Maybe', 3],
            [5, 'Please', 'Please', 4],
            [5, 'Sorry', 'I am sorry', 5],
            [5, 'Okay', 'Okay', 6],
            [5, "I don't know", "I don't know", 7],
            [5, 'I understand', 'I understand', 8],
            [5, 'Repeat please', 'Can you repeat that please?', 9],
            [5, 'Wait', 'Please wait', 10],

            // People (6)
            [6, 'I', 'I', 1],
            [6, 'You', 'You', 2],
            [6, 'Mom', 'Mom', 3],
            [6, 'Dad', 'Dad', 4],
            [6, 'Family', 'My family', 5],
            [6, 'Friend', 'My friend', 6],
            [6, 'Doctor', 'The doctor', 7],
            [6, 'Teacher', 'My teacher', 8],
            [6, 'Everyone', 'Everyone', 9],
            [6, 'Someone', 'Someone', 10],

            // Places (7)
            [7, 'Home', 'Home', 1],
            [7, 'School', 'School', 2],
            [7, 'Work', 'Work', 3],
            [7, 'Hospital', 'The hospital', 4],
            [7, 'Store', 'The store', 5],
            [7, 'Outside', 'Outside', 6],
            [7, 'Here', 'Here', 7],
            [7, 'There', 'Over there', 8],
            [7, 'Bathroom', 'The bathroom', 9],
            [7, 'Bedroom', 'My bedroom', 10],

            // Actions (8)
            [8, 'Go', 'Go', 1],
            [8, 'Come', 'Come', 2],
            [8, 'Eat', 'Eat', 3],
            [8, 'Drink', 'Drink', 4],
            [8, 'Sleep', 'Sleep', 5],
            [8, 'Play', 'Play', 6],
            [8, 'Read', 'Read', 7],
            [8, 'Watch', 'Watch', 8],
            [8, 'Listen', 'Listen', 9],
            [8, 'Call', 'Call', 10],

            // Time (9)
            [9, 'Now', 'Now', 1],
            [9, 'Later', 'Later', 2],
            [9, 'Today', 'Today', 3],
            [9, 'Tomorrow', 'Tomorrow', 4],
            [9, 'Yesterday', 'Yesterday', 5],
            [9, 'Morning', 'In the morning', 6],
            [9, 'Afternoon', 'In the afternoon', 7],
            [9, 'Night', 'At night', 8],
            [9, 'Soon', 'Soon', 9],
            [9, 'Always', 'Always', 10],

            // Food & Drink (10)
            [10, 'Water', 'Water', 1],
            [10, 'Juice', 'Juice', 2],
            [10, 'Milk', 'Milk', 3],
            [10, 'Coffee', 'Coffee', 4],
            [10, 'Breakfast', 'Breakfast', 5],
            [10, 'Lunch', 'Lunch', 6],
            [10, 'Dinner', 'Dinner', 7],
            [10, 'Snack', 'A snack', 8],
            [10, 'Fruit', 'Fruit', 9],
            [10, 'Vegetables', 'Vegetables', 10],
        ];

        $stmt = $pdo->prepare("INSERT INTO phrases (category_id, text_label, speech_output, sort_order) VALUES (?, ?, ?, ?)");
        foreach ($phrases as $phrase) {
            $stmt->execute($phrase);
        }
        echo "  - Phrases seeded (" . count($phrases) . " phrases)\n";

        // Seed demo user
        $pdo->exec("
            INSERT INTO users (username, password_hash, email, settings) VALUES
            ('demo_user', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'demo@example.com',
            '{\"voice\": \"default\", \"rate\": 1.0, \"pitch\": 1.0, \"gridSize\": \"medium\", \"theme\": \"light\"}')
        ");
        echo "  - Demo user created\n";

        // Seed quick access for demo user
        $quickAccess = [
            [1, 1, 50],   // Hello
            [1, 11, 45],  // I need
            [1, 13, 40],  // Help
            [1, 41, 38],  // Yes
            [1, 42, 35],  // No
            [1, 14, 30],  // Water
            [1, 46, 28],  // Okay
            [1, 21, 25],  // Happy
            [1, 6, 20],   // Goodbye
            [1, 44, 18],  // Please
        ];

        $stmt = $pdo->prepare("INSERT INTO quick_access (user_id, phrase_id, use_count) VALUES (?, ?, ?)");
        foreach ($quickAccess as $qa) {
            $stmt->execute($qa);
        }
        echo "  - Quick access data seeded\n";
    } else {
        echo "Data already exists, skipping seed (use --fresh to reset).\n";
    }

    echo "\nMigration completed successfully!\n";

} catch (PDOException $e) {
    echo "\nError: " . $e->getMessage() . "\n";
    exit(1);
}
