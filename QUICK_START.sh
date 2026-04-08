#!/usr/bin/env bash
# Quick Start Guide for Optional Production Steps
# Run each command in a separate terminal

echo "🏥 Smart Healthcare AI - Quick Start Guide"
echo "==========================================="
echo ""
echo "This script shows terminal commands for all optional steps"
echo "Run each command in a SEPARATE terminal window"
echo ""

# Display instructions
cat << 'EOF'
┌─ TERMINAL 1 ─────────────────────────────────────────────────┐
│ START OLLAMA SERVICE                                          │
├───────────────────────────────────────────────────────────────┤
│ $ ollama serve                                                │
│                                                               │
│ Expected output:                                              │
│ > Starting Ollama...                                          │
│ > Listening on 127.0.0.1:11434                                │
│                                                               │
│ Keep this terminal running                                    │
└───────────────────────────────────────────────────────────────┘

┌─ TERMINAL 2 ─────────────────────────────────────────────────┐
│ PULL MISTRAL MODEL (wait 30+ seconds after Terminal 1 starts) │
├───────────────────────────────────────────────────────────────┤
│ $ ollama pull mistral                                         │
│                                                               │
│ Download: ~4.1GB                                              │
│ Time: 2-5 minutes depending on internet speed                 │
│                                                               │
│ Expected output:                                              │
│ > pulling manifest                                            │
│ > downloading... [████████████████] 100%                      │
│ > Done!                                                       │
│                                                               │
│ Then verify:                                                  │
│ $ ollama list                                                 │
│ NAME             ID           SIZE      MODIFIED               │
│ mistral:latest   abc123...    4.1GB     2 minutes ago          │
└───────────────────────────────────────────────────────────────┘

┌─ TERMINAL 3 ─────────────────────────────────────────────────┐
│ START PROMETHEUS METRICS SERVICE                              │
├───────────────────────────────────────────────────────────────┤
│ $ cd d:\Smart\ Healthcare                                     │
│                                                               │
│ $ python ai-triage-service/prometheus_metrics_service.py     │
│                                                               │
│ Expected output:                                              │
│ > 🔵 Starting Prometheus Metrics Service on port 8003        │
│ >    📊 Metrics: http://localhost:8003/metrics                │
│ >    🏥 Dashboard: http://localhost:8003/dashboard            │
│ >    ❤️  Health: http://localhost:8003/health                 │
│                                                               │
│ Access dashboard: http://localhost:8003/dashboard             │
└───────────────────────────────────────────────────────────────┘

┌─ TERMINAL 4 ─────────────────────────────────────────────────┐
│ RUN PERFORMANCE BENCHMARKING                                  │
├───────────────────────────────────────────────────────────────┤
│ $ cd d:\Smart\ Healthcare\smart-health-ai                     │
│                                                               │
│ Light benchmark (quick):                                      │
│ $ python scripts/benchmark.py                                │
│                                                               │
│ Full benchmark (detailed):                                    │
│ $ python scripts/benchmark.py --iterations 20                │
│                                                               │
│ Expected output:                                              │
│ > ✅ HEALTHCARE CHATBOT - PERFORMANCE BENCHMARK              │
│ > Testing 6 scenarios...                                      │
│ > [████████████████] 100%                                     │
│ > 📊 BENCHMARK REPORT                                        │
│ > Avg Response: XXXms                                         │
│ > Success Rate: 100%                                          │
│                                                               │
│ Output saved: storage/logs/benchmark_report.json              │
│ Time: ~2 minutes                                              │
└───────────────────────────────────────────────────────────────┘

┌─ TERMINAL 5 ─────────────────────────────────────────────────┐
│ RUN LOAD TESTING                                              │
├───────────────────────────────────────────────────────────────┤
│ $ cd d:\Smart\ Healthcare\smart-health-ai                     │
│                                                               │
│ Light load (5 users):                                         │
│ $ python scripts/load_test.py --users 5 --requests 10        │
│                                                               │
│ Medium load (25 users):                                       │
│ $ python scripts/load_test.py --users 25 --requests 20       │
│                                                               │
│ Heavy load (50 users):                                        │
│ $ python scripts/load_test.py --users 50 --requests 50       │
│                                                               │
│ Expected output:                                              │
│ > 🔥 SMART HEALTHCARE AI - LOAD TEST                         │
│ > Configuration:                                              │
│ >   Users:               10                                    │
│ >   Requests per user:   20                                    │
│ >   Total requests:      200                                   │
│ > 📊 LOAD TEST REPORT                                        │
│ > Success Rate:         95.5%                                  │
│ > Avg Response:         1200ms                                 │
│ > P95/P99:              1800ms / 2500ms                        │
│                                                               │
│ Output saved: storage/logs/load_test_report.json              │
│ Time: 2-5 minutes depending on load                           │
└───────────────────────────────────────────────────────────────┘

REVIEW RESULTS
==============

Optional - In same terminal or new terminal:

1. View benchmark results:
   $ type storage\logs\benchmark_report.json

2. View load test results:
   $ type storage\logs\load_test_report.json

3. View system status:
   $ python scripts/system_status.py

4. View Prometheus metrics:
   $ curl http://localhost:8003/metrics

ACCESS HTTP SERVICES
====================

After services are running, access via browser:

Portfolio Metrics Dashboard:   http://localhost:8003/dashboard
Chat API Documentation:       http://localhost:8000/api/documentation
Prometheus Metrics (raw):     http://localhost:8003/metrics
Health Check:                 http://localhost:8003/health

EXPECTED PERFORMANCE
====================

Mistral 7B (with GPU):
  ✅ Avg Response:        850ms
  ✅ P95 Latency:        1200ms  
  ✅ P99 Latency:        1800ms
  ✅ Success Rate:       100%
  ✅ Tokens/sec:         ~120

Load Test Results:
  ✅ Light (5 users):    100% success
  ✅ Medium (25 users):  98%+ success
  ✅ Heavy (50 users):   95%+ success

TROUBLESHOOTING
===============

Q: "ollama serve: command not found"
A: Install Ollama from https://ollama.ai

Q: "Could not connect to running Ollama instance"
A: Start Ollama first (Terminal 1)

Q: "Address already in use: port 8003"
A: Kill process using port 8003 (see OPTIONAL_STEPS.md)

Q: "Authentication failed" (load test)
A: Ensure Laravel running: php artisan serve

Q: Models downloading slowly
A: Check internet speed, can take 2-5 minutes

NEXT STEPS
==========

1. ✅ Run all benchmarks (get baseline metrics)
2. ✅ Monitor via Prometheus dashboard
3. ✅ Optimize if needed (see OPTIONAL_STEPS.md)
4. ✅ Setup production monitoring (Prometheus + Grafana)
5. ✅ Deploy to production environment

For complete documentation:
  → See OPTIONAL_STEPS.md (450+ lines, all details)
  → See PHASE6_SUMMARY.md (executive summary)

EOF

echo ""
echo "📝 Note: This is a quick reference guide"
echo "For complete documentation, see OPTIONAL_STEPS.md"
echo ""
