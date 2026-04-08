# ⚠️ SYSTEM SETUP REQUIRED

**Date**: April 8, 2026  
**Issue**: PHP and MySQL not found in system PATH  
**Solution**: Use Docker OR Add to PATH

---

## 🐳 RECOMMENDED: Use Docker (Easiest)

Docker containers have everything pre-configured.

### Option 1: Docker Compose (ONE command, starts everything)

```powershell
cd "d:\Smart Healthcare"
docker-compose up -d --build
```

**What it does**:
- Downloads/builds all images
- Starts MySQL, Laravel, Python, Queue, Prometheus
- All services run in containers (automatic)
- Everything configured and ready

**Expected output** (after 2-3 minutes):
```
✅ app (Laravel)
✅ mysql (Database)
✅ python-ai (AI Service)
✅ queue (Jobs)
✅ prometheus (Monitoring)
```

**Verify running**:
```powershell
docker-compose ps
```

Should show all services in "Up" state.

**Then validate**:
```powershell
python validate_system.py
```

---

## 🔧 ALTERNATIVE: Manual Setup + Add to PATH

If you want to run services locally (not Docker):

### Step 1: Find PHP Location
```powershell
# Search Windows for php.exe
Get-ChildItem -Path C:\ -Filter "php.exe" -Recurse -ErrorAction SilentlyContinue
```

**Common locations**:
- `C:\php\php.exe`
- `C:\xampp\php\php.exe`
- `C:\wamp\bin\php\phpX.X.X\php.exe`
- `C:\laragon\bin\php\php-X.X.X\php.exe`

### Step 2: Find MySQL Location
```powershell
# Search Windows for mysql.exe
Get-ChildItem -Path C:\ -Filter "mysql.exe" -Recurse -ErrorAction SilentlyContinue
```

**Common locations**:
- `C:\Program Files\MySQL\MySQL Server 8.0\bin\`
- `C:\xampp\mysql\bin\`
- `C:\wamp\bin\mysql\mysqlX.X.X\bin\`

### Step 3: Add PHP to Windows PATH
1. **Open**: Windows Settings → Environment Variables
   - Or: Right-click "This PC" → Properties → Advanced → Environment Variables

2. **Edit**: User/System PATH variable

3. **Add**: The PHP bin directory path
   - Example: `C:\php` or `C:\xampp\php`

4. **Save** and restart PowerShell

5. **Verify**: 
```powershell
php --version
```

### Step 4: Add MySQL to Windows PATH
Similar to PHP:
1. Edit PATH
2. Add MySQL bin directory
3. Restart PowerShell
4. Verify:
```powershell
mysql --version
```

### Step 5: Then Run Manual Startup
```powershell
cd "d:\Smart Healthcare"
python QUICK_STARTUP_REFERENCE.md
```

---

## ✅ RECOMMENDED CHOICE: Docker

Given the missing PATH variables, **Docker is strongly recommended** because:

✅ Everything pre-configured  
✅ No PATH issues  
✅ One command to start everything  
✅ Services isolated and managed  
✅ Easy to stop/start  
✅ Matches production setup  

---

## 🚀 QUICK START WITH DOCKER

```powershell
# 1. Navigate to workspace
cd "d:\Smart Healthcare"

# 2. Build and start all services
docker-compose up -d --build

# 3. Wait 2-3 minutes for services to warm up

# 4. Check they're running
docker-compose ps

# 5. Run validation
python validate_system.py

# Expected: All 7 tests passing ✅
```

**That's it!** Services are running in Docker containers.

---

## 📊 Docker vs Manual Comparison

| Aspect | Docker | Manual |
|--------|--------|--------|
| **Setup** | 1 command | Multiple steps + PATH |
| **Complexity** | Low | High (PATH issues) |
| **Configuration** | Pre-configured | Self-managed |
| **Issues** | Rare | More common |
| **Time to ready** | 3-5 min | 5-10 min + troubleshooting |
| **Restart** | `docker-compose down` | Kill processes |

---

## 📞 WHICH PATH TO CHOOSE?

### Choose Docker if:
- ✅ Want fastest setup (3-5 minutes)
- ✅ Don't want to deal with PATH variables
- ✅ Want reliable, consistent environment
- ✅ Production uses Docker anyway
- ✅ Don't have PHP/MySQL installed

### Choose Manual if:
- ✅ PHP and MySQL already working
- ✅ Prefer running locally (not containers)
- ✅ Need to debug PHP/Python directly
- ✅ Want more control

---

## 🎯 DECISION TIME

**Recommended**: Use Docker (simpler, more reliable)

```powershell
cd "d:\Smart Healthcare"
docker-compose up -d --build
docker-compose ps
python validate_system.py
```

---

## 📚 NEXT STEPS AFTER STARTING

Once services are running (Docker or Manual):

1. **Wait 2-3 minutes** for services to warm up
2. **Run validation**:
   ```powershell
   python validate_system.py
   ```
3. **Expected**: All 7 tests passing ✅
4. **Then**: Follow DEPLOYMENT_CHECKLIST.md

---

## 🆘 IF ISSUES

**Docker can't start**:
- Make sure Docker Desktop is running
- Check: `docker --version`
- If Docker not installed: https://www.docker.com/products/docker-desktop

**Services won't respond**:
- Wait full 3-5 minutes (Python model loading)
- Check: `docker-compose logs`
- Check: `docker ps`

**Still stuck**:
- Provide error message and I can help troubleshoot

---

**Action**: Choose one path above and proceed! 🚀

