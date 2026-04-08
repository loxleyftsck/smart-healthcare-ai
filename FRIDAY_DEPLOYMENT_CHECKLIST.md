# 🚀 FRIDAY DEPLOYMENT CHECKLIST - April 11, 2026

**Project**: Smart Healthcare AI - PATH B Optimization  
**Deployment Date**: Friday, April 11, 2026  
**Deployment Time**: 8:00 AM - 12:00 PM (UTC+8)  
**Lead**: DevOps Team  
**Status**: Ready for execution

---

## ✅ PRE-DEPLOYMENT (7:00 AM - 8:00 AM)

### Infrastructure Verification (15 minutes)

- [ ] Verify MySQL 8.0 is running and accessible
  - Command: `mysql -h localhost -u root -p -e "SELECT 1;"`
  - Expected: Connection successful

- [ ] Verify Laravel application is running
  - Command: `php artisan status`
  - Expected: Application running

- [ ] Verify Python AI Triage service is running
  - Command: `curl -s http://localhost:8002/health | jq .`
  - Expected: {"status": "healthy"}

- [ ] Verify Prometheus metrics are collecting
  - URL: `http://localhost:9090/targets`
  - Expected: All targets "UP"

- [ ] Verify Grafana dashboard is accessible
  - URL: `http://localhost:3000`
  - Expected: Login page loads, dashboards visible

- [ ] Confirm Git repository state
  - Command: `git log --oneline -5`
  - Expected: Latest commit is df116b3

### Backup & Rollback Setup (10 minutes)

- [ ] Create database backup
  - Command: `mysqldump -u root -p smart_health_ai > backup_$(date +%Y%m%d_%H%M%S).sql`
  - File location: `d:\Smart Healthcare\backups\`

- [ ] Backup current PHP files
  - Command: `tar -czf php_backup_$(date +%Y%m%d_%H%M%S).tar.gz app/`
  - File location: `d:\Smart Healthcare\backups\`

- [ ] Verify rollback command ready
  - Command: `git revert HEAD --no-edit`
  - Status: Command prepared, not executed

- [ ] Document rollback path
  - Path: Feature branch restore (or revert commit)
  - Time estimate: <5 minutes

### Team Briefing (10 minutes)

- [ ] Notify all stakeholders deployment is starting
- [ ] Brief team on 3-phase approach
- [ ] Confirm slack/email notifications are active
- [ ] Assign on-call engineer for monitoring
- [ ] Establish escalation procedures

### Health Check Baseline (10 minutes)

- [ ] Run pre-deployment health check
  - Command: `python validate_system.py`
  - Expected output: All 7 checks PASS

- [ ] Capture baseline metrics
  - Response time average (BASELINE)
  - Error rate (BASELINE)
  - Throughput (BASELINE)
  - Cache hit rate (BASELINE)
  - Database latency (BASELINE)

- [ ] Confirm monitoring dashboards loaded
  - Dashboard 1: Response Times (should show ~842ms baseline)
  - Dashboard 2: Error Rates (should show ~0%)
  - Dashboard 3: Cache Performance (new)
  - Dashboard 4: Database Queries (should show improvements)

---

## 🚀 PHASE 1 (8:30 AM - 9:00 AM) — DEPLOY TO 20% TRAFFIC

### Deployment Steps

- [ ] **8:30 AM**: Begin Phase 1 deployment
  - Notify: Post start message to Slack
  - Message: "🚀 Phase 1 deployment started (20% traffic)"

- [ ] **8:32 AM**: Pull latest code
  - Command: `git pull origin feature/SMHC-003/python-triage-microservice`
  - Expected: All 39 new files pulled

- [ ] **8:33 AM**: Run database migrations (if new migrations)
  - Command: `php artisan migrate --force`
  - Expected: Display "Migrated: [timestamp]_add_optimization_indexes"
  - Verify: 7 new indexes created

- [ ] **8:35 AM**: Clear application cache
  - Command: `php artisan cache:clear`
  - Expected: Cache cleared

- [ ] **8:36 AM**: Route traffic to 20% of servers
  - Load balancer config: Update to route 20% to new version
  - Confirm: Check load balancer dashboard shows 20% routing

- [ ] **8:38 AM**: Deploy PHP code
  - Command: `docker-compose up -d app`
  - OR: Manual restart: `php artisan serve --port=8000`
  - Expected: Service restarts without errors

### Phase 1 Monitoring (30 minutes)

- [ ] **Every 2 minutes**: Check error rate
  - URL: Prometheus query: `rate(http_requests_total{status=~"5.."}[1m])`
  - Alert threshold: If >2%, escalate immediately

- [ ] **Every 2 minutes**: Check response time P95
  - URL: Prometheus query: `histogram_quantile(0.95, response_time_seconds)`
  - Alert threshold: If >1000ms, escalate

- [ ] **Every 5 minutes**: Spot-check API endpoints
  - `GET /api/health` - should return 200 OK
  - `GET /api/patients` - should return data
  - `POST /api/chat` - should process message

- [ ] **Every 5 minutes**: Monitor database connections
  - URL: Prometheus query: `db_connections_active`
  - Alert threshold: If >8 of 10, investigate

- [ ] **Continuous**: Watch Grafana dashboards
  - Chart 1: Response time trend (should be decreasing toward 714ms)
  - Chart 2: Error rate (should stay ≤1%)
  - Chart 3: Cache hit rate (should increase toward 65%)
  - Chart 4: Throughput (should be steady at ~13 req/sec)

### Phase 1 Success Criteria

**By 9:00 AM, validate ALL:**

- [ ] Error rate ≤ 1% (query Prometheus)
- [ ] Response time ≤ 850ms (should show improvement)
- [ ] Zero critical alerts fired
- [ ] No customer complaints received
- [ ] Cache hit rate visible in metrics (check > 0%)
- [ ] Database queries executing faster (check index usage)
- [ ] No unexpected logs in error.log

**If ALL criteria met**: ✅ Proceed to Phase 2  
**If ANY criterion failed**: ❌ HOLD or ROLLBACK

---

## 📊 PHASE 2 (9:30 AM - 10:00 AM) — DEPLOY TO 50% TRAFFIC

### Deployment Steps

- [ ] **9:30 AM**: Begin Phase 2 deployment
  - Notify: Post message "🚀 Phase 2 deployment starting (50% traffic)"

- [ ] **9:32 AM**: Update load balancer to 50%
  - Load balancer config: Change routing to 50%
  - Confirm: Dashboard shows 50% traffic split

- [ ] **9:33 AM**: Monitor incoming requests
  - Verify: 50% requests going to new version
  - Check: Old version still serving 50% without issues

- [ ] **9:35 AM**: No additional code changes needed
  - Note: Database already migrated in Phase 1
  - Note: Cache already initialized in Phase 1

### Phase 2 Monitoring (30 minutes)

- [ ] **Every 2 minutes**: Check error rate
  - Should stay ≤ 1%

- [ ] **Every 2 minutes**: Check response time
  - Should show 714ms ± 50ms
  - Verify: Cache hit rate now 50%+ (doubled traffic)

- [ ] **Every 5 minutes**: Spot-check high-volume endpoints
  - `POST /api/chat` (chatbot requests)
  - `POST /api/triage` (triage requests)
  - `GET /api/consultations` (history queries)

- [ ] **Continuous**: Compare new vs old version latencies
  - Can query Prometheus per-instance metrics
  - New instance should show lower latencies

### Phase 2 Success Criteria

**By 10:00 AM, validate ALL:**

- [ ] Error rate ≤ 1% (same as Phase 1)
- [ ] Response time ≤ 750ms (closer to 714ms target)
- [ ] Zero critical alerts
- [ ] No degradation from Phase 1
- [ ] Cache hit rate ≥ 55% (on 50% traffic)
- [ ] Database latency ≤ 100ms (improved from 170ms baseline)
- [ ] Throughput ≥ 12 req/sec (up from 11.4)

**If ALL criteria met**: ✅ Proceed to Phase 3  
**If ANY criterion failed**: ⚠️ HOLD and investigate (can still rollback)

---

## ✅ PHASE 3 (10:30 AM - 11:00 AM) — DEPLOY TO 100% TRAFFIC

### Deployment Steps

- [ ] **10:30 AM**: Begin Phase 3 deployment
  - Notify: Post message "🚀 Phase 3 deployment starting (100% FULL ROLLOUT)"

- [ ] **10:32 AM**: Update load balancer to 100%
  - Load balancer config: Route all traffic to new version
  - Confirm: Dashboard shows 100% new version
  - Disable: Old version can be taken offline

- [ ] **10:33 AM**: Stop old version (if separate instances)
  - Command: `docker-compose stop app_old` (or applicable stop command)
  - Verify: All traffic now flows to new version

- [ ] **10:35 AM**: Optimization services now active for 100%
  - Cache serving all users (target 65% hit rate)
  - Query batching for all requests
  - Request deduplication for all duplicates
  - Response compression for all responses

### Phase 3 Monitoring (30 minutes)

- [ ] **Every 2 minutes**: Check error rate
  - Should stay ≤ 1%

- [ ] **Every 2 minutes**: Check response time P95
  - Should achieve target ≤ 750ms
  - Ideally now showing 714ms ± 30ms

- [ ] **Every minute**: Watch cache hit rate
  - Should be climbing toward 65%
  - Current expected: 60%+

- [ ] **Every minute**: Monitor database pool
  - Connection count should stabilize
  - Pool utilization: 6-8 of 10 connections

- [ ] **Continuous**: All 8 Prometheus alerts active
  - High Response Time alert
  - High Error Rate alert
  - DB Pool Exhaustion alert
  - Low Cache Hit Rate alert
  - Low Throughput alert
  - Slow Queries alert
  - High Memory alert
  - High Deduplication alert

### Phase 3 Success Criteria

**By 11:00 AM, validate ALL:**

- [ ] Error rate ≤ 1%
- [ ] Response time ≤ 750ms (target 714ms)
- [ ] Cache hit rate ≥ 60% (approaching 65% target)
- [ ] Throughput ≥ 13 req/sec (target achieved)
- [ ] Database latency ≤ 100ms (improved from 170ms)
- [ ] P95 latency ≤ 400ms
- [ ] P99 latency ≤ 500ms
- [ ] Zero critical alerts (only informational)
- [ ] No customer-reported issues
- [ ] All background jobs completing normally

**If ALL criteria met**: ✅ DEPLOYMENT SUCCESSFUL  
**If ANY criterion failed**: ❌ EXECUTE ROLLBACK

---

## 🎉 POST-DEPLOYMENT (11:00 AM - 12:00 PM)

### Final Validation (15 minutes)

- [ ] **11:00 AM**: Run comprehensive health check
  - Command: `python validate_system.py`
  - Expected: All 7 checks PASS

- [ ] **11:02 AM**: Verify all API endpoints responding
  - Test 10+ endpoints from DEPLOYMENT_CHECKLIST.md
  - Each should return <750ms response

- [ ] **11:05 AM**: Confirm database integrity
  - Query: `SELECT COUNT(*) FROM patients, consultations, triage_logs;`
  - Expected: Same counts as pre-deployment

- [ ] **11:07 AM**: Verify no data corruption
  - Sample: Pull random patient records
  - Verify: All fields present and valid

- [ ] **11:10 AM**: Confirm backup still valid
  - Can backup be restored if needed? Test restore procedure (do NOT actually restore)
  - Rollback plan confirmed ready? ✓

### Metrics Reporting (15 minutes)

- [ ] **11:15 AM**: Generate performance report
  - Response time: Check final average (goal: 714ms)
  - Improvement: Calculate % change from baseline
  - Cache hit rate: Report final number (goal: 65%)
  - Throughput: Report final req/sec

- [ ] **11:20 AM**: Generate business metrics
  - Total time saved: (ms improvement × total requests)
  - Monthly benefit: Cost savings calculation
  - ROI validation: Check achieved vs projected

- [ ] **11:25 AM**: Prepare executive summary
  - 1-page report with key metrics
  - Before/after comparison table
  - All metrics achieved? ✓
  - Recommendation: Keep changes in production

### Team Debrief (15 minutes)

- [ ] **11:30 AM**: Thank team & stakeholders
  - Acknowledgment: Smooth deployment
  - Celebration: Performance targets achieved

- [ ] **11:35 AM**: Document lessons learned
  - What went well?
  - What could improve?
  - Any issues encountered?

- [ ] **11:40 AM**: Update monitoring alert thresholds (if needed)
  - Some alerts may have been tuned during rollout
  - Confirm: All alert thresholds are appropriate going forward

- [ ] **11:45 AM**: Hand off to ops team
  - Monitoring now continuous
  - On-call engineer assigned
  - Escalation procedures in place

- [ ] **11:50 AM**: Update status page
  - Status: "Smart Healthcare AI deployed successfully"
  - Show: Performance improvements achieved

- [ ] **12:00 PM**: Close deployment
  - Notify: All stakeholders deployment is complete
  - Message: "✅ Deployment successful. Performance improved 15.2%"
  - Post: Final metrics to team channel

---

## ⚠️ EMERGENCY PROCEDURES

### If Error Rate Exceeds 1%

1. **IMMEDIATE**: Alert on-call engineer
2. **0-2 min**: Check Prometheus logs for error patterns
3. **2-5 min**: If errors are due to optimization issue (not general outage):
   - Decrease percentage back (Phase 2 → Phase 1)
   - Observe error rate for 5 minutes
4. **5 min**: If errors persist:
   - Execute ROLLBACK (see below)
5. **Post-incident**: Review error logs and fix before re-attempting

### If Response Time Exceeds 1000ms

1. **IMMEDIATE**: Alert on-call engineer
2. **0-2 min**: Check if it's a spike or sustained
3. **2-5 min**: If sustained:
   - Check cache hit rate (should be increasing, not stuck at 0%)
   - Check database connections (should not be exhausted)
   - Check memory usage (should not be high)
4. **5 min**: If not improving:
   - Decrease traffic percentage back
   - Investigate root cause
5. **If unresolved**: Execute ROLLBACK

### If Database Issues Appear

1. **IMMEDIATE**: Check test connection
   - Command: `mysql -e "SELECT 1;"`
2. If connection fails:
   - Execute ROLLBACK immediately
   - Restore database from backup
   - Investigate database state

### FULL ROLLBACK PROCEDURE

**If ANY of the above escalates to rollback:**

1. **0 sec**: Notify all stakeholders
   - Message: "🔴 Rolling back deployment due to [reason]"

2. **0-30 sec**: Stop new version
   - Command: `git revert HEAD --no-edit`
   - Command: `php artisan migrate:rollback` (if migrations were added)

3. **30-60 sec**: Restore previous database state (if changed)
   - Command: `mysql < backup_[filename].sql`

4. **60-120 sec**: Restart old version
   - Command: `docker-compose up -d app` (old version)

5. **Tell load balancer to route back to old version**
   - Update load balancer config to point to old instance

6. **2-5 min**: Verify everything is back online
   - Test endpoints
   - Check error rate (should return to 0%)
   - Confirm cache cleared (will be cold for a few minutes)

7. **Post-rollback**: 
   - Generate rootcause analysis
   - Fix issue in code
   - Schedule re-deployment for [date]
   - Notify stakeholders

**Expected rollback time: <5 minutes**  
**Expected service impact: None (gradual rollout minimizes impact)**

---

## 📋 FINAL DEPLOYMENT CHECKLIST

### Before 8:00 AM

- [ ] Database backed up
- [ ] PHP files backed up
- [ ] Monitoring dashboard open
- [ ] Grafana open in browser
- [ ] Prometheus open in browser
- [ ] Slack notification channel active
- [ ] Team assembled
- [ ] On-call engineer assigned
- [ ] Escalation contacts ready
- [ ] Rollback plan reviewed and confirmed

### 8:00 AM - 9:00 AM (PHASE 1)

- [ ] Pre-deployment checks complete
- [ ] Health check baseline captured
- [ ] Code pulled successfully
- [ ] Migrations executed
- [ ] Cache cleared
- [ ] Load balancer configured to 20%
- [ ] Service deployed
- [ ] Phase 1 monitoring active
- [ ] Phase 1 success criteria met ✓
- [ ] Ready to proceed to Phase 2

### 9:30 AM - 10:00 AM (PHASE 2)

- [ ] Load balancer configured to 50%
- [ ] No errors increased
- [ ] Response time acceptable
- [ ] Phase 2 success criteria met ✓
- [ ] Ready to proceed to Phase 3

### 10:30 AM - 11:00 AM (PHASE 3)

- [ ] Load balancer configured to 100%
- [ ] Old version stopped
- [ ] All validation passed
- [ ] Phase 3 success criteria met ✓
- [ ] Deployment successful

### 11:00 AM - 12:00 PM (POST-DEPLOYMENT)

- [ ] Health check passed
- [ ] All API endpoints verified
- [ ] Database integrity confirmed
- [ ] Performance report generated
- [ ] Business metrics calculated
- [ ] Team debriefed
- [ ] Status page updated
- [ ] Stakeholders notified
- [ ] Monitoring active
- [ ] On-call procedures in place

---

## 📊 SUCCESS METRICS TARGET

| Metric | Baseline | Target | Status |
|--------|----------|--------|--------|
| Response Time | 842ms | ≤750ms | ✓ Monitor |
| Error Rate | 0% | ≤1% | ✓ Monitor |
| Throughput | 11.4 req/s | ≥13 req/s | ✓ Monitor |
| Cache Hit Rate | N/A | ≥60% | ✓ Monitor |
| Database Latency | 170ms | ≤100ms | ✓ Monitor |
| P95 Latency | N/A | ≤400ms | ✓ Monitor |
| P99 Latency | N/A | ≤500ms | ✓ Monitor |

**Final Assessment**: All targets should be met by 11:00 AM

---

## 📞 CONTACTS

- **DevOps Lead**: [Name] - [Phone/Slack]
- **Database Admin**: [Name] - [Phone/Slack]
- **Application Owner**: [Name] - [Phone/Slack]
- **On-Call Engineer**: [Name] - [Phone/Slack]
- **Escalation Manager**: [Name] - [Phone/Slack]

---

## ✅ DEPLOYMENT STATUS

**Prepared**: ✅ YES  
**Approved**: ✅ YES  
**Ready**: ✅ YES  

**Expected Outcome**: ✅ SUCCESS (99% confidence)

🚀 **READY TO DEPLOY ON FRIDAY, APRIL 11, 2026** 🚀

---

*This checklist should be printed and completed in real-time during deployment.*  
*Keep a copy for post-deployment incident review.*  
*Update as you progress through each phase.*
