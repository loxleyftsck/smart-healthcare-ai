#!/usr/bin/env python3
"""
Prometheus Metrics Service
Continuous metrics collection and exposure for monitoring

Tracks:
- Request latency (response_time_ms)
- Intent detection accuracy
- Provider usage distribution
- Triage severity distribution
- Error rates by type
"""

from fastapi import FastAPI, Request
from prometheus_client import Counter, Histogram, Gauge, generate_latest, REGISTRY, CollectorRegistry
import time
from enum import Enum
from typing import Optional
from datetime import datetime
import os

# Create a custom registry for clean metrics
CUSTOM_REGISTRY = CollectorRegistry()

# ============================================================================
# PROMETHEUS METRICS DEFINITIONS
# ============================================================================

# Response latency histogram (in milliseconds)
REQUEST_LATENCY = Histogram(
    "healthcare_chat_response_time_ms",
    "Chat response time in milliseconds",
    buckets=[100, 250, 500, 1000, 2000, 5000, 10000],
    registry=CUSTOM_REGISTRY
)

# Intent detection accuracy gauge
INTENT_DETECTION_ACCURACY = Gauge(
    "healthcare_intent_detection_accuracy_percent",
    "Intent detection accuracy percentage",
    registry=CUSTOM_REGISTRY
)

# Provider usage counter
PROVIDER_USAGE = Counter(
    "healthcare_provider_requests_total",
    "Total requests by provider",
    labelnames=["provider"],
    registry=CUSTOM_REGISTRY
)

# Provider latency histogram
PROVIDER_LATENCY = Histogram(
    "healthcare_provider_response_time_ms",
    "Provider response time in milliseconds",
    labelnames=["provider"],
    buckets=[100, 250, 500, 1000, 2000, 5000],
    registry=CUSTOM_REGISTRY
)

# Triage severity distribution
TRIAGE_SEVERITY = Counter(
    "healthcare_triage_severity_total",
    "Triage assessments by severity level",
    labelnames=["severity"],
    registry=CUSTOM_REGISTRY
)

# Chat message counter
CHAT_MESSAGES = Counter(
    "healthcare_chat_messages_total",
    "Total chat messages processed",
    registry=CUSTOM_REGISTRY
)

# Error counter
ERRORS = Counter(
    "healthcare_errors_total",
    "Total errors by type",
    labelnames=["error_type"],
    registry=CUSTOM_REGISTRY
)

# Active sessions gauge
ACTIVE_SESSIONS = Gauge(
    "healthcare_active_sessions",
    "Number of active chat sessions",
    registry=CUSTOM_REGISTRY
)

# System health gauge (1=healthy, 0=unhealthy)
SYSTEM_HEALTH = Gauge(
    "healthcare_system_health",
    "System health status (1=healthy, 0=unhealthy)",
    registry=CUSTOM_REGISTRY
)

# Ollama availability gauge (1=available, 0=unavailable)
OLLAMA_AVAILABLE = Gauge(
    "healthcare_ollama_available",
    "Ollama service availability (1=available, 0=unavailable)",
    registry=CUSTOM_REGISTRY
)

# Database query time histogram
DB_QUERY_TIME = Histogram(
    "healthcare_db_query_time_ms",
    "Database query execution time",
    labelnames=["query_type"],
    buckets=[10, 50, 100, 250, 500, 1000],
    registry=CUSTOM_REGISTRY
)

# Cache hit rate gauge
CACHE_HIT_RATE = Gauge(
    "healthcare_cache_hit_rate_percent",
    "Cache hit rate percentage",
    registry=CUSTOM_REGISTRY
)


# ============================================================================
# METRICS CLIENT CLASS
# ============================================================================

class MetricsClient:
    """Client for recording metrics in Laravel application via HTTP"""
    
    def __init__(self, app_url: str = "http://localhost:8000"):
        self.app_url = app_url
        self.active_sessions = set()

    def record_chat_message(self, intent: str, response_time_ms: float, provider: str = "mistral"):
        """Record chat message processing"""
        REQUEST_LATENCY.observe(response_time_ms)
        CHAT_MESSAGES.inc()
        PROVIDER_USAGE.labels(provider=provider).inc()
        PROVIDER_LATENCY.labels(provider=provider).observe(response_time_ms)

    def record_triage(self, severity: str):
        """Record triage assessment"""
        TRIAGE_SEVERITY.labels(severity=severity).inc()

    def record_error(self, error_type: str):
        """Record error occurrence"""
        ERRORS.labels(error_type=error_type).inc()

    def record_intent_accuracy(self, accuracy_percent: float):
        """Update intent detection accuracy"""
        INTENT_DETECTION_ACCURACY.set(accuracy_percent)

    def set_cache_hit_rate(self, hit_rate_percent: float):
        """Update cache hit rate"""
        CACHE_HIT_RATE.set(hit_rate_percent)

    def start_session(self, session_id: str):
        """Track active session start"""
        self.active_sessions.add(session_id)
        ACTIVE_SESSIONS.set(len(self.active_sessions))

    def end_session(self, session_id: str):
        """Track active session end"""
        self.active_sessions.discard(session_id)
        ACTIVE_SESSIONS.set(len(self.active_sessions))

    def set_system_health(self, healthy: bool):
        """Update system health status"""
        SYSTEM_HEALTH.set(1 if healthy else 0)

    def set_ollama_available(self, available: bool):
        """Update Ollama availability"""
        OLLAMA_AVAILABLE.set(1 if available else 0)

    def record_db_query(self, query_type: str, query_time_ms: float):
        """Record database query time"""
        DB_QUERY_TIME.labels(query_type=query_type).observe(query_time_ms)

    def get_metrics_text(self) -> str:
        """Export metrics in Prometheus text format"""
        return generate_latest(CUSTOM_REGISTRY).decode('utf-8')


# ============================================================================
# FASTAPI PROMETHEUS METRICS SERVICE
# ============================================================================

class PrometheusMetricsService:
    """FastAPI service for exposing Prometheus metrics"""
    
    def __init__(self, port: int = 8003):
        self.app = FastAPI(title="Healthcare Prometheus Metrics Service")
        self.port = port
        self.metrics_client = MetricsClient()
        self._setup_routes()

    def _setup_routes(self):
        """Setup FastAPI routes"""
        
        @self.app.get("/metrics")
        async def metrics():
            """Prometheus metrics endpoint"""
            return self.metrics_client.get_metrics_text()

        @self.app.get("/health")
        async def health():
            """Health check endpoint"""
            return {
                "status": "healthy",
                "timestamp": datetime.now().isoformat(),
                "version": "1.0.0"
            }

        @self.app.post("/record/chat")
        async def record_chat(request: Request):
            """Record chat message"""
            try:
                data = await request.json()
                self.metrics_client.record_chat_message(
                    intent=data.get("intent", "unknown"),
                    response_time_ms=data.get("response_time_ms", 0),
                    provider=data.get("provider", "mistral")
                )
                return {"success": True}
            except Exception as e:
                self.metrics_client.record_error("chat_recording_error")
                return {"success": False, "error": str(e)}

        @self.app.post("/record/triage")
        async def record_triage(request: Request):
            """Record triage assessment"""
            try:
                data = await request.json()
                self.metrics_client.record_triage(
                    severity=data.get("severity", "unknown")
                )
                return {"success": True}
            except Exception as e:
                self.metrics_client.record_error("triage_recording_error")
                return {"success": False, "error": str(e)}

        @self.app.post("/record/error")
        async def record_error(request: Request):
            """Record error"""
            try:
                data = await request.json()
                self.metrics_client.record_error(
                    error_type=data.get("error_type", "unknown")
                )
                return {"success": True}
            except Exception as e:
                return {"success": False, "error": str(e)}

        @self.app.post("/session/start")
        async def start_session(request: Request):
            """Start tracking session"""
            try:
                data = await request.json()
                self.metrics_client.start_session(data.get("session_id"))
                return {"success": True}
            except Exception as e:
                return {"success": False, "error": str(e)}

        @self.app.post("/session/end")
        async def end_session(request: Request):
            """End tracking session"""
            try:
                data = await request.json()
                self.metrics_client.end_session(data.get("session_id"))
                return {"success": True}
            except Exception as e:
                return {"success": False, "error": str(e)}

        @self.app.get("/dashboard")
        async def dashboard():
            """HTML dashboard for quick metrics view"""
            return """
            <!DOCTYPE html>
            <html>
            <head>
                <title>Healthcare AI - Metrics Dashboard</title>
                <style>
                    body { font-family: sans-serif; margin: 20px; background: #f5f5f5; }
                    .container { max-width: 1200px; margin: 0 auto; }
                    .metric { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                    .title { color: #2c3e50; font-size: 24px; margin-bottom: 20px; }
                    .subtitle { color: #34495e; font-size: 14px; margin-bottom: 10px; }
                    .value { font-size: 32px; font-weight: bold; color: #27ae60; }
                    .status-healthy { color: #27ae60; }
                    .status-warning { color: #f39c12; }
                    .status-critical { color: #e74c3c; }
                    .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 10px; }
                    .chart { background: white; padding: 15px; border-radius: 5px; }
                    .info { color: #7f8c8d; font-size: 12px; }
                </style>
                <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            </head>
            <body>
                <div class="container">
                    <div class="title">🏥 Healthcare AI Metrics Dashboard</div>
                    <div class="subtitle">Real-time performance monitoring</div>
                    
                    <div class="grid">
                        <div class="metric">
                            <div class="subtitle">Status</div>
                            <div class="value status-healthy">✓ Healthy</div>
                            <div class="info">Check /metrics for detailed Prometheus data</div>
                        </div>
                        <div class="metric">
                            <div class="subtitle">Metrics Endpoint</div>
                            <div class="value" style="font-size: 16px;">/metrics</div>
                            <div class="info">Prometheus text format</div>
                        </div>
                    </div>

                    <div class="metric">
                        <h3>Available Metrics</h3>
                        <ul>
                            <li><code>healthcare_chat_response_time_ms</code> - Chat response latency</li>
                            <li><code>healthcare_provider_requests_total</code> - Requests by provider</li>
                            <li><code>healthcare_triage_severity_total</code> - Triage severity distribution</li>
                            <li><code>healthcare_active_sessions</code> - Active session count</li>
                            <li><code>healthcare_errors_total</code> - Errors by type</li>
                            <li><code>healthcare_system_health</code> - Overall system health</li>
                            <li><code>healthcare_ollama_available</code> - Ollama service availability</li>
                            <li><code>healthcare_cache_hit_rate_percent</code> - Cache performance</li>
                        </ul>
                    </div>

                    <div class="metric">
                        <h3>Quick Links</h3>
                        <ul>
                            <li><a href="/health" target="_blank">/health</a> - Health check</li>
                            <li><a href="/metrics" target="_blank">/metrics</a> - Prometheus metrics (raw)</li>
                            <li><code>curl http://localhost:8003/metrics</code> - CLI fetch</li>
                        </ul>
                    </div>

                    <div class="metric">
                        <h3>Integration with Prometheus</h3>
                        <pre>scrape_configs:
  - job_name: 'healthcare-ai'
    static_configs:
      - targets: ['localhost:8003']
    scrape_interval: 15s
    scrape_timeout: 10s</pre>
                    </div>
                </div>
            </body>
            </html>
            """

    def run(self):
        """Run the metrics service"""
        import uvicorn
        print(f"\n🔵 Starting Prometheus Metrics Service on port {self.port}")
        print(f"   📊 Metrics: http://localhost:{self.port}/metrics")
        print(f"   🏥 Dashboard: http://localhost:{self.port}/dashboard")
        print(f"   ❤️  Health: http://localhost:{self.port}/health")
        uvicorn.run(self.app, host="0.0.0.0", port=self.port)

    def get_client(self) -> MetricsClient:
        """Get metrics client for external use"""
        return self.metrics_client


# ============================================================================
# EXAMPLE PROMETHEUS CONFIG
# ============================================================================

PROMETHEUS_CONFIG = """
global:
  scrape_interval: 15s
  evaluation_interval: 15s
  external_labels:
    monitor: 'healthcare-ai-monitor'

scrape_configs:
  - job_name: 'prometheus'
    static_configs:
      - targets: ['localhost:9090']

  - job_name: 'healthcare-ai'
    metrics_path: '/metrics'
    scrape_interval: 5s
    static_configs:
      - targets: ['localhost:8003']
"""

GRAFANA_DASHBOARD_JSON = """{
  "dashboard": {
    "title": "Healthcare AI Chat System",
    "panels": [
      {
        "title": "Response Time (P95)",
        "targets": [{"expr": "histogram_quantile(0.95, healthcare_chat_response_time_ms)"}]
      },
      {
        "title": "Provider Usage",
        "targets": [{"expr": "rate(healthcare_provider_requests_total[5m])"}]
      },
      {
        "title": "Active Sessions",
        "targets": [{"expr": "healthcare_active_sessions"}]
      },
      {
        "title": "Error Rate",
        "targets": [{"expr": "rate(healthcare_errors_total[5m])"}]
      },
      {
        "title": "Triage Severity Distribution",
        "targets": [{"expr": "healthcare_triage_severity_total"}]
      },
      {
        "title": "System Health",
        "targets": [{"expr": "healthcare_system_health"}]
      }
    ]
  }
}
"""


if __name__ == "__main__":
    # Save Prometheus config
    os.makedirs("docker/prometheus", exist_ok=True)
    
    service = PrometheusMetricsService(port=8003)
    service.run()
