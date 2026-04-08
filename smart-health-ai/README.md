# Smart Healthcare AI - Laravel Backend 🏥

API backend service untuk Smart Healthcare Assistant System. Mengelola patient data, consultations, dan triage integration dengan Python AI microservice.

## 📋 Quick Info

- **Framework:** Laravel 11
- **PHP:** 8.2+
- **Database:** MySQL 8.0 / SQLite (dev)
- **API Auth:** JWT (tymon/jwt-auth)
- **API Docs:** Swagger/OpenAPI 3
- **Tests:** 45+ PHPUnit tests (Feature + Unit)

---

## 🏗️ Project Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       ├── AuthController.php
│   │       ├── PatientController.php
│   │       ├── TriageController.php
│   │       └── ConsultationController.php
│   ├── Requests/
│   │   ├── StorePatientRequest.php
│   │   ├── TriageRequest.php
│   │   └── ChatRequest.php
│   ├── Resources/
│   │   ├── PatientResource.php
│   │   ├── TriageResultResource.php
│   │   └── ConsultationResource.php
│   └── Middleware/
│       ├── JwtAuthMiddleware.php
│       └── RequestLoggingMiddleware.php
├── Models/
│   ├── User.php
│   ├── Patient.php
│   ├── Consultation.php
│   └── TriageLog.php
├── Services/
│   ├── PatientService.php
│   ├── TriageService.php (integrates with Python AI)
│   └── ConsultationService.php
└── Exceptions/
    └── Handler.php (Global JSON error handling)

database/
├── migrations/
│   ├── create_users_table.php
│   ├── create_patients_table.php
│   ├── create_consultations_table.php
│   └── create_triage_logs_table.php
└── seeders/
    └── DatabaseSeeder.php

routes/
├── api.php (All API routes with JWT protection)
└── web.php (Health check + docs)

tests/
├── Feature/
│   ├── PatientApiTest.php
│   ├── TriageApiTest.php
│   └── ConsultationApiTest.php
└── Unit/
    ├── PatientServiceTest.php
    └── TriageServiceTest.php
```

---

## 🚀 Getting Started

### Local Development (Docker)

```bash
# From root directory:
docker compose up -d --build

# Setup Laravel container
docker exec -it smarthealth_app bash

# Inside container:
composer install
php artisan key:generate
php artisan jwt:secret
php artisan migrate
php artisan test                    # Run tests
php artisan l5-swagger:generate    # Generate API docs
exit
```

### Access Services

- **Laravel API:** http://localhost:8000
- **API Docs:** http://localhost:8000/api/documentation
- **Health Check:** http://localhost:8000/api/health

---

## 📡 API Endpoints

### Authentication
```
POST /api/auth/register
  {"name": "John", "email": "john@example.com", "password": "..."}

POST /api/auth/login
  {"email": "john@example.com", "password": "..."}
  → Returns JWT token
```

### Patients
```
POST /api/patients (Requires JWT)
  {"name": "Jane", "email": "jane@example.com", ...}

GET /api/patients (Requires JWT)
  → Paginated list

GET /api/patients/{id} (Requires JWT)
  → Patient detail with consultations

PUT /api/patients/{id} (Requires JWT)
  → Update patient

DELETE /api/patients/{id} (Requires JWT)
  → Remove patient
```

### Triage (AI Analysis)
```
POST /api/triage (Requires JWT)
  {
    "symptoms": ["demam_tinggi", "sesak_napas"],
    "patient_id": 1 (optional)
  }
  → Returns: {severity: "HIGH", confidence: 0.95, recommendation: "..."}
```

### Consultations (Chat History)
```
POST /api/consultations (Requires JWT)
  {"message": "Saya demam 39°", "session_id": "uuid"}

GET /api/consultations (Requires JWT)
  → List user's consultations

GET /api/consultations/{id} (Requires JWT)
  → Consultation detail
```

---

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/PatientApiTest.php

# With coverage
php artisan test --coverage
```

**Current Coverage:** 45+ tests
- Patient CRUD: 8 tests
- Authentication: 6 tests
- Triage Integration: 12 tests
- Consultations: 10+ tests
- Service Layer: 10+ tests

---

## 🔐 Security Features

✅ **JWT Authentication** - All API endpoints protected  
✅ **CSRF Protection** - Laravel built-in middleware  
✅ **Input Validation** - Form Requests with rules  
✅ **SQL Injection Prevention** - Eloquent ORM parametrized queries  
✅ **CORS Configuration** - Cross-origin policy  
✅ **Error Handling** - Global JSON responses (no stack traces)  
✅ **Audit Logging** - Request logging middleware  

---

## ⚙️ Environment Configuration

See `.env.example` for all required variables:

```env
APP_NAME="Smart Health AI"
APP_ENV=local
APP_DEBUG=true

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=smart_health_ai
DB_USERNAME=root
DB_PASSWORD=secret

JWT_SECRET=your_secret_key_here
JWT_TTL=15  # Token expires in 15 minutes

PYTHON_SERVICE_URL=http://localhost:8001
GEMINI_API_KEY=your_gemini_key_here
```

---

## 📊 Performance Optimizations (PATH B)

**Implemented Improvements:**
- ✅ Database query caching (65% cache hit rate)
- ✅ Response compression (70% payload reduction)
- ✅ Connection pooling (10 connections, 5 min idle)
- ✅ Index optimization (13 strategic indexes)
- ✅ Async consultation processing

**Results:**
- Response time: 842ms → 714ms (-15.2%)
- Throughput: 11.4 → 13.3 req/s (+16.7%)
- Database latency: 170ms → 45ms (-73%)

See root [DELIVERY_SUMMARY.md](../DELIVERY_SUMMARY.md) for details.

---

## 📚 Documentation

See root directory for comprehensive guides:

- **[HEALTHCARE_STANDARDS_COMPLETE.md](../HEALTHCARE_STANDARDS_COMPLETE.md)** - HL7 FHIR, ICD-10, HIPAA, UU PDP
- **[HEALTHCARE_IMPLEMENTATION_PRACTICAL.md](../HEALTHCARE_IMPLEMENTATION_PRACTICAL.md)** - Code examples, FHIR patterns
- **[HEALTHCARE_REGULATIONS_AND_LICENSING.md](../HEALTHCARE_REGULATIONS_AND_LICENSING.md)** - Compliance roadmap, licensing
- **[CONTRIBUTING.md](../CONTRIBUTING.md)** - Development guidelines
- **[SECURITY_POLICY.md](../SECURITY_POLICY.md)** - Vulnerability reporting

---

## 🔗 Integration

### Python AI Microservice
```
POST http://localhost:8001/api/triage
Content-Type: application/json

{
  "symptoms": ["demam", "batuk"],
  "session_id": "uuid-here"
}

Response:
{
  "severity": "MEDIUM",
  "confidence": 0.88,
  "recommendation": "...",
  "symptoms_found": ["demam", "batuk"]
}
```

### Database Schema
- **patients:** id, name, email, phone, dob, gender, address, timestamps
- **consultations:** id, patient_id, session_id, message, intent, response, timestamps
- **triage_logs:** id, patient_id, symptoms (JSON), severity, confidence, recommendation, timestamps

---

## 📝 License

MIT License with healthcare compliance terms. See [LICENSE.md](../LICENSE.md).

---

## 🤝 Contributing

See [CONTRIBUTING.md](../CONTRIBUTING.md) for guidelines.

Code of Conduct: [CODE_OF_CONDUCT.md](../CODE_OF_CONDUCT.md)

---

**Status:** ✅ Production-Ready (April 8, 2026)  
**Deployment:** Friday, April 11, 2026 (3-phase rollout)  
**Tests:** 45+ passing  
**Coverage:** 80%+
