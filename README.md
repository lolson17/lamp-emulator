# AAC Assist - Augmentative and Alternative Communication Application

A web-based Assisted Speech (AAC) application that allows users with speech impairments to build sentences by tapping symbols/text on a grid, which are then vocalized using the browser's Web Speech API.

## Architecture

This application uses a **split deployment** architecture:

- **Frontend** (`frontend/`) → Hosted on **GitHub Pages** (static HTML/CSS/JS)
- **Backend** (`backend/`) → Hosted on **Railway** (PHP + MySQL)

```
┌─────────────────────┐         ┌─────────────────────┐
│   GitHub Pages      │   API   │      Railway        │
│   ─────────────     │ ──────► │   ─────────────     │
│   index.html        │         │   api.php           │
│   (Static Frontend) │         │   MySQL Database    │
└─────────────────────┘         └─────────────────────┘
```

---

## Quick Start Deployment

### Prerequisites

- GitHub account
- Railway account (https://railway.app) - free tier available
- Git installed locally

---

## Part 1: Deploy Backend to Railway

### Step 1: Create Railway Account & Project

1. Go to [railway.app](https://railway.app) and sign up/login (GitHub OAuth recommended)
2. Click **"New Project"** → **"Deploy from GitHub repo"**
3. Select this repository or connect your fork
4. Railway will detect the `backend/` folder

### Step 2: Configure Railway Service

1. In your Railway project dashboard, click on your service
2. Go to **Settings** → **General**
3. Set **Root Directory** to: `backend`
4. Set **Start Command** to: `php -S 0.0.0.0:$PORT`

### Step 3: Add MySQL Database

1. In your Railway project, click **"+ New"** → **"Database"** → **"MySQL"**
2. Railway automatically creates and links these environment variables:
   - `MYSQL_HOST`
   - `MYSQL_PORT`
   - `MYSQL_DATABASE`
   - `MYSQL_USER`
   - `MYSQL_PASSWORD`

### Step 4: Run Database Migration

**Option A: Using Railway CLI (Recommended)**

```bash
# Install Railway CLI
npm install -g @railway/cli

# Login to Railway
railway login

# Link to your project
railway link

# Run migration
railway run php migrate.php
```

**Option B: Manual via Railway Shell**

1. In Railway dashboard, click on your PHP service
2. Go to **"Deployments"** tab
3. Click on the active deployment → **"View Logs"** → **"Shell"**
4. Run: `php migrate.php`

### Step 5: Get Your Backend URL

1. In Railway, go to your PHP service **Settings** → **Networking**
2. Click **"Generate Domain"** to get a public URL
3. Your API URL will be: `https://YOUR-APP.up.railway.app/api.php`

**Save this URL - you'll need it for the frontend!**

---

## Part 2: Deploy Frontend to GitHub Pages

### Step 1: Update API URL in Frontend

Edit `frontend/index.html` and update the configuration:

```javascript
window.AAC_CONFIG = {
    // Replace with your Railway backend URL from Step 5 above
    API_URL: 'https://YOUR-RAILWAY-APP.up.railway.app/api.php',
    USER_ID: 1,
    DEBOUNCE_DELAY: 300,
    STORAGE_KEY: 'aac_assist_settings'
};
```

### Step 2: Enable GitHub Pages

1. Go to your GitHub repository
2. Navigate to **Settings** → **Pages**
3. Under **Source**, select:
   - Branch: `main` (or your default branch)
   - Folder: `/frontend`
4. Click **Save**

### Step 3: Access Your App

After a few minutes, your app will be live at:
```
https://YOUR-USERNAME.github.io/YOUR-REPO-NAME/
```

---

## Project Structure

```
aac-assist/
├── frontend/                    # GitHub Pages (Static)
│   └── index.html               # Main application interface
│
├── backend/                     # Railway (PHP + MySQL)
│   ├── api.php                  # REST API endpoints
│   ├── migrate.php              # Database migration script
│   ├── railway.json             # Railway configuration
│   ├── nixpacks.toml            # Nixpacks build config
│   ├── config/
│   │   └── database.php         # Database configuration
│   └── database/
│       └── schema.sql           # Full SQL schema (reference)
│
└── README.md                    # This file
```

---

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `?action=categories` | Get all categories |
| GET | `?action=phrases` | Get all phrases |
| GET | `?action=phrases&category=X` | Get phrases for category X |
| GET | `?action=search&q=term` | Search phrases by text |
| GET | `?action=quick_access&user=X` | Get user's quick access phrases |
| POST | `?action=track_usage` | Track phrase usage |

---

## Railway CLI Quick Reference

```bash
# Install CLI
npm install -g @railway/cli

# Login
railway login

# Link project (run in repo root)
railway link

# Run commands in Railway environment
railway run php migrate.php
railway run php migrate.php --fresh    # Reset database

# View logs
railway logs

# Open project dashboard
railway open

# Check status
railway status
```

---

## Environment Variables

Railway automatically provides MySQL variables. For local development, create `backend/config/database.local.php`:

```php
<?php
return [
    'host'     => 'localhost',
    'port'     => '3306',
    'database' => 'aac_assist',
    'username' => 'root',
    'password' => 'your_password',
    'charset'  => 'utf8mb4',
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ],
];
```

---

## Local Development

### Backend

```bash
cd backend

# Start PHP built-in server
php -S localhost:8080

# Run migrations
php migrate.php
```

### Frontend

```bash
cd frontend

# Serve with any static server, e.g., Python
python -m http.server 3000

# Or use VS Code Live Server extension
```

Update `frontend/index.html` API_URL to `http://localhost:8080/api.php` for local testing.

---

## Features

- **Sentence Builder**: Tap phrases to build sentences
- **Text-to-Speech**: Browser's Speech Synthesis API
- **Categorized Phrases**: 10 categories, 100+ phrases
- **Quick Access**: Frequently used phrases tracking
- **Search**: Real-time phrase filtering
- **Accessibility**:
  - WCAG 2.1 AA compliant
  - High contrast mode
  - 48px+ touch targets
  - Full keyboard navigation
  - ARIA labels and live regions

---

## Troubleshooting

### "Failed to load application data"

1. Check browser console for errors (F12)
2. Verify API URL in frontend config is correct
3. Test API directly: `https://YOUR-APP.up.railway.app/api.php?action=categories`
4. Check Railway logs for PHP errors

### Database Connection Errors

1. Ensure MySQL addon is added in Railway
2. Check that environment variables are linked
3. Run `railway run php migrate.php` to initialize

### CORS Errors

The API includes CORS headers. If you still see errors:
1. Check the API is responding (not erroring before headers)
2. Ensure you're using HTTPS for both frontend and backend

### Speech Not Working

1. Ensure browser supports Web Speech API (Chrome, Edge, Safari, Firefox)
2. Check browser permissions
3. Try different voice in settings panel

---

## Cost Estimates

- **GitHub Pages**: Free
- **Railway Free Tier**:
  - $5 credit/month
  - Sufficient for light usage
  - Database included

For production use, Railway Pro starts at $20/month with more resources.

---

## License

MIT License - See LICENSE file for details.
