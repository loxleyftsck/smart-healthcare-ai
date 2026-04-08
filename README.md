# Smart Healthcare Assistant System 🏥🤖

An AI-powered smart healthcare system featuring a Laravel API backend fused with a Python/FastAPI microservice running Google's Gemini LLM. Designed to handle user consultations and conduct intelligent medical triage automatically.

## 🏗️ Architecture Stack

- **Core Backend:** Laravel 11 (PHP 8.2), MySQL 8.0
- **AI Triage Engine:** FastAPI (Python 3.11), Google Gemini 2.5 LLM
- **Security:** Tymon JWT-Auth (JSON Web Tokens)
- **API Documentation:** L5-Swagger (OpenAPI 3)
- **Infrastructure:** Docker Compose (Nginx + PHP-FPM + Python Server + MySQL)

### Service Communication
```ascii
[Client] ---> (Port 8000) [Laravel API Gateway]
                                  │
                                  ├──> [MySQL DB] (Port 3306)
                                  │
                                  └──> [Python AI Microservice] (Port 8001) ---> [Google Gemini API]
```

---

## 🚀 Quick Start (Dockerized)

Ensure you have **Docker** and **Docker Compose** installed.

1. **Clone the Repository**
   ```bash
   git clone https://github.com/your-repo/smart-health-ai.git
   cd smart-health-ai
   ```

2. **Setup Environment Variables**
   ```bash
   cp smart-health-ai/.env.example smart-health-ai/.env
   cp ai-triage-service/.env.example ai-triage-service/.env
   ```
   **CRITICAL:** Open `ai-triage-service/.env` and add your `GEMINI_API_KEY`.

3. **Start the Infrastructure**
   ```bash
   docker compose up -d --build
   ```

4. **Initialize Laravel App**
   ```bash
   docker exec -it smarthealth_app bash
   # Inside container:
   composer install
   php artisan key:generate
   php artisan jwt:secret
   php artisan migrate --seed
   php artisan l5-swagger:generate
   exit
   ```

---

## 📡 API Endpoints 

### Global Services
- `http://localhost:8000/api/documentation` - Interactive Swagger API Docs UI
- `http://localhost:8000/api/health` - Check backend health status
- `http://localhost:8001/api/health` - Check Python generic microservice health

### Auth & Patients (Requires JWT)
- `POST /api/auth/register` - Create user account
- `POST /api/auth/login` - Obtain JWT Token
- `POST /api/patients` - Register a patient profile
- `GET /api/patients/{id}` - Complete CRUD available

### AI Consultation
- `POST /api/consultations` - Submit symptoms for LLM triage assessment

---

## 🧪 Testing
The system holds a comprehensive suite of PHPUnit Feature & Unit tests.

To run the tests on your host machine:
```bash
php artisan test
```

*Note: All network calls to the AI Microservice are elegantly mocked using `Http::fake()` during testing to guarantee fast, deterministic integration test cycles.*
