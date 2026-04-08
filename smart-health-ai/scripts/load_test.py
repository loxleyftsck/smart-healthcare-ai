#!/usr/bin/env python3
"""
Load Testing Script
Test concurrent users and system capacity

Usage:
    python scripts/load_test.py --users 10 --requests 100
"""

import time
import json
import argparse
import requests
import concurrent.futures
from typing import List, Dict
from dataclasses import dataclass
from datetime import datetime
from collections import defaultdict
import statistics

@dataclass
class LoadTestResult:
    """Single load test request result"""
    user_id: int
    request_num: int
    response_time: float
    status_code: int
    success: bool
    error: str = None


class LoadTester:
    """Load test the healthcare chat system"""
    
    HEALTHCARE_MESSAGES = [
        "Saya demam 39 derajat",
        "Halo, apa kabar?",
        "Sakit kepala berhari-hari",
        "Boleh minum paracetamol?",
        "Ingin membuat jadwal konsultasi",
        "Bagaimana diet sehat?",
        "Mual dan muntah terus",
        "Batuk kering selama seminggu",
    ]

    def __init__(self, api_url: str = "http://localhost:8000", token: str = None):
        self.api_url = api_url
        self.token = token or self._get_test_token()
        self.results: List[LoadTestResult] = []
        self.errors: List[str] = []

    def _get_test_token(self) -> str:
        """Get JWT token for testing"""
        try:
            resp = requests.post(
                f"{self.api_url}/api/auth/login",
                json={"email": "loadtest@test.com", "password": "SecurePassword123!"},
                timeout=5
            )
            if resp.status_code == 200:
                return resp.json()["data"]["access_token"]
        except:
            pass
        
        try:
            resp = requests.post(
                f"{self.api_url}/api/auth/register",
                json={
                    "name": "Load Test User",
                    "email": "loadtest@test.com",
                    "password": "SecurePassword123!"
                },
                timeout=5
            )
            if resp.status_code == 201:
                return resp.json()["data"]["access_token"]
        except:
            pass
        
        raise Exception("Could not authenticate for load testing")

    def _single_request(self, user_id: int, request_num: int) -> LoadTestResult:
        """Execute single chat request"""
        start = time.time()
        
        try:
            message = self.HEALTHCARE_MESSAGES[request_num % len(self.HEALTHCARE_MESSAGES)]
            
            resp = requests.post(
                f"{self.api_url}/api/chat",
                headers={
                    "Authorization": f"Bearer {self.token}",
                    "Content-Type": "application/json",
                },
                json={
                    "message": message,
                    "session_id": f"loadtest-user{user_id}"
                },
                timeout=15
            )
            
            elapsed = time.time() - start
            
            return LoadTestResult(
                user_id=user_id,
                request_num=request_num,
                response_time=elapsed,
                status_code=resp.status_code,
                success=resp.status_code == 200,
            )
        except requests.Timeout as e:
            return LoadTestResult(
                user_id=user_id,
                request_num=request_num,
                response_time=15.0,
                status_code=0,
                success=False,
                error=f"Timeout: {str(e)}"
            )
        except Exception as e:
            elapsed = time.time() - start
            return LoadTestResult(
                user_id=user_id,
                request_num=request_num,
                response_time=elapsed,
                status_code=0,
                success=False,
                error=str(e)
            )

    def run_load_test(self, num_users: int = 5, requests_per_user: int = 10) -> Dict:
        """Run load test with concurrent users"""
        print("\n" + "="*70)
        print("🔥 SMART HEALTHCARE AI - LOAD TEST")
        print("="*70)
        print(f"Configuration:")
        print(f"  Users:               {num_users}")
        print(f"  Requests per user:   {requests_per_user}")
        print(f"  Total requests:      {num_users * requests_per_user}")
        print()

        total_requests = num_users * requests_per_user
        completed = 0

        # Use ThreadPoolExecutor for concurrent requests
        with concurrent.futures.ThreadPoolExecutor(max_workers=num_users) as executor:
            futures = []
            
            for user_id in range(num_users):
                for req_num in range(requests_per_user):
                    future = executor.submit(
                        self._single_request,
                        user_id=user_id,
                        request_num=req_num
                    )
                    futures.append(future)
            
            # Collect results as they complete
            for future in concurrent.futures.as_completed(futures):
                try:
                    result = future.result()
                    self.results.append(result)
                    completed += 1
                    
                    # Progress indicator
                    if completed % 5 == 0:
                        success_count = sum(1 for r in self.results if r.success)
                        print(f"  Progress: {completed}/{total_requests} ({success_count} successful)")
                except Exception as e:
                    self.errors.append(str(e))

        print()
        return self._generate_report()

    def _generate_report(self) -> Dict:
        """Generate load test report"""
        successful = [r for r in self.results if r.success]
        failed = [r for r in self.results if not r.success]
        
        response_times = [r.response_time for r in successful]
        
        stats = {
            "timestamp": datetime.now().isoformat(),
            "total_requests": len(self.results),
            "successful_requests": len(successful),
            "failed_requests": len(failed),
            "success_rate": round((len(successful) / len(self.results) * 100), 2) if self.results else 0,
            "response_times": {
                "avg_ms": round(statistics.mean(response_times) * 1000, 2) if response_times else 0,
                "min_ms": round(min(response_times) * 1000, 2) if response_times else 0,
                "max_ms": round(max(response_times) * 1000, 2) if response_times else 0,
                "median_ms": round(statistics.median(response_times) * 1000, 2) if response_times else 0,
                "p95_ms": round(self._percentile(response_times, 95) * 1000, 2) if response_times else 0,
                "p99_ms": round(self._percentile(response_times, 99) * 1000, 2) if response_times else 0,
                "stdev_ms": round(statistics.stdev(response_times) * 1000, 2) if len(response_times) > 1 else 0,
            },
            "errors": {
                error_type: sum(1 for r in failed if error_type in (r.error or ""))
                for error_type in ["Timeout", "Connection", "500", "validation"]
            }
        }

        # Print report
        print("="*70)
        print("📊 LOAD TEST REPORT")
        print("="*70)
        print(f"Total Requests:        {stats['total_requests']}")
        print(f"Successful:            {stats['successful_requests']} ({stats['success_rate']}%)")
        print(f"Failed:                {stats['failed_requests']}")
        print()
        print("Response Times:")
        print(f"  Avg:                 {stats['response_times']['avg_ms']}ms")
        print(f"  Min/Max:             {stats['response_times']['min_ms']}ms / {stats['response_times']['max_ms']}ms")
        print(f"  Median:              {stats['response_times']['median_ms']}ms")
        print(f"  P95/P99:             {stats['response_times']['p95_ms']}ms / {stats['response_times']['p99_ms']}ms")
        print(f"  StDev:               {stats['response_times']['stdev_ms']}ms")
        print()
        
        if failed:
            print("Error Distribution:")
            for error_type, count in stats['errors'].items():
                if count > 0:
                    print(f"  {error_type}:              {count}")
        
        print("="*70)

        # Recommendations
        print("\n💡 Performance Assessment:")
        if stats['success_rate'] >= 95:
            print("  ✅ EXCELLENT - System handles concurrent load well")
        elif stats['success_rate'] >= 90:
            print("  ✅ GOOD - System performing adequately")
        elif stats['success_rate'] >= 80:
            print("  ⚠️  WARNING - Some failures detected, monitor closely")
        else:
            print("  ❌ CRITICAL - Significant failures, investigate immediately")
        
        if stats['response_times']['p99_ms'] > 5000:
            print(f"  ⚠️  P99 latency high ({stats['response_times']['p99_ms']}ms), consider optimization")
        
        if stats['response_times']['avg_ms'] > 2000:
            print(f"  💬 Average response time ({stats['response_times']['avg_ms']}ms), may need tuning")

        return stats

    @staticmethod
    def _percentile(data: List[float], percentile: int) -> float:
        """Calculate percentile"""
        if not data:
            return 0
        sorted_data = sorted(data)
        index = int((percentile / 100) * len(sorted_data))
        return sorted_data[min(index, len(sorted_data) - 1)]


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Load test healthcare chat system")
    parser.add_argument("--users", type=int, default=5, help="Number of concurrent users")
    parser.add_argument("--requests", type=int, default=10, help="Requests per user")
    parser.add_argument("--output", type=str, default="storage/logs/load_test_report.json", help="Output file")
    
    args = parser.parse_args()

    tester = LoadTester()
    report = tester.run_load_test(
        num_users=args.users,
        requests_per_user=args.requests
    )
    
    # Save report
    with open(args.output, "w") as f:
        json.dump(report, f, indent=2)
    
    print(f"\n✅ Report saved to: {args.output}")
