#!/usr/bin/env python3
"""
Performance Benchmark Script
Compares Mistral 7B vs Fallback response times and quality

Usage:
    python scripts/benchmark.py
"""

import time
import json
import requests
import statistics
from typing import Dict, List
from dataclasses import dataclass
from datetime import datetime

@dataclass
class BenchmarkResult:
    """Single benchmark result"""
    test_case: str
    provider: str
    response_time: float
    status_code: int
    tokens_estimated: int
    quality_score: float  # 0-1.0


class HealthcareBenchmark:
    """Benchmark Mistral 7B vs Fallback for healthcare queries"""
    
    # Test cases (realistic healthcare scenarios)
    TEST_CASES = [
        {
            "name": "Low Severity",
            "message": "Saya demam sedikit, tapi masih bisa bekerja",
            "expected_intent": "symptom_query",
        },
        {
            "name": "Medium Severity",
            "message": "Demam 38 derajat dan sakit kepala berhari-hari",
            "expected_intent": "symptom_query",
        },
        {
            "name": "Greeting",
            "message": "Halo, selamat pagi. Bagaimana kabar Anda?",
            "expected_intent": "greeting",
        },
        {
            "name": "Medication Query",
            "message": "Apakah saya boleh minum paracetamol untuk demam ini?",
            "expected_intent": "medication_advice",
        },
        {
            "name": "Appointment",
            "message": "Saya ingin membuat jadwal konsultasi dengan dokter",
            "expected_intent": "appointment",
        },
        {
            "name": "Lifestyle",
            "message": "Bagaimana cara menurunkan berat badan dengan sehat?",
            "expected_intent": "lifestyle",
        },
    ]

    def __init__(self, api_url: str = "http://localhost:8000", token: str = None):
        self.api_url = api_url
        self.token = token or self._get_test_token()
        self.headers = {
            "Authorization": f"Bearer {self.token}",
            "Content-Type": "application/json",
        }
        self.results: List[BenchmarkResult] = []

    def _get_test_token(self) -> str:
        """Get JWT token for testing"""
        # Try to login with test user, or register if needed
        try:
            resp = requests.post(
                f"{self.api_url}/api/auth/login",
                json={"email": "benchmark@test.com", "password": "SecurePassword123!"},
                timeout=5
            )
            if resp.status_code == 200:
                return resp.json()["data"]["access_token"]
        except:
            pass
        
        # Register if not exists
        try:
            resp = requests.post(
                f"{self.api_url}/api/auth/register",
                json={
                    "name": "Benchmark User",
                    "email": "benchmark@test.com",
                    "password": "SecurePassword123!"
                },
                timeout=5
            )
            if resp.status_code == 201:
                return resp.json()["data"]["access_token"]
        except:
            pass
        
        raise Exception("Could not authenticate for benchmarking")

    def run_single_test(self, test_case: Dict, iterations: int = 3) -> List[float]:
        """Run single test case multiple times"""
        times = []
        
        for i in range(iterations):
            start = time.time()
            try:
                resp = requests.post(
                    f"{self.api_url}/api/chat",
                    headers=self.headers,
                    json={"message": test_case["message"], "session_id": f"bench-{i}"},
                    timeout=10
                )
                elapsed = time.time() - start
                
                if resp.status_code == 200:
                    times.append(elapsed)
                    data = resp.json().get("data", {})
                    
                    # Store result
                    self.results.append(BenchmarkResult(
                        test_case=test_case["name"],
                        provider="Mistral 7B",
                        response_time=elapsed,
                        status_code=resp.status_code,
                        tokens_estimated=len(data.get("response", "").split()),
                        quality_score=0.9 if data.get("intent") == test_case["expected_intent"] else 0.7
                    ))
            except requests.Timeout:
                times.append(10.0)  # Max timeout
            except Exception as e:
                print(f"  ⚠️  Error: {str(e)}")
                times.append(None)
        
        return [t for t in times if t is not None]

    def run_benchmark(self, iterations: int = 3) -> Dict:
        """Run full benchmark suite"""
        print("\n" + "="*70)
        print("🚀 SMART HEALTHCARE AI - PERFORMANCE BENCHMARK")
        print("="*70)
        print(f"Testing {len(self.TEST_CASES)} scenarios × {iterations} iterations")
        print()

        benchmark_stats = {}
        
        for test_case in self.TEST_CASES:
            print(f"📋 Testing: {test_case['name']}")
            print(f"   Message: \"{test_case['message'][:60]}...\"")
            
            times = self.run_single_test(test_case, iterations)
            
            if times:
                avg = statistics.mean(times)
                min_time = min(times)
                max_time = max(times)
                stdev = statistics.stdev(times) if len(times) > 1 else 0
                
                benchmark_stats[test_case['name']] = {
                    "avg_ms": round(avg * 1000, 2),
                    "min_ms": round(min_time * 1000, 2),
                    "max_ms": round(max_time * 1000, 2),
                    "stdev_ms": round(stdev * 1000, 2),
                    "iterations": len(times),
                }
                
                # Display result
                bar_length = int(avg * 50)  # Scale for visualization
                bar = "█" * bar_length
                print(f"   ├─ Avg: {avg*1000:.1f}ms {bar}")
                print(f"   ├─ Min/Max: {min_time*1000:.1f}ms / {max_time*1000:.1f}ms")
                print(f"   └─ StDev: {stdev*1000:.1f}ms")
                print()
            else:
                print(f"   ❌ Failed\n")

        return self._generate_report(benchmark_stats)

    def _generate_report(self, stats: Dict) -> Dict:
        """Generate comprehensive benchmark report"""
        all_times = [r.response_time for r in self.results]
        
        report = {
            "timestamp": datetime.now().isoformat(),
            "total_requests": len(self.results),
            "total_time_seconds": sum(all_times),
            "avg_response_ms": round(statistics.mean(all_times) * 1000, 2) if all_times else 0,
            "min_response_ms": round(min(all_times) * 1000, 2) if all_times else 0,
            "max_response_ms": round(max(all_times) * 1000, 2) if all_times else 0,
            "p95_response_ms": round(self._percentile(all_times, 95) * 1000, 2) if all_times else 0,
            "p99_response_ms": round(self._percentile(all_times, 99) * 1000, 2) if all_times else 0,
            "avg_quality_score": round(
                statistics.mean([r.quality_score for r in self.results]),
                2
            ) if self.results else 0,
            "test_cases": {
                r.test_case: {
                    "response_ms": round(r.response_time * 1000, 2),
                    "tokens": r.tokens_estimated,
                    "tokens_per_sec": round(r.tokens_estimated / r.response_time, 1) if r.response_time > 0 else 0,
                    "quality": r.quality_score,
                }
                for r in self.results
            }
        }
        
        print("\n" + "="*70)
        print("📊 BENCHMARK SUMMARY")
        print("="*70)
        print(f"Total Requests:        {report['total_requests']}")
        print(f"Avg Response Time:     {report['avg_response_ms']}ms")
        print(f"Min Response Time:     {report['min_response_ms']}ms")
        print(f"Max Response Time:     {report['max_response_ms']}ms")
        print(f"P95 Response Time:     {report['p95_response_ms']}ms")
        print(f"P99 Response Time:     {report['p99_response_ms']}ms")
        print(f"Avg Quality Score:     {report['avg_quality_score']}/1.0")
        print("="*70)
        
        return report

    @staticmethod
    def _percentile(data: List[float], percentile: int) -> float:
        """Calculate percentile"""
        sorted_data = sorted(data)
        index = int((percentile / 100) * len(sorted_data))
        return sorted_data[min(index, len(sorted_data) - 1)]


class MistralVsFallbackBenchmark:
    """Compare Mistral 7B vs Fallback provider performance"""
    
    def __init__(self, api_url: str = "http://localhost:8000"):
        self.api_url = api_url

    def run(self) -> Dict:
        """Run comparison benchmark"""
        print("\n🆚 MISTRAL 7B vs FALLBACK COMPARISON")
        print("="*70)
        
        # Note: This would require switching providers dynamically
        # For now, just run with current provider
        benchmark = HealthcareBenchmark(self.api_url)
        return benchmark.run_benchmark(iterations=2)


if __name__ == "__main__":
    import sys
    
    # Run benchmark
    benchmark = HealthcareBenchmark()
    report = benchmark.run_benchmark(iterations=3)
    
    # Save report
    report_file = "storage/logs/benchmark_report.json"
    with open(report_file, "w") as f:
        json.dump(report, f, indent=2)
    
    print(f"\n✅ Report saved to: {report_file}")
    print("\n💡 Recommendations:")
    print("  • Install Ollama + pull mistral for GPU acceleration")
    print("  • Consider caching frequent query patterns")
    print("  • Monitor p95/p99 latencies in production")
    print("  • Set up alerts for response time degradation")
