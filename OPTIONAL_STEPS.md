# 🚀 Optional Production Steps - Complete Guide

> Smart Healthcare AI System - Performance Optimization & Production Monitoring

## 📋 Overview

This guide walks through 4 optional but recommended production-readiness steps:

1. **Ollama Setup** - Local GPU-accelerated Mistral 7B LLM
2. **Performance Benchmarking** - Establish baseline metrics  
3. **Prometheus Metrics** - Real-time monitoring infrastructure
4. **Load Testing** - Validate system under concurrent load

**Time Estimate**: 15-30 minutes  
**Prerequisites**: Python 3.8+, Ollama v0.18.2+, Docker (optional for Prometheus)

---

## ✅ Step 1: Ollama Setup (GPU-Accelerated Mistral 7B)

### Why Ollama?
- **Local inference**: No API costs, no internet required
- **GPU acceleration**: ~3.5GB VRAM, 1-2s response time with NVIDIA GPU
- **Privacy**: Patient data never leaves your system
- **Healthcare focus**: Optimized for medical terminology

### Prerequisites
```bash
# Check Python availability
python --version  # Python 3.8+

# Check Ollama installation
ollama --version  # Should be v0.18.2+
```

### Installation (Windows)

**Option A: Official Installer (Recommended)**
1. Download: https://ollama.ai/download/windows
2. Run installer
3. Close any command windows that auto-open

**Option B: Windows Subsystem for Linux (WSL2)**
```bash
# In WSL2 terminal
curl https://ollama.ai/install.sh | sh
```

**Option C: Docker (if available)**
```bash
docker run -d --gpus all -v ollama:/root/.ollama \
  -p 11434:11434 ollama/ollama
```

### Verify Installation

```bash
# Check Ollama is installed
ollama --version

# Expected output: ollama version is 0.18.2 (or higher)
```

### Start Ollama Service

```bash
# Windows - Command Prompt (as Administrator)
ollama serve

# Output should show:
# Starting Ollama...
# Listening on 127.0.0.1:11434
```

### Pull Mistral Model

```bash
# In a NEW terminal/command window (while ollama serve runs)
ollama pull mistral

# First run: Downloads ~4.1GB
# Subsequent runs: Uses cached model
# Progress: Should complete in 2-5 minutes depending on internet
```

### Verify Model

```bash
# List available models
ollama list

# Expected output:
# NAME              ID              SIZE      MODIFIED
# mistral:latest    abc123...       4.1GB     2 minutes ago
```

### Test Local LLM

```bash
# Test chat interface
ollama run mistral

# Type a message and press Enter:
> Halo, apa kabar?

# Expected response within 2-3 seconds
```

### Configuration

Update `.env` file:
```env
OLLAMA_URL=http://localhost:11434
OLLAMA_MODEL=mistral
OLLAMA_TIMEOUT=60
```

**Verify connectivity:**
```bash
curl http://localhost:11434/api/tags
```

---

## ✅ Step 2: Performance Benchmarking

### Purpose
- Establish **baseline metrics** for optimization comparison
- Measure response times across healthcare scenarios
- Track quality scores and token efficiency

### Run Benchmark

```bash
# From project root
cd d:\Smart\ Healthcare\smart-health-ai

# Run 1: Light load (quick baseline)
python scripts/benchmark.py

# Run 2: Medium load (default - 5 users, 10 requests each)
python scripts/benchmark.py

# Run 3: Custom parameters
python scripts/benchmark.py --iterations 20

# Expected output:
# ✅ Healthcare Chatbot - Performance Benchmark
# ... test results ...
# 📊 BENCHMARK REPORT
# Avg Response Time: XXXms
# ... detailed metrics ...
```

### Output Files

```
storage/logs/benchmark_report.json

{
  "timestamp": "2025-01-XX...",
  "test_cases": [...],
  "provider_stats": {...},
  "quality_metrics": {...}
}
```

### Interpretation Guide

| Metric | Excellent | Good | Warning | Critical |
|--------|-----------|------|---------|----------|
| **Avg Response** | <500ms | <1000ms | <2000ms | >2000ms |
| **P95 Response** | <1000ms | <2000ms | <4000ms | >4000ms |
| **Success Rate** | 100% | 95%+ | 90%+ | <90% |
| **Tokens/sec** | >100 | >50 | >25 | <25 |

### Expected Baseline (Mistral 7B on GPU)

```
Avg Response Time:     850ms
P95 Response Time:    1200ms
P99 Response Time:    1800ms
Success Rate:         100%
Tokens/sec:           120
```

---

## ✅ Step 3: Prometheus Metrics Integration

### Architecture

```
Laravel ChatController
         ↓
  LocalLlmService
         ↓
    Metrics HTTP Call
         ↓
Prometheus Metrics Service (port 8003)
         ↓
   /metrics endpoint
         ↓
  Prometheus Server (port 9090)
         ↓
   Grafana Dashboard (port 3000)
```

### Start Metrics Service

```bash
# From project root
cd d:\Smart\ Healthcare

# Install Prometheus client (if not already installed)
pip install prometheus-client

# Start metrics service
python ai-triage-service/prometheus_metrics_service.py

# Expected output:
# 🔵 Starting Prometheus Metrics Service on port 8003
#    📊 Metrics: http://localhost:8003/metrics
#    🏥 Dashboard: http://localhost:8003/dashboard
#    ❤️  Health: http://localhost:8003/health
```

### Verify Metrics Endpoint

```bash
# In new terminal
curl http://localhost:8003/metrics

# Expected output: Prometheus text format
# healthcare_chat_response_time_ms_bucket{le="100.0"} 0.0
# healthcare_chat_response_time_ms_bucket{le="250.0"} 1.0
# ...
```

### Setup Prometheus (Optional but Recommended)

**Docker Compose approach:**

```yaml
# docker-compose.override.yml
services:
  prometheus:
    image: prom/prometheus:latest
    ports:
      - "9090:9090"
    volumes:
      - ./docker/prometheus/prometheus.yml:/etc/prometheus/prometheus.yml
      - prometheus_data:/prometheus
    command:
      - "--config.file=/etc/prometheus/prometheus.yml"
      - "--storage.tsdb.path=/prometheus"

  grafana:
    image: grafana/grafana:latest
    ports:
      - "3000:3000"
    environment:
      GF_SECURITY_ADMIN_PASSWORD: admin
    volumes:
      - grafana_data:/var/lib/grafana

volumes:
  prometheus_data:
  grafana_data:
```

**Start Prometheus:**
```bash
docker-compose up -d prometheus grafana
```

**Configure Prometheus:**

Create `docker/prometheus/prometheus.yml`:
```yaml
global:
  scrape_interval: 15s
  evaluation_interval: 15s

scrape_configs:
  - job_name: 'healthcare-metrics'
    static_configs:
      - targets: ['localhost:8003']
    scrape_interval: 5s
```

**Access Dashboards:**
- Prometheus: http://localhost:9090
- Grafana: http://localhost:3000 (admin/admin)
- Metrics Service: http://localhost:8003/dashboard

### Key Metrics to Monitor

```
healthcare_chat_response_time_ms
  └─ Histogram: 95th percentile, 99th percentile
  └─ Alert: P95 > 2s

healthcare_provider_requests_total{provider="mistral"}
  └─ Counter: Requests per provider
  └─ Alert: None

healthcare_triage_severity_total{severity="HIGH"}
  └─ Counter: Triage assessments
  └─ Alert: HIGH severity spikes may indicate system issues

healthcare_active_sessions
  └─ Gauge: Current active sessions
  └─ Alert: > 100 sessions = scale warning

healthcare_errors_total
  └─ Counter: Errors by type
  └─ Alert: Any increase = investigate

healthcare_system_health
  └─ Gauge: 1=healthy, 0=unhealthy
  └─ Alert: = 0 = immediate investigation
```

---

## ✅ Step 4: Load Testing

### Purpose
- Validate system handles concurrent users
- Measure throughput and latency under load
- Identify bottlenecks and capacity limits

### Run Load Test

```bash
# Light load: 5 users, 10 requests each = 50 total
python scripts/load_test.py --users 5 --requests 10

# Medium load: 25 users, 20 requests = 500 total
python scripts/load_test.py --users 25 --requests 20

# Heavy load: 50 users, 50 requests = 2500 total
python scripts/load_test.py --users 50 --requests 50

# Custom: 100 users, 100 requests each
python scripts/load_test.py --users 100 --requests 100
```

### Expected Results (Mistral 7B + GPU)

| Users | Requests | Avg Response | P95 | P99 | Success Rate |
|-------|----------|--------------|-----|-----|--------------|
| 5 | 50 | 900ms | 1200ms | 1800ms | 100% |
| 25 | 500 | 1100ms | 1800ms | 2500ms | 98%+ |
| 50 | 2500 | 1500ms | 2500ms | 4000ms | 95%+ |
| 100 | 5000 | 2000ms+ | 3500ms+ | 5000ms+ | 90%+ |

### Performance Optimization Tips

If response times are slow:

1. **GPU Acceleration**
   ```bash
   # Enable GPU in LocalLlmService
   # Set: num_gpu: -1  (auto-detect all GPUs)
   # Requires: NVIDIA GPU + CUDA toolkit
   ```

2. **Model Size Reduction**
   ```bash
   # Switch to smaller model
   ollama pull mistral:7b-q4  # 4-bit quantized (faster, less VRAM)
   ```

3. **Prompt Caching**
   - System prompt cached for 1 hour (reduces re-processing)
   - Already enabled in LocalLlmService

4. **Connection Pooling**
   ```php
   // In LocalLlmService
   // Use persistent HTTP connections
   'http_client' => ['pool_connections' => 10]
   ```

5. **Horizontal Scaling**
   - Run multiple Laravel instances behind load balancer
   - Keep single Ollama/GPU instance (resource-intensive)

---

## 🔄 Complete Workflow

### All-in-One Setup Script

```bash
# 1. Start Ollama (Terminal 1)
ollama serve &

# 2. In new terminal, pull model
ollama pull mistral

# 3. Start Laravel dev server (Terminal 2)
cd smart-health-ai
php artisan serve

# 4. Start Python AI service (Terminal 3)
cd ..
python ai-triage-service/main.py

# 5. Start Prometheus metrics (Terminal 4)
python ai-triage-service/prometheus_metrics_service.py

# 6. Run benchmark (Terminal 5)
python smart-health-ai/scripts/benchmark.py

# 7. Run load test (Terminal 5)
python smart-health-ai/scripts/load_test.py --users 10

# 8. Monitor in browser
# - Metrics: http://localhost:8003/dashboard
# - Prometheus: http://localhost:9090 (if running)
# - API docs: http://localhost:8000/api/documentation
```

---

## 📊 Continuous Monitoring Checklist

- [ ] Ollama running (`ollama serve`)
- [ ] Mistral model available (`ollama list`)
- [ ] Laravel backend healthy (`php artisan serve`)
- [ ] Python AI service responding (`curl http://localhost:8002/health`)
- [ ] Prometheus metrics available (`curl http://localhost:8003/metrics`)
- [ ] No error spikes in logs
- [ ] Response times within target (<1s avg, <2s p95)
- [ ] Success rate >95%
- [ ] GPU utilization visible (if GPU available)

---

## 🆘 Troubleshooting

### Ollama Issues

**Problem**: "Could not connect to running Ollama instance"
```bash
# Solution: Start Ollama server
ollama serve
```

**Problem**: Model download fails
```bash
# Solution: Check internet, retry
ollama pull mistral

# If still fails, check disk space
df -h  # Need ~5GB
```

**Problem**: Slow responses (>3s)
```bash
# Solution 1: Check GPU is being used
ollama ps  # Shows GPU usage

# Solution 2: Reduce model size
ollama pull mistral:7b-q4

# Solution 3: Increase timeout in .env
OLLAMA_TIMEOUT=120
```

### Metrics Service Issues

**Problem**: "Address already in use" port 8003
```bash
# Solution: Kill process using port
# Windows:
netstat -ano | findstr :8003
taskkill /PID <PID> /F

# Linux/Mac:
lsof -i :8003
kill -9 <PID>
```

**Problem**: Metrics not updating
```bash
# Solution: Verify service running
curl http://localhost:8003/health

# Check metrics endpoint
curl http://localhost:8003/metrics | head -20
```

### Load Test Issues

**Problem**: "Authentication failed"
```bash
# Solution: Ensure Laravel is running and database migrated
cd smart-health-ai
php artisan migrate:fresh --seed
php artisan serve
```

**Problem**: "Connection refused"
```bash
# Solution: Start Laravel and check port
php artisan serve --port=8000

# Test:
curl http://localhost:8000/api/health
```

---

## 📈 Next Steps After Setup

1. **Review Benchmark Report**
   - Check `storage/logs/benchmark_report.json`
   - Compare against baselines in Step 2

2. **Set Prometheus Alerts**
   - P95 latency > 2000ms
   - Error rate > 5%
   - Active sessions > 100

3. **Optimization Recommendations**
   - If avg time > 1s: Enable GPU acceleration
   - If errors spike: Check Ollama/database logs
   - If sessions decline: Investigate error logs

4. **Production Deployment**
   - Use Prometheus + Grafana for monitoring
   - Setup alerting (PagerDuty, Slack, etc.)
   - Enable auto-scaling based on metrics
   - Configure backup Ollama instance for HA

---

## 📚 References

- **Ollama Docs**: https://github.com/ollama/ollama
- **Prometheus Docs**: https://prometheus.io/docs/
- **Grafana Docs**: https://grafana.com/docs/
- **Laravel Testing**: https://laravel.com/docs/11.x/testing

---

**Created**: 2025-01-XX  
**Version**: 1.0  
**Status**: Production-Ready
