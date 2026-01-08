# 📁 SneakEazy - Project Structure

Dokumentasi lengkap struktur folder project SneakEazy.

## 🗂️ Root Directory

```
php-shoe-recommender/
├── 📄 index.php                    # Main application file (74KB)
├── 📄 README.md                    # Project documentation
├── 📄 DATABASE_SETUP.md            # Database setup guide
├── 📄 CONTRIBUTING.md              # Contributing guidelines
├── 📄 .gitignore                   # Git ignore rules
├── 📄 app.yaml                     # Google App Engine config
├── 📁 config/                      # Configuration files
├── 📁 database/                    # Database schemas & seeds
├── 📁 data/                        # JSON data files
├── 📁 api/                         # API endpoints
├── 📁 proc/                        # Process scripts & docs
└── 📁 sneakeazy_core/              # Core application files
```

## 📂 Detailed Structure

### `/config/` - Configuration Files

```
config/
├── database.php          # Database connection config
│                        # - Auto-detects local vs cloud
│                        # - MAMP/XAMPP settings
│                        # - Cloud SQL socket connection
└── cacert.pem           # SSL certificate (cloud only)
```

**Purpose**: Centralized configuration untuk database connections.

---

### `/database/` - Database Files

```
database/
├── schema.sql                  # Database schema only
│                              # - Table definitions
│                              # - Indexes
│                              # - Foreign keys
│
└── sneakeazy_complete.sql     # Complete database setup ⭐
                               # - Schema + seed data
                               # - Ready to import
                               # - Users, products, interactions
```

**Purpose**: Database setup files untuk local dan cloud deployment.

**Usage**:
```bash
# Import complete database
mysql -u root -p < database/sneakeazy_complete.sql
```

---

### `/data/` - JSON Data Files

```
data/
├── products.json         # 600+ shoe products
│                        # - Product details
│                        # - Prices, images, ratings
│                        # - Brand, category info
│
├── users.json           # 54 dummy users
│                        # - User accounts
│                        # - For testing CF algorithm
│
└── interactions.json    # 1700+ user interactions
                         # - Ratings (1-5 stars)
                         # - Timestamps
                         # - User-product relationships
```

**Purpose**: Source data yang di-import ke database via PHP auto-seeding.

**File Sizes**:
- `products.json`: ~285KB
- `users.json`: ~10KB
- `interactions.json`: ~224KB

---

### `/api/` - API Endpoints

```
api/
└── config/
    └── database.php      # API-specific database config
```

**Purpose**: API endpoints untuk AJAX calls (future expansion).

---

### `/proc/` - Process Files & Documentation

```
proc/
├── 📄 README.md                      # Process documentation
├── 📄 FLOW_DOCUMENTATION.md          # System flow & logic
├── 📄 DATABASE_SETUP.md              # Database setup (old)
├── 📄 DEPLOY_GUIDE_ONLINE.md         # Cloud deployment guide
├── 📄 PYTHON_GUIDE.md                # Python scripts guide
│
├── 🐍 generate_interactions_sql.py   # Generate SQL from JSON
├── 🐍 recommendation_model.py        # CF algorithm (Python)
├── 📄 requirements.txt               # Python dependencies
│
├── 💾 interactions_seed.sql          # Generated SQL seed
├── 💾 database_dump.sql              # Database backup
│
├── 🔧 init_cloud_db.php             # Cloud DB initialization
├── 🔧 seed_cloud_db.php             # Cloud DB seeding
├── 🔧 seed_dummy.php                # Dummy data seeding
├── 🔧 migrate_to_database.php       # Migration script
├── 🔧 setup_reza.php                # Setup script
├── 🔧 stats.php                     # Statistics viewer
│
├── 📄 app.yaml                      # App Engine config (backup)
├── 📄 vercel.json                   # Vercel config
├── 📄 .gitignore                    # Proc-specific gitignore
├── 📄 .gcloudignore                 # GCloud ignore rules
└── 📦 sneakeazy_upload.zip          # Deployment package
```

**Purpose**: Development scripts, documentation, dan deployment files.

**Key Files**:
- `FLOW_DOCUMENTATION.md` - Alur sistem rekomendasi
- `generate_interactions_sql.py` - Generate seed data
- `seed_cloud_db.php` - Deploy ke cloud

---

### `/sneakeazy_core/` - Core Application

```
sneakeazy_core/
├── [15 files]           # Core application logic
                         # (Legacy/backup files)
```

**Purpose**: Core application files (backup/legacy).

---

## 📊 File Size Overview

| Directory | Files | Total Size |
|-----------|-------|------------|
| `/` | 4 | ~74KB |
| `/config/` | 2 | ~3KB |
| `/database/` | 2 | ~75KB |
| `/data/` | 3 | ~520KB |
| `/api/` | 1 | ~2KB |
| `/proc/` | 22 | ~500KB |
| `/sneakeazy_core/` | 15 | ~200KB |

**Total Project Size**: ~1.4MB (excluding `.git`)

---

## 🎯 Important Files for GitHub

### Must Include ✅

```
✅ index.php                    # Main app
✅ README.md                    # Documentation
✅ DATABASE_SETUP.md            # Setup guide
✅ CONTRIBUTING.md              # Contributing guide
✅ .gitignore                   # Git rules
✅ config/database.php          # Config
✅ database/sneakeazy_complete.sql  # Complete DB
✅ data/*.json                  # All JSON data
✅ app.yaml                     # Cloud config
```

### Can Exclude ❌

```
❌ .DS_Store                    # macOS files
❌ proc/sneakeazy_upload.zip    # Deployment archive
❌ proc/tunnel.log              # Log files
❌ proc/database_dump.sql       # Empty backup
❌ config/cacert.pem            # SSL cert (if sensitive)
```

---

## 🚀 Quick Navigation

### For Development
- **Main App**: `index.php`
- **Database Config**: `config/database.php`
- **Database Schema**: `database/sneakeazy_complete.sql`

### For Documentation
- **Project Overview**: `README.md`
- **Database Setup**: `DATABASE_SETUP.md`
- **System Flow**: `proc/FLOW_DOCUMENTATION.md`
- **Deployment**: `proc/DEPLOY_GUIDE_ONLINE.md`

### For Data
- **Products**: `data/products.json`
- **Users**: `data/users.json`
- **Interactions**: `data/interactions.json`

### For Deployment
- **Cloud Config**: `app.yaml`
- **Cloud Setup**: `proc/init_cloud_db.php`
- **Cloud Seeding**: `proc/seed_cloud_db.php`

---

## 📝 File Naming Conventions

### PHP Files
- `snake_case.php` - Scripts & utilities
- `PascalCase.php` - Classes (future)

### Documentation
- `UPPERCASE.md` - Important docs (README, CONTRIBUTING)
- `PascalCase.md` - Guides (DatabaseSetup, DeployGuide)

### Data Files
- `lowercase.json` - Data files
- `lowercase.sql` - SQL files

### Config Files
- `lowercase.yaml` - Config files
- `lowercase.php` - Config scripts

---

## 🔄 Workflow

### Local Development
```
1. Clone repo
2. Import database/sneakeazy_complete.sql
3. Edit config/database.php
4. Run: php -S localhost:8000
5. Access: http://localhost:8000
```

### Cloud Deployment
```
1. Setup Cloud SQL
2. Import database/sneakeazy_complete.sql
3. Update app.yaml
4. Deploy: gcloud app deploy
```

### Data Updates
```
1. Edit data/*.json
2. Delete database tables
3. Reload app (auto-seed)
OR
4. Run proc/seed_cloud_db.php
```

---

## ✅ Pre-Push Checklist

Sebelum push ke GitHub:

- [ ] Remove sensitive files (`.env`, `*.pem`)
- [ ] Update `.gitignore`
- [ ] Update `README.md` dengan info terbaru
- [ ] Test database import: `sneakeazy_complete.sql`
- [ ] Verify app runs: `php -S localhost:8000`
- [ ] Check file sizes (< 100MB per file)
- [ ] Remove unnecessary files dari `/proc/`
- [ ] Update version numbers (if applicable)

---

## 📚 Additional Resources

- **GitHub Repo**: [Link to repo]
- **Live Demo**: [Link to demo]
- **Documentation**: See `README.md`
- **Issues**: [GitHub Issues]
- **Discussions**: [GitHub Discussions]

---

**Last Updated**: 2026-01-09
**Project Version**: 1.0
**Maintainer**: Your Name
