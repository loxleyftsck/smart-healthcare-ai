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

---

## 🤝 Contributing

We welcome contributions from developers of all experience levels! 

- **[Contributing Guide](CONTRIBUTING.md)** - How to contribute
- **[Code of Conduct](CODE_OF_CONDUCT.md)** - Community standards
- **[Security Policy](SECURITY_POLICY.md)** - Report vulnerabilities
- **[Issue Templates](.github/ISSUE_TEMPLATE/)** - Report bugs/features

### Quick Contribution Steps

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/your-feature`
3. Make changes following [PSR-12](CONTRIBUTING.md#php-coding-style) standards
4. Add tests (80%+ coverage required)
5. Commit: `git commit -m "feat: description"`
6. Push and create a Pull Request

See [CONTRIBUTING.md](CONTRIBUTING.md) for detailed guidelines.

---

## 📜 License

This project is licensed under the **MIT License** with additional healthcare compliance terms.

- See [LICENSE.md](LICENSE.md) for full license text
- Healthcare deployments must comply with HIPAA/GDPR requirements
- No warranty provided - see disclaimer in LICENSE

---

## 🔐 Security

The Smart Healthcare AI takes security seriously.

- **Report vulnerabilities privately** → [SECURITY_POLICY.md](SECURITY_POLICY.md)
- **Email:** security@smarthealth-ai.io
- **Do NOT** create public issues for security vulnerabilities
- Response time: 24 hours for all security reports

---

## 🏥 Healthcare Compliance & Standards

Smart Healthcare AI is designed with healthcare interoperability and compliance at its core.

### Standards & Regulations
- **HL7 FHIR 4.0** - RESTful healthcare data exchange
- **HL7 v2** - Legacy system compatibility
- **Terminologies** - ICD-10, SNOMED CT, LOINC, CPT/HCPCS
- **HIPAA** - US Healthcare Privacy & Security Rule compliance
- **UU PDP Indonesia** - Indonesian data privacy law compliance
- **SATUSEHAT** - Indonesian national health information exchange

### Documentation
- **[Healthcare Standards Guide](HEALTHCARE_STANDARDS_COMPLETE.md)** (2,840 lines)
  - HL7 FHIR vs HL7 v2 comparison
  - Medical terminology systems
  - Regulatory frameworks (HIPAA, UU PDP)
  - Open-source alternatives

- **[Implementation Guide](HEALTHCARE_IMPLEMENTATION_PRACTICAL.md)** (2,500 lines)
  - FHIR resource creation patterns
  - Database-to-FHIR mapping
  - Encryption & security implementation
  - SATUSEHAT integration

- **[Regulations & Licensing](HEALTHCARE_REGULATIONS_AND_LICENSING.md)** (3,600 lines)
  - HIPAA vs UU PDP detailed comparison
  - Data subject rights implementation
  - 3-phase compliance roadmap
  - Dual-license strategy (MIT + Apache 2.0 + Proprietary)

---

## 📊 Performance 

**Optimization Implementation (PATH B):**
- Response time: 842ms → 714ms (-15.2%)
- Throughput: 11.4 → 13.3 req/s (+16.7%)
- Database latency: 170ms → 45ms (-73%)
- Cache hit rate: 65%
- Payload compression: 70%

See [DELIVERY_SUMMARY.md](DELIVERY_SUMMARY.md) for comprehensive metrics.

---

## 📅 Deployment

**Status:** ✅ Production Ready (April 8, 2026)

- **Development:** Complete (6,460+ lines, 39 files)
- **Tests:** Ready (45+ test cases)
- **Deployment:** Friday, April 11, 2026 (3-phase rollout)

See [FRIDAY_DEPLOYMENT_CHECKLIST.md](FRIDAY_DEPLOYMENT_CHECKLIST.md) for deployment procedures.
