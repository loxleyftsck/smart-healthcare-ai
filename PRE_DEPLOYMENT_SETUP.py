#!/usr/bin/env python3
"""
Smart Healthcare AI - Pre-Deployment Setup Assistant
Guides through starting all services and running pre-deployment checks
"""

import os
import subprocess
import time
from pathlib import Path

WORKSPACE_ROOT = r"d:\Smart Healthcare"
LARAVEL_DIR = os.path.join(WORKSPACE_ROOT, "smart-health-ai")
PYTHON_DIR = os.path.join(WORKSPACE_ROOT, "ai-triage-service")

def print_header(title):
    print(f"\n{'='*70}")
    print(f"  🏥 {title}")
    print(f"{'='*70}\n")

def print_section(title):
    print(f"\n{'─'*70}")
    print(f"  📋 {title}")
    print(f"{'─'*70}\n")

def print_step(step_num, total, description):
    print(f"  [{step_num}/{total}] {description}")

def print_success(msg):
    print(f"  ✅ {msg}")

def print_warning(msg):
    print(f"  ⚠️  {msg}")

def print_info(msg):
    print(f"  ℹ️  {msg}")

def startup_guide():
    print_header("PRE-DEPLOYMENT STARTUP GUIDE")
    
    print("""
    WHAT'S ABOUT TO HAPPEN:
    ─────────────────────
    We will walk through starting all required services for deployment.
    
    Services to start (in order):
    1. MySQL Database (port 3306)
    2. Laravel Application (port 8000)
    3. Python AI Triage Service (port 5000)
    4. Queue Worker (background)
    
    Choose your startup method:
    """)
    
    print("  A) DOCKER (Recommended - Easiest) 🐳")
    print("     └─ Starts everything in containers automatically")
    print()
    print("  B) MANUAL (For detailed control) 🔧")
    print("     └─ Start each service in separate terminal")
    print()
    
    choice = input("  Choose [A/B]: ").strip().upper()
    
    if choice == "A":
        docker_startup()
    elif choice == "B":
        manual_startup()
    else:
        print_warning("Invalid choice. Please run again.")

def docker_startup():
    print_section("DOCKER STARTUP METHOD")
    
    print_info("This method uses docker-compose to start all services at once.")
    print()
    
    print_step(1, 4, "Navigate to workspace root")
    print(f"  cd \"{WORKSPACE_ROOT}\"")
    print()
    
    print_step(2, 4, "Build and start all services")
    print("  docker-compose up -d --build")
    print()
    print_info("This will:")
    print("  • Pull latest images")
    print("  • Build Docker images")
    print("  • Start MySQL, Laravel, Python, Nginx, Prometheus")
    print("  • All services run in background")
    print()
    
    print_step(3, 4, "Wait for services to be ready (2-3 minutes)")
    print("  • Docker containers starting...")
    print("  • Database initializing...")
    print("  • Laravel app warming up...")
    print()
    
    print_step(4, 4, "Verify all services are running")
    print("  docker-compose ps")
    print()
    print_info("Expected output: All containers in 'Up' state")
    print()
    
    print("\n" + "="*70)
    print("  📋 DOCKER STARTUP COMMANDS (Copy & Paste These)")
    print("="*70 + "\n")
    
    print(f"  cd \"{WORKSPACE_ROOT}\"")
    print("  docker-compose up -d --build")
    print("  docker-compose ps")
    print()

def manual_startup():
    print_section("MANUAL STARTUP METHOD")
    
    print_info("Start each service in a separate terminal/PowerShell window.")
    print()
    
    print_step(1, 4, "TERMINAL 1 - MySQL Database")
    print("  This step depends on your MySQL installation:")
    print()
    print("  Option A: MySQL Service (Windows)")
    print("    • If installed as Windows service: mysql service should auto-start")
    print("    • Verify: mysql -u root -p")
    print()
    print("  Option B: Docker for MySQL alone")
    print("    • docker run -d -p 3306:3306 -e MYSQL_ROOT_PASSWORD=secret mysql:8.0")
    print()
    print("  Option C: Already running")
    print("    • If MySQL already running, skip this step")
    print()
    
    print_step(2, 4, "TERMINAL 2 - Laravel Application")
    print(f"  cd \"{LARAVEL_DIR}\"")
    print("  php artisan serve --port=8000 --no-interaction")
    print()
    print_info("Expected: 'Laravel development server started at http://127.0.0.1:8000'")
    print()
    
    print_step(3, 4, "TERMINAL 3 - Python AI Triage Service")
    print(f"  cd \"{PYTHON_DIR}\"")
    print("  python main.py")
    print()
    print_info("Expected: 'Uvicorn running on http://0.0.0.0:5000'")
    print()
    
    print_step(4, 4, "TERMINAL 4 - Queue Worker (Background Jobs)")
    print(f"  cd \"{LARAVEL_DIR}\"")
    print("  php artisan queue:work --tries=3 --max-time=3600")
    print()
    print_info("Expected: 'Listening on the queue...'")
    print()
    
    print("\n" + "="*70)
    print("  📋 MANUAL STARTUP QUICK REFERENCE")
    print("="*70 + "\n")
    
    print("  TERMINAL 1 (MySQL):")
    print("    mysql -u root -p")
    print()
    print("  TERMINAL 2 (Laravel):")
    print(f"    cd \"{LARAVEL_DIR}\" && php artisan serve --port=8000")
    print()
    print("  TERMINAL 3 (Python):")
    print(f"    cd \"{PYTHON_DIR}\" && python main.py")
    print()
    print("  TERMINAL 4 (Queue):")
    print(f"    cd \"{LARAVEL_DIR}\" && php artisan queue:work --tries=3")
    print()

def post_startup_guide():
    print_section("POST-STARTUP VERIFICATION")
    
    print_info("After services are running, follow these steps:")
    print()
    
    print_step(1, 3, "Wait 2-3 minutes for systems to stabilize")
    print("  • Database migrations running")
    print("  • Cache warming up")
    print("  • All connections establishing")
    print()
    
    print_step(2, 3, "Run validation script")
    print(f"  cd \"{WORKSPACE_ROOT}\"")
    print("  python validate_system.py")
    print()
    print_info("Expected: All 7 tests PASSING ✅")
    print()
    
    print_step(3, 3, "Proceed with deployment checklist")
    print("  Review: DEPLOYMENT_CHECKLIST.md")
    print("  Follow: Phase 1 → Phase 2 → Phase 3")
    print()

def main():
    print_header("SMART HEALTHCARE AI PRE-DEPLOYMENT")
    print(f"  Date: 2026-04-08")
    print(f"  Status: Ready to start services")
    print(f"  Deployment: Friday, April 11, 2026")
    print()
    
    print_section("CURRENT STATE")
    print_info("Services status: NOT RUNNING")
    print_info("Validation results: Some tests failed (expected - services not started)")
    print_info("Next step: Start services using Docker or manual method")
    print()
    
    startup_guide()
    
    print("\n" + "="*70)
    print("  ⏭️  NEXT STEPS")
    print("="*70 + "\n")
    
    print("  After services are running:")
    print()
    print("  1. Wait 2-3 minutes for systems to stabilize")
    print("  2. Run: python validate_system.py")
    print("  3. Verify all tests pass: ✅ 7/7 PASSED")
    print("  4. Check baseline metrics")
    print("  5. Start deployment checklist")
    print()
    
    print("="*70)
    print("  📚 HELPFUL DOCUMENTS")
    print("="*70 + "\n")
    
    print("  • DEPLOYMENT_CHECKLIST.md")
    print("    └─ Complete step-by-step deployment guide")
    print()
    print("  • DEPLOYMENT_STARTUP_GUIDE.md")
    print("    └─ Detailed service startup procedures")
    print()
    print("  • PROJECT_COMPLETE.md")
    print("    └─ Project status and overview")
    print()
    print("  • EXECUTIVE_SUMMARY.md")
    print("    └─ Business impact metrics")
    print()

if __name__ == "__main__":
    main()
