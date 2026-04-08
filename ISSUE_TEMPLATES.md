# 🐛 Issue Templates

This file documents the issue templates available in `.github/ISSUE_TEMPLATE/`

---

## Issue Types

### 1. Bug Report Template

**File:** `.github/ISSUE_TEMPLATE/bug_report.md`

**Used for:** Reporting unexpected behavior or crashes

**Template includes:**
- Description of the bug
- Steps to reproduce
- Expected vs actual behavior
- Screenshots/logs
- System information

**Example:**
```
### Description
The chat endpoint returns 500 errors occasionally

### Steps to Reproduce
1. Send 10+ continuous messages
2. Observe response pattern

### Expected Behavior
Should handle all messages gracefully

### Actual Behavior
Returns Internal Server Error on 3rd message

### Environment
- OS: Windows 11
- PHP: 8.2
- MySQL: 8.0
```

---

### 2. Feature Request Template

**File:** `.github/ISSUE_TEMPLATE/feature_request.md`

**Used for:** Proposing new features or enhancements

**Template includes:**
- Problem statement
- Proposed solution
- Why it's needed
- Alternatives considered
- Additional context

**Example:**
```
### Problem
Users cannot schedule appointments directly from chat

### Solution
Add `/schedule` command to chatbot with calendar integration

### Why Needed
Currently requires manual intervention

### Alternatives
- Email-based scheduling
- Separate scheduling app

### Impact
Would improve user experience by 40%
```

---

### 3. Documentation Issue Template

**File:** `.github/ISSUE_TEMPLATE/documentation_issue.md`

**Used for:** Documentation improvements

**Template includes:**
- What's unclear
- Where in docs
- Suggested improvement
- Difficulty level

**Example:**
```
### What's Unclear
API authentication steps are not clear

### Current Location
README.md line 45-60

### Suggested Fix
Add code example showing JWT token generation

### Difficulty
Medium - someone familiar with JWT could fix in 30 min
```

---

### 4. Security Vulnerability Template

**File:** `.github/ISSUE_TEMPLATE/security_vulnerability.md`

**Used for:** Security issues (should be kept PRIVATE)

**Template includes:**
- Vulnerability type
- Affected component
- Severity assessment
- Steps to reproduce

**⚠️ NOTE:** Security issues should be reported privately to security@smarthealth-ai.io

---

## Creating an Issue

### Step 1: Choose Template
- Go to Issues → New Issue
- Select appropriate template

### Step 2: Fill Template
- Complete all required sections
- Be specific and detailed
- Include reproduction steps for bugs

### Step 3: Add Labels
- `bug` - for bug reports
- `feature` - for feature requests
- `documentation` - for doc improvements
- `security` - for security issues
- `help wanted` - if you need assistance

### Step 4: Submit
- Click "Submit new issue"
- Maintainers will review within 24 hours

---

## Issue Labels

| Label | Color | Used For | 
|-------|-------|----------|
| `bug` | 🔴 Red | Unexpected behavior |
| `feature` | 🟢 Green | New features |
| `documentation` | 🔵 Blue | Docs improvements |
| `security` | 🟣 Purple | Security issues |
| `help wanted` | 🟠 Orange | Looking for contributors |
| `good first issue` | 🟡 Yellow | New contributor friendly |
| `in progress` | ⚪ Gray | Being worked on |
| `blocked` | ⚫ Black | Waiting on something |

---

## Issue Lifecycle

```
Opened
  ↓
Reviewed (24-48 hours)
  ↓
Triaged (assigned label, priority)
  ↓
In Progress (if someone takes it)
  ↓
Pull Request Submitted
  ↓
Closed (merged or rejected)
```

---

## Guidelines for Good Issues

### ✅ Good Issue Example

```
### Title
Response time increases after 1000 concurrent requests

### Description
When load testing with 1000+ concurrent users, 
response times increase from 714ms to 2000ms+

### Reproduction Steps
1. Run: `python load_test_advanced.py --users 1000`
2. Monitor Prometheus metrics
3. Observe response_time_p95 increasing

### Expected
Response time should stay <800ms

### Actual
Response time deteriorates to >1500ms

### Environment
- Laravel 11, PHP 8.2
- MySQL 8.0 connection pool: 10 connections
- Load test duration: 5 minutes

### Performance Log
[prometheus_metrics.json attached]
```

### ❌ Poor Issue Example

```
### Title
Thing broken

### Description
stuff not working pls fix

### Error
error 500
```

---

## Tips for Issue Success

1. **Search first** - avoid duplicates
2. **Be specific** - vague issues take longer to resolve
3. **Include context** - OS, version, configuration
4. **Provide logs** - error messages help debugging
5. **One issue per report** - don't bundle multiple problems
6. **Include examples** - code samples or screenshots
7. **Suggest solutions** - if you have ideas, share them
8. **Be respectful** - maintainers are volunteers

---

## Issue Response Times

| Type | Priority | Response Time |
|------|----------|----------------|
| Security | Critical | < 4 hours |
| Bug | High | < 24 hours |
| Feature | Medium | < 48 hours |
| Documentation | Low | < 1 week |

---

**Last Updated:** April 8, 2026  
**Version:** 1.0
