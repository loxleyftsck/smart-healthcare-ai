# CLAUDE.md — Smart Healthcare Assistant System
> **Project memory file for Claude Code.**
> Baca file ini di awal setiap sesi sebelum menulis kode apapun.

---

## 🏥 Project Identity

| Key | Value |
|-----|-------|
| **Project Name** | Smart Healthcare Assistant System |
| **Stack** | Laravel 11 · PHP 8.2 · MySQL 8.0 |
| **Purpose** | Portfolio AI Application Engineer |
| **Target** | Production-ready dalam 32 hari |
| **Repo Root** | `smart-health-ai/` |

---

## 🧠 System Architecture

```
Request
  ↓
routes/api.php
  ↓
Http/Controllers/Api/{Controller}
  ↓
Http/Requests/{FormRequest}   ← validasi input
  ↓
Services/{Service}            ← SEMUA business logic di sini
  ↓
Models/{Model} ← Eloquent ORM
  ↓
MySQL Database
```

**Prinsip Utama:**
- Controller hanya memanggil Service — TIDAK boleh ada business logic di controller
- Service tidak boleh akses Request langsung — terima parameter biasa
- Model hanya berisi relasi, scope, dan cast — TIDAK ada logic
- Semua response menggunakan `Http/Resources/` (API Resource)

---

## 📁 Struktur Folder (Canonical)

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       ├── PatientController.php
│   │       ├── TriageController.php
│   │       ├── ChatController.php
│   │       └── ConsultationController.php
│   ├── Requests/
│   │   ├── StorePatientRequest.php
│   │   ├── UpdatePatientRequest.php
│   │   ├── TriageRequest.php
│   │   └── ChatRequest.php
│   ├── Resources/
│   │   ├── PatientResource.php
│   │   ├── TriageResultResource.php
│   │   └── ChatResponseResource.php
│   └── Middleware/
│       └── JwtAuthMiddleware.php
├── Models/
│   ├── Patient.php
│   ├── Consultation.php
│   └── TriageLog.php
├── Services/
│   ├── PatientService.php
│   ├── TriageService.php
│   ├── ChatbotService.php
│   ├── IntentDetectorService.php
│   └── ConsultationService.php
├── Enums/
│   ├── SeverityLevel.php      ← LOW, MEDIUM, HIGH
│   └── IntentType.php         ← greeting, symptom_query, schedule, emergency, fallback
└── Exceptions/
    └── Handler.php             ← global JSON error responses

storage/app/datasets/
├── triage_rules.json           ← rules engine data
├── intents.json                ← keyword → intent mapping
└── responses.json              ← intent → response template

database/migrations/
├── create_patients_table.php
├── create_consultations_table.php
└── create_triage_logs_table.php

tests/
├── Unit/
│   ├── TriageServiceTest.php
│   ├── IntentDetectorServiceTest.php
│   └── ChatbotServiceTest.php
└── Feature/
    ├── PatientApiTest.php
    ├── TriageApiTest.php
    └── ChatApiTest.php
```

---

## 🗄️ Database Schema

### `patients`
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
name            VARCHAR(255) NOT NULL
email           VARCHAR(255) UNIQUE NOT NULL
phone           VARCHAR(20) NULLABLE
date_of_birth   DATE NULLABLE
gender          ENUM('male','female','other') NULLABLE
address         TEXT NULLABLE
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

### `consultations`
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
patient_id      BIGINT UNSIGNED FK → patients.id
session_id      VARCHAR(36) NOT NULL   ← UUID
message         TEXT NOT NULL
intent          VARCHAR(50) NULLABLE
response        TEXT NOT NULL
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

### `triage_logs`
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
patient_id      BIGINT UNSIGNED FK → patients.id NULLABLE
consultation_id BIGINT UNSIGNED FK → consultations.id NULLABLE
symptoms        JSON NOT NULL
severity        ENUM('LOW','MEDIUM','HIGH') NOT NULL
confidence      DECIMAL(4,2) NOT NULL   ← 0.00 – 1.00
recommendation  TEXT NOT NULL
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

---

## 🔌 API Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/api/health` | ❌ | Status API |
| POST | `/api/auth/login` | ❌ | Login → JWT token |
| POST | `/api/auth/register` | ❌ | Register pasien baru |
| GET | `/api/patients` | ✅ | List pasien (paginated) |
| POST | `/api/patients` | ✅ | Tambah pasien |
| GET | `/api/patients/{id}` | ✅ | Detail pasien |
| PUT | `/api/patients/{id}` | ✅ | Update pasien |
| DELETE | `/api/patients/{id}` | ✅ | Hapus pasien |
| POST | `/api/triage` | ✅ | Klasifikasi severity |
| POST | `/api/chat` | ✅ | Kirim pesan chatbot |
| GET | `/api/consultations` | ✅ | History konsultasi |
| GET | `/api/consultations/{id}` | ✅ | Detail konsultasi |

**Standard Response Format:**
```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... },
  "meta": { "timestamp": "2025-01-01T00:00:00Z" }
}
```

**Error Response Format:**
```json
{
  "success": false,
  "message": "Error description",
  "errors": { "field": ["validation error"] },
  "meta": { "timestamp": "2025-01-01T00:00:00Z" }
}
```

---

## 🧩 Service Contracts

### `TriageService`
```php
// Input: array of symptom strings (bahasa Indonesia)
// Output: array dengan keys severity, confidence, recommendation, matched_symptoms
public function analyze(array $symptoms): array

// Load rules dari storage/app/datasets/triage_rules.json
private function loadRules(): array

// Hitung confidence score dari matched rules
private function calculateConfidence(array $matched, array $allRules): float
```

### `IntentDetectorService`
```php
// Input: string pesan dari user
// Output: IntentType enum value
public function detect(string $message): IntentType

// Normalize: lowercase, strip punctuation, stem kata dasar Indonesia
private function normalize(string $text): string
```

### `ChatbotService`
```php
// Input: message string, patient_id (nullable), session_id
// Output: array dengan keys intent, response, triage (nullable), session_id
public function respond(string $message, ?int $patientId, string $sessionId): array

// Jika intent = symptom_query, extract gejala dan panggil TriageService
private function extractSymptoms(string $message): array
```

---

## 📊 Data Files Schema

### `triage_rules.json`
```json
[
  {
    "id": "rule_001",
    "symptoms": ["demam_tinggi", "sesak_napas"],
    "severity": "HIGH",
    "confidence_weight": 0.9,
    "recommendation": "Segera ke IGD — kombinasi gejala ini memerlukan penanganan darurat"
  },
  {
    "id": "rule_002",
    "symptoms": ["batuk_ringan"],
    "severity": "LOW",
    "confidence_weight": 0.7,
    "recommendation": "Istirahat cukup dan minum air putih yang banyak"
  }
]
```

### `intents.json`
```json
{
  "greeting": ["halo", "hai", "selamat pagi", "selamat siang", "hei", "hi"],
  "symptom_query": ["sakit", "demam", "batuk", "sesak", "nyeri", "pusing", "gejala", "keluhan"],
  "schedule": ["jadwal", "booking", "daftar", "antri", "appointment", "reservasi"],
  "emergency": ["darurat", "parah sekali", "tidak bisa napas", "pingsan", "tidak sadarkan diri"],
  "fallback": []
}
```

### `responses.json`
```json
{
  "greeting": "Halo! Saya asisten kesehatan digital. Ada yang bisa saya bantu?",
  "schedule": "Untuk booking jadwal, silakan hubungi resepsionis di nomor 021-xxxx atau kunjungi langsung.",
  "emergency": "DARURAT — Segera hubungi 119 atau pergi ke IGD terdekat!",
  "fallback": "Maaf, saya tidak memahami pertanyaan Anda. Bisa ceritakan gejala yang Anda rasakan?"
}
```

---

## ⚙️ Coding Conventions

### PHP / Laravel
```php
// ✅ BENAR — type hints wajib di semua method
public function analyze(array $symptoms): array {}

// ✅ BENAR — gunakan Enum bukan string literal
$severity = SeverityLevel::HIGH;

// ✅ BENAR — early return pattern
public function find(int $id): Patient
{
    $patient = Patient::find($id);
    if (!$patient) {
        throw new ModelNotFoundException("Patient {$id} not found");
    }
    return $patient;
}

// ❌ SALAH — jangan taruh logic di controller
public function store(StorePatientRequest $request): JsonResponse
{
    // JANGAN: $patient = Patient::create([...]) — ini di controller
    // BENAR:
    $patient = $this->patientService->create($request->validated());
    return new JsonResponse(new PatientResource($patient), 201);
}
```

### Naming Conventions
| Type | Convention | Example |
|------|-----------|---------|
| Class | PascalCase | `TriageService` |
| Method | camelCase | `analyzeSymptoms()` |
| Variable | camelCase | `$patientId` |
| DB Column | snake_case | `date_of_birth` |
| Route | kebab-case | `/api/triage-logs` |
| JSON key | snake_case | `"confidence_score"` |
| Constant/Enum | SCREAMING_SNAKE | `SeverityLevel::HIGH` |

### File Naming
- Controller: `{Resource}Controller.php` (selalu plural resource)
- Service: `{Domain}Service.php`
- Request: `{Action}{Resource}Request.php`
- Resource: `{Resource}Resource.php`
- Test: `{Subject}Test.php`

---

## 🧪 Testing Standards

Setiap Service **wajib** punya Unit Test. Setiap endpoint **wajib** punya Feature Test.

```php
// Minimal test coverage per phase:
// Phase 1: PatientApiTest (CRUD semua endpoint)
// Phase 3: TriageServiceTest (LOW/MEDIUM/HIGH + edge cases)
// Phase 4: IntentDetectorServiceTest, ChatbotServiceTest
// Phase 5: Integration test full flow chat → triage

// Template Unit Test
class TriageServiceTest extends TestCase
{
    private TriageService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TriageService();
    }

    /** @test */
    public function it_returns_high_severity_for_critical_symptoms(): void
    {
        $result = $this->service->analyze(['demam_tinggi', 'sesak_napas']);
        $this->assertEquals('HIGH', $result['severity']);
        $this->assertGreaterThan(0.8, $result['confidence']);
    }
}
```

---

## 🔐 Environment Variables

```bash
# .env (wajib ada semua ini)
APP_NAME="Smart Health AI"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=smart_health_ai
DB_USERNAME=root
DB_PASSWORD=secret

JWT_SECRET=          # generate: php artisan jwt:secret
JWT_TTL=60           # menit

LOG_CHANNEL=stack
LOG_LEVEL=debug

TRIAGE_RULES_PATH=datasets/triage_rules.json
INTENTS_PATH=datasets/intents.json
RESPONSES_PATH=datasets/responses.json
```

---

## 🚫 Anti-Patterns (DILARANG)

```php
// ❌ Logic di Controller
public function store(Request $request) {
    $symptoms = explode(',', $request->symptoms);
    // ... logic panjang
}

// ❌ Raw query tanpa Eloquent
DB::statement("SELECT * FROM patients WHERE ...");

// ❌ Hardcode rules di PHP
if (in_array('demam_tinggi', $symptoms) && in_array('sesak_napas', $symptoms)) {
    return 'HIGH'; // JANGAN — harus dari JSON file
}

// ❌ Return array biasa dari controller (harus pakai Resource)
return response()->json(['data' => $patient]);

// ❌ Catch Exception tanpa log
try { ... } catch (Exception $e) { return false; }
```

---

## 📦 Composer Dependencies

```json
{
  "require": {
    "php": "^8.2",
    "laravel/framework": "^11.0",
    "tymon/jwt-auth": "^2.1",
    "darkaonline/l5-swagger": "^8.6"
  },
  "require-dev": {
    "fakerphp/faker": "^1.23",
    "phpunit/phpunit": "^11.0",
    "nunomaduro/collision": "^8.0"
  }
}
```

---

## 🐳 Docker Services

```yaml
# docker-compose.yml services:
# - app: PHP 8.2 + Laravel (port 8000)
# - mysql: MySQL 8.0 (port 3306, volume: mysql_data)
# - nginx: reverse proxy (port 80 → app:9000)
```

---

## ✅ Definition of Done (per Phase)

- [ ] Semua endpoint return response format standar
- [ ] Semua input tervalidasi via FormRequest
- [ ] Business logic ada di Service, bukan Controller
- [ ] Unit/Feature test tersedia dan passing
- [ ] Tidak ada `dd()`, `var_dump()`, `print_r()` tertinggal
- [ ] PHPDoc di semua public method Service
- [ ] Swagger annotation di semua Controller method
