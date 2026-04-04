# 🌿 GIT_WORKFLOW.md — Smart Healthcare AI
> Standar Git Branching & CI/CD Strategy — berdasarkan **Modified GitFlow + GitLab Flow**
> Disesuaikan untuk project dengan 3 environment: Dev → QA → Production

---

## 📐 Branching Strategy

Strategi yang digunakan adalah **Modified GitFlow** — best practice untuk tim yang memiliki
multiple environments (dev/qa/prod) dan membutuhkan kontrol rilis yang ketat.

```
                    ┌─────────────────────────────────┐
                    │         BRANCH HIERARCHY         │
                    └─────────────────────────────────┘

  feature/SMHC-123/add-triage-endpoint
  bugfix/SMHC-456/fix-jwt-refresh
  hotfix/SMHC-789/patch-sql-injection
         │  │  │
         │  │  └──── merge via PR ──────────────────────────────────────┐
         │  └──────── merge via PR ──────────────┐                      │
         └─────────── merge via PR ──┐            │                      │
                                     │            │                      │
                              ┌──────▼──────┐     │                      │
                              │   develop   │     │                      │
                              │  (Dev Env)  │     │                      │
                              └──────┬──────┘     │                      │
                                     │            │                      │
                              PR + CI Pass        │                      │
                                     │            │                      │
                              ┌──────▼──────┐     │                      │
                              │   staging   │◄────┘                      │
                              │  (QA Env)   │                            │
                              └──────┬──────┘                            │
                                     │                                   │
                              PR + CI Pass + Manual Approval             │
                                     │                                   │
                              ┌──────▼──────┐                            │
                              │    main     │◄───────────────────────────┘
                              │  (Prod Env) │  (hotfix langsung ke main)
                              └─────────────┘
```

---

## 🌿 Branch Definitions

### 🔵 `main` — Production
| Property | Value |
|----------|-------|
| **Environment** | Production |
| **Protected** | ✅ Yes — force push DILARANG |
| **Merge from** | `staging` only (via PR) |
| **Who can merge** | Tech Lead / Project Owner only |
| **Required reviews** | Minimum 2 approvals |
| **Status checks** | Semua CI jobs HARUS pass |
| **Manual approval** | ✅ WAJIB (GitHub Environment protection) |
| **Deployment** | Auto setelah manual approval di GitHub |
| **Tag format** | `v1.0.0`, `v1.1.0` (semver) |

### 🟡 `staging` — QA / Testing
| Property | Value |
|----------|-------|
| **Environment** | QA / Staging |
| **Protected** | ✅ Yes |
| **Merge from** | `develop` atau `release/*` branch |
| **Who can merge** | Developer + Tech Lead (via PR) |
| **Required reviews** | Minimum 1 approval |
| **Status checks** | Semua CI jobs HARUS pass |
| **Manual approval** | Optional (auto-deploy ke QA jika CI pass) |
| **Deployment** | Auto setelah CI pass |
| **Purpose** | Final QA sebelum naik ke production |

### 🟢 `develop` — Development
| Property | Value |
|----------|-------|
| **Environment** | Development |
| **Protected** | Moderate |
| **Merge from** | `feature/*`, `bugfix/*` branches |
| **Who can merge** | Semua developer (via PR) |
| **Required reviews** | Minimum 1 approval |
| **Status checks** | Unit + integration tests HARUS pass |
| **Manual approval** | ❌ Auto-deploy |
| **Deployment** | Auto setelah CI pass |
| **Purpose** | Integration branch — dev environment |

---

## 🔀 Branch Naming Convention

```
feature/{ticket-id}/{short-description}
bugfix/{ticket-id}/{short-description}
hotfix/{ticket-id}/{short-description}
release/{version}
```

### Contoh Nama Branch:
```bash
# Feature
feature/SMHC-001/add-patient-crud
feature/SMHC-015/implement-triage-engine
feature/SMHC-030/chatbot-intent-detection

# Bug fix
bugfix/SMHC-042/fix-jwt-token-expiry
bugfix/SMHC-055/correct-confidence-calculation

# Hot fix (production emergency)
hotfix/SMHC-099/patch-sql-injection-vulnerability

# Release candidate
release/1.0.0
release/1.1.0-beta
```

### Rules (WAJIB):
```
✅ Lowercase dan kebab-case
✅ Ada ticket ID (SMHC-xxx)
✅ Deskripsi singkat tapi jelas
✅ Max 50 karakter total
❌ DILARANG: main, master, develop, staging, production
❌ DILARANG: spasi, underscore, huruf kapital
❌ DILARANG: branch tanpa ticket ID
```

---

## 🔄 Development Workflow

### 1. Mulai Feature Baru
```bash
# Pastikan develop up-to-date
git checkout develop
git pull origin develop

# Buat feature branch
git checkout -b feature/SMHC-001/add-patient-crud

# Push ke remote (tracking)
git push -u origin feature/SMHC-001/add-patient-crud
```

### 2. Work & Commit
```bash
# Commit convention: Conventional Commits (https://conventionalcommits.org)
git add .
git commit -m "feat(patient): add PatientService with CRUD operations"
git commit -m "test(patient): add unit tests for PatientService"
git commit -m "fix(patient): correct email unique validation rule"
```

### 3. Commit Message Format (Conventional Commits)

```
<type>(<scope>): <subject>

[optional body]
[optional footer]
```

| Type | Kapan Dipakai |
|------|--------------|
| `feat` | Fitur baru |
| `fix` | Bug fix |
| `docs` | Perubahan dokumentasi |
| `style` | Formatting, bukan logic |
| `refactor` | Refactor tanpa fitur/fix baru |
| `test` | Tambah/ubah test |
| `chore` | Build tools, dependencies |
| `security` | Security patch |
| `perf` | Performance improvement |

**Contoh commit messages:**
```bash
feat(auth): implement JWT login with refresh token
fix(triage): correct confidence score calculation
test(chatbot): add intent detection unit tests
security(auth): enforce bcrypt rounds=12 for password hashing
docs(api): add Swagger annotations to PatientController
chore(deps): upgrade laravel/framework to 11.5
```

### 4. Pull Request ke develop

```bash
# Sebelum PR: rebase ke develop terbaru
git fetch origin
git rebase origin/develop

# Push
git push origin feature/SMHC-001/add-patient-crud

# Buka PR di GitHub: feature/SMHC-001/add-patient-crud → develop
```

**PR Checklist (WAJIB sebelum minta review):**
```
□ Semua test passing (php artisan test)
□ Tidak ada dd(), var_dump(), print_r()
□ .env tidak ter-commit
□ PHPDoc di semua public method baru
□ FormRequest untuk semua input
□ Mengikuti konvensi di CLAUDE.md
□ PR description jelas (apa, kenapa, cara test)
```

### 5. Promote ke Staging (QA)
```bash
# Setelah develop stabil → buka PR: develop → staging
# Tech Lead / QA Engineer yang approve
```

### 6. Promote ke Production
```bash
# Setelah QA approve → buka PR: staging → main
# Membutuhkan 2 approvals + GitHub Environment approval
# Auto-tagged dengan versi baru (v1.x.x)
```

### 7. Hotfix (Production Emergency)
```bash
# HANYA untuk critical production bug
git checkout main
git pull origin main
git checkout -b hotfix/SMHC-099/patch-critical-vulnerability

# Fix → test → commit
git commit -m "security(auth): patch SQL injection in login endpoint"

# PR ke main (fast-track, 1 approval)
# Setelah merge ke main → cherry-pick / backport ke staging + develop
git checkout develop
git cherry-pick <commit-hash>
git push origin develop
```

---

## 🏷️ Version Tagging (Semantic Versioning)

```
v{MAJOR}.{MINOR}.{PATCH}

MAJOR → Breaking changes (rare)
MINOR → New features (monthly)
PATCH → Bug fixes / hotfixes (as needed)
```

**Contoh:**
```bash
v0.1.0 → Phase 1 selesai (Foundation)
v0.2.0 → Phase 2 selesai (Data Engineering)
v0.3.0 → Phase 3 selesai (Triage Engine)
v0.4.0 → Phase 4 selesai (Chatbot Engine)
v1.0.0 → Phase 5 selesai (Production-ready)
v1.0.1 → Hotfix kecil
```

```bash
# Buat tag di main setelah deploy
git tag -a v1.0.0 -m "Release v1.0.0 — Production ready"
git push origin v1.0.0
```

---

## 🔐 Branch Protection Rules (GitHub Settings)

### `main` Branch:
```yaml
protection:
  require_pull_request: true
  required_approving_reviews: 2
  dismiss_stale_reviews: true
  require_code_owner_reviews: true
  require_status_checks:
    - "lint"
    - "test-full"
    - "security-audit"
    - "build"
  require_linear_history: true    # squash/rebase only
  allow_force_pushes: false
  allow_deletions: false
  enforce_admins: true            # bahkan admin harus ikut aturan
```

### `staging` Branch:
```yaml
protection:
  require_pull_request: true
  required_approving_reviews: 1
  dismiss_stale_reviews: true
  require_status_checks:
    - "lint"
    - "test-full"
    - "security-scan"
    - "integration-test"
  require_linear_history: true
  allow_force_pushes: false
  allow_deletions: false
```

### `develop` Branch:
```yaml
protection:
  require_pull_request: true
  required_approving_reviews: 1
  require_status_checks:
    - "lint"
    - "test"
    - "security"
  allow_force_pushes: false
```

---

## 🔁 Environment & CI/CD Summary

| Branch | Environment | Auto-Deploy | Manual Approval | Min Coverage |
|--------|-------------|-------------|-----------------|--------------|
| `develop` | Dev | ✅ Yes | ❌ No | 70% |
| `staging` | QA | ✅ Yes | Optional | 80% |
| `main` | Production | ⚠️ After approval | ✅ REQUIRED | 90% |

---

## 🚫 Anti-Patterns (DILARANG)

```bash
# ❌ Push langsung ke main atau develop tanpa PR
git push origin main

# ❌ Force push ke protected branch
git push --force origin main

# ❌ Commit .env
git add .env && git commit -m "add env"

# ❌ Branch terlalu lama (>1 sprint)
# Feature branch tidak boleh lebih dari 2 minggu

# ❌ Merge tanpa CI pass
# Jangan bypass status checks

# ❌ 1 PR untuk banyak fitur tidak related
# 1 PR = 1 fitur / 1 bugfix
```

---

## 📋 Quick Reference

```bash
# Setup awal (clone & track semua branch)
git clone git@github.com:loxleyftsck/smart-healthcare-ai.git
cd smart-healthcare-ai
git checkout develop
git checkout staging

# Daily workflow
git checkout develop && git pull
git checkout -b feature/SMHC-XXX/your-feature
# ... work work work ...
git add . && git commit -m "feat(scope): description"
git push -u origin feature/SMHC-XXX/your-feature
# Buka PR di GitHub

# Cek semua branch
git branch -a

# Delete merged feature branch
git branch -d feature/SMHC-XXX/your-feature
git push origin --delete feature/SMHC-XXX/your-feature
```

---

*Smart Healthcare AI — Git Workflow v1.0*
*Based on: Modified GitFlow + GitLab Flow with Pipeline Promotion*
*References: Atlassian GitFlow, GitHub Flow, DORA Metrics*
