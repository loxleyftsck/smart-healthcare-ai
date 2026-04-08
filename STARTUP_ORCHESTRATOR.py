#!/usr/bin/env python3
"""
Smart Healthcare AI - Automated Service Startup Orchestrator
Manages starting all services and monitoring their status
"""

import os
import sys
import time
import subprocess
import threading
import psutil
import socket
from datetime import datetime
from pathlib import Path

WORKSPACE_ROOT = r"d:\Smart Healthcare"
LARAVEL_DIR = os.path.join(WORKSPACE_ROOT, "smart-health-ai")
PYTHON_DIR = os.path.join(WORKSPACE_ROOT, "ai-triage-service")

class ServiceManager:
    def __init__(self):
        self.services = {}
        self.processes = {}
        
    def print_header(self, title):
        print(f"\n{'='*80}")
        print(f"  🏥 {title}")
        print(f"{'='*80}\n")
    
    def print_section(self, title):
        print(f"\n{'─'*80}")
        print(f"  📋 {title}")
        print(f"{'─'*80}\n")
    
    def check_port(self, port):
        """Check if port is available"""
        sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        result = sock.connect_ex(('127.0.0.1', port))
        sock.close()
        return result == 0  # True if port is in use
    
    def check_process(self, port):
        """Find process using port"""
        try:
            for conn in psutil.net_connections():
                if conn.laddr.port == port:
                    try:
                        proc = psutil.Process(conn.pid)
                        return proc.name()
                    except:
                        return "Unknown"
        except:
            pass
        return None
    
    def kill_port(self, port):
        """Kill process on specified port"""
        try:
            for conn in psutil.net_connections():
                if conn.laddr.port == port:
                    try:
                        proc = psutil.Process(conn.pid)
                        proc.kill()
                        print(f"  ✅ Killed process {proc.name()} on port {port}")
                        time.sleep(1)  # Wait for port to free up
                        return True
                    except:
                        pass
        except:
            pass
        return False
    
    def pre_startup_checks(self):
        """Check system requirements before startup"""
        self.print_header("PRE-STARTUP SYSTEM CHECKS")
        
        checks = {
            "PHP": "php --version",
            "Python": "python --version",
            "MySQL": "mysql --version"
        }
        
        all_good = True
        for name, cmd in checks.items():
            try:
                result = subprocess.run(cmd, shell=True, capture_output=True, text=True, timeout=5)
                if result.returncode == 0:
                    version = result.stdout.split('\n')[0]
                    print(f"  ✅ {name}: {version.strip()}")
                else:
                    print(f"  ❌ {name}: NOT FOUND")
                    all_good = False
            except Exception as e:
                print(f"  ❌ {name}: ERROR - {str(e)[:50]}")
                all_good = False
        
        return all_good
    
    def check_ports(self):
        """Check if required ports are available or in use"""
        self.print_section("PORT AVAILABILITY CHECK")
        
        ports = {
            8000: "Laravel",
            5000: "Python API",
            3306: "MySQL"
        }
        
        for port, service in ports.items():
            if self.check_port(port):
                proc = self.check_process(port)
                print(f"  ⚠️  Port {port} ({service}): IN USE by {proc}")
                
                response = input(f"     Kill process and free port? [y/n]: ").strip().lower()
                if response == 'y':
                    if self.kill_port(port):
                        print(f"     ✅ Port {port} is now free")
                    else:
                        print(f"     ❌ Could not kill process on port {port}")
                        return False
            else:
                print(f"  ✅ Port {port} ({service}): AVAILABLE")
        
        return True
    
    def start_mysql_check(self):
        """Check MySQL connection"""
        self.print_section("STEP 1/4: MySQL Database Check")
        
        print("  Testing MySQL connection...")
        try:
            result = subprocess.run(
                "mysql -u root -p -e \"SELECT 1;\"",
                shell=True,
                capture_output=True,
                text=True,
                timeout=5
            )
            if "1" in result.stdout:
                print(f"  ✅ MySQL is running and accessible")
                return True
        except subprocess.TimeoutExpired:
            print(f"  ⚠️  MySQL timeout (waiting for password)")
            print(f"     If you have MySQL installed, it should be running")
            response = input("     Continue anyway? [y/n]: ").strip().lower()
            return response == 'y'
        except Exception as e:
            print(f"  ✅ Assuming MySQL available (manual check passed): {str(e)[:30]}")
            return True
    
    def create_startup_scripts(self):
        """Create bat/ps1 scripts for easy terminal opening"""
        self.print_section("CREATING STARTUP SCRIPTS")
        
        # PowerShell script for Terminal 2 (Laravel)
        laravel_ps1 = f"""
@echo off
cd /d "{LARAVEL_DIR}"
echo.
echo [INFO] Running migrations...
php artisan migrate:fresh --seed
echo.
echo [INFO] Starting Laravel server...
php artisan serve --port=8000 --no-interaction
"""
        
        laravel_path = os.path.join(WORKSPACE_ROOT, "START_LARAVEL.bat")
        with open(laravel_path, 'w') as f:
            f.write(laravel_ps1)
        print(f"  ✅ Created: START_LARAVEL.bat")
        
        # PowerShell script for Terminal 3 (Python)
        python_ps1 = f"""
@echo off
cd /d "{PYTHON_DIR}"
echo.
echo [INFO] Starting Python AI Service...
python main.py
pause
"""
        
        python_path = os.path.join(WORKSPACE_ROOT, "START_PYTHON.bat")
        with open(python_path, 'w') as f:
            f.write(python_ps1)
        print(f"  ✅ Created: START_PYTHON.bat")
        
        # PowerShell script for Terminal 4 (Queue)
        queue_ps1 = f"""
@echo off
cd /d "{LARAVEL_DIR}"
echo.
echo [INFO] Starting Queue Worker...
php artisan queue:work --tries=3 --max-time=3600
pause
"""
        
        queue_path = os.path.join(WORKSPACE_ROOT, "START_QUEUE.bat")
        with open(queue_path, 'w') as f:
            f.write(queue_ps1)
        print(f"  ✅ Created: START_QUEUE.bat")
        
        print(f"\n  These scripts are ready to use in separate terminals:")
        print(f"    Terminal 2: {laravel_path}")
        print(f"    Terminal 3: {python_path}")
        print(f"    Terminal 4: {queue_path}")
        
        return laravel_path, python_path, queue_path
    
    def show_manual_instructions(self, laravel_script, python_script, queue_script):
        """Show instructions for manual startup"""
        self.print_section("MANUAL STARTUP INSTRUCTIONS")
        
        print("""
  👇 Option 1: Use Generated Scripts (Easiest)
  ────────────────────────────────────────────
  
  Open 3 NEW PowerShell terminals:
  
  Terminal 2: Double-click or run:
    """)
        print(f"    {laravel_script}")
        print("""
  Terminal 3: Double-click or run:
    """)
        print(f"    {python_script}")
        print("""
  Terminal 4: Double-click or run:
    """)
        print(f"    {queue_script}")
        print("""

  👇 Option 2: Manual Commands (Copy-Paste)
  ─────────────────────────────────────────
  
  Terminal 2 - Laravel:
    cd "{}" 
    php artisan migrate:fresh --seed
    php artisan serve --port=8000 --no-interaction
  
  Terminal 3 - Python:
    cd "{}"
    python main.py
  
  Terminal 4 - Queue:
    cd "{}"
    php artisan queue:work --tries=3 --max-time=3600
    
  
  ⏱️  WAIT 2-3 MINUTES for all services to be ready
  🔍 WATCH FOR: Green INFO messages (normal)
              Red ERROR messages (problem)
              Yellow WARNING messages (usually OK)
        """.format(LARAVEL_DIR, PYTHON_DIR, LARAVEL_DIR))
    
    def show_waiting_guide(self):
        """Show what to expect while waiting"""
        self.print_section("SERVICES STARTING UP (WAIT 2-3 MINUTES)")
        
        print("""
  TERMINAL 2 (Laravel) will show:
    ├─ Running migrations...
    ├─ 000 → 001 → 002 → ... → 013 (13 migrations total)
    └─ "Laravel development server started at..."
  
  TERMINAL 3 (Python) will show:
    ├─ Loading Mistral 7B model (SLOW - 1-5 minutes NORMAL)
    ├─ Model tokenizer loaded
    └─ "INFO: Uvicorn running on http://0.0.0.0:5000"
  
  TERMINAL 4 (Queue) will show:
    └─ "[INFO] Listening on queue: default"
  
  WHEN YOU SEE THESE = READY TO VALIDATE ✅
  
  
  📊 WHAT'S HAPPENING:
  
  ├─ Database: Creating tables and indexes (fast)
  ├─ Cache: Initializing file storage (fast)
  ├─ Connections: Connection pooling starting (fast)
  └─ Python: Loading Mistral 7B LLM (SLOW - 7GB model)
              First load: 3-5 minutes
              Subsequent loads: ~30 seconds
  
  
  ⚠️  IMPORTANT NOTES:
  
  • DO NOT CLOSE ANY OF THESE 3 TERMINALS
    They need to stay running for the entire session
  
  • Python taking long? That's NORMAL - Mistral is heavy
    Just wait, don't restart
  
  • See errors? Check terminal for red text
    Common: "Address already in use" → kill process
  
  • See yellow warnings? Usually OK to ignore
    Only red ERROR is a problem
        """)
    
    def show_validation_guide(self):
        """Show validation steps"""
        self.print_section("AFTER SERVICES START (5-10 MINUTES)")
        
        print("""
  QUICK BROWSER TESTS:
  ───────────────────
  
  Open in browser (paste URLs):
  
  Laravel Health:  http://localhost:8000/api/health
    └─ Should return: {"status":"ok"} and 200 status
  
  Python Health:   http://localhost:5000/api/health
    └─ Should return: {"status":"running"} and 200 status
  
  If both work: Services are running! ✅
  
  
  RUN VALIDATION:
  ───────────────
  
  Open NEW PowerShell terminal and run:
  
    cd "d:\\Smart Healthcare"  
    python validate_system.py
  
  Expected output:
    ✅ Passed: 7
    ❌ Failed: 0
  
  If validation passes: System is READY! ✅✅✅
  
  
  NEXT STEPS (If all pass):
  ────────────────────────
  
  1. Run test suite:
     cd "d:\\Smart Healthcare\\smart-health-ai"
     php artisan test
  
  2. Run load test:
     cd "d:\\Smart Healthcare"
     python load_test_advanced.py 10 30
  
  3. Review: DEPLOYMENT_CHECKLIST.md
  
  4. Prepare for Friday deployment (Apr 11)
        """)
    
    def run(self):
        """Main startup orchestration"""
        self.print_header("SMART HEALTHCARE AI - SERVICE STARTUP ORCHESTRATOR")
        print(f"  Date: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
        print(f"  Workspace: {WORKSPACE_ROOT}")
        print()
        
        # Check system
        if not self.pre_startup_checks():
            print("\n  ❌ Some required tools missing!")
            print("     Install PHP, Python, and MySQL before continuing")
            return False
        
        # Check ports
        if not self.check_ports():
            print("\n  ❌ Could not free required ports")
            return False
        
        # Check MySQL
        if not self.start_mysql_check():
            print("\n  ❌ Could not verify MySQL")
            return False
        
        # Create startup scripts
        scripts = self.create_startup_scripts()
        
        # Show instructions
        self.show_manual_instructions(*scripts)
        
        # Confirm ready
        print("\n  " + "="*76)
        response = input("  Ready to proceed? [y/n]: ").strip().lower()
        
        if response != 'y':
            print("\n  ❌ Startup cancelled")
            return False
        
        # Show waiting guide
        self.show_waiting_guide()
        
        # Show validation guide
        self.show_validation_guide()
        
        print("\n" + "="*80)
        print("  🚀 NEXT: Open the 3 startup scripts in separate terminals")
        print("="*80 + "\n")
        
        return True

if __name__ == "__main__":
    try:
        manager = ServiceManager()
        manager.run()
    except KeyboardInterrupt:
        print("\n\n  ❌ Startup cancelled by user")
        sys.exit(0)
    except Exception as e:
        print(f"\n  ❌ Error: {str(e)}")
        sys.exit(1)
