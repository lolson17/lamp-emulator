# AAC Assist - Augmentative and Alternative Communication Application

A web-based Assisted Speech (AAC) application built on the LAMP stack that allows users with speech impairments to build sentences by tapping symbols/text on a grid, which are then vocalized using the browser's Web Speech API.

## Features

- **Sentence Builder**: Tap phrases to build sentences visually
- **Text-to-Speech**: Vocalize sentences using browser's Speech Synthesis API
- **Categorized Phrases**: Organized into intuitive categories (Greetings, Needs, Feelings, etc.)
- **Quick Access**: Frequently used phrases are tracked and displayed for fast access
- **Search**: Filter phrases by text label
- **Accessibility First**:
  - WCAG 2.1 AA compliant
  - High contrast mode
  - Large touch targets (minimum 48x48px)
  - Full keyboard navigation
  - ARIA labels and live regions for screen readers
- **Customizable**: Adjustable voice, speech rate, and pitch

## Requirements

- **Web Server**: Apache 2.4+ with mod_rewrite enabled
- **PHP**: 7.4+ (8.0+ recommended)
- **MySQL**: 5.7+ or MariaDB 10.3+
- **Browser**: Modern browser with Web Speech API support (Chrome, Edge, Safari, Firefox)

## Installation

### 1. Clone or Download

```bash
git clone <repository-url> /var/www/html/aac-assist
cd /var/www/html/aac-assist
```

### 2. Create the Database

Connect to MySQL and run the schema file:

```bash
mysql -u root -p < database/schema.sql
```

Or via MySQL client:

```sql
SOURCE /path/to/database/schema.sql;
```

This will:
- Create the `aac_assist` database
- Create all required tables (users, categories, phrases, quick_access)
- Populate seed data with common AAC phrases

### 3. Configure Database Connection

Copy the configuration template and edit with your credentials:

```bash
cp config/database.php config/database.local.php
```

Edit `config/database.local.php`:

```php
<?php
return [
    'host'     => 'localhost',
    'port'     => '3306',
    'database' => 'aac_assist',
    'username' => 'your_username',
    'password' => 'your_password',
    'charset'  => 'utf8mb4',
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ],
];
```

Alternatively, use environment variables:

```bash
export DB_HOST=localhost
export DB_PORT=3306
export DB_NAME=aac_assist
export DB_USER=your_username
export DB_PASS=your_password
```

### 4. Set File Permissions

```bash
chmod 644 config/*.php
chmod 755 . config database
```

### 5. Configure Apache Virtual Host (Optional)

Create a virtual host for the application:

```apache
<VirtualHost *:80>
    ServerName aac-assist.local
    DocumentRoot /var/www/html/aac-assist

    <Directory /var/www/html/aac-assist>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/aac-assist-error.log
    CustomLog ${APACHE_LOG_DIR}/aac-assist-access.log combined
</VirtualHost>
```

### 6. Test the Installation

1. Start your Apache and MySQL services
2. Open your browser and navigate to: `http://localhost/aac-assist/` or your configured domain
3. The application should load with categories and phrases

## Project Structure

```
aac-assist/
├── index.php              # Main frontend interface
├── api.php                # RESTful API backend
├── config/
│   ├── database.php       # Database configuration template
│   └── database.local.php # Local database config (create this)
├── database/
│   └── schema.sql         # MySQL database schema and seed data
├── icons/                 # Icon assets (add your own)
│   └── phrases/
└── README.md
```

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `api.php?action=categories` | Get all categories |
| GET | `api.php?action=phrases` | Get all phrases |
| GET | `api.php?action=phrases&category=X` | Get phrases for category X |
| GET | `api.php?action=search&q=term` | Search phrases by text label |
| GET | `api.php?action=quick_access&user=X` | Get quick access phrases for user X |
| GET | `api.php?action=categories_with_phrases` | Get categories with nested phrases |
| POST | `api.php?action=track_usage` | Track phrase usage (body: user_id, phrase_id) |

## Usage

1. **Build a Sentence**: Tap/click on phrase buttons to add words to the sentence bar
2. **Remove Words**: Click on words in the sentence bar to remove them, or use the Back button
3. **Speak**: Press the Speak button to vocalize the sentence
4. **Search**: Use the search bar to quickly find specific phrases
5. **Settings**: Click the gear icon to adjust voice, speed, pitch, and contrast settings

## Accessibility Features

- **Keyboard Navigation**: Full support for Tab, Enter, Space, and Escape keys
- **Screen Reader Support**: ARIA labels, roles, and live regions for announcements
- **High Contrast Mode**: Toggle in settings for visually impaired users
- **Large Touch Targets**: All interactive elements are at least 48x48px
- **Focus Indicators**: Visible focus rings on all interactive elements
- **Skip Links**: Skip to main content for keyboard users

## Adding Custom Phrases

Insert new phrases via SQL:

```sql
INSERT INTO phrases (category_id, text_label, speech_output, sort_order)
VALUES (1, 'Custom Phrase', 'What the system will say', 100);
```

Or create an admin interface to manage phrases through the API.

## Browser Support

| Browser | Minimum Version | Notes |
|---------|-----------------|-------|
| Chrome | 33+ | Full support |
| Edge | 14+ | Full support |
| Safari | 7+ | Full support |
| Firefox | 49+ | Full support |

## Troubleshooting

### Database Connection Error
- Verify MySQL is running: `sudo systemctl status mysql`
- Check credentials in `config/database.local.php`
- Ensure the database exists: `mysql -u root -p -e "SHOW DATABASES;"`

### No Speech Output
- Ensure browser supports Web Speech API
- Check browser permissions for speech synthesis
- Try a different voice in settings

### Phrases Not Loading
- Check browser console for JavaScript errors
- Verify API is working: `curl http://localhost/aac-assist/api.php?action=categories`
- Check PHP error logs

## License

This project is provided for educational and assistive technology purposes.

## Contributing

Contributions are welcome! Please ensure any changes maintain WCAG 2.1 AA accessibility compliance.
