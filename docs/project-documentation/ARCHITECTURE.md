# SOET College Website - System Architecture & Structure Guide

## 1. Executive Architecture Summary

The **School of Engineering and Technology (SOET), MGM University** web portal and Content Management System (CMS) is engineered as a high-performance, modular, full-stack PHP/MySQL application. The architecture cleanly separates presentation (Frontend), administrative control (Admin Panel), intelligent conversational AI (CampusAI Chatbot Suite), centralized data access (ContentManager & Database), assets/media management, and documentation.

```text
                                +---------------------------+
                                |      Web Client / PWA     |
                                +-------------+-------------+
                                              |
                       +----------------------+----------------------+
                       |                                             |
            +----------v----------+                       +----------v----------+
            |   Public Frontend   |                       |     Admin Panel     |
            |   (Root Pages)      |                       |    (/admin/*)       |
            +----------+----------+                       +----------+----------+
                       |                                             |
                       +----------------------+----------------------+
                                              |
                                   +----------v----------+
                                   |   Core Architecture |
                                   |      (/core/*)      |
                                   | - Auth, Security    |
                                   | - ContentManager    |
                                   | - Database (PDO)    |
                                   | - Session, Mailer   |
                                   +----------+----------+
                                              |
                +-----------------------------+-----------------------------+
                |                             |                             |
     +----------v----------+       +----------v----------+       +----------v----------+
     |   Chatbot Engine    |       |   Storage Layer     |       |   MySQL Database    |
     |    (/chatbot/*)     |       |  - /assets (Static) |       |     (InnoDB)        |
     | - SmartChatEngine   |       |  - /uploads (Media) |       | - 33 Tables         |
     | - Database Search   |       |  - /docs (Brochures)|       | - ACID Transactions |
     | - OpenAI Fallback   |       +---------------------+       +---------------------+
     +---------------------+
```

---

## 2. Directory Structure & Organization

```text
project/
│
├── admin/                      # Admin Panel & CMS Control Suite
│   ├── ai-chatbot/             # CampusAI Management (KB, FAQs, Sessions, Feedback)
│   ├── includes/               # Admin Header, Footer, Sidebar layouts
│   └── modules/                # Feature CMS Modules (Admissions, Courses, Depts, etc.)
│
├── api/                        # Public API Endpoints
│   ├── chatbot.php             # Unified Chatbot REST API
│   ├── quick_search.php        # Real-time search endpoint
│   └── seats.php               # Live seat intake & availability endpoint
│
├── assets/                     # Static Application Assets
│   ├── css/                    # Global stylesheets (style.css, admin.css)
│   ├── js/                     # Frontend & admin JS scripts
│   └── images/                 # Static branding, UI icons & placeholders
│       ├── branding/           # Logos, seals, insignias
│       ├── placeholders/       # Default avatar, banner, building SVGs
│       ├── facilities/         # Static facility graphics
│       └── recruiters/         # Recruiter logos
│
├── chatbot/                    # CampusAI Intelligent Assistant Subsystem
│   ├── api/                    # Chatbot internal REST handlers
│   ├── assets/                 # Chatbot CSS & JS
│   ├── config/                 # Chatbot rules & synonym dictionaries
│   ├── engine/                 # Core NLP & retrieval orchestration
│   ├── services/               # Knowledge base, search & OpenAI services
│   └── train/                  # Q&A datasets, 10K dataset, PDF guides
│
├── config/                     # Centralized Application Configuration
│   ├── app.php                 # Global constants, URLs, uploads, security settings
│   └── database.php            # Database PDO connection settings
│
├── core/                       # Backend Core Services & Utilities
│   ├── Auth.php                # Authentication & RBAC permissions
│   ├── Database.php            # Singleton PDO database connector
│   ├── Session.php             # Secure session management & flash alerts
│   ├── Security.php            # CSRF, XSS filter, sanitization, rate limiting
│   ├── ContentManager.php      # CMS Data Access Repository
│   ├── Visitor.php             # Visitor traffic & analytics tracker
│   ├── Mailer.php              # Email notification service
│   └── bootstrap.php           # Global bootstrapper
│
├── database/                   # Database Schemas, Migrations & Backups
│   ├── schema.sql              # Master Database Schema & Seeds
│   ├── backups/                # SQL backup files
│   ├── migrations/             # Schema migration scripts
│   └── seeds/                  # Seed scripts & initial configurations
│
├── docs/                       # Official Documents & Documentation
│   ├── brochures/              # Official Admission Brochures & Leaflets
│   ├── booklets/               # Curriculum & Course Specification Booklets
│   └── project-documentation/  # System architecture & schema manuals
│
├── includes/                   # Reusable Frontend Components
│   ├── header.php              # Main Navigation Bar & Banner
│   ├── footer.php              # Global Footer & Scripts
│   ├── functions.php           # Helper functions & asset path resolver
│   ├── chatbot-ui.php          # Floating Chatbot UI Widget
│   └── whatsapp-ui.php         # Floating WhatsApp Connect Widget
│
├── uploads/                    # Dynamic User & Admin Uploads (Dedicated Storage)
│   ├── admissions/             # Applicant marksheets & documents
│   ├── blogs/                  # Blog featured images
│   ├── courses/                # Course images & syllabi
│   ├── departments/            # Department cover images
│   ├── documents/              # Dynamic PDF circulars & forms
│   ├── events/                 # Event banners & posters
│   ├── facilities/             # Facility photos
│   ├── faculty/                # Faculty profile photos
│   ├── gallery/                # Campus photo gallery uploads
│   ├── logos/                  # Uploaded logos & partner graphics
│   ├── news/                   # News item images
│   ├── notices/                # Notice attachments
│   ├── placements/             # Placed student photos & recruiter graphics
│   ├── settings/               # Hero banners, about images, site settings media
│   └── students/               # Student profile photos
│
├── scripts/                    # Maintenance, Migration & Data Sync CLI Utilities
│   ├── sync_database.php       # DB Sync & Consistency Utility
│   ├── sync_assets.php         # Asset Validation & Linking
│   ├── audit_images.php        # Image Path & Integrity Auditor
│   ├── backup_database.php     # CLI Database Backup Exporter
│   └── extract_media.py        # PDF media extraction utility
│
├── tests/                      # Automated Verification & Integration Tests
│   ├── test_db.php             # Database Connection Test
│   ├── test_cms_dynamic.php    # CMS Dynamic Content Test
│   ├── validate_page_images.php# Page Image & Link Validation Test
│   └── validate_admin_images.php # Admin Image Verification Test
│
├── .env.example                # Environment variables template
├── .env                        # Local environment variables
├── .gitignore                  # Git repository exclusion rules
├── .htaccess                   # Apache security directives & rewrite rules
├── manifest.json               # Progressive Web App manifest
├── push_to_github.bat          # Git version control push script
├── QUICK_START.txt             # Quick start instructions
├── README.md                   # Project documentation
├── run_server.bat              # Local Windows PHP development server launcher
├── run_server.sh               # Local Linux/macOS development server launcher
├── setup.php                   # Database initialization script
├── about.php                   # About SOET & MGM University
├── admissions.php              # Admissions & Online Application Form
├── blogs.php                   # Student & Faculty Blog Directory
├── contact.php                 # Contact Form & Campus Location
├── courses.php                 # Degree Programs, Curricula & Fees
├── departments.php             # Academic Departments
├── events.php                  # Events, Hackathons & Workshops
├── faculty.php                 # Faculty Directory & Profiles
├── gallery.php                 # Photo Gallery & Campus Life
├── index.php                   # Homepage & Interactive Hero
├── news.php                    # News & Official Announcements
├── placements.php              # Placement Cell, Statistics & Recruiters
├── submit-blog.php             # Public Blog Submission Portal
└── submit-news.php             # Public News/Notice Submission Portal
```

---

## 3. Core Architectural Modules

### A. Data Access & CMS Layer (`core/ContentManager.php` & `core/Database.php`)
- **Database Wrapper**: Implements a Singleton PDO connection with automated UTF-8 charset negotiation, prepared statement parameter binding, and transaction isolation (`beginTransaction`, `commit`, `rollBack`).
- **Content Manager Repository**: Encapsulates 40+ high-level queries for courses, departments, faculty, admissions, seat quotas, events, notices, blogs, facilities, and dynamic site settings.

### B. Security & Authentication (`core/Auth.php` & `core/Security.php`)
- **Role-Based Access Control (RBAC)**: Supports roles (`Super Admin`, `Admin`, `Editor`, `Faculty`, `Placement Officer`, etc.) with granular permissions (`manage_courses`, `manage_admissions`, `manage_chatbot_kb`, `backup_restore`, etc.).
- **CSRF Token Validation**: 64-character cryptographic token validated using constant-time `hash_equals()`.
- **MIME Verification**: Inspects magic bytes via `finfo_file()` for all uploaded media and documents.
- **Session Auto-Expiry**: 1-hour session lifetime configured with automatic sliding window refresh.

### C. Asset & Upload Management (`includes/functions.php` & `config/app.php`)
- **Separation of Concerns**: Static assets reside in `/assets/` while user/admin generated files are stored in `/uploads/`.
- **Dynamic Fallback Resolver (`uploadUrl()`)**: Intelligently resolves file paths across `/uploads/`, `/assets/uploads/`, `/assets/images/`, and `/docs/`, guaranteeing zero broken links.

### D. Intelligent AI Chatbot Suite (`chatbot/`)
- **Multi-Tier Retrieval**:
  1. Direct SQL Knowledge Base & FAQ search.
  2. Synonym mapper and query normalizer for intent matching.
  3. Live CMS database search (seat counts, fees, eligibility, course details).
  4. OpenAI fallback for general educational assistance with hallucination guardrails.
- **Admin Control Suite**: Real-time conversation monitoring, unanswered query resolution, FAQ management, and document knowledge base training.
