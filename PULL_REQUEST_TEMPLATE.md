# 🔄 Pull Request Template

This template helps ensure all pull requests contain the necessary information for review.

**Copy and fill this template when creating a Pull Request:**

---

## PR Title Format

Follow **Conventional Commits**:
```
type(scope): description
```

**Examples:**
```
feat(chat): add message deduplication
fix(triage): correct severity calculation
docs(api): update JWT authentication docs
perf(cache): optimize query cache TTL
test(services): add QueryBatchingService tests
```

---

## PR Description Template

```markdown
## 📝 Description

Brief description of what this PR does and why.

### Related Issues
Closes #123 (if fixing an issue)
Related to #456 (if related but not closing)

---

## 🎯 Type of Change

- [ ] Bug fix (non-breaking change that fixes an issue)
- [ ] New feature (non-breaking change that adds functionality)
- [ ] Breaking change (fix or feature that would cause existing functionality to change)
- [ ] Documentation update
- [ ] Performance improvement
- [ ] Test addition/update

---

## 🔍 Changes Made

List the specific changes:
- Added `QueryCacheService` for multi-tier caching
- Updated `ConsultationController` to use new service
- Added 10 unit tests for cache invalidation

---

## ✅ Testing

### Tests Added/Updated
- [x] Unit tests for new service
- [x] Feature tests for API endpoints
- [x] Load testing (100+ concurrent users)

### Test Coverage
- Lines added: 150
- Lines covered: 145 (96.7%)
- Lines uncovered: 5

### Manual Testing Steps
1. Start Docker services: `docker-compose up -d`
2. Run tests: `php artisan test`
3. Test endpoint: `curl -X POST http://localhost:8000/api/chat -H "Authorization: Bearer TOKEN"`

### Test Results
```
✅ All 45 tests passing
✅ No regressions detected
✅ Load test: 100 concurrent users
```

---

## 📊 Performance Impact

### Before
- Response time: 842ms
- Throughput: 11.4 req/s
- Database latency: 170ms

### After
- Response time: 714ms (-15.2%)
- Throughput: 13.3 req/s (+16.7%)
- Database latency: 45ms (-73%)

### Benchmarks
```
Query Cache Hit Rate: 65%
Response Compression: 70% reduction
Deduplication: 2% of requests eliminated
```

---

## 🔒 Security Considerations

- [ ] No hardcoded secrets or credentials
- [ ] No SQL injection vulnerabilities
- [ ] No XSS vulnerabilities
- [ ] Input validation on all new endpoints
- [ ] Rate limiting applied
- [ ] Authentication required where needed

### Security Review
- New endpoints require JWT auth ✅
- All inputs validated with FormRequest ✅
- Database queries use Eloquent ORM ✅

---

## 📚 Documentation

- [ ] Updated README if needed
- [ ] Updated API documentation
- [ ] Added code comments for complex logic
- [ ] Updated SECURITY.md if security-related
- [ ] Added examples for new endpoints

### Doc Changes
- Added `QueryCacheService` to README Architecture
- Updated API docs with new `/cache/status` endpoint
- Added configuration section to DOCKER_STARTUP_GUIDE.md

---

## 🔄 Dependencies

### New Dependencies
- None added

### Updated Dependencies
- `laravel/framework`: 11.0 → 11.1 (patch, non-breaking)

### Breaking Changes
- None

---

## ⚙️ Configuration Changes

### Environment Variables
- `CACHE_TTL` - Cache TTL in seconds (default: 3600)
- `CACHE_DRIVER` - Cache driver (default: redis)

### Database Changes
- No migrations added
- No schema changes
- No data migrations needed

---

## 🎨 Code Quality

- [x] Follows PSR-12 standards
- [x] All methods have type hints
- [x] All public methods documented with PHPDoc
- [x] No code duplication
- [x] Variable names are clear and meaningful
- [x] Comments explain *why*, not *what*

### Code Style Check
```bash
./vendor/bin/pint --test
# ✅ All files pass PSR-12 check
```

---

## 📋 Checklist

Before submitting:

- [ ] My code follows the project's style guidelines
- [ ] I have performed a self-review of my code
- [ ] I have commented my code, particularly in hard-to-understand areas
- [ ] I have updated the documentation accordingly
- [ ] My changes generate no new warnings
- [ ] I have added tests that prove my fix/feature works
- [ ] New and existing tests pass locally
- [ ] Any dependent changes have been merged and published
- [ ] I have reviewed for security issues
- [ ] No breaking changes (or breaking changes are documented)

---

## 🖼️ Screenshots/Demos

### API Response Example
```json
{
  "success": true,
  "message": "Cache status retrieved",
  "data": {
    "hit_rate": 0.65,
    "total_requests": 1000,
    "cached_requests": 650,
    "ttl": 3600
  }
}
```

### Grafana Dashboard
[Screenshot of performance improvement]

### Load Test Results
[Load test comparison graph]

---

## 📝 Additional Notes

Any other context or information that reviewers should know:

- This PR resolves a 15% performance bottleneck identified in production
- Performance improvement validated with 100+ concurrent users
- Zero breaking changes - fully backward compatible
- Rollback plan documented (< 5 minutes)

---

## 👥 Reviewers

@maintainer-name - Code review  
@maintenance-team - Performance review

---

**PR Summary:**
- **Type:** Optimization
- **Risk Level:** Low
- **Testing:** Comprehensive (45+ tests)
- **Dependencies:** None new
- **Breaking Changes:** None
- **Status:** Ready for review ✅
```

---

## PR Review Process

### For Reviewers

1. **Check formatting** - Does PR follow template?
2. **Review tests** - Are tests comprehensive?
3. **Check performance** - Are metrics provided?
4. **Security review** - Any security concerns?
5. **Code quality** - Does it follow standards?
6. **Documentation** - Is it updated?
7. **Approve or request changes**

### For Authors

1. **Respond to feedback** - Address each comment
2. **Update code** - Make requested changes
3. **Re-test** - Run tests again locally
4. **Commit updates** - Push with clear messages
5. **Remove draft status** - Mark as ready
6. **Merge when approved** - Don't force merge

---

## Commit Messages in PR

Each commit in a PR should follow Conventional Commits:

```
feat(cache): add QueryCacheService with 4-tier TTL

- Added multi-tier cache layer (5s, 5m, 30m, 1h)
- Integrated with ConsultationService
- Added 10 unit tests
- Performance: 40x faster on cache hits
```

---

## Common Feedback & How to Handle

### "Add Tests"
- Add unit tests for new methods
- Add feature tests for new endpoints
- Aim for 80%+ coverage

### "Update Docs"
- Update README if architecture changes
- Add code examples
- Update related guides

### "Performance Concern"
- Add benchmark comparison
- Show load test results
- Profile code if complex

### "Security Issue"
- Use parameterized queries
- Validate all inputs
- Don't hardcode secrets
- Apply least privilege

---

## PR Merge Criteria

All of the following must be true:

✅ All tests passing  
✅ No merge conflicts  
✅ At least 1 approval from maintainer  
✅ No requested changes pending  
✅ Code quality checks passing  
✅ Documentation updated  
✅ No security issues  
✅ Performance acceptable  

---

**Last Updated:** April 8, 2026  
**Version:** 1.0
