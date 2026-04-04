# 🔐 SECURITY.md — Smart Healthcare AI
> Standar keamanan industri untuk Smart Healthcare Assistant System.
> Dokumen ini mendefinisikan seluruh kebijakan keamanan yang WAJIB diimplementasikan.

---

## 🛡️ Security Overview

| Layer | Implementasi | Status |
|-------|-------------|--------|
| Password Policy | Min 12 char, complexity enforced | Phase 1 |
| Authentication | JWT (access + refresh token) | Phase 1 |
| Rate Limiting | Throttle login 5x/15 min | Phase 1 |
| Account Lockout | Auto-lock after 5 gagal | Phase 1 |
| CORS | Whitelist domain saja | Phase 1 |
| Security Headers | HSTS, CSP, X-Frame-Options | Phase 5 |
| Input Validation | FormRequest + sanitization | All phases |
| SQL Injection | Eloquent ORM (parameterized) | Built-in |
| XSS Protection | Output encoding + headers | Phase 5 |
| HTTPS | Force redirect + HSTS | Phase 5 |
| Logging | Audit trail semua auth events | Phase 5 |
| Secrets | .env only, never hardcoded | All phases |

---

## 🔑 Password Policy (NIST SP 800-63B)

### Rules (WAJIB enforced di RegisterRequest + UpdatePasswordRequest):

| Rule | Value | Dasar |
|------|-------|-------|
| Minimum length | **12 karakter** | NIST 800-63B |
| Maximum length | 128 karakter | NIST 800-63B |
| Uppercase | Min 1 huruf besar | OWASP |
| Lowercase | Min 1 huruf kecil | OWASP |
| Angka | Min 1 digit (0–9) | OWASP |
| Simbol | Min 1 karakter spesial (!@#$%^&*) | OWASP |
| No whitespace | Dilarang spasi | Security |
| No common passwords | Cek against blocklist | OWASP |
| No username in password | Dilarang | OWASP |

### Hashing Algorithm:
- **bcrypt** dengan `cost factor = 12` (default Laravel)
- Upgrade otomatis jika cost factor berubah (rehash on login)

```php
// config/hashing.php
'bcrypt' => [
    'rounds' => env('BCRYPT_ROUNDS', 12),
],
```

---

## 🔐 JWT Security Configuration

### Token Strategy:
```
Access Token:
  - TTL: 15 menit (bukan 60!)
  - Payload: sub (user_id), iat, exp, jti (unique ID)
  - Algorithm: HS256

Refresh Token:
  - TTL: 7 hari
  - Stored: Database (revokable)
  - Rotation: Issued new refresh on every use
```

### JWT Environment Config:
```env
JWT_SECRET=                     # min 256-bit random string
JWT_TTL=15                      # access token: 15 menit
JWT_REFRESH_TTL=10080           # refresh token: 7 hari (7*24*60)
JWT_ALGO=HS256
JWT_BLACKLIST_ENABLED=true      # enable token revocation
JWT_BLACKLIST_GRACE_PERIOD=30   # 30 detik grace period
```

### Endpoint Auth:
```
POST /api/auth/login      → returns { access_token, refresh_token, expires_in }
POST /api/auth/refresh    → returns new access_token (rotate refresh)
POST /api/auth/logout     → blacklist token
GET  /api/auth/me         → current user info
```

---

## 🚦 Rate Limiting

### Strategy (Laravel Throttle Middleware):

| Endpoint | Max Requests | Window | Action |
|----------|-------------|--------|--------|
| `POST /auth/login` | **5 requests** | 15 menit | 429 Too Many Requests |
| `POST /auth/register` | 10 requests | 1 jam | 429 Too Many Requests |
| `POST /auth/forgot-password` | 3 requests | 1 jam | 429 Too Many Requests |
| API global (authenticated) | 60 requests | 1 menit | 429 Too Many Requests |
| API global (unauthenticated) | 20 requests | 1 menit | 429 Too Many Requests |

### Response Header (RFC 6585):
```
X-RateLimit-Limit: 5
X-RateLimit-Remaining: 0
X-RateLimit-Reset: 1735689600
Retry-After: 900
```

---

## 🔒 Account Lockout Policy

### Implementation (LoginAttempt tracking):

```
Failed Login → increment failed_attempts counter (DB)

Threshold:
  3 failures    → warning response ("2 percobaan tersisa")
  5 failures    → LOCK account (15 menit)
  10 failures   → LOCK account (1 jam) + kirim email alert
  20 failures   → PERMANENT LOCK + butuh admin reset

Lock Status → cek di LoginRequest/AuthController
Unlock:
  - Otomatis setelah TTL habis
  - Manual via admin endpoint (Phase 5)
```

### Tambahan kolom ke `users` table:
```sql
failed_login_attempts   TINYINT UNSIGNED DEFAULT 0
last_failed_login       TIMESTAMP NULLABLE
locked_until            TIMESTAMP NULLABLE
```

---

## 🌐 CORS Configuration

### `config/cors.php`:
```php
'allowed_origins' => env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000'),
// Production: ['https://yourdomain.com']

'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With', 'Accept'],
'exposed_headers' => ['X-RateLimit-Limit', 'X-RateLimit-Remaining'],
'max_age' => 86400,    // 24 jam preflight cache
'supports_credentials' => true,
```

---

## 🧱 Security Headers Middleware

### `SecurityHeadersMiddleware.php` (Phase 5):
```
Header                          Value
──────────────────────────────────────────────────────
X-Content-Type-Options          nosniff
X-Frame-Options                 DENY
X-XSS-Protection                1; mode=block
Strict-Transport-Security       max-age=31536000; includeSubDomains; preload
Content-Security-Policy         default-src 'self'
Referrer-Policy                 strict-origin-when-cross-origin
Permissions-Policy              geolocation=(), microphone=(), camera=()
Cache-Control                   no-store (untuk API responses)
```

---

## 🧼 Input Sanitization

### Wajib di semua FormRequest:
```php
// ❌ DILARANG — trust raw input langsung ke DB
$patient = Patient::create($request->all());

// ✅ BENAR — hanya ambil validated fields
$patient = Patient::create($request->validated());

// ✅ BENAR — sanitize string fields
$message = strip_tags(trim($request->input('message')));
$message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
```

### Rules di FormRequest:
```php
'name'    => ['required', 'string', 'max:255', 'regex:/^[\p{L} ]+$/u'],
'email'   => ['required', 'email:rfc,dns', 'max:255'],
'phone'   => ['nullable', 'regex:/^[0-9+\-() ]{7,20}$/'],
'message' => ['required', 'string', 'min:2', 'max:2000'],
```

---

## 🔏 Secrets Management

### Rules (WAJIB):

```
✅ Semua secrets di .env ONLY
✅ .env masuk ke .gitignore (tidak boleh di-commit)
✅ .env.example berisi placeholder, BUKAN nilai asli
✅ JWT_SECRET min 64 karakter (512-bit entropy)
✅ DB_PASSWORD min 16 karakter di production
❌ DILARANG hardcode secret di kode
❌ DILARANG secret di comments atau logs
❌ DILARANG commit .env ke git
```

### Generate secrets secara aman:
```bash
# App key
php artisan key:generate

# JWT secret (256-bit)
php artisan jwt:secret

# Generate random password (production DB)
openssl rand -base64 32

# Generate random string untuk API keys
openssl rand -hex 32
```

---

## 📋 Audit Logging

### Events yang WAJIB dilog:

| Event | Log Level | Data |
|-------|-----------|------|
| Successful login | `INFO` | user_id, ip, timestamp |
| Failed login | `WARNING` | email, ip, attempts_count |
| Account locked | `WARNING` | user_id, ip, reason |
| Token refresh | `INFO` | user_id, ip |
| Logout | `INFO` | user_id |
| Password changed | `INFO` | user_id, ip |
| Patient data accessed | `INFO` | user_id, patient_id |
| Patient data modified | `INFO` | user_id, patient_id, changes |
| Unauthorized access attempt | `ERROR` | ip, endpoint, token |

### Log format (structured JSON):
```json
{
  "timestamp": "2025-01-01T00:00:00Z",
  "level": "WARNING",
  "event": "failed_login",
  "user_id": null,
  "email": "user@example.com",
  "ip_address": "192.168.1.1",
  "user_agent": "Mozilla/5.0...",
  "attempts": 3,
  "locked": false
}
```

---

## 🛡️ SQL Injection Prevention

Laravel Eloquent sudah parameterized secara default. **ATURAN:**

```php
// ✅ AMAN — Eloquent (parameterized)
Patient::where('email', $email)->first();
Patient::whereIn('id', $ids)->get();

// ✅ AMAN — Query Builder dengan binding
DB::select('SELECT * FROM patients WHERE email = ?', [$email]);

// ❌ BERBAHAYA — raw tanpa binding
DB::select("SELECT * FROM patients WHERE email = '{$email}'");
DB::statement("DELETE FROM patients WHERE id = " . $id);
```

---

## 🔐 API Key (untuk integrasi eksternal, Phase 5)

```
Format: smhc_{environment}_{random_32_bytes_hex}
Contoh: smhc_prod_a3f8b2c1d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1

Storage: hashed (SHA-256) di database, tidak pernah plain text
Header:  X-API-Key: smhc_prod_xxx
```

---

## 📐 Security Checklist (QA Agent)

Jalankan checklist ini sebelum setiap phase diapprove:

```
Authentication & Authorization
  □ JWT secret min 64 karakter?
  □ Access token TTL ≤ 15 menit?
  □ Refresh token di-rotate setiap digunakan?
  □ Logout mem-blacklist token?
  □ Semua protected routes pakai JWT middleware?

Password
  □ Bcrypt rounds = 12?
  □ Password validation: min 12 char, complexity rules?
  □ Password confirm divalidasi di register?
  □ Password tidak pernah di-log?
  □ Password di-hash SEBELUM disimpan (tidak pernah plain text)?

Rate Limiting
  □ Login endpoint ter-throttle (5/15min)?
  □ Register endpoint ter-throttle (10/jam)?
  □ API global throttle aktif?
  □ Rate limit headers dikembalikan di response?

Input Security
  □ Semua input via FormRequest validated?
  □ Hanya $request->validated() yang masuk ke DB?
  □ File upload divalidasi MIME type + size (jika ada)?

Secrets
  □ .env ada di .gitignore?
  □ Tidak ada hardcoded secret di kode?
  □ .env.example menggunakan placeholder?

Headers & CORS
  □ CORS origin hanya whitelist?
  □ Security headers aktif di production?
  □ X-Frame-Options: DENY?
  □ HSTS aktif? (production only)

Logging
  □ Failed login ter-log?
  □ Tidak ada password/token di log output?
  □ Audit trail untuk data access?
```

---

*Smart Healthcare Security Standards — OWASP Top 10 · NIST SP 800-63B · Laravel Security Best Practices*
