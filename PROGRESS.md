# PROGRESS.md — Smart Healthcare AI
> Source of truth untuk status development. Update setiap task selesai.

Last updated: [ISI TANGGAL & WAKTU SETIAP UPDATE]
Current Phase: **Phase 1 — Foundation**
Overall Progress: 0 / 32 hari

---

## Phase 1 — Foundation (Hari 1–6)
**Agent:** @foundation-agent | **Status:** 🔴 NOT STARTED

- [ ] `composer create-project laravel/laravel smart-health-ai`
- [ ] `.env` configured — DB_DATABASE, DB_USERNAME, DB_PASSWORD
- [ ] Migration: `create_patients_table`
- [ ] Migration: `create_consultations_table`
- [ ] Migration: `create_triage_logs_table`
- [ ] `app/Models/Patient.php` + relasi
- [ ] `app/Models/Consultation.php` + relasi
- [ ] `app/Models/TriageLog.php` + relasi
- [ ] `GET /api/health` endpoint
- [ ] `app/Services/PatientService.php`
- [ ] `app/Http/Controllers/Api/PatientController.php`
- [ ] `app/Http/Requests/StorePatientRequest.php`
- [ ] `app/Http/Requests/UpdatePatientRequest.php`
- [ ] `app/Http/Resources/PatientResource.php`
- [ ] JWT Auth — install + configure `tymon/jwt-auth`
- [ ] `POST /api/auth/login` + `POST /api/auth/register`
- [ ] `tests/Feature/PatientApiTest.php` — semua passing
- [ ] **QA Review:** ⬜ PENDING

**Blockers:** none

---

## Phase 2 — Data Engineering (Hari 7–12)
**Agent:** @data-agent | **Status:** ⏸️ WAITING (Phase 1)

- [ ] `storage/app/datasets/triage_rules.json` (min 35 rules)
- [ ] `storage/app/datasets/intents.json` (5 intents, min 10 keywords each)
- [ ] `storage/app/datasets/responses.json` (template per intent)
- [ ] `storage/app/datasets/triage_dataset.csv` (200+ rows)
- [ ] `storage/app/datasets/intent_dataset.csv` (100+ rows)
- [ ] Validasi JSON valid + coverage semua severity
- [ ] **QA Review:** ⬜ PENDING

**Blockers:** Phase 1 harus selesai

---

## Phase 3 — Triage Engine (Hari 13–18)
**Agent:** @triage-agent | **Status:** ⏸️ WAITING (Phase 2)

- [ ] `app/Enums/SeverityLevel.php`
- [ ] `app/Services/TriageService.php`
- [ ] `app/Http/Controllers/Api/TriageController.php`
- [ ] `app/Http/Requests/TriageRequest.php`
- [ ] `app/Http/Resources/TriageResultResource.php`
- [ ] `tests/Unit/TriageServiceTest.php` (min 6 test cases)
- [ ] `tests/Feature/TriageApiTest.php` (min 4 test cases)
- [ ] `php artisan test --filter TriageServiceTest` → ALL PASS
- [ ] **QA Review:** ⬜ PENDING

**Blockers:** `triage_rules.json` dari Phase 2

---

## Phase 4 — Chatbot Engine (Hari 19–25)
**Agent:** @chatbot-agent | **Status:** ⏸️ WAITING (Phase 3)

- [ ] `app/Enums/IntentType.php`
- [ ] `app/Services/IntentDetectorService.php`
- [ ] `app/Services/ChatbotService.php`
- [ ] `app/Http/Controllers/Api/ChatController.php`
- [ ] `app/Http/Requests/ChatRequest.php`
- [ ] `app/Http/Resources/ChatResponseResource.php`
- [ ] Session management (UUID)
- [ ] Save conversation ke `consultations` table
- [ ] `tests/Unit/IntentDetectorServiceTest.php`
- [ ] `tests/Unit/ChatbotServiceTest.php`
- [ ] `tests/Feature/ChatApiTest.php`
- [ ] `php artisan test --filter ChatApiTest` → ALL PASS
- [ ] **QA Review:** ⬜ PENDING

**Blockers:** `TriageService` dari Phase 3

---

## Phase 5 — Integration & Polish (Hari 26–32)
**Agent:** @devops-agent | **Status:** ⏸️ WAITING (Phase 4)

- [ ] `app/Exceptions/Handler.php` — global JSON error response
- [ ] `app/Http/Middleware/RequestLoggingMiddleware.php`
- [ ] JWT middleware applied ke semua protected routes
- [ ] Swagger annotations — PatientController
- [ ] Swagger annotations — TriageController
- [ ] Swagger annotations — ChatController
- [ ] `Dockerfile` (PHP 8.2 + Laravel)
- [ ] `docker-compose.yml` (app + mysql + nginx)
- [ ] `docker/nginx/default.conf`
- [ ] `README.md` — setup, endpoints, architecture ASCII
- [ ] `.env.example` — semua keys terdokumentasi
- [ ] Integration test: full flow chat → triage → log
- [ ] `docker compose up -d` → semua services UP
- [ ] **QA Review:** ⬜ PENDING

**Blockers:** Phase 4 harus selesai

---

## QA Review Log
| Feature | Agent | Tanggal | Decision | Notes |
|---------|-------|---------|---------|-------|
| - | - | - | - | - |

## Issue Log
| # | Issue | Phase | Status | Resolved By |
|---|-------|-------|--------|------------|
| - | - | - | - | - |
