# ComplySmart — Production-Ready Full-Stack Architecture

ComplySmart is an Enterprise Compliance Platform for Sri Lankan SMEs to manage business registrations, tax filings (VAT/TIN), EPF/ETF labor contributions, permit renewals, document storage (Cloudinary + Tesseract OCR), and AI guidance (Google Gemini AI).

This repository is organized into a clean, decoupled full-stack architecture with independent `/frontend` and `/backend` applications.

---

## 📁 Project Structure

```
complysmart/
├── frontend/                     # Standalone Client Application (Vite SPA)
│   ├── src/
│   │   └── js/
│   │       └── api.js            # Universal API client reading VITE_API_URL
│   ├── index.html                # Landing / Welcome page
│   ├── login.html                # User login page
│   ├── register.html             # User registration page
│   ├── dashboard.html            # Main compliance dashboard
│   ├── profile.html              # Business profile settings
│   ├── documents.html            # Vault document manager
│   ├── upload_document.html      # Document upload & OCR trigger
│   ├── document_details.html     # Document inspector & OCR viewer
│   ├── tasks.html                # Task tracker
│   ├── renewals.html             # Permit & license renewal tracker
│   ├── ai.html                   # Gemini AI chatbot & checklist generator
│   ├── reports.html              # Analytics & audit reports
│   ├── notifications.html        # System notifications
│   ├── package.json              # Frontend client dependencies & scripts
│   ├── vite.config.js            # Vite multi-page SPA configuration
│   ├── .env                      # Frontend environment variables
│   └── .env.example              # Frontend environment template
│
├── backend/                      # Standalone Server Application (Laravel 12 REST API)
│   ├── app/                      # Controllers, Models, Services, Requests
│   ├── bootstrap/                # Application bootstrap
│   ├── config/                   # Configuration (CORS, Database, Services)
│   ├── database/                 # Migrations & Seeders
│   ├── public/                   # Public entrypoint
│   ├── routes/
│   │   └── api.php               # Versioned REST API endpoints (/api/v1/...)
│   ├── storage/                  # Logs & temporary files
│   ├── tests/                    # Unit & Feature test suites
│   ├── artisan                   # Laravel CLI tool
│   ├── composer.json             # PHP backend dependencies
│   ├── phpunit.xml               # Testing configuration
│   ├── .env                      # Backend environment variables
│   └── .env.example              # Backend environment template
│
└── README.md                     # Root project documentation
```

---

## ⚙️ Environment Variables

### Frontend Configuration (`/frontend/.env`)
```env
# URL pointing to the running backend API
VITE_API_URL=http://localhost:8000/api/v1
```

### Backend Configuration (`/backend/.env`)
```env
APP_NAME=ComplySmart
APP_ENV=local
APP_KEY=base64:Go73CTK/2PCkx3vVF5yaI4gnLGqFRCW9JtgrB3+Ku+I=
APP_URL=http://localhost:8000

# Database (MongoDB / SQLite)
DB_CONNECTION=mongodb
MONGODB_URI="your_mongodb_connection_string"
MONGODB_DATABASE=complysmart

# Google Gemini AI Integration
GEMINI_API_KEY=your_gemini_api_key_here
GEMINI_MODEL=gemini-2.5-flash
GEMINI_TIMEOUT=30
```

---

## 🚀 Getting Started & Installation

### Prerequisites
- Node.js (v18+)
- PHP (v8.2+ or v8.3)
- Composer

---

### 1. Running the Backend API Server

```bash
# Navigate to backend directory
cd backend

# Install PHP dependencies
composer install

# Start the API server on port 8000
php artisan serve --port=8000
```
> The API server will run at: `http://localhost:8000/api/v1`  
> Test ping endpoint: `http://localhost:8000/api/v1/ping`

---

### 2. Running the Frontend Client Application

In a new terminal window:

```bash
# Navigate to frontend directory
cd frontend

# Install JavaScript dependencies
npm install

# Start the Vite development server
npm run dev
```
> The client application will run at: `http://localhost:5173`

---

## 🧪 Running Automated Tests

```bash
cd backend
php artisan test
```

---

## 🔒 Security & CORS

The backend API uses Sanctum tokens for user authentication and has Cross-Origin Resource Sharing (CORS) configured in `backend/config/cors.php` to explicitly accept credentials and headers from the frontend dev server (`http://localhost:5173`).
