# 🐳 DOCKER SERVICE STARTUP (RECOMMENDED)

**Status**: ✅ Docker installed (v29.1.2)  
**Status**: ✅ Docker Compose installed (v2.40.3)  
**Status**: ✅ Ready to start services!

---

## 🚀 START ALL SERVICES NOW

### ONE Command - That's It!

```powershell
cd "d:\Smart Healthcare"
docker-compose up -d --build
```

**What this does**:
1. Builds Docker images for Laravel, Python, MySQL
2. Starts all containers
3. Runs migrations and setup
4. Initializes databases
5. All services run in background

**Estimated time**: 3-5 minutes

---

## ✅ VERIFY SERVICES ARE RUNNING

```powershell
docker-compose ps
```

**Expected output**:
```
NAME              IMAGE                   STATUS
smarthealth_app         smarthealth-app         Up
smarthealth_mysql       mysql:8.0               Up
smarthealth_ai_triage   smarthealth-ai-triage   Up
smarthealth_nginx       nginx:alpine            Up
```

All containers should show `Up` status.

---

## 📋 WHAT EACH SERVICE DOES

| Service | Port | Purpose |
|---------|------|---------|
| **app** (Laravel) | 8000 | Main API server |
| **mysql** | 3306 | Database |
| **ai-triage** (Python) | 8001 | AI Service |
| **nginx** | 80→8000 | Web proxy |

---

## ⏱️ WAIT 2-3 MINUTES FOR WARM UP

**What's happening**:
```
├─ MySQL: Creating tables and indexes
├─ Laravel: Running migrations (13 total)
├─ Python: Loading Mistral 7B model (SLOW - 1-3 min)
├─ Cache: Initializing file storage
└─ All services: Connecting and warming up
```

**Monitor startup**:
```powershell
docker-compose logs -f
```
(Press Ctrl+C to stop watching logs)

---

## 🧪 QUICK HEALTH CHECK

Once services running, test them:

**Browser tests**:
- Laravel: http://localhost:8000/api/health
- Python: http://localhost:8001/api/health

**Command line tests**:
```powershell
curl http://localhost:8000/api/health
curl http://localhost:8001/api/health
```

**Expected**: Both return JSON with status OK

---

## 🔍 RUN VALIDATION

After 3-5 minutes, run validation:

```powershell
cd "d:\Smart Healthcare"
python validate_system.py
```

**Expected Result**:
```
✅ Passed: 7
❌ Failed: 0
⚠️ Warned: 0
```

If validation passes: **Systems are ready!** ✅✅✅

---

## 📊 CHECK SERVICE LOGS

If something seems wrong, check logs:

```powershell
# All services
docker-compose logs

# Specific service
docker-compose logs app          # Laravel
docker-compose logs ai-triage    # Python
docker-compose logs mysql        # Database
```

Look for `ERROR` or `FATAL` (yellow warnings usually OK).

---

## 🧼 USEFUL DOCKER COMMANDS

**View running services**:
```powershell
docker-compose ps
```

**Stop all services**:
```powershell
docker-compose down
```

**Restart services**:
```powershell
docker-compose restart
```

**View logs**:
```powershell
docker-compose logs -f --tail 20
```

**Enter container**:
```powershell
docker-compose exec app bash
```

**Rebuild images**:
```powershell
docker-compose up -d --build
```

---

## 📈 NEXT STEPS (After Validation Passes)

### 1. Run Full Test Suite
```powershell
cd "d:\Smart Healthcare"
python validate_system.py
```

Should pass all 7 tests.

### 2. Run PHP Unit Tests
```powershell
docker-compose exec app php artisan test
```

Expected: All 45+ tests passing

### 3. Run Load Test
```powershell
cd "d:\Smart Healthcare"
python load_test_advanced.py 10 30
```

Expected: 99%+ success rate

### 4. Capture Baseline Metrics
```powershell
# Single request timing
curl -w "Response time: %{time_total}s`n" http://localhost:8000/api/health

# Record: _______ seconds (baseline for Friday comparison)
```

### 5. Review Deployment Plan
- Read: [DEPLOYMENT_CHECKLIST.md](./DEPLOYMENT_CHECKLIST.md)
- Plan: 3-phase rollout for Friday
- Prepare: Team briefing

---

## 🎯 CHECKLIST

- [ ] Run: `docker-compose up -d --build`
- [ ] Wait: 3-5 minutes for warmup
- [ ] Verify: `docker-compose ps` (all Up)
- [ ] Test: Browser at http://localhost:8000/api/health
- [ ] Validate: `python validate_system.py` (7/7 passing)
- [ ] Success! 🎉

---

## ❌ TROUBLESHOOTING

### Docker containers won't start
```
Error: bind: address already in use
Solution: Kill processes on ports 8000, 3306, 8001
  netstat -ano | findstr :8000
  taskkill /PID {PID} /F
Then try again: docker-compose up -d --build
```

### Services very slow to start
```
Status: Python loading Mistral model
Action: Wait 3-5 minutes (NORMAL - 7GB model)
Monitor: docker-compose logs -f
```

### MySQL migrations fail
```
Error: Migration error, key constraints, etc.
Solution: Reset database
  docker-compose down -v   (removes volumes)
  docker-compose up -d --build
```

### Can't access http://localhost:8000
```
Issue: Services still warming up
Wait: Full 5 minutes minimum
Check: docker-compose ps (should all be Up)
Logs: docker-compose logs app
```

---

## 🆘 EMERGENCY

### Full reset and restart
```powershell
docker-compose down -v
docker system prune -a
docker-compose up -d --build
```

### View detailed logs
```powershell
docker-compose logs --follow
```

### Stop everything
```powershell
docker-compose down
```

---

## 📊 EXPECTED TIMELINE

| Step | Time | Status |
|------|------|--------|
| Start Docker | 1 min | Now |
| Build images | 2-3 min | Automatic |
| Services startup | 2-3 min | Automatic |
| Python warm up | 1-3 min | Wait |
| Validation | 1 min | Run |
| **Total** | **~10 min** | **Ready!** |

---

## 🎉 SUCCESS INDICATORS

When you see these, services are ready:

```
docker-compose ps shows: All "Up"
http://localhost:8000/api/health → 200 OK JSON
http://localhost:8001/api/health → 200 OK JSON
python validate_system.py → 7/7 PASSED
```

---

## 🚀 NEXT MILESTONE

Once validation passes:
- ✅ Pre-deployment phase complete
- ✅ Systems ready for testing
- ✅ Ready for Friday deployment (Apr 11)
- ✅ Expected: 15.2% performance improvement

---

**Status**: 🚀 Ready to start!  
**Next Action**: Run command above  
**Questions**: Check logs with: `docker-compose logs`

