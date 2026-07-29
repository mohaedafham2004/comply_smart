# 🛡️ ComplySmart API — Backend & Test Suite

ComplySmart is an AI-powered compliance management backend built for small businesses in Sri Lanka. It provides secure document storage with OCR, compliance task tracking, renewal management, daily automated email/in-app reminders, and an AI assistant powered by Google Gemini.

---

## 🛠️ Tech Stack

- **Backend**: Laravel 12 (PHP 8.3+)
- **Database**: MongoDB Atlas via `jenssegers/mongodb` (Eloquent ODM)
- **Authentication**: Laravel Sanctum (Bearer Token API)
- **Document Storage**: Cloudinary SDK (v3)
- **OCR Engine**: Tesseract OCR
- **AI Engine**: Google Gemini API (`gemini-1.5-flash`)
- **Queue / Cache**: Redis (Fallback to `sync`)
- **Test Frontend**: Vanilla Blade + JS + Alpine.js + Tailwind CDN (No build step)

---

## 🚀 Installation & Setup Guide

### 1. Prerequisites
- PHP 8.3+ with `mongodb` and `curl` extensions enabled
- Composer
- Tesseract OCR installed locally (or in PATH)
- Redis server (optional for dev, required for prod background jobs)

### 2. Environment Configuration
Clone the repository and copy `.env.example` to `.env`:

```bash
cp .env.example .env
php artisan key:generate
```

Update your `.env` with actual credentials:

```env
APP_NAME=ComplySmart
APP_ENV=local
APP_URL=http://localhost:8000

# MongoDB Atlas
DB_CONNECTION=mongodb
MONGODB_URI=mongodb+srv://<username>:<password>@<cluster>.mongodb.net
MONGODB_DATABASE=complysmart

# Cloudinary
CLOUDINARY_URL=cloudinary://API_KEY:API_SECRET@CLOUD_NAME
CLOUDINARY_CLOUD_NAME=your_cloud_name
CLOUDINARY_API_KEY=your_api_key
CLOUDINARY_API_SECRET=your_api_secret

# Tesseract OCR
TESSERACT_PATH="C:\Program Files\Tesseract-OCR\tesseract.exe"

# Google Gemini API
GEMINI_API_KEY=your_gemini_api_key_here
GEMINI_MODEL=gemini-1.5-flash

# Mailer (log driver for dev testing)
MAIL_MAILER=log
MAIL_FROM_ADDRESS="noreply@complysmart.lk"
```

### 3. Database Indexes Setup
Execute the index setup artisan command to establish MongoDB collection indexes:

```bash
php artisan complysmart:setup-indexes
```

### 4. Running Queue Worker & Scheduler
Start the Redis queue worker for asynchronous OCR and email processing:

```bash
# Production / Local with Redis
php artisan queue:work redis --tries=3 --timeout=120

# Development without Redis (Sync mode)
# Set QUEUE_CONNECTION=sync in .env
```

To run the automated reminder scheduler:

```bash
# Run scheduler daemon
php artisan schedule:work

# Or trigger reminder job manually anytime (dev testing)
php artisan complysmart:send-reminders
```

### 5. Running the Local Dev Server
```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Access the minimal test UI at: **`http://127.0.0.1:8000/test`**

---

## 📑 Complete API Endpoint Contract

All API responses are formatted as JSON. Protected endpoints require `Authorization: Bearer <sanctum_token>`.

### 1. Authentication & Profile
| Method | Endpoint | Auth Required | Role | Description |
|--------|----------|---------------|------|-------------|
| `POST` | `/api/v1/auth/register` | ❌ No | Any | Register owner user & create business shell |
| `POST` | `/api/v1/auth/login` | ❌ No | Any | Authenticate & return Sanctum token |
| `POST` | `/api/v1/auth/logout` | ✅ Yes | Any | Revoke active API token |
| `GET`  | `/api/v1/auth/me` | ✅ Yes | Any | Get current authenticated user profile |
| `GET`  | `/api/v1/business` | ✅ Yes | Any | Get current user's business profile |
| `PUT`  | `/api/v1/business` | ✅ Yes | Owner/Admin | Update current business profile |
| `GET`  | `/api/v1/business/compliance-score` | ✅ Yes | Any | Get live business compliance score & breakdown |

### 2. Document Vault & OCR
| Method | Endpoint | Auth Required | Role | Description |
|--------|----------|---------------|------|-------------|
| `GET`  | `/api/v1/documents` | ✅ Yes | Any | List business documents (filterable, paginated) |
| `POST` | `/api/v1/documents` | ✅ Yes | Any | Upload document to Cloudinary & queue OCR |
| `GET`  | `/api/v1/documents/{id}` | ✅ Yes | Any | Get single document metadata |
| `PUT`  | `/api/v1/documents/{id}` | ✅ Yes | Any | Update document metadata |
| `DELETE` | `/api/v1/documents/{id}` | ✅ Yes | Any | Delete document from MongoDB & Cloudinary |
| `GET`  | `/api/v1/documents/{id}/ocr-status` | ✅ Yes | Any | Poll OCR status and extracted text |

### 3. Compliance Task Management
| Method | Endpoint | Auth Required | Role | Description |
|--------|----------|---------------|------|-------------|
| `GET`  | `/api/v1/tasks` | ✅ Yes | Any | List tasks (filter by status/category) |
| `POST` | `/api/v1/tasks` | ✅ Yes | Any | Create a new compliance task |
| `GET`  | `/api/v1/tasks/{id}` | ✅ Yes | Any | Get single task details |
| `PUT`  | `/api/v1/tasks/{id}` | ✅ Yes | Any | Update task details |
| `PATCH`| `/api/v1/tasks/{id}/status` | ✅ Yes | Any | Transition status (pending, in_progress, completed, overdue) |
| `DELETE`| `/api/v1/tasks/{id}` | ✅ Yes | Any | Delete task |

### 4. Renewal Tracking
| Method | Endpoint | Auth Required | Role | Description |
|--------|----------|---------------|------|-------------|
| `GET`  | `/api/v1/renewals` | ✅ Yes | Any | List renewals (filter by status) |
| `GET`  | `/api/v1/renewals/upcoming` | ✅ Yes | Any | Get renewals due in next N days (default 30) |
| `POST` | `/api/v1/renewals` | ✅ Yes | Any | Create a renewal tracking item |
| `GET`  | `/api/v1/renewals/{id}` | ✅ Yes | Any | Get single renewal record |
| `PUT`  | `/api/v1/renewals/{id}` | ✅ Yes | Any | Update renewal details/status |
| `DELETE`| `/api/v1/renewals/{id}` | ✅ Yes | Any | Delete renewal record |

### 5. Notifications
| Method | Endpoint | Auth Required | Role | Description |
|--------|----------|---------------|------|-------------|
| `GET`  | `/api/v1/notifications` | ✅ Yes | Any | Get user notifications (supports `unread_only=1`) |
| `PATCH`| `/api/v1/notifications/{id}/read` | ✅ Yes | Any | Mark single notification as read |
| `PATCH`| `/api/v1/notifications/read-all` | ✅ Yes | Any | Mark all notifications as read |

### 6. AI Assistant (Gemini)
| Method | Endpoint | Auth Required | Role | Description |
|--------|----------|---------------|------|-------------|
| `POST` | `/api/v1/ai/chat` | ✅ Yes | Any | Send message to AI chatbot |
| `GET`  | `/api/v1/ai/chat/history` | ✅ Yes | Any | Retrieve AI chat history |
| `POST` | `/api/v1/ai/checklist` | ✅ Yes | Any | Generate structured compliance checklist |
| `POST` | `/api/v1/ai/summarize` | ✅ Yes | Any | Summarize regulation text or document OCR |

### 7. Reports & Administration
| Method | Endpoint | Auth Required | Role | Description |
|--------|----------|---------------|------|-------------|
| `GET`  | `/api/v1/reports/overview` | ✅ Yes | Any | Executive compliance summary & metrics |
| `GET`  | `/api/v1/reports/audit-log` | ✅ Yes | Admin | System audit trail (paginated, filterable) |
| `GET`  | `/api/v1/businesses` | ✅ Yes | Admin | List all businesses |
| `POST` | `/api/v1/businesses` | ✅ Yes | Admin | Create business entity |
| `GET`  | `/api/v1/businesses/{id}` | ✅ Yes | Admin | Show business details |
| `PUT`  | `/api/v1/businesses/{id}` | ✅ Yes | Admin | Update business entity |
