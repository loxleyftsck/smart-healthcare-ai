# 🏥 Smart Healthcare Assistant System

> An AI-powered healthcare assistant backend built with **Laravel 11**, featuring rule-based triage classification, intelligent chatbot with intent detection, JWT authentication, and full RESTful API — production-ready in 32 days.

![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=flat-square&logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-11.x-FF2D20?style=flat-square&logo=laravel&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat-square&logo=mysql&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?style=flat-square&logo=docker&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)

---

## 📋 Table of Contents

- [Overview](#-overview)
- [Architecture](#-architecture)
- [Features](#-features)
- [Prerequisites](#-prerequisites)
- [Installation](#-installation)
- [Environment Setup](#-environment-setup)
- [Database Setup](#-database-setup)
- [Running the App](#-running-the-app)
- [Docker Setup](#-docker-setup)
- [API Endpoints](#-api-endpoints)
- [Request & Response Format](#-request--response-format)
- [Triage System](#-triage-system)
- [Chatbot System](#-chatbot-system)
- [Testing](#-testing)
- [Project Roadmap](#-project-roadmap)
- [Contributing](#-contributing)

---

## 🧠 Overview

Smart Healthcare Assistant System is a **portfolio AI application** built to demonstrate production-grade backend engineering skills. It provides:

- 🤖 **AI Triage Engine** — Rule-based symptom classifier with confidence scoring
- 💬 **Chatbot** — Intent-aware conversational assistant in Indonesian
- 🔐 **JWT Auth** — Secure token-based authentication
- 📊 **RESTful API** — Fully documented with Swagger/OpenAPI
- 🐳 **Dockerized** — Single-command deployment

---

## 🏗 Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                          HTTP Request                           │
└──────────────────────────┬──────────────────────────────────────┘
                           │
                    routes/api.php
                           │
           ┌───────────────▼───────────────┐
           │   Http/Controllers/Api/       │
           │  (thin — delegates only)      │
           └───────────────┬───────────────┘
                           │
           ┌───────────────▼───────────────┐
           │   Http/Requests/ (validate)   │
           └───────────────┬───────────────┘
                           │
           ┌───────────────▼───────────────┐
           │      Services/ (all logic)    │
           │  ┌──────────────────────────┐ │
           │  │ PatientService           │ │
           │  │ TriageService            │ │
           │  │ ChatbotService           │ │
           │  │ IntentDetectorService    │ │
           │  │ ConsultationService      │ │
           │  └──────────────────────────┘ │
           └───────────────┬───────────────┘
                           │
           ┌───────────────▼───────────────┐
           │      Models/ (Eloquent ORM)   │
           │  Patient · Consultation       │
           │  TriageLog                    │
           └───────────────┬───────────────┘
                           │
           ┌───────────────▼───────────────┐
           │         MySQL 8.0             │
           └───────────────────────────────┘

Data Files (storage/app/datasets/):
  triage_rules.json  ←  rule-based classifier data
  intents.json       ←  keyword → intent mapping
  responses.json     ←  intent → response templates
```

### Multi-Agent Development System

This project was architected using a **hierarchical multi-agent pattern**:

```
ORCHESTRATOR AGENT
  ├── @foundation-agent   → Phase 1: Laravel setup, migrations, CRUD
  ├── @data-agent         → Phase 2: JSON rules, datasets
  ├── @triage-agent       → Phase 3: Triage engine, confidence scoring
  ├── @chatbot-agent      → Phase 4: Intent detection, conversation flow
  ├── @devops-agent       → Phase 5: Docker, Swagger, logging
  └── @qa-agent           → QA review every phase before merge
```

---

## ✨ Features

| Feature | Status |
|---------|--------|
| Patient CRUD API | 🔄 Phase 1 |
| JWT Authentication | 🔄 Phase 1 |
| Rule-based Triage Engine | ⏸ Phase 3 |
| Intent Detection Chatbot | ⏸ Phase 4 |
| Swagger / OpenAPI Docs | ⏸ Phase 5 |
| Docker Deployment | ⏸ Phase 5 |
| Request Logging Middleware | ⏸ Phase 5 |

---

## ✅ Prerequisites

| Requirement | Version |
|-------------|---------|
| PHP | `^8.2` |
| Composer | `^2.x` |
| MySQL | `^8.0` |
| Node.js (optional, for docs) | `^18.x` |
| Docker (optional) | `^24.x` |

---

## 🚀 Installation

### 1. Clone the repository

```bash
git clone https://github.com/your-username/smart-health-ai.git
cd smart-health-ai
```

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Copy environment file

```bash
cp .env.example .env
```

### 4. Generate application key

```bash
php artisan key:generate
```

### 5. Generate JWT secret

```bash
php artisan jwt:secret
```

---

## ⚙️ Environment Setup

Edit `.env` with your local credentials:

```env
APP_NAME="Smart Health AI"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=smart_health_ai
DB_USERNAME=root
DB_PASSWORD=your_password_here

JWT_SECRET=           # auto-generated by php artisan jwt:secret
JWT_TTL=60            # token lifetime in minutes

LOG_CHANNEL=stack
LOG_LEVEL=debug

TRIAGE_RULES_PATH=datasets/triage_rules.json
INTENTS_PATH=datasets/intents.json
RESPONSES_PATH=datasets/responses.json
```

---

## 🗄️ Database Setup

### Create the database

```sql
CREATE DATABASE smart_health_ai CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Run migrations

```bash
php artisan migrate
```

### (Optional) Seed with sample data

```bash
php artisan db:seed
```

---

## ▶️ Running the App

```bash
php artisan serve
```

API will be available at: `http://localhost:8000/api`

Health check: `GET http://localhost:8000/api/health`

---

## 🐳 Docker Setup

> Requires Docker and Docker Compose installed.

```bash
# Start all services (app + mysql + nginx)
docker compose up -d

# Run migrations inside container
docker compose exec app php artisan migrate

# Generate JWT secret
docker compose exec app php artisan jwt:secret
```

Services:
| Service | Port |
|---------|------|
| Laravel App (nginx) | `http://localhost:80` |
| MySQL | `localhost:3306` |

---

## 📡 API Endpoints

### Public Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/health` | API health status |
| `POST` | `/api/auth/register` | Register new patient |
| `POST` | `/api/auth/login` | Login → JWT token |

### Protected Endpoints (JWT Required)

> Add `Authorization: Bearer {token}` header.

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/patients` | List all patients (paginated) |
| `POST` | `/api/patients` | Create new patient |
| `GET` | `/api/patients/{id}` | Get patient detail |
| `PUT` | `/api/patients/{id}` | Update patient |
| `DELETE` | `/api/patients/{id}` | Delete patient |
| `POST` | `/api/triage` | AI triage symptom analysis |
| `POST` | `/api/chat` | Send chatbot message |
| `GET` | `/api/consultations` | List consultation history |
| `GET` | `/api/consultations/{id}` | Get consultation detail |

---

## 📦 Request & Response Format

### Success Response
```json
{
  "success": true,
  "message": "Operation successful",
  "data": { "..." },
  "meta": {
    "timestamp": "2025-01-01T00:00:00Z"
  }
}
```

### Error Response
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."]
  },
  "meta": {
    "timestamp": "2025-01-01T00:00:00Z"
  }
}
```

### Paginated Response (list endpoints)
```json
{
  "success": true,
  "message": "Patients retrieved",
  "data": [ "...items..." ],
  "meta": {
    "timestamp": "2025-01-01T00:00:00Z",
    "pagination": {
      "current_page": 1,
      "per_page": 15,
      "total": 42,
      "last_page": 3
    }
  }
}
```

---

## 🩺 Triage System

The triage engine uses a **rule-based classifier** loaded from `storage/app/datasets/triage_rules.json`.

### How It Works

```
Input symptoms: ["demam_tinggi", "sesak_napas"]
       │
       ▼
Load rules from triage_rules.json
       │
       ▼
For each rule: count symptom matches
Score = matched_symptoms / total_rule_symptoms
       │
       ▼
Weighted average confidence across matching rules
       │
       ▼
Severity mapping:
  score < 0.4  → LOW    (rest, hydration)
  0.4 – 0.7   → MEDIUM (visit clinic)
  score > 0.7  → HIGH   (go to ER)
```

### Example Request

```bash
POST /api/triage
Authorization: Bearer {token}
Content-Type: application/json

{
  "symptoms": ["demam_tinggi", "sesak_napas", "nyeri_dada"]
}
```

### Example Response

```json
{
  "success": true,
  "message": "Triage analysis complete",
  "data": {
    "severity": "HIGH",
    "confidence": 0.92,
    "recommendation": "Segera ke IGD — kombinasi gejala ini memerlukan penanganan darurat",
    "matched_symptoms": ["demam_tinggi", "sesak_napas"]
  }
}
```

---

## 💬 Chatbot System

The chatbot detects **5 intents** via keyword matching:

| Intent | Trigger Keywords |
|--------|-----------------|
| `greeting` | halo, hai, selamat pagi, hi... |
| `symptom_query` | sakit, demam, batuk, sesak, nyeri... |
| `schedule` | jadwal, booking, daftar, antri... |
| `emergency` | darurat, pingsan, tidak bisa napas... |
| `fallback` | (default when no match) |

### Chatbot Flow

```
User: "Saya demam tinggi dan sesak napas"
  │
  ▼
IntentDetectorService::detect()
  → normalize → keyword match → IntentType::SYMPTOM_QUERY
  │
  ▼
ChatbotService::extractSymptoms()
  → ["demam_tinggi", "sesak_napas"]
  │
  ▼
TriageService::analyze(symptoms)
  → { severity: HIGH, confidence: 0.92 }
  │
  ▼
ConsultationService::save(message, intent, response, triage_log)
  │
  ▼
Response: "Gejala Anda termasuk kategori HIGH. Segera ke IGD!"
```

### Example Request

```bash
POST /api/chat
Authorization: Bearer {token}
Content-Type: application/json

{
  "message": "Saya demam tinggi dan sesak napas",
  "session_id": "optional-uuid-here"
}
```

---

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Run specific test suites
php artisan test --filter PatientApiTest
php artisan test --filter TriageServiceTest
php artisan test --filter ChatApiTest

# With coverage report
php artisan test --coverage
```

### Test Coverage Targets

| Suite | Min Coverage |
|-------|-------------|
| `TriageServiceTest` | 80% |
| `IntentDetectorServiceTest` | 80% |
| `PatientApiTest` | 100% endpoints |
| `ChatApiTest` | 100% endpoints |

---

## 🗺️ Project Roadmap

```
Phase 1 — Foundation (Days 1–6)      🔄 IN PROGRESS
  └── Laravel setup, migrations, Patient CRUD, JWT Auth

Phase 2 — Data Engineering (Days 7–12)   ⏸ NOT STARTED
  └── triage_rules.json, intents.json, responses.json, datasets

Phase 3 — Triage Engine (Days 13–18)     ⏸ NOT STARTED
  └── TriageService, confidence scoring, unit/feature tests

Phase 4 — Chatbot Engine (Days 19–25)    ⏸ NOT STARTED
  └── IntentDetectorService, ChatbotService, session management

Phase 5 — Integration & Polish (Days 26–32)  ⏸ NOT STARTED
  └── Docker, Swagger, logging middleware, README final
```

---

## 📁 Project Structure

```
smart-health-ai/
├── app/
│   ├── Enums/
│   │   ├── SeverityLevel.php
│   │   └── IntentType.php
│   ├── Exceptions/
│   │   └── Handler.php
│   ├── Http/
│   │   ├── Controllers/Api/
│   │   │   ├── AuthController.php
│   │   │   ├── ChatController.php
│   │   │   ├── ConsultationController.php
│   │   │   ├── HealthController.php
│   │   │   ├── PatientController.php
│   │   │   └── TriageController.php
│   │   ├── Middleware/
│   │   │   └── RequestLoggingMiddleware.php
│   │   ├── Requests/
│   │   │   ├── Auth/
│   │   │   ├── ChatRequest.php
│   │   │   ├── StorePatientRequest.php
│   │   │   ├── TriageRequest.php
│   │   │   └── UpdatePatientRequest.php
│   │   └── Resources/
│   │       ├── ChatResponseResource.php
│   │       ├── PatientResource.php
│   │       └── TriageResultResource.php
│   ├── Models/
│   │   ├── Consultation.php
│   │   ├── Patient.php
│   │   └── TriageLog.php
│   └── Services/
│       ├── ChatbotService.php
│       ├── ConsultationService.php
│       ├── IntentDetectorService.php
│       ├── PatientService.php
│       └── TriageService.php
├── database/
│   └── migrations/
├── docker/
│   └── nginx/default.conf
├── storage/app/datasets/
│   ├── intents.json
│   ├── responses.json
│   └── triage_rules.json
├── tests/
│   ├── Feature/
│   └── Unit/
├── docker-compose.yml
├── Dockerfile
├── .env.example
├── CLAUDE.md
├── AGENT_ORCHESTRATOR.md
└── PROGRESS.md
```

---

## 🤝 Contributing

This is a portfolio project. Agent-based development workflow:

1. Read `CLAUDE.md` and `PROGRESS.md` at the start of every session
2. Use trigger phrases to activate specialist agents (`@foundation-agent:`, `@triage-agent:`, etc.)
3. QA review required before marking any phase as DONE
4. Update `PROGRESS.md` after every completed task

---

## 📄 License

MIT License — see [LICENSE](LICENSE) for details.

---

*Smart Healthcare Assistant System — Built as a Portfolio AI Application*  
*Stack: Laravel 11 · PHP 8.2 · MySQL 8.0 · JWT · Docker · Swagger*
