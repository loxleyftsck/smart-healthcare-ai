#!/usr/bin/env python3
"""
Advanced Load Testing Script - PATH B Day 3
Tests response performance with:
- 100+ concurrent users
- Mixed request patterns
- Real-world simulation
- Metrics aggregation
"""

import time
import requests
import json
import threading
import statistics
from datetime import datetime
from collections import defaultdict
from concurrent.futures import ThreadPoolExecutor, as_completed
import sys

class LoadTester:
    def __init__(self, base_url="http://localhost:8000", num_users=100, duration_seconds=60):
        self.base_url = base_url
        self.num_users = num_users
        self.duration_seconds = duration_seconds
        self.results = defaultdict(list)
        self.errors = defaultdict(int)
        self.start_time = None
        self.end_time = None
        
    def make_request(self, method, endpoint, **kwargs):
        """Make HTTP request and track metrics"""
        url = f"{self.base_url}{endpoint}"
        start = time.time()
        
        try:
            if method.upper() == 'GET':
                response = requests.get(url, timeout=30, **kwargs)
            elif method.upper() == 'POST':
                response = requests.post(url, timeout=30, **kwargs)
            elif method.upper() == 'PUT':
                response = requests.put(url, timeout=30, **kwargs)
            else:
                response = requests.request(method, url, timeout=30, **kwargs)
            
            elapsed = (time.time() - start) * 1000  # Convert to ms
            
            self.results[endpoint].append({
                'method': method,
                'status': response.status_code,
                'time': elapsed,
                'timestamp': datetime.now().isoformat(),
                'success': 200 <= response.status_code < 300,
            })
            
            return response
            
        except Exception as e:
            elapsed = (time.time() - start) * 1000
            self.errors[endpoint] += 1
            self.results[endpoint].append({
                'method': method,
                'status': 0,
                'time': elapsed,
                'error': str(e),
                'timestamp': datetime.now().isoformat(),
                'success': False,
            })

    def test_patient_operations(self, user_id):
        """Simulate patient CRUD operations"""
        headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'Accept-Encoding': 'gzip, deflate',
        }
        
        # GET patient list
        self.make_request('GET', '/api/patients', headers=headers)
        
        # GET single patient (if any exist)
        self.make_request('GET', '/api/patients/1', headers=headers)
        
        # POST new patient (simulate)
        patient_data = {
            'name': f'Test User {user_id}',
            'email': f'user{user_id}@test.com',
            'phone': f'0812345678{user_id}',
        }
        self.make_request('POST', '/api/patients', 
                         headers=headers,
                         json=patient_data)

    def test_consultation_operations(self, user_id):
        """Simulate consultation operations"""
        headers = {
            'Content-Type': 'application/json',
            'Accept-Encoding': 'gzip, deflate',
        }
        
        # GET consultations
        self.make_request('GET', '/api/consultations', headers=headers)
        
        # POST chat message
        chat_data = {
            'message': f'Saya rasa pusing dan demam tinggi user{user_id}',
            'session_id': f'session-{user_id}',
        }
        self.make_request('POST', '/api/chat', 
                         headers=headers,
                         json=chat_data)

    def test_health_check(self, user_id):
        """Simple health check"""
        self.make_request('GET', '/api/health')

    def user_simulation(self, user_id):
        """Simulate single user behavior"""
        timeout = time.time() + self.duration_seconds
        request_count = 0
        
        while time.time() < timeout:
            # Mix of different operations
            operation = request_count % 3
            
            if operation == 0:
                self.test_patient_operations(user_id)
            elif operation == 1:
                self.test_consultation_operations(user_id)
            else:
                self.test_health_check(user_id)
            
            request_count += 1
            time.sleep(0.1)  # Small delay between requests
        
        return request_count

    def run(self):
        """Execute load test"""
        print(f"""
╔════════════════════════════════════════════╗
║     ADVANCED LOAD TESTING - PATH B DAY 3   ║
╚════════════════════════════════════════════╝

Configuration:
  • Concurrent Users: {self.num_users}
  • Duration: {self.duration_seconds} seconds
  • Base URL: {self.base_url}
  • Start Time: {datetime.now().isoformat()}

Testing with mixed workload:
  • Patient CRUD operations
  • Consultation queries
  • Chat processing
  • Health checks
        """)
        
        self.start_time = time.time()
        
        # Execute concurrent user simulation
        with ThreadPoolExecutor(max_workers=self.num_users) as executor:
            futures = [
                executor.submit(self.user_simulation, i)
                for i in range(self.num_users)
            ]
            
            for future in as_completed(futures):
                try:
                    future.result()
                except Exception as e:
                    print(f"User simulation error: {e}")
        
        self.end_time = time.time()
        self.generate_report()

    def generate_report(self):
        """Generate detailed test report"""
        total_time = self.end_time - self.start_time
        total_requests = sum(len(v) for v in self.results.values())
        successful_requests = sum(
            1 for results in self.results.values() 
            for r in results if r['success']
        )
        failed_requests = sum(
            1 for results in self.results.values() 
            for r in results if not r['success']
        )
        
        print(f"""
╔════════════════════════════════════════════╗
║          LOAD TEST RESULTS                 ║
╚════════════════════════════════════════════╝

Test Duration: {total_time:.2f} seconds
Total Requests: {total_requests}
Successful: {successful_requests} ({(successful_requests/total_requests*100):.1f}%)
Failed: {failed_requests} ({(failed_requests/total_requests*100):.1f}%)

Throughput: {total_requests/total_time:.1f} requests/second
        """)
        
        print("┌─ Endpoint Performance ─┐")
        for endpoint, results in sorted(self.results.items()):
            if not results:
                continue
            
            times = [r['time'] for r in results]
            success_count = sum(1 for r in results if r['success'])
            
            print(f"""
Endpoint: {endpoint}
  Requests: {len(results)}
  Success: {success_count}/{len(results)} ({success_count/len(results)*100:.1f}%)
  Min Time: {min(times):.2f}ms
  Max Time: {max(times):.2f}ms
  Avg Time: {statistics.mean(times):.2f}ms
  Median: {statistics.median(times):.2f}ms
  P95: {statistics.quantiles(times, n=20)[18] if len(times) > 1 else times[0]:.2f}ms
  P99: {statistics.quantiles(times, n=100)[98] if len(times) > 1 else times[0]:.2f}ms
            """)
        
        # Summary statistics
        all_times = [r['time'] for results in self.results.values() for r in results]
        
        print(f"""
┌─ Overall Performance ─┐
  Min Response: {min(all_times):.2f}ms
  Max Response: {max(all_times):.2f}ms
  Avg Response: {statistics.mean(all_times):.2f}ms
  Median Response: {statistics.median(all_times):.2f}ms
  Std Dev: {statistics.stdev(all_times):.2f}ms (stability metric)
  
┌─ Concurrency Test ─┐
  Concurrent Users Simulated: {self.num_users}
  Error Rate: {(failed_requests/total_requests*100):.1f}%
  Status: {'✅ PASSED' if failed_requests/total_requests < 0.05 else '⚠️ NEEDS INVESTIGATION'}
        """)

if __name__ == '__main__':
    # Configuration
    base_url = "http://localhost:8000"
    num_users = 100
    duration = 60
    
    # Allow override via command line
    if len(sys.argv) > 1:
        num_users = int(sys.argv[1])
    if len(sys.argv) > 2:
        duration = int(sys.argv[2])
    if len(sys.argv) > 3:
        base_url = sys.argv[3]
    
    # Run test
    tester = LoadTester(
        base_url=base_url,
        num_users=num_users,
        duration_seconds=duration
    )
    tester.run()
