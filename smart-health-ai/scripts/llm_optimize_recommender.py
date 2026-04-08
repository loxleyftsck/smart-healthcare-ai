#!/usr/bin/env python3
"""
LLM Optimization Report Generator
Analyzes current system and provides optimization recommendations
"""

import json
import subprocess
from pathlib import Path
from datetime import datetime
from typing import Dict, List

class LLMOptimizationRecommender:
    """Generate optimization recommendations based on system metrics"""
    
    def __init__(self):
        self.metrics = {}
        self.profile = 'balanced'
        self.recommendations = []
        
    def analyze_system(self) -> Dict:
        """Analyze current system metrics"""
        print("\n📊 Analyzing system metrics...")
        
        analysis = {
            'timestamp': datetime.now().isoformat(),
            'gpu_available': self._check_gpu(),
            'cpu_cores': self._get_cpu_cores(),
            'available_memory_mb': self._get_available_memory(),
            'current_load': self._get_system_load(),
        }
        
        return analysis
    
    def _check_gpu(self) -> Dict:
        """Check GPU availability"""
        try:
            result = subprocess.run(
                ['nvidia-smi', '--query-gpu=memory.free,memory.total', '--format=csv,nounits,noheader'],
                capture_output=True,
                text=True,
                timeout=5
            )
            
            if result.returncode == 0:
                mem_free, mem_total = map(int, result.stdout.strip().split(','))
                return {
                    'available': True,
                    'memory_free_mb': mem_free,
                    'memory_total_mb': mem_total,
                    'utilization_percent': round((1 - mem_free/mem_total) * 100, 2)
                }
        except:
            pass
        
        return {
            'available': False,
            'memory_free_mb': 0,
            'memory_total_mb': 0,
            'utilization_percent': 0
        }
    
    def _get_cpu_cores(self) -> int:
        """Get CPU core count"""
        try:
            import multiprocessing
            return multiprocessing.cpu_count()
        except:
            return 4
    
    def _get_available_memory(self) -> int:
        """Get available system memory in MB"""
        try:
            import psutil
            return int(psutil.virtual_memory().available / (1024**2))
        except:
            return 4096
    
    def _get_system_load(self) -> Dict:
        """Get system load average"""
        try:
            import os
            loadavg = os.getloadavg()
            return {
                '1min': round(loadavg[0], 2),
                '5min': round(loadavg[1], 2),
                '15min': round(loadavg[2], 2)
            }
        except:
            return {'1min': 0, '5min': 0, '15min': 0}
    
    def generate_recommendations(self, analysis: Dict) -> List[str]:
        """Generate optimization recommendations"""
        recommendations = []
        
        # GPU Recommendations
        if analysis['gpu_available']:
            if analysis['gpu_available']['utilization_percent'] < 30:
                recommendations.append(
                    "✅ GPU is available and underutilized\n"
                    "   Action: Ensure OLLAMA_MODEL=mistral (full precision)\n"
                    "   Setting: num_gpu=-1 (use all GPU)"
                )
            elif analysis['gpu_available']['memory_free_mb'] < 1000:
                recommendations.append(
                    "⚠️  GPU memory low (<1GB available)\n"
                    "   Action: Switch to quantized model\n"
                    "   Setting: OLLAMA_MODEL=mistral:7b-q4"
                )
        else:
            recommendations.append(
                "❌ No NVIDIA GPU detected\n"
                "   Action: CPU inference only (slower)\n"
                "   Recommendation: Use mistral:7b-q4 (quantized)"
            )
        
        # Memory Recommendations
        if analysis['available_memory_mb'] < 2048:
            recommendations.append(
                "⚠️  Low available memory (<2GB)\n"
                "   Action: Use edge profile\n"
                "   Settings: num_ctx=2048, num_batch=512"
            )
        elif analysis['available_memory_mb'] > 6000:
            recommendations.append(
                "✅ High available memory (>6GB)\n"
                "   Action: Use quality profile\n"
                "   Settings: num_ctx=8192, num_batch=2048"
            )
        
        # Load Recommendations
        load_1min = analysis['current_load']['1min']
        if load_1min > 4:
            recommendations.append(
                f"⚠️  High system load ({load_1min})\n"
                "   Action: Reduce inference complexity\n"
                "   Settings: temperature+=0.2, num_predict-=100"
            )
        
        return recommendations
    
    def get_profile_recommendation(self, analysis: Dict) -> str:
        """Get recommended optimization profile"""
        if not analysis['gpu_available']:
            return 'edge'
        
        gpu_mem = analysis['gpu_available']['memory_free_mb']
        sys_mem = analysis['available_memory_mb']
        load = analysis['current_load']['1min']
        
        if load > 4 or sys_mem < 2048:
            return 'speed'
        elif gpu_mem > 2000 and sys_mem > 6000:
            return 'quality'
        else:
            return 'balanced'
    
    def generate_report(self) -> Dict:
        """Generate complete optimization report"""
        print("\n🔍 Generating LLM Optimization Report...\n")
        
        # Analyze system
        analysis = self.analyze_system()
        
        # Get recommendations
        recommendations = self.generate_recommendations(analysis)
        profile = self.get_profile_recommendation(analysis)
        
        report = {
            'timestamp': analysis['timestamp'],
            'system_analysis': {
                'gpu_available': analysis['gpu_available']['available'],
                'gpu_memory_free_mb': analysis['gpu_available']['memory_free_mb'],
                'cpu_cores': analysis['cpu_cores'],
                'available_memory_mb': analysis['available_memory_mb'],
                'system_load_1min': analysis['current_load']['1min'],
            },
            'recommended_profile': profile,
            'optimization_recommendations': recommendations,
            'detailed_settings': self._get_profile_settings(profile),
            'expected_improvements': self._get_expected_improvements(profile),
        }
        
        return report
    
    def _get_profile_settings(self, profile: str) -> Dict:
        """Get settings for recommended profile"""
        profiles = {
            'speed': {
                'temperature': 0.5,
                'top_p': 0.7,
                'top_k': 20,
                'num_predict': 150,
                'num_ctx': 2048,
                'use_case': 'Fast responses, low latency'
            },
            'balanced': {
                'temperature': 0.3,
                'top_p': 0.85,
                'top_k': 40,
                'num_predict': 300,
                'num_ctx': 4096,
                'use_case': 'Balanced quality and speed'
            },
            'quality': {
                'temperature': 0.2,
                'top_p': 0.75,
                'top_k': 30,
                'num_predict': 500,
                'num_ctx': 8192,
                'use_case': 'High quality, comprehensive responses'
            },
            'edge': {
                'temperature': 0.5,
                'top_p': 0.75,
                'top_k': 20,
                'num_predict': 150,
                'num_ctx': 2048,
                'use_case': 'Minimal resources, edge devices'
            },
        }
        
        return profiles.get(profile, profiles['balanced'])
    
    def _get_expected_improvements(self, profile: str) -> Dict:
        """Get expected improvements for profile"""
        improvements = {
            'speed': {
                'response_time_ms': '300-400',
                'quality_score': '7.5/10',
                'tokens_per_second': '200+',
                'advantage': '35-50% faster than balanced'
            },
            'balanced': {
                'response_time_ms': '800-1000',
                'quality_score': '8.5/10',
                'tokens_per_second': '120',
                'advantage': 'Default, balanced'
            },
            'quality': {
                'response_time_ms': '1200-1500',
                'quality_score': '9.2/10',
                'tokens_per_second': '90',
                'advantage': '8-10% better quality than balanced'
            },
            'edge': {
                'response_time_ms': '400-600',
                'quality_score': '7.0/10',
                'tokens_per_second': '180',
                'advantage': 'Works with minimal resources'
            },
        }
        
        return improvements.get(profile, improvements['balanced'])
    
    def print_report(self, report: Dict) -> None:
        """Print formatted report"""
        print("\n" + "="*70)
        print("🚀 LLM OPTIMIZATION ANALYSIS REPORT")
        print("="*70)
        print(f"\nGenerated: {report['timestamp']}")
        
        # System Analysis
        print("\n📊 SYSTEM ANALYSIS")
        print("-"*70)
        analysis = report['system_analysis']
        print(f"GPU Available:           {'✅ Yes' if analysis['gpu_available'] else '❌ No'}")
        print(f"GPU Memory Free:         {analysis['gpu_memory_free_mb']}MB")
        print(f"CPU Cores:               {analysis['cpu_cores']}")
        print(f"System Memory Available: {analysis['available_memory_mb']}MB")
        print(f"System Load (1min):      {analysis['system_load_1min']}")
        
        # Recommendation
        print("\n🎯 RECOMMENDED PROFILE")
        print("-"*70)
        profile = report['recommended_profile']
        print(f"Profile: {profile.upper()}")
        
        settings = report['detailed_settings']
        print(f"Use Case: {settings['use_case']}")
        print(f"\nSettings:")
        print(f"  Temperature:    {settings['temperature']}")
        print(f"  Top-P:          {settings['top_p']}")
        print(f"  Top-K:          {settings['top_k']}")
        print(f"  Num Predict:    {settings['num_predict']} tokens")
        print(f"  Num Context:    {settings['num_ctx']} tokens")
        
        # Expected Improvements
        improvements = report['expected_improvements']
        print(f"\nExpected Performance:")
        print(f"  Response Time:     {improvements['response_time_ms']}ms")
        print(f"  Quality Score:     {improvements['quality_score']}")
        print(f"  Throughput:        {improvements['tokens_per_second']} tokens/sec")
        print(f"  Advantage:         {improvements['advantage']}")
        
        # Recommendations
        print("\n💡 OPTIMIZATION RECOMMENDATIONS")
        print("-"*70)
        for i, rec in enumerate(report['optimization_recommendations'], 1):
            print(f"\n{i}. {rec}")
        
        # Implementation
        print("\n🔧 IMPLEMENTATION")
        print("-"*70)
        print(f"\n1. Update configuration:")
        print(f"   Set LLM_OPTIMIZATION_PROFILE={profile} in .env")
        print(f"\n2. Or use built-in optimizer:")
        print(f"   php artisan llm:optimize --profile={profile}")
        print(f"\n3. Run benchmark to verify:")
        print(f"   python scripts/benchmark.py")
        print(f"\n4. Monitor performance:")
        print(f"   python scripts/load_test.py --users 10")
        
        print("\n" + "="*70)
        print("✅ Report complete!")
        print("="*70 + "\n")


if __name__ == "__main__":
    recommender = LLMOptimizationRecommender()
    report = recommender.generate_report()
    recommender.print_report(report)
    
    # Save report
    Path("storage/logs").mkdir(parents=True, exist_ok=True)
    with open("storage/logs/llm_optimization_report.json", "w") as f:
        json.dump(report, f, indent=2)
    
    print(f"📄 Full report saved to: storage/logs/llm_optimization_report.json")
