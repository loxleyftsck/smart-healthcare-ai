#!/usr/bin/env python3
"""
Smart Healthcare AI - Manual Service Startup Guide
Step-by-step instructions for starting all services manually
"""

def print_header(title):
    print(f"\n{'='*80}")
    print(f"  🏥 {title}")
    print(f"{'='*80}\n")

def print_section(title):
    print(f"\n{'─'*80}")
    print(f"  📋 {title}")
    print(f"{'─'*80}\n")

def print_instruction(step, total, title, details):
    print(f"\n  ▶️  STEP {step}/{total}: {title}")
    print(f"  {'─'*76}")
    for line in details:
        print(f"     {line}")

def main():
    print_header("MANUAL SERVICE STARTUP GUIDE")
    print("  Follow these steps in separate PowerShell terminals")
    print("  Each terminal stays running - do NOT close until deployment complete")
    
    # Terminal 1 - MySQL
    print_section("TERMINAL 1: MySQL Database Setup")
    print_instruction(
        1, 4,
        "Check MySQL Status (Run ONCE)",
        [
            "Command: mysql -u root -p",
            "",
            "You should see: mysql> prompt",
            "",
            "If MySQL is already running, just type: exit",
            "If MySQL not running, it auto-starts on Windows usually.",
            "",
            "Exit with: exit",
            "",
            "✅ Expected Result: Can connect to MySQL"
        ]
    )
    
    # Terminal 2 - Laravel
    print_section("TERMINAL 2: Laravel Application Server")
    print_instruction(
        2, 4,
        "Start Laravel Development Server",
        [
            "Commands:",
            "  cd \"d:\\Smart Healthcare\\smart-health-ai\"",
            "  php artisan migrate:fresh --seed  # Reset/seed database",
            "  php artisan serve --port=8000 --no-interaction",
            "",
            "This terminal should show:",
            "  INFO  Server running on [http://127.0.0.1:8000]",
            "",
            "DO NOT close this terminal - Laravel stays running",
            "",
            "Test it: Open browser → http://localhost:8000/api/health",
            "",
            "✅ Expected Result: Returns JSON with 'status': 'ok'"
        ]
    )
    
    # Terminal 3 - Python Service
    print_section("TERMINAL 3: Python AI Triage Service")
    print_instruction(
        3, 4,
        "Start Python FastAPI Service",
        [
            "Commands:",
            "  cd \"d:\\Smart Healthcare\\ai-triage-service\"",
            "  python main.py",
            "",
            "This terminal should show:",
            "  INFO:     Uvicorn running on http://0.0.0.0:5000",
            "",
            "DO NOT close this terminal - Python service stays running",
            "",
            "Test it: Open browser → http://localhost:5000/api/health",
            "",
            "✅ Expected Result: Returns {'status': 'running'}"
        ]
    )
    
    # Terminal 4 - Queue Worker
    print_section("TERMINAL 4: Laravel Queue Worker (Background)")
    print_instruction(
        4, 4,
        "Start Queue Job Processor",
        [
            "Commands:",
            "  cd \"d:\\Smart Healthcare\\smart-health-ai\"",
            "  php artisan queue:work --tries=3 --max-time=3600",
            "",
            "This terminal should show:",
            "  [INFO] Listening on queue: default",
            "",
            "DO NOT close this terminal - Queue worker stays running",
            "",
            "What it does: Processes async database optimization jobs",
            "",
            "✅ Expected Result: Waiting for jobs to process"
        ]
    )
    
    # Waiting and Verification
    print_section("WAIT FOR STARTUP (2-3 MINUTES)")
    print("""
  Systems are starting and warming up:
  
  ✓ Laravel: Compiling routes and loading config
  ✓ Python: Loading Mistral 7B LLM model (LARGEST WAIT)
  ✓ Database: Creating tables and indexes
  ✓ Cache: Initializing file cache storage
  
  WHAT YOU'LL SEE:
  ├─ Laravel terminal: [INFO] Messages about migrations
  ├─ Python terminal: [INFO] Model loaded messages
  ├─ Database: Creating tables, adding indexes
  └─ Queue: Ready for jobs
  
  ⏱️  Estimated time: 2-3 minutes for full warmup
  🔍 Monitor each terminal for errors (red text = problem)
    """)
    
    # Verification Commands
    print_section("VERIFICATION COMMANDS (Run in new terminal)")
    print("\n  Test Laravel API:")
    print("    curl http://localhost:8000/api/health")
    print()
    print("  Test Python API:")
    print("    curl http://localhost:5000/api/health")
    print()
    print("  Test Database:")
    print("    mysql -u root -p smart_health_ai -e \"SHOW TABLES;\"")
    print()
    print("  ✅ Expected: All three return successful responses")
    
    # Run Validation
    print_section("RUN VALIDATION SCRIPT")
    print("""
  Once services are stable (2-3 mins after startup):
  
  Command (in NEW terminal):
    cd "d:\\Smart Healthcare"
    python validate_system.py
  
  Expected Output:
    📊 RESULTS SUMMARY
      Total Tests: 7
      ✅ Passed: 7
      ❌ Failed: 0
      ⚠️  Warned: 0
  
  🎯 SUCCESS: All 7 tests passing = Services ready!
    """)
    
    # Troubleshooting
    print_section("TROUBLESHOOTING")
    print("""
  ❌ "Address already in use" error?
     └─ Kill process on port: netstat -ano | findstr :8000
             taskkill /PID {PID} /F
  
  ❌ "Could not connect to database"?
     └─ Check MySQL is running: mysql -u root -p
        If not running, start: net start MySQL80 (Windows service)
  
  ❌ Python service very slow to start?
     └─ Mistral 7B model is 7GB, takes time to load first time
        This is NORMAL - wait 3-5 minutes
  
  ❌ "php: command not found"?
     └─ PHP not in PATH. Add PHP bin to Windows PATH or:
        C:\\php\\php.exe artisan serve --port=8000
  
  ❌ Validation still fails after 5 minutes?
     └─ Check all 4 terminals for error messages (red text)
        Look for: FATAL, ERROR, Exception
    """)
    
    # Next Steps
    print_section("NEXT STEPS AFTER STARTUP")
    print("""
  After validation passes (all 7 tests ✅):
  
  1. Keep all 4 terminals RUNNING
     (They stay running for entire development session)
  
  2. Run test suite in NEW terminal:
     cd "d:\\Smart Healthcare\\smart-health-ai"
     php artisan test
  
  3. Capture baseline metrics:
     curl -w "@curl-format.txt" -o /dev/null -s http://localhost:8000/api/health
  
  4. Run load test (quick 30-second test):
     cd "d:\\Smart Healthcare"
     python load_test_advanced.py 10 30
  
  5. Then proceed to: DEPLOYMENT_CHECKLIST.md
    """)
    
    # Final Summary
    print_section("SUMMARY - Keep These 4 Terminals Running")
    print("""
  Terminal 1: MySQL Database
    └─ cd, mysql setup (quick check)
  
  Terminal 2: Laravel Server
    └─ cd smart-health-ai && php artisan serve --port=8000
    └─ ▶️  STAYS RUNNING (do NOT close)
  
  Terminal 3: Python AI Service
    └─ cd ai-triage-service && python main.py
    └─ ▶️  STAYS RUNNING (do NOT close)
  
  Terminal 4: Queue Worker
    └─ cd smart-health-ai && php artisan queue:work
    └─ ▶️  STAYS RUNNING (do NOT close)
  
  ✅ Once all 4 running → Validation will pass
  ✅ Then → Full deployment checklist
  ✅ Then → Friday production deployment (3-phase rollout)
    """)
    
    print("\n" + "="*80)
    print("  🚀 READY TO START? Go to each terminal and run the commands above!")
    print("="*80 + "\n")

if __name__ == "__main__":
    main()
