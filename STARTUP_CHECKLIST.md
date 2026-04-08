# 🎯 SERVICE STARTUP ACTION CHECKLIST

**Date**: April 8, 2026  
**Goal**: Start all services and validate deployment readiness  
**Expected Duration**: 30-45 minutes total  

---

## ✅ PRE-STARTUP (Before You Start Anything)

- [ ] Have 4-5 PowerShell terminals open (or ready to open)
- [ ] Have [QUICK_STARTUP_REFERENCE.md](./QUICK_STARTUP_REFERENCE.md) visible for copy-paste
- [ ] Verified PHP is installed: `php -v` (in any terminal)
- [ ] Verified MySQL exists: `mysql --version`
- [ ] Verified Python installed: `python --version`
- [ ] Checked no services already running on ports 8000, 5000, 3306

---

## 🔄 MANUAL STARTUP STEPS

### Step 1: MySQL Check (Terminal 1)
**Command**: 
```powershell
mysql -u root -p
```
- [ ] MySQL prompt appears asking for password
- [ ] Can connect successfully (you see `mysql>`)
- [ ] Type `exit` to close connection
- [ ] ✅ MySQL is running and accessible

### Step 2: Laravel Server (Terminal 2)
**Commands**:
```powershell
cd "d:\Smart Healthcare\smart-health-ai"
php artisan migrate:fresh --seed
php artisan serve --port=8000 --no-interaction
```
- [ ] Terminal shows: `Migrating:` (database migrations running)
- [ ] All migrations succeed (no red FAIL text)
- [ ] Shows: `INFO  Server running on [http://127.0.0.1:8000]`
- [ ] ✅ **DO NOT CLOSE** this terminal - Laravel stays running

### Step 3: Python AI Service (Terminal 3)
**Commands**:
```powershell
cd "d:\Smart Healthcare\ai-triage-service"
python main.py
```
- [ ] Terminal shows initial loading messages
- [ ] Eventually: `INFO: Uvicorn running on ...`
- [ ] ✅ **DO NOT CLOSE** this terminal - Python stays running
- [ ] (Mistral model loading can take 1-3 minutes - NORMAL)

### Step 4: Queue Worker (Terminal 4)
**Commands**:
```powershell
cd "d:\Smart Healthcare\smart-health-ai"
php artisan queue:work --tries=3 --max-time=3600
```
- [ ] Terminal shows: `[INFO] Listening on queue: default`
- [ ] No errors in output
- [ ] ✅ **DO NOT CLOSE** this terminal - Worker stays running

### Step 5: Wait for Stabilization (2-3 minutes)
**What's happening**:
- [ ] Laravel compiling routes
- [ ] Database indexes being created
- [ ] Cache initialized
- [ ] Python model loading (can be slow - NORMAL)
- [ ] All services warming up

**Monitor**: Watch all 4 terminals for red ERROR/FATAL text. If you see errors, note them for troubleshooting.

---

## ✅ VERIFICATION (Terminal 5 - New)

### Quick Browser Tests
- [ ] Open: http://localhost:8000/api/health → Should return JSON
- [ ] Open: http://localhost:5000/api/health → Should return 200 OK

### Validation Script Test
**Command**:
```powershell
cd "d:\Smart Healthcare"
python validate_system.py
```

**Results** (watch for these):
- [ ] Result: `✅ Passed: 7`
- [ ] Result: `❌ Failed: 0`
- [ ] Result: `⚠️  Warned: 0`

**If validation passes**: 🎉 Go to next section  
**If validation fails**: 
- [ ] Wait another 2 minutes (model might still loading)
- [ ] Check for red ERROR text in all 4 terminals
- [ ] Refer to troubleshooting section below

---

## 🧪 POST-VERIFICATION TESTS (Only if Validation Passed)

### Test 1: Full Test Suite (Terminal 5)
**Command**:
```powershell
cd "d:\Smart Healthcare\smart-health-ai"
php artisan test
```

**Expected Output**:
```
OK (45 tests, 123 assertions)
```

- [ ] All tests passing (no FAILED in output)
- [ ] Duration usually 30-60 seconds
- [ ] ✅ All unit/feature tests working

### Test 2: Quick Load Test (New command in Terminal 5)
**Command**:
```powershell
cd "d:\Smart Healthcare"
python load_test_advanced.py 10 30
```

**Expected Output**:
- [ ] Total Requests: ~300-330
- [ ] Success: ~99%+
- [ ] Errors: ~1% or less
- [ ] Average latency: 150-300ms
- [ ] P95: 300-400ms
- [ ] ✅ Load handling good

### Test 3: Baseline Response Time
**Command**:
```powershell
curl -w "Response Time: %{time_total}s`n" http://localhost:8000/api/health -o $null
```

**Record These Values**:
- [ ] Response Time: _____ seconds (should be < 1.0s)
- [ ] Status Code: _____ (should be 200)
- [ ] This is your BASELINE for Friday comparison

---

## 🚀 READY FOR DEPLOYMENT

Once all above checks complete:

- [ ] ✅ All 4 services running (Terminals 2, 3, 4)
- [ ] ✅ Validation passed (7/7 tests)
- [ ] ✅ Test suite passed (45+ tests)
- [ ] ✅ Load test passed (~300 requests successful)
- [ ] ✅ Baseline metrics captured

**Status**: 🟢 **READY FOR DEPLOYMENT CHECKLIST**

---

## 📋 NEXT PHASE CHECKLIST

Now proceed to: [DEPLOYMENT_CHECKLIST.md](./DEPLOYMENT_CHECKLIST.md)

Before Friday deployment (Apr 11), complete:

- [ ] Run full test suite daily (should remain 100% passing)
- [ ] Keep baseline metrics saved for comparison
- [ ] Review deployment 3-phase plan
- [ ] Brief team on procedures
- [ ] Verify rollback plan understood
- [ ] Set up monitoring dashboards
- [ ] Configure alert notifications

---

## 🚨 TROUBLESHOOTING

### Laravel Won't Start
```
Error: "Address 127.0.0.1:8000 already in use"
Solution: Kill process on 8000
  netstat -ano | findstr :8000
  taskkill /PID {PID} /F
  Then retry: php artisan serve --port=8000
```

- [ ] Issue resolved or skipped

### Python Service Very Slow
```
Error: Taking more than 3 minutes to start
Note: First time Mistral 7B loads is SLOW (7GB model)
Solution: Just wait, DO NOT CLOSE terminal
Expected: 3-5 minutes for first load
```

- [ ] Waited and service eventually started

### Database Migrations Fail
```
Error: Migration error, duplicate key, etc.
Solution: Database might have old data
  php artisan migrate:fresh # Drops all, recreates fresh
  May need password again after this
```

- [ ] Issue resolved or skipped

### Validation Still Fails After 5 Minutes
```
Check: Look at Terminal 2, 3, 4 for red ERROR/FATAL text
Common: Python still loading model
Solution: Wait another 2 minutes minimum
```

- [ ] Resolved by waiting
- [ ] Found and fixed error in terminal

### Can't Connect to MySQL
```
Error: "Can't connect to local MySQL server"
Solution: MySQL not running
  Check: mysql -u root -p
  If fails: Windows Start → Services → MySQL80 → Start
```

- [ ] Issue resolved or skipped

---

## 📊 FINAL STATUS CHECK

Before moving to deployment checklist:

```
SYSTEM STATUS CHECK
├─ Laravel Health: ✅ Responding on port 8000
├─ Python Health: ✅ Responding on port 5000
├─ MySQL Database: ✅ Accessible
├─ Queue Worker: ✅ Ready for jobs
├─ Cache Layer: ✅ File storage ready
├─ All Indexes: ✅ 7 optimization indexes
├─ Validation: ✅ 7/7 tests passing
├─ Test Suite: ✅ 45+ tests passing
├─ Load Test: ✅ 10+ concurrent users OK
└─ Baseline: ✅ Metrics captured
```

**OVERALL STATUS**: ✅ **READY FOR DEPLOYMENT**

---

## 🎉 COMPLETION!

Once this checklist is complete:

1. **Keep all 4 services running** (Terminals 2, 3, 4)
2. **Review**: DEPLOYMENT_CHECKLIST.md
3. **Plan**: 3-phase rollout for Friday
4. **Prepare**: Team briefing and procedures
5. **Deploy**: Friday, April 11, 2026

---

**Checklist Started**: _______  
**Checklist Completed**: _______  
**Status**: 🚀 Ready for Production Deployment

