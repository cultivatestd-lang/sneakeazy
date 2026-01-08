# 🤝 Contributing to SneakEazy

Terima kasih sudah tertarik untuk berkontribusi ke SneakEazy! 🎉

## 📋 Code of Conduct

Dengan berpartisipasi dalam project ini, Anda setuju untuk menjaga lingkungan yang ramah dan profesional.

## 🚀 How to Contribute

### 1. Fork & Clone

```bash
# Fork repository via GitHub
# Clone fork Anda
git clone https://github.com/YOUR_USERNAME/sneakeazy.git
cd sneakeazy
```

### 2. Create Branch

```bash
# Create feature branch
git checkout -b feature/amazing-feature

# Atau bug fix branch
git checkout -b fix/bug-description
```

### 3. Make Changes

- Tulis kode yang clean dan readable
- Follow coding standards (lihat di bawah)
- Test perubahan Anda secara menyeluruh
- Update dokumentasi jika diperlukan

### 4. Commit Changes

```bash
# Add changes
git add .

# Commit dengan pesan yang jelas
git commit -m "feat: add amazing feature"
```

#### Commit Message Format

Gunakan conventional commits:

```
<type>: <description>

[optional body]
[optional footer]
```

**Types:**
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes (formatting)
- `refactor`: Code refactoring
- `test`: Adding tests
- `chore`: Maintenance tasks

**Examples:**
```
feat: add product comparison feature
fix: resolve rating calculation bug
docs: update README with deployment guide
refactor: optimize recommendation algorithm
```

### 5. Push & Pull Request

```bash
# Push to your fork
git push origin feature/amazing-feature

# Create Pull Request via GitHub
# Describe your changes clearly
```

## 💻 Development Setup

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7+ / MariaDB
- MAMP / XAMPP (for local development)
- Git

### Setup Steps

```bash
# 1. Clone repository
git clone https://github.com/YOUR_USERNAME/sneakeazy.git
cd sneakeazy

# 2. Setup database
mysql -u root -p < database/sneakeazy_complete.sql

# 3. Configure database
# Edit config/database.php

# 4. Run server
php -S localhost:8000

# 5. Open browser
# http://localhost:8000
```

## 📝 Coding Standards

### PHP

- Follow PSR-12 coding standard
- Use meaningful variable names
- Add comments untuk logic yang kompleks
- Gunakan prepared statements untuk database queries

**Good:**
```php
// Fetch user recommendations based on collaborative filtering
function getUserRecommendations($userId, $limit = 10) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN (?)");
    $stmt->execute([$productIds]);
    return $stmt->fetchAll();
}
```

**Bad:**
```php
function gr($u, $l = 10) {
    $p = getDBConnection();
    $s = $p->query("SELECT * FROM products WHERE id IN ($ids)");
    return $s->fetchAll();
}
```

### JavaScript

- Use ES6+ syntax
- Use `const` and `let`, avoid `var`
- Add comments untuk complex logic
- Format dengan 2 spaces indentation

### CSS

- Use meaningful class names
- Organize by component
- Avoid `!important` unless necessary
- Use CSS variables untuk colors

### SQL

- Use UPPERCASE untuk SQL keywords
- Proper indentation
- Add comments untuk complex queries

```sql
-- Get top rated products with minimum 10 ratings
SELECT 
    p.id,
    p.product_name,
    p.rating,
    p.rating_count
FROM products p
WHERE p.rating_count >= 10
ORDER BY p.rating DESC
LIMIT 20;
```

## 🧪 Testing

### Before Submitting PR

- [ ] Test di local environment
- [ ] Test semua fitur yang terpengaruh
- [ ] Check console untuk errors
- [ ] Verify database queries berjalan efisien
- [ ] Test di different browsers (Chrome, Firefox, Safari)
- [ ] Test responsive design (mobile, tablet)

### Test Scenarios

1. **New User Flow**
   - Access tanpa login
   - Verify cold start recommendations
   - Test filter functionality

2. **Existing User Flow**
   - Login dengan test account
   - Verify personalized recommendations
   - Test rating system
   - Test interaction tracking

3. **Edge Cases**
   - Empty database
   - User dengan no interactions
   - Products dengan no ratings
   - Invalid user IDs

## 📚 Documentation

### Update Documentation When:

- Adding new features
- Changing API endpoints
- Modifying database schema
- Updating configuration
- Changing deployment process

### Documentation Files

- `README.md` - Project overview
- `DATABASE_SETUP.md` - Database setup guide
- `CONTRIBUTING.md` - This file
- `proc/*.md` - Process documentation

## 🐛 Reporting Bugs

### Before Reporting

1. Check existing issues
2. Verify bug masih ada di latest version
3. Test di clean environment

### Bug Report Template

```markdown
**Describe the bug**
A clear description of what the bug is.

**To Reproduce**
Steps to reproduce:
1. Go to '...'
2. Click on '...'
3. See error

**Expected behavior**
What you expected to happen.

**Screenshots**
If applicable, add screenshots.

**Environment:**
- OS: [e.g. macOS, Windows]
- Browser: [e.g. Chrome, Safari]
- PHP Version: [e.g. 7.4]
- MySQL Version: [e.g. 8.0]

**Additional context**
Any other context about the problem.
```

## 💡 Feature Requests

### Feature Request Template

```markdown
**Is your feature request related to a problem?**
A clear description of the problem.

**Describe the solution you'd like**
A clear description of what you want to happen.

**Describe alternatives you've considered**
Other solutions you've considered.

**Additional context**
Any other context or screenshots.
```

## 🎯 Priority Areas

Kami sangat welcome contributions di area:

1. **Algorithm Improvements**
   - Optimize collaborative filtering
   - Improve cold start strategy
   - Better similarity calculations

2. **Performance**
   - Database query optimization
   - Caching strategies
   - Frontend performance

3. **Features**
   - User authentication
   - Product search
   - Advanced filtering
   - Wishlist functionality

4. **UI/UX**
   - Responsive design improvements
   - Accessibility features
   - Loading states
   - Error handling

5. **Testing**
   - Unit tests
   - Integration tests
   - E2E tests

## ❓ Questions?

- Open an issue dengan label `question`
- Email: your.email@example.com
- Discussion forum: [GitHub Discussions]

## 📄 License

By contributing, you agree that your contributions will be licensed under the MIT License.

---

**Thank you for contributing to SneakEazy! 🚀**
