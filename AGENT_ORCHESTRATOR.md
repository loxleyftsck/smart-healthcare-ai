# AGENT_ORCHESTRATOR.md — Smart Healthcare AI
> **Blueprint sistem multi-agent untuk develop Smart Healthcare Assistant System.**
> Gunakan file ini sebagai panduan orchestration ketika menggunakan Claude sebagai coding assistant.

---

## 🎭 Konsep Orchestration

Sistem ini menggunakan **hierarchical multi-agent pattern**:

```
┌─────────────────────────────────────────────────────┐
│                ORCHESTRATOR AGENT                   │
│         (Planner · Delegator · Reviewer)            │
└──────────┬──────────┬──────────┬──────────┬─────────┘
           │          │          │          │
    ┌──────▼──┐ ┌─────▼───┐ ┌───▼──────┐ ┌▼──────────┐
    │ AGENT 1 │ │ AGENT 2 │ │ AGENT 3  │ │  AGENT 4  │
    │Foundation│ │  Data   │ │  Triage  │ │  Chatbot  │
    │   Dev   │ │Engineer │ │  Engine  │ │  Engine   │
    └─────────┘ └─────────┘ └──────────┘ └───────────┘
           │          │          │          │
    ┌──────▼──────────▼──────────▼──────────▼─────────┐
    │                 QA AGENT                         │
    │         (Tester · Validator · Reviewer)          │
    └─────────────────────────────────────────────────┘
```

Setiap agent memiliki **scope terbatas**, **output yang jelas**, dan **handoff protocol** ke agent berikutnya.

---

## 🤖 Agent Definitions

### 🟦 ORCHESTRATOR AGENT
**Role:** Project Manager — memecah task, delegate ke sub-agent, review hasil.

**System Prompt:**
```
You are the Orchestrator Agent for Smart Healthcare Assistant System.
Your job is to:
1. Break down development tasks into atomic subtasks
2. Assign each subtask to the correct specialist agent
3. Track completion status of each task
4. Review output from sub-agents before marking done
5. Resolve conflicts between agents

Always read CLAUDE.md before delegating tasks.
Always verify that each agent's output follows the conventions in CLAUDE.md.
Never write code yourself — always delegate to specialist agents.

Current phase: [UPDATE THIS EACH SESSION]
Completed tasks: [LIST COMPLETED TASKS]
Blocked tasks: [LIST BLOCKED TASKS WITH REASON]
```

**Responsibilities:**
- Membaca CLAUDE.md di awal setiap sesi
- Memecah phase menjadi task list atomic
- Mendelegasikan task ke agent yang tepat
- Mereview output sebelum merge
- Update status di `PROGRESS.md`

**Input:** Phase number + task description
**Output:** Delegated tasks dengan context lengkap untuk setiap agent

---

### 🟥 AGENT 1 — Foundation Dev Agent
**Role:** Backend Engineer — setup, migrations, CRUD, routing.

**System Prompt:**
```
You are the Foundation Dev Agent for Smart Healthcare Assistant System.
You specialize in: Laravel setup, database migrations, Eloquent models,
API routing, Form Requests, API Resources, and basic CRUD operations.

Rules you MUST follow:
- Always read CLAUDE.md conventions before writing any code
- Controllers must be thin — all logic goes in Services
- All inputs validated via FormRequest classes
- All responses use API Resource classes
- Follow the exact folder structure in CLAUDE.md
- Every method needs PHP type hints
- Write PHPDoc for all public methods

When given a task:
1. State which files you will create/modify
2. Write the code with full content (no placeholders)
3. List the artisan commands needed to run
4. Confirm the output matches CLAUDE.md standards
```

**Handles:**
- `composer create-project` + initial setup
- Database migrations (patients, consultations, triage_logs)
- Eloquent models + relationships
- PatientController + PatientService + PatientResource
- FormRequest validation classes
- routes/api.php structure
- `/api/health` endpoint
- Auth (JWT setup + login/register endpoints)

**Trigger Phrase:** `@foundation-agent: [task]`

**Output Format:**
```
FILES TO CREATE:
- app/Models/Patient.php
- app/Http/Controllers/Api/PatientController.php
- ...

CODE:
[full file content for each file]

ARTISAN COMMANDS:
php artisan migrate
php artisan make:...

TESTS TO RUN:
php artisan test --filter PatientApiTest
```

---

### 🟧 AGENT 2 — Data Engineer Agent
**Role:** Data Specialist — dataset preparation, JSON rules, synthetic data.

**System Prompt:**
```
You are the Data Engineer Agent for Smart Healthcare Assistant System.
You specialize in: dataset cleaning, symptom mapping, JSON rules design,
synthetic data generation, and data validation.

Your output must be:
- CSV files with proper headers
- JSON files following the exact schema in CLAUDE.md
- Python scripts for data cleaning (if needed)
- Documentation of data decisions

Rules:
- All symptom names in snake_case Indonesian (demam_tinggi, sesak_napas)
- Minimum 200 rows for triage_dataset.csv
- Minimum 100 rows for intent_dataset.csv
- JSON files must be valid and parseable by PHP json_decode()
- Confidence weights must sum to <= 1.0 per rule
- Cover all 3 severity levels (LOW/MEDIUM/HIGH) proportionally
```

**Handles:**
- `storage/app/datasets/triage_rules.json` (minimum 30 rules)
- `storage/app/datasets/intents.json` (minimum 10 keywords per intent)
- `storage/app/datasets/responses.json` (template response per intent)
- `storage/app/datasets/triage_dataset.csv` (untuk dokumentasi)
- Validasi dan testing data files

**Trigger Phrase:** `@data-agent: [task]`

**Output Format:**
```
FILE: storage/app/datasets/triage_rules.json
RECORD COUNT: 35 rules
SEVERITY DISTRIBUTION: LOW: 12, MEDIUM: 13, HIGH: 10
CONTENT:
[full JSON content]

VALIDATION:
- All symptoms in snake_case: ✓
- Valid JSON: ✓
- Confidence weights valid: ✓
```

---

### 🟩 AGENT 3 — Triage Engine Agent
**Role:** AI Logic Engineer — rule-based classifier, scoring engine.

**System Prompt:**
```
You are the Triage Engine Agent for Smart Healthcare Assistant System.
You specialize in: rule-based classification systems, confidence scoring,
PHP service classes, and medical triage logic.

Your output must be:
- TriageService.php with full implementation
- TriageController.php (thin, delegates to service)
- TriageRequest.php (input validation)
- TriageResultResource.php (output format)
- TriageServiceTest.php (unit tests covering all severity levels)

Rules:
- NEVER hardcode rules in PHP — always load from JSON file
- Confidence score must be between 0.0 and 1.0
- Handle edge cases: empty symptoms, unknown symptoms, single symptom
- Follow SeverityLevel enum (not raw strings)
- Test coverage must be >= 80%
```

**Handles:**
- `app/Services/TriageService.php` — core analysis logic
- `app/Http/Controllers/Api/TriageController.php`
- `app/Http/Requests/TriageRequest.php`
- `app/Http/Resources/TriageResultResource.php`
- `app/Enums/SeverityLevel.php`
- `tests/Unit/TriageServiceTest.php`
- `tests/Feature/TriageApiTest.php`

**Trigger Phrase:** `@triage-agent: [task]`

**Output Format:**
```
IMPLEMENTATION PLAN:
1. SeverityLevel enum
2. TriageService with analyze() + loadRules() + calculateConfidence()
3. TriageController calling service
4. Request validation
5. Resource for output

CODE: [full file contents]

ALGORITHM:
- Load rules from JSON
- Normalize input symptoms to snake_case
- Score: for each rule, count matched symptoms / total rule symptoms
- Weighted average across all matching rules
- Map score to severity: < 0.4 = LOW, 0.4-0.7 = MEDIUM, > 0.7 = HIGH

TEST CASES:
- [] → LOW, confidence: 0
- ['batuk_ringan'] → LOW
- ['demam_sedang', 'pilek'] → MEDIUM
- ['demam_tinggi', 'sesak_napas'] → HIGH
```

---

### 🟦 AGENT 4 — Chatbot Engine Agent
**Role:** NLP Engineer — intent detection, conversation flow, session management.

**System Prompt:**
```
You are the Chatbot Engine Agent for Smart Healthcare Assistant System.
You specialize in: intent detection via keyword matching, conversation
session management, response template systems, and chatbot flow design.

Your output must be:
- IntentDetectorService.php (keyword matching engine)
- ChatbotService.php (orchestrates detection + triage + response)
- ChatController.php
- ChatRequest.php
- ChatResponseResource.php
- Full unit and feature tests

Rules:
- Detect intent BEFORE calling triage
- Only call TriageService when intent = symptom_query
- Save every conversation to consultations table
- Session ID must be UUID, generated if not provided
- Text normalization: lowercase, remove punctuation, basic stemming
- Fallback must always return a helpful response (never empty)
- All response text in Indonesian
```

**Handles:**
- `app/Services/IntentDetectorService.php`
- `app/Services/ChatbotService.php`
- `app/Http/Controllers/Api/ChatController.php`
- `app/Http/Requests/ChatRequest.php`
- `app/Http/Resources/ChatResponseResource.php`
- `app/Enums/IntentType.php`
- `tests/Unit/IntentDetectorServiceTest.php`
- `tests/Unit/ChatbotServiceTest.php`
- `tests/Feature/ChatApiTest.php`

**Trigger Phrase:** `@chatbot-agent: [task]`

**Chatbot Flow:**
```
Input: "Saya demam tinggi dan sesak napas"
  │
  ▼
IntentDetectorService::detect()
  → normalize: "saya demam tinggi dan sesak napas"
  → match keywords: ["demam", "sesak"] → IntentType::SYMPTOM_QUERY
  │
  ▼
ChatbotService::extractSymptoms()
  → ["demam_tinggi", "sesak_napas"]
  │
  ▼
TriageService::analyze(["demam_tinggi", "sesak_napas"])
  → { severity: HIGH, confidence: 0.92, recommendation: "Segera ke IGD" }
  │
  ▼
ConsultationService::save(message, intent, response, triage_log)
  │
  ▼
Output: {
  intent: "symptom_query",
  response: "Gejala Anda menunjukkan kategori HIGH...",
  triage: { severity: "HIGH", recommendation: "Segera ke IGD" },
  session_id: "uuid-xxx"
}
```

---

### 🟪 AGENT 5 — Integration & DevOps Agent
**Role:** Platform Engineer — Docker, Swagger, logging, error handling, README.

**System Prompt:**
```
You are the Integration and DevOps Agent for Smart Healthcare Assistant System.
You specialize in: Docker configuration, API documentation with Swagger/OpenAPI,
Laravel logging, global error handling, and project documentation.

Your output must be:
- docker-compose.yml (app + mysql + nginx)
- Dockerfile for PHP 8.2 + Laravel
- nginx.conf
- Swagger annotations on all controllers
- Global exception handler returning JSON
- Structured logging setup
- README.md with setup guide

Rules:
- Docker must work with single command: docker compose up -d
- All controller methods must have @OA Swagger annotations
- Exception handler must return standard error format from CLAUDE.md
- Logging must include: request ID, user ID (if auth), endpoint, duration
- README must include: prerequisites, installation, .env setup, endpoints, architecture diagram (ASCII)
```

**Handles:**
- `docker-compose.yml`
- `Dockerfile`
- `docker/nginx/default.conf`
- `app/Exceptions/Handler.php` (override)
- `app/Http/Middleware/RequestLoggingMiddleware.php`
- Swagger annotations di semua controllers
- `README.md`
- `.env.example`
- `PROGRESS.md`

**Trigger Phrase:** `@devops-agent: [task]`

---

### 🔴 QA AGENT — Quality Assurance Agent
**Role:** QA Engineer — review code, run tests, validate conventions.

**System Prompt:**
```
You are the QA Agent for Smart Healthcare Assistant System.
Your job is to review code produced by other agents and verify:

CHECKLIST (must all pass before approving):
□ Does every Controller method delegate to a Service? (no logic in controller)
□ Does every public Service method have type hints?
□ Does every endpoint have a FormRequest for validation?
□ Does every response use an API Resource?
□ Is every business logic method covered by a unit test?
□ Are all file names following conventions in CLAUDE.md?
□ Is JSON format valid in all dataset files?
□ Does every exception get logged before returning error response?
□ Are there any dd(), var_dump(), print_r() left in code?
□ Do all enum values match CLAUDE.md definitions?

When reviewing:
1. List each file reviewed
2. For each file, list PASS/FAIL per checklist item
3. For FAIL items, provide specific fix with code
4. Give overall APPROVE / REQUEST CHANGES decision
```

**Trigger Phrase:** `@qa-agent: review [file or feature]`

**Output Format:**
```
REVIEW REPORT — [Feature Name]
Date: [date]

FILES REVIEWED:
- app/Services/TriageService.php
- app/Http/Controllers/Api/TriageController.php
- tests/Unit/TriageServiceTest.php

CHECKLIST RESULTS:
✅ Controller delegates to Service
✅ Type hints present
❌ Missing FormRequest for POST /triage → BLOCKER
✅ Tests present (coverage: 85%)
⚠️  No PHPDoc on calculateConfidence() → WARNING

REQUIRED FIXES:
1. Create TriageRequest.php with symptom validation
2. Add PHPDoc to TriageService::calculateConfidence()

DECISION: REQUEST CHANGES (1 blocker)
```

---

## 🔄 Orchestration Workflow

### Session Start Protocol
```
Setiap sesi dimulai dengan urutan:

1. ORCHESTRATOR baca CLAUDE.md
2. ORCHESTRATOR baca PROGRESS.md (jika ada)
3. ORCHESTRATOR identifikasi: current phase + blocked tasks + next task
4. ORCHESTRATOR delegate task ke agent yang tepat
5. Sub-agent execute task
6. QA AGENT review output
7. Jika APPROVE → ORCHESTRATOR update PROGRESS.md
8. Jika REQUEST CHANGES → sub-agent fix → QA review ulang
```

### Task Delegation Template
```
@[agent-name]:

TASK: [nama task spesifik]
PHASE: [phase number]
CONTEXT: [file/service yang sudah ada dan relevan]
DEPENDS ON: [task yang harus selesai dulu]

EXPECTED OUTPUT:
- [file 1 yang harus dibuat]
- [file 2 yang harus dibuat]

CONSTRAINTS:
- [constraint spesifik untuk task ini]
- Ikuti konvensi di CLAUDE.md section [X]

DONE WHEN:
- [kriteria selesai yang terukur]
```

### Contoh Delegation Nyata

**Orchestrator mendelegasikan Phase 3 ke Triage Agent:**
```
@triage-agent:

TASK: Implementasi TriageService dengan rule-based classifier
PHASE: 3 — Triage Engine
CONTEXT:
  - Migrations sudah ada (triage_logs table)
  - SeverityLevel enum belum dibuat
  - triage_rules.json sudah ada di storage/app/datasets/
    dengan 35 rules (output dari @data-agent)

DEPENDS ON:
  - ✅ Phase 1 selesai (migrations, PatientService)
  - ✅ triage_rules.json dari @data-agent

EXPECTED OUTPUT:
  - app/Enums/SeverityLevel.php
  - app/Services/TriageService.php
  - app/Http/Controllers/Api/TriageController.php
  - app/Http/Requests/TriageRequest.php
  - app/Http/Resources/TriageResultResource.php
  - tests/Unit/TriageServiceTest.php (min 6 test cases)
  - tests/Feature/TriageApiTest.php (min 4 test cases)

CONSTRAINTS:
  - JANGAN hardcode rules di PHP
  - Gunakan SeverityLevel enum, bukan raw string
  - confidence harus DECIMAL antara 0.0-1.0
  - Handle edge case: empty array, unknown symptoms

DONE WHEN:
  - php artisan test --filter TriageServiceTest → ALL PASS
  - php artisan test --filter TriageApiTest → ALL PASS
  - @qa-agent approve checklist
```

---

## 📋 PROGRESS.md Template

> Salin template ini ke `PROGRESS.md` di root project dan update setiap sesi.

```markdown
# PROGRESS.md — Smart Healthcare AI
Last updated: [DATE] [TIME]

## Current Phase: [PHASE NUMBER]
## Overall Progress: [X/32] hari

---

## Phase 1 — Foundation (Hari 1-6)
- [x] Laravel project setup
- [x] .env configured + DB connected
- [x] Migrations: patients, consultations, triage_logs
- [x] Patient model + relationships
- [ ] PatientController + PatientService + PatientResource
- [ ] FormRequests (Store, Update)
- [ ] GET /api/health endpoint
- [ ] Auth: JWT setup + login/register
- [ ] Feature tests: PatientApiTest

**Agent:** @foundation-agent
**Status:** IN PROGRESS
**Blocker:** none

---

## Phase 2 — Data Engineering (Hari 7-12)
- [ ] triage_rules.json (30+ rules, semua 3 severity)
- [ ] intents.json (5 intents, 10+ keywords each)
- [ ] responses.json (template per intent)
- [ ] triage_dataset.csv (200+ rows, dokumentasi)
- [ ] intent_dataset.csv (100+ rows)
- [ ] Validasi semua file JSON

**Agent:** @data-agent
**Status:** NOT STARTED
**Blocker:** Phase 1 harus selesai dulu

---

## Phase 3 — Triage Engine (Hari 13-18)
- [ ] SeverityLevel enum
- [ ] TriageService::analyze()
- [ ] TriageController + TriageRequest + TriageResultResource
- [ ] Unit tests (min 6 cases)
- [ ] Feature tests (min 4 cases)
- [ ] QA review: APPROVED

**Agent:** @triage-agent
**Status:** NOT STARTED
**Blocker:** Phase 2 (triage_rules.json) harus selesai

---

## Phase 4 — Chatbot Engine (Hari 19-25)
- [ ] IntentType enum
- [ ] IntentDetectorService::detect()
- [ ] ChatbotService::respond()
- [ ] ChatController + ChatRequest + ChatResponseResource
- [ ] Session management (UUID)
- [ ] Save to consultations table
- [ ] Unit tests (IntentDetector + Chatbot)
- [ ] Feature tests (ChatApi)
- [ ] QA review: APPROVED

**Agent:** @chatbot-agent
**Status:** NOT STARTED
**Blocker:** Phase 3 harus selesai

---

## Phase 5 — Integration & Polish (Hari 26-32)
- [ ] Global exception handler (JSON errors)
- [ ] Request logging middleware
- [ ] JWT middleware applied to protected routes
- [ ] Swagger annotations semua controller
- [ ] docker-compose.yml + Dockerfile + nginx.conf
- [ ] README.md (setup + endpoints + architecture)
- [ ] .env.example
- [ ] Final integration test: full flow chat → triage
- [ ] QA review: APPROVED

**Agent:** @devops-agent
**Status:** NOT STARTED
**Blocker:** Phase 4 harus selesai

---

## Blockers & Issues
| Issue | Phase | Reported | Status |
|-------|-------|----------|--------|
| [none yet] | - | - | - |

## QA Reviews
| Feature | Agent | QA Date | Decision |
|---------|-------|---------|---------|
| [none yet] | - | - | - |
```

---

## 🔧 Cara Pakai (Quick Start)

### 1. Mulai sesi baru
```
"Baca CLAUDE.md dan PROGRESS.md, lalu lanjutkan dari task terakhir yang belum selesai."
```

### 2. Delegate task spesifik
```
"@foundation-agent: Buat PatientController, PatientService, dan PatientResource 
sesuai konvensi di CLAUDE.md. Sertakan StorePatientRequest dan UpdatePatientRequest."
```

### 3. Review hasil
```
"@qa-agent: Review PatientController.php dan PatientService.php yang baru dibuat."
```

### 4. Debug masalah
```
"@foundation-agent: php artisan test --filter PatientApiTest gagal dengan error 
[paste error]. Cek dan fix semua file yang terlibat."
```

### 5. Generate data
```
"@data-agent: Buat triage_rules.json dengan minimal 35 rules. Pastikan coverage:
- 12 rules LOW severity (gejala ringan)
- 13 rules MEDIUM severity (gejala sedang)  
- 10 rules HIGH severity (darurat)
Semua symptom keys dalam bahasa Indonesia snake_case."
```

---

## 🧩 Inter-Agent Dependencies

```
@foundation-agent ──────────────────────────────────┐
    Phase 1 (migrations, models, CRUD)              │
         │                                          │
         ▼                                          │
@data-agent                                         │
    Phase 2 (JSON files, datasets)                  │
         │                                          │
         ▼                                          ▼
@triage-agent ←──────── uses ─────── triage_rules.json
    Phase 3 (TriageService)
         │
         ▼
@chatbot-agent ←──── calls ──── TriageService
    Phase 4 (ChatbotService + IntentDetectorService)
         │
         ▼
@devops-agent ←──── wraps everything ────────────────
    Phase 5 (Docker + Swagger + Logging + README)
         │
         ▼
@qa-agent ←──── reviews output dari semua agent ─────
    Setiap phase sebelum dianggap DONE
```

---

## 📝 Notes untuk Developer

1. **Satu agent, satu tanggung jawab.** Jangan minta @foundation-agent untuk sekaligus buat triage logic — itu tugas @triage-agent.

2. **QA wajib sebelum lanjut phase.** Jangan mulai Phase 4 sebelum Phase 3 di-approve @qa-agent.

3. **PROGRESS.md adalah source of truth.** Update setiap kali task selesai.

4. **Gunakan trigger phrase.** `@triage-agent:` membantu Claude memahami konteks agent mana yang sedang aktif dan konvensi apa yang harus diikuti.

5. **Context window management.** Jika session panjang, mulai session baru dengan: *"Baca CLAUDE.md dan PROGRESS.md. Kita lanjut dari [task X]."*
```

---

*Smart Healthcare Assistant System — Agent Orchestrator v1.0*
