# 🤝 Contributing Guide

Thank you for your interest in contributing to Smart Healthcare AI! We welcome contributions from developers, designers, documentation writers, and community members of all experience levels.

---

## How to Contribute

### 1. Before You Start

- **Read the README.md** to understand the project architecture
- **Review SECURITY.md** for security standards
- **Check existing issues** to avoid duplicating work
- **Review open pull requests** to know what's being worked on

### 2. Setting Up Your Development Environment

```bash
# Clone the repository
git clone https://github.com/smart-health-ai/smart-health-ai.git
cd smart-health-ai

# Create a new branch for your work
git checkout -b feature/your-feature-name

# Setup Laravel environment
cd smart-health-ai
composer install
cp .env.example .env
php artisan key:generate

# Setup Python service
cd ../ai-triage-service
python -m venv venv
source venv/bin/activate  # On Windows: venv\Scripts\activate
pip install -r requirements.txt
```

### 3. Development Workflow

#### For Bug Fixes:
1. Create a branch: `git checkout -b fix/issue-description`
2. Make your changes
3. Write or update tests
4. Run test suite: `php artisan test`
5. Commit with clear message: `fix: brief description`
6. Push to your fork and create a Pull Request

#### For Features:
1. Create a branch: `git checkout -b feature/feature-name`
2. Implement the feature
3. Add comprehensive tests
4. Update documentation if needed
5. Commit with clear message: `feat: description`
6. Push and create a Pull Request

#### For Documentation:
1. Create a branch: `git checkout -b docs/description`
2. Update or create documentation
3. Proofread for clarity and accuracy
4. Commit: `docs: description`
5. Push and create a Pull Request

---

## Code Style & Standards

### PHP/Laravel

- **PSR-12** compliance (follow `phpunit.xml` configuration)
- **Type hints** required on all method signatures
- **Docblocks** on all public methods
- **Single responsibility** principle
- **No magic methods** - explicit is better than implicit

Example:
```php
/**
 * Analyze patient symptoms and return triage severity.
 *
 * @param array<string> $symptoms List of symptom strings
 * @return array{severity: string, confidence: float, recommendation: string}
 */
public function analyze(array $symptoms): array
{
    // Implementation
}
```

### Python

- **PEP 8** compliance
- **Type hints** using `typing` module
- **Docstrings** on all functions and classes
- **Black** formatter (100 char line length)

Example:
```python
from typing import List, Dict

def classify_severity(symptoms: List[str]) -> Dict[str, any]:
    """
    Classify severity based on symptoms.
    
    Args:
        symptoms: List of symptom strings
        
    Returns:
        Dictionary with severity, confidence, and recommendation
    """
    # Implementation
```

### Commit Messages

Follow **Conventional Commits** format:

```
<type>(<scope>): <subject>

<body>

<footer>
```

**Types:**
- `feat:` A new feature
- `fix:` A bug fix
- `docs:` Documentation only changes
- `style:` Changes that don't affect code meaning
- `refactor:` Code change that doesn't fix or add features
- `perf:` Performance improvement
- `test:` Adding or updating tests
- `chore:` Maintenance tasks

**Examples:**
```
feat(chat): add message deduplication service
fix(triage): correct severity calculation logic
docs(api): update endpoint documentation
test(cache): add TTL expiration tests
```

---

## Testing Standards

All code contributions must include tests:

### Running Tests

```bash
# PHP Unit Tests
cd smart-health-ai
php artisan test

# Python Tests
cd ai-triage-service
pytest tests/
```

### Test Coverage Requirements

- **Minimum 80%** code coverage
- **Unit tests** for all services
- **Feature tests** for all API endpoints
- **Integration tests** for service interactions

### Test File Structure

```
tests/
├── Unit/
│   ├── Services/
│   │   └── YourServiceTest.php
│   └── Models/
└── Feature/
    └── YourEndpointTest.php
```

---

## Database Changes

If your contribution involves database changes:

1. **Create a migration** with timestamp:
   ```php
   // database/migrations/2025_04_08_120000_add_new_column.php
   Schema::table('table_name', function (Blueprint $table) {
       $table->string('new_column');
   });
   ```

2. **Add rollback** in the `down()` method
3. **Test locally** before submitting
4. **Document** schema changes in PR description

---

## Pull Request Process

### Before Submitting

1. **Update your fork** with main branch
   ```bash
   git fetch upstream main
   git rebase upstream/main
   ```

2. **Run all tests locally**
   ```bash
   cd smart-health-ai && php artisan test
   cd ../ai-triage-service && pytest tests/
   ```

3. **Check code style**
   ```bash
   # PHP
   ./vendor/bin/pint --test
   
   # Python
   black --check ai-triage-service/
   ```

4. **Verify no conflicts** with main branch

### PR Description Template

```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Related Issues
Closes #123

## Testing
- [ ] Added unit tests
- [ ] Added feature tests
- [ ] All existing tests pass

## Checklist
- [ ] Code follows PSR-12 / PEP 8
- [ ] Documentation updated
- [ ] Tests added/updated
- [ ] No breaking changes
```

### PR Review Process

- **Code review** by maintainers (2-5 days)
- **Automated tests** must pass (required)
- **Address feedback** promptly
- **Final approval** before merge

---

## Documentation

### Documentation Standards

- **Clear and concise** language
- **Markdown format** for all docs
- **Code examples** for complex topics
- **Links** to related documentation

### Files to Update

When contributing features:
- Update `README.md` with new features
- Add API docs in comment blocks
- Update `SECURITY.md` if security-related
- Add examples to relevant guide files

---

## Community

### Communication Channels

- **Issues**: Bug reports, feature requests
- **Discussions**: General questions, ideas
- **Pull Requests**: Code contributions
- **Security Issues**: Report privately to security@smarthealth-ai.io

### Code Review Etiquette

- **Be respectful** - feedback is on code, not the person
- **Explain reasoning** - help others learn
- **Ask questions** - seek to understand, not judge
- **Acknowledge good work** - recognize effort

---

## Getting Help

- **Documentation**: Check README.md and related guides
- **Issues**: Search existing issues for solutions
- **Discussions**: Ask questions in project discussions
- **Security**: Report vulnerabilities privately

---

## Recognition

Contributors are recognized in:
- **CONTRIBUTORS.md** file (after first contribution)
- **GitHub** as contributors
- **Release notes** for significant contributions

---

## License

By contributing to Smart Healthcare AI, you agree that your contributions will be licensed under the same license as the project (typically MIT or Apache 2.0).

---

**Thank you for contributing to Smart Healthcare AI! 🎉**

---

**Last Updated:** April 8, 2026  
**Version:** 1.0
