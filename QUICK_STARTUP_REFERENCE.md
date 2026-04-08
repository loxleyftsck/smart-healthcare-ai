# 🚀 QUICK START: Manual Service Startup Commands

Copy & paste these commands into **separate** PowerShell terminals (keep them all open).

---

## Terminal 1: MySQL Setup (Quick Check)
```powershell
mysql -u root -p
```
**Type** `exit` **after connecting**  
**Expected**: MySQL password prompt, then mysql> prompt, then exits

---

## Terminal 2: Laravel Server (Keep Running)
```powershell
cd "d:\Smart Healthcare\smart-health-ai"
php artisan migrate:fresh --seed
php artisan serve --port=8000 --no-interaction
```
**Keep this terminal open**  
**Expected**: `INFO  Server running on [http://127.0.0.1:8000]`

Test in browser: http://localhost:8000/api/health

---

## Terminal 3: Python AI Service (Keep Running)
```powershell
cd "d:\Smart Healthcare\ai-triage-service"
python main.py
```
**Keep this terminal open**  
**Expected**: `INFO: Uvicorn running on http://0.0.0.0:5000`

Test in browser: http://localhost:5000/api/health

---

## Terminal 4: Queue Worker (Keep Running)
```powershell
cd "d:\Smart Healthcare\smart-health-ai"
php artisan queue:work --tries=3 --max-time=3600
```
**Keep this terminal open**  
**Expected**: `[INFO] Listening on queue: default`

---

## Terminal 5 (NEW): Validation Check
After 2-3 minutes, all services should be running. Check them:

```powershell
cd "d:\Smart Healthcare"
python validate_system.py
```

**Expected Output**:
```
✅ Passed: 7
❌ Failed: 0
```

If not all passing, wait another 1-2 minutes (Python model is heavy).

---

## 📊 After Validation Passes

### Run Full Test Suite (New Terminal)
```powershell
cd "d:\Smart Healthcare\smart-health-ai"
php artisan test
```

### Quick Load Test (New Terminal)
```powershell
cd "d:\Smart Healthcare"
python load_test_advanced.py 10 30
```

### Capture Baseline (New Terminal)
```powershell
cd "d:\Smart Healthcare"
# Test single request
curl http://localhost:8000/api/health

# Test with timing
curl -w "Total time: %{time_total}s`n" http://localhost:8000/api/health -o $null
```

---

## 🎯 Summary Table

| Terminal | Purpose | Command | Keep Open? |
|----------|---------|---------|-----------|
| 1 | MySQL Check | `mysql -u root -p` | No (exit after test) |
| 2 | Laravel App | `php artisan serve --port=8000` | ✅ YES |
| 3 | Python API | `python main.py` | ✅ YES |
| 4 | Queue Worker | `php artisan queue:work` | ✅ YES |
| 5+ | Testing | `python validate_system.py` | No (temporary) |

---

## ⏱️ Timeline

- **0-2 min**: Start Terminal 2, 3, 4
- **2-3 min**: Wait for services to stabilize
- **3-5 min**: Systems ready, Mistral model loaded
- **5-10 min**: Run validation, all tests should pass ✅
- **10-15 min**: Run test suite
- **15-20 min**: Run load test
- **20+ min**: Ready for deployment checklist

---

## 🔍 Health Check URLs

While services are running, test these in browser:

- **Laravel Health**: http://localhost:8000/api/health
- **Python Health**: http://localhost:5000/api/health
- **Laravel Patients**: http://localhost:8000/api/patients (should be cached)
- **Python Triage**: http://localhost:5000/api/triage

All should return **2XX status** and **JSON response**.

---

## ❌ Common Issues

| Error | Solution |
|-------|----------|
| "Address already in use" | Kill: `taskkill /PID {PID} /F` |
| "Could not connect to MySQL" | Start: `net start MySQL80` |
| Python very slow | Normal! Mistral 7B is heavy, wait 3-5 min |
| "php not found" | Add PHP to PATH or use full path |
| Tests still failing | Check for red ERROR/FATAL lines in terminals |

---

## 📚 Next Step

**After validation passes:**
1. Keep all 4 terminals running
2. Review: [DEPLOYMENT_CHECKLIST.md](./DEPLOYMENT_CHECKLIST.md)
3. Prepare for Friday: 3-phase gradual rollout

---

**Status**: 🚀 Ready to start!  
**Deployment**: Friday, April 11, 2026  
**Expected Performance Gain**: 15.2% (-128ms)

