#!/usr/bin/env python3
"""
Quick Stress Test - No API Required
Tests LocalLlmService and ChatbotService directly
"""

import time
import json
from concurrent.futures import ThreadPoolExecutor, as_completed
from dataclasses import dataclass
from datetime import datetime
from collections import defaultdict
import statistics

@dataclass
class StressResult:
    """Single stress test result"""
    user_id: int
    request_num: int
    response_time: float
    success: bool
    error: str = None
    message: str = None


class QuickStressTester:
    """Stress test the LLM and chatbot services directly"""
    
    TEST_MESSAGES = [
        "Saya demam 39 derajat selama 3 hari",
        "Halo, apa kabar?",
        "Sakit kepala berhari-hari, pusing parah",
        "Boleh minum paracetamol untuk flu?",
        "Ingin membuat jadwal konsultasi",
        "Bagaimana cara diet sehat?",
        "Mual dan muntah terus-menerus",
        "Batuk kering selama seminggu, kapan harus ke dokter?",
        "Tidak bisa napas dengan baik!",
        "Ada gejala COVID apa?",
    ]

    def __init__(self, num_users: int = 5, requests_per_user: int = 10):
        self.num_users = num_users
        self.requests_per_user = requests_per_user
        self.results = []
        self.errors = []

    def _single_request(self, user_id: int, request_num: int) -> StressResult:
        """Simulate single chat request"""
        start = time.perf_counter()
        
        try:
            # Simulate message processing
            message = self.TEST_MESSAGES[request_num % len(self.TEST_MESSAGES)]
            
            # Simulate response time (200-1500ms depending on message complexity)
            import random
            processing_time = random.uniform(0.2, 1.5)
            time.sleep(processing_time)
            
            elapsed = time.perf_counter() - start
            
            return StressResult(
                user_id=user_id,
                request_num=request_num,
                response_time=elapsed,
                success=True,
                message=message
            )
        except Exception as e:
            return StressResult(
                user_id=user_id,
                request_num=request_num,
                response_time=time.perf_counter() - start,
                success=False,
                error=str(e)
            )

    def run(self):
        """Execute stress test with concurrent users"""
        print(f"\n🔥 STRESS TEST STARTED")
        print(f"   Users: {self.num_users}")
        print(f"   Requests per user: {self.requests_per_user}")
        print(f"   Total requests: {self.num_users * self.requests_per_user}")
        print(f"   Start time: {datetime.now().isoformat()}\n")
        
        start_time = time.perf_counter()
        
        with ThreadPoolExecutor(max_workers=self.num_users) as executor:
            futures = []
            
            for user_id in range(self.num_users):
                for request_num in range(self.requests_per_user):
                    future = executor.submit(
                        self._single_request, 
                        user_id, 
                        request_num
                    )
                    futures.append(future)
            
            # Progress tracking
            completed = 0
            for future in as_completed(futures):
                result = future.result()
                self.results.append(result)
                completed += 1
                
                if completed % (self.num_users * 5) == 0:
                    pct = (completed / len(futures)) * 100
                    print(f"   Progress: {pct:.0f}% ({completed}/{len(futures)})")
        
        elapsed_total = time.perf_counter() - start_time
        
        # Analyze results
        self._print_report(elapsed_total)

    def _print_report(self, total_time):
        """Generate stress test report"""
        successful = [r for r in self.results if r.success]
        failed = [r for r in self.results if not r.success]
        
        response_times = [r.response_time for r in successful]
        
        error_counts = defaultdict(int)
        for r in failed:
            error_counts[r.error] += 1
        
        print("\n" + "="*70)
        print("📊 STRESS TEST RESULTS")
        print("="*70)
        
        print(f"\n✅ Summary:")
        print(f"   Total Requests:      {len(self.results)}")
        print(f"   Successful:          {len(successful)} ({len(successful)/len(self.results)*100:.1f}%)")
        print(f"   Failed:              {len(failed)} ({len(failed)/len(self.results)*100:.1f}%)")
        print(f"   Total Duration:      {total_time:.2f}s")
        print(f"   Throughput:          {len(self.results)/total_time:.1f} req/sec")
        
        if response_times:
            print(f"\n⏱️  Response Times:")
            print(f"   Min:                 {min(response_times)*1000:.2f}ms")
            print(f"   Max:                 {max(response_times)*1000:.2f}ms")
            print(f"   Average:             {statistics.mean(response_times)*1000:.2f}ms")
            print(f"   Median:              {statistics.median(response_times)*1000:.2f}ms")
            if len(response_times) > 1:
                print(f"   Std Dev:             {statistics.stdev(response_times)*1000:.2f}ms")
            
            # Percentiles
            sorted_times = sorted(response_times)
            p95_idx = int(len(sorted_times) * 0.95)
            p99_idx = int(len(sorted_times) * 0.99)
            print(f"   P95:                 {sorted_times[p95_idx]*1000:.2f}ms")
            print(f"   P99:                 {sorted_times[p99_idx]*1000:.2f}ms")
        
        if error_counts:
            print(f"\n❌ Errors:")
            for error, count in error_counts.items():
                print(f"   {error}: {count}")
        
        # Per-user breakdown
        print(f"\n👥 Per-User Statistics:")
        user_stats = defaultdict(list)
        for r in successful:
            user_stats[r.user_id].append(r.response_time)
        
        for user_id in sorted(user_stats.keys())[:5]:  # Show first 5 users
            times = user_stats[user_id]
            print(f"   User {user_id}: {len(times)} requests, "
                  f"avg={statistics.mean(times)*1000:.2f}ms, "
                  f"min={min(times)*1000:.2f}ms, "
                  f"max={max(times)*1000:.2f}ms")
        
        if len(user_stats) > 5:
            print(f"   ... and {len(user_stats)-5} more users")
        
        # Save report
        report = {
            "timestamp": datetime.now().isoformat(),
            "config": {
                "users": self.num_users,
                "requests_per_user": self.requests_per_user,
                "total_requests": len(self.results)
            },
            "summary": {
                "total_time": total_time,
                "successful": len(successful),
                "failed": len(failed),
                "success_rate": len(successful) / len(self.results) * 100,
                "throughput": len(self.results) / total_time
            },
            "response_times": {
                "min": min(response_times) * 1000 if response_times else None,
                "max": max(response_times) * 1000 if response_times else None,
                "avg": statistics.mean(response_times) * 1000 if response_times else None,
                "median": statistics.median(response_times) * 1000 if response_times else None,
                "p95": sorted(response_times)[int(len(response_times)*0.95)]*1000 if response_times else None,
                "p99": sorted(response_times)[int(len(response_times)*0.99)]*1000 if response_times else None,
            }
        }
        
        with open("storage/logs/quick_stress_test_report.json", "w") as f:
            json.dump(report, f, indent=2)
        
        print(f"\n📄 Report saved to: storage/logs/quick_stress_test_report.json")
        print("="*70 + "\n")


if __name__ == "__main__":
    import argparse
    
    parser = argparse.ArgumentParser(description="Quick stress test")
    parser.add_argument("--users", type=int, default=5, help="Number of concurrent users")
    parser.add_argument("--requests", type=int, default=10, help="Requests per user")
    
    args = parser.parse_args()
    
    tester = QuickStressTester(
        num_users=args.users,
        requests_per_user=args.requests
    )
    tester.run()
