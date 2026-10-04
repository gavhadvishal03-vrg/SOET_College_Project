# 🏛️ School of Engineering and Technology (SOET)
## Enterprise College Web Portal, Real-Time Admission System & 🤖 CampusAI Engine

A production-ready institutional web application and dynamic Content Management System (CMS) designed for MGM University's School of Engineering & Technology (SOET). 

Features **real-time seat availability tracking**, **transactional admission workflows**, **a multi-stage natural language AI chatbot (CampusAI)**, **editorial publishing pipelines**, and **9 role-based access control (RBAC) admin management suites**.

---

## 🌟 Key Functional Architecture

### 🎓 Real-Time Admission & Seat Intake Integration
- **Transactional Seat Allocation**: `ContentManager.php` handles transactional seat recount and synchronization using `SELECT ... FOR UPDATE` locks to prevent mathematical drift or over-admission (`filled_seats >= intake_capacity`).
- **Live Metric Formula**: Calculates real-time vacancy using `Vacant Seats = MAX(0, Intake Capacity - Filled Seats)`.
- **Public & API Integration**: `admissions.php` renders real-time vacant seat counts and status badges (`OPEN`/`FULL`) in course dropdowns, while `/api/seats.php` exposes a RESTful JSON endpoint.
- **Admin Seat Matrix**: Real-time capacity dashboard inside `admin/modules/admissions.php` allows capacity editing, status toggles, and 1-click confirmation/cancellation with email notifications.

### 🤖 CampusAI Natural Language Engine (`/chatbot`)
- **Stage 1: Preprocessor & Normalizer (`QueryNormalizer.php` + `SynonymMapper.php`)**:
  Cleans punctuation, converts common spelling errors (`cmputer` $\rightarrow$ `computer`), normalizes plurals (`courses` $\rightarrow$ `course`), and expands abbreviations (`cse` $\rightarrow$ `computer science`).
- **Stage 2: Contextual Intent Classifier (`QueryClassifier.php` + `synonyms.php`)**:
  Uses surrounding words for context disambiguation (e.g., *"python program"* $\rightarrow$ `CODING` vs *"programs offered"* $\rightarrow$ `PROGRAM`). Prompts `UNCLEAR` queries with structured clarification choices.
- **Stage 3: Verified Database Priority Search (`DatabaseSearch.php`)**:
  Directly queries `courses`, `admissions`, `faculty`, `departments`, `fees`, `placements`, `notices`, `events`, and `news` MySQL tables. Bypasses generic scoring for category queries (`notices`, `events`, `fees`).
- **Stage 4: Anti-Hallucination & Response Formatter (`HallucinationGuard.php` + `ResponseFormatter.php`)**:
  Guards against hallucinated dates/fees and renders formatted HTML with verified badges (`🤖 CampusAI — SOET Verified Data`, `🤖 CampusAI — AI Knowledge`, `🤖 CampusAI — DB + AI Hybrid`).
- **Stage 5: Admin Feedback & Unanswered Queue (`admin/ai-chatbot/unanswered.php`)**:
  Automatically logs questions requiring administrative review or negative ratings to the **Unanswered Questions Queue** for 1-click publishing to Knowledge Base & FAQ.

---

## 📁 Repository Directory Structure

```
C:\xampp\htdocs\project\
├── admin/                         # Control Panel & Management Suites
│   ├── ai-chatbot/                # 🤖 CampusAI Admin Control Suite (KB, FAQs, Sessions, Logs)
│   ├── includes/                  # Header, Sidebar, Footer Partials
│   ├── modules/                   # 21 Functional CMS Modules (Admissions, Courses, Fees, etc.)
│   ├── dashboard.php              # Central Admin KPI & Metric Dashboard
│   ├── login.php                  # Authentication Screen
│   └── logout.php                 # Session Termination
│
├── api/                           # Public RESTful JSON Endpoints
│   ├── chatbot.php                # AI Chatbot Unified Gateway
│   ├── quick_search.php           # Real-Time Header Search
│   └── seats.php                  # Real-Time Admission Seat Availability API
│
├── assets/                        # Static Web Assets (CSS, JS, Static Graphics)
│   ├── css/                       # Primary Portal (style.css) & Admin (admin.css) Stylesheets
│   ├── js/                        # Client Scripts (main.js) & Admin (admin.js)
│   └── images/                    # Static UI Graphics & Default Placeholders
│       ├── branding/              # Institutional Logos & Insignias
│       ├── placeholders/          # Default Avatar, Banner & Building SVGs
│       ├── facilities/            # Static Facility Graphics
│       └── recruiters/            # Partner & Recruiter Logos
│
├── chatbot/                       # 🤖 CampusAI Subsystem
│   ├── api/                       # Chat Gateway Endpoints (send, suggestions, feedback, history, export)
│   ├── assets/                    # Chat Widget Stylesheets & Dynamic JavaScript
│   ├── config/                    # Chatbot Config & Central Synonym Dictionary
│   ├── engine/                    # Core NLP Pipeline (SmartChatEngine, QueryNormalizer, Guard)
│   ├── services/                  # Database Search, Document Extractor & OpenAI Services
│   └── train/                     # Master Q&A Dataset, 10K Dataset & Reference PDFs
│
├── config/                        # Centralized System Configurations
│   ├── app.php                    # Application Environment Constants & .env Loader
│   └── database.php               # Singleton MySQL Database Connection Settings
│
├── core/                          # Backend Core Framework Architecture (OOP)
│   ├── Auth.php                   # Authentication & 9-Role RBAC Authorization
│   ├── ContentManager.php         # Data Access Layer & Transactional Seat Sync
│   ├── Database.php               # PDO Singleton Wrapper
│   ├── Mailer.php                 # SMTP Email Notification Service
│   ├── Security.php               # XSS, CSRF, & Input Sanitization
│   ├── Session.php                # Secure Session Handler
│   ├── Visitor.php                # Traffic & Page View Analytics Recorder
│   └── bootstrap.php              # Global Application Bootstrapper
│
├── database/                      # MySQL Database Master Schemas & Backups
│   ├── schema.sql                 # Complete Production Schema & Seed Data
│   ├── backups/                   # Timestamped Database SQL Backups
│   ├── migrations/                # Schema Migration Utilities
│   └── seeds/                     # Default Site Settings & Configuration Seeds
│
├── docs/                          # Institutional Documentation & Curriculum Booklets
│   ├── brochures/                 # Official Admission Brochures & Leaflets
│   ├── booklets/                  # Degree Program Specification & Curriculum Booklets
│   └── project-documentation/     # Architecture, Schema & API Reference Manuals
│
├── includes/                      # Public Page Partials & Reusable Layouts
│   ├── chatbot-ui.php             # Floating CampusAI Widget Partial
│   ├── footer.php                 # Public Footer & Script Includes
│   ├── functions.php              # Global Helper Functions & Dynamic Upload Path Resolver
│   ├── header.php                 # Public Navigation Header & Dynamic Menu
│   └── whatsapp-ui.php            # Floating WhatsApp Quick Connect Widget
│
├── scripts/                       # Maintenance, Sync & Migration CLI Utilities
│   ├── sync_database.php          # Database Synchronization & Consistency Checker
│   ├── sync_assets.php            # Media Asset Linking & Validation
│   ├── audit_images.php           # Image Integrity & URL Auditor
│   ├── backup_database.php        # CLI Database Backup Utility
│   └── extract_media.py           # Document Media Extractor
│
├── tests/                         # Automated Verification & Integration Test Suites
│   ├── test_system_integrity.php  # Master 33-Check Integrity Test Suite
│   ├── test_endpoints.php         # Public Frontend & API Rendering Tests
│   └── test_admin_modules.php     # 30-Module Admin Panel Execution Tests
│
├── uploads/                       # Dynamic User & Admin Upload Storage
│   ├── admissions/                # Applicant Marksheets & Verification Files
│   ├── blogs/                     # Blog Featured Images
│   ├── courses/                   # Course Banners & Syllabi
│   ├── departments/               # Department Header Images
│   ├── documents/                 # Uploaded Booklets & Official Circulars
│   ├── events/                    # Event Posters & Activities
│   ├── facilities/                # Campus Infrastructure Media
│   ├── faculty/                   # Faculty Profile Photographs
│   ├── gallery/                   # Photo Gallery Uploads
│   ├── logos/                     # Partner & Organization Logos
│   ├── news/                      # News Article Images
│   ├── notices/                   # Notice Documents & Attachments
│   ├── placements/                # Placed Student Photographs
│   └── settings/                  # Homepage Hero Banners & Portal Identity Media
│
├── .env.example                   # Environment Configuration Template
├── .env                           # Local Environment Secrets
├── .gitignore                     # Git Repository Exclusion Directives
├── .htaccess                      # Apache Security Directives & Legacy Upload Routing
├── manifest.json                  # PWA Manifest
├── push_to_github.bat             # Git Push Deployment Helper
├── QUICK_START.txt                # Fast Onboarding Guide
├── README.md                      # Complete Project Specifications
├── run_server.bat                 # Windows PHP Local Server Runner
├── run_server.sh                  # Linux/macOS Local Server Runner
├── setup.php                      # Database Setup Utility
├── about.php                      # College Overview
├── admissions.php                 # Real-Time Online Admission Portal
├── blogs.php                      # Student & Faculty Blogs
├── contact.php                    # Contact Form & Campus Map
├── courses.php                    # Degree Programs & Fee Matrix
├── departments.php                # Academic Departments Overview
├── events.php                     # Campus Events & Workshops
├── faculty.php                    # Faculty Directory
├── gallery.php                    # Filterable Photo Gallery
├── index.php                      # Homepage & Interactive Hero
├── news.php                       # News & Announcements
├── placements.php                 # Placement Statistics & Recruiters
├── submit-blog.php                # Public Blog Submission Portal
└── submit-news.php                # Public News/Notice Submission Portal
```

---

## 🔐 Admin Roles & Credentials

- **Default Super Admin**: `superadmin`
- **Default Password**: `admin123`
- **Supported RBAC Roles**: `Super Admin`, `Admin`, `HOD`, `Teacher`, `Student`, `Admission Manager`, `Content & News Manager`, `Site & Data Manager`, `Chatbot & Support Officer`.

---

## ⚡ Quick Start & Running Live

1. **Start XAMPP Apache & MySQL Services** (Default MySQL port: `3307` or `3306`).
2. **Import Database Dump**:
   Import `database/schema.sql` into MySQL database `soet_college`.
3. **Configuration**:
   Copy `.env.example` to `.env` and verify database credentials in `.env` / `config/database.php`.
4. **Launch Local Server**:
   Double click `run_server.bat` or run:
   ```bash
   php -S localhost:8000 -t C:\xampp\htdocs\project
   ```
5. **Access Application**:
   - 🌐 **Public Web Portal**: [http://localhost:8000](http://localhost:8000)
   - 🔒 **Admin Control Panel**: [http://localhost:8000/admin/login.php](http://localhost:8000/admin/login.php)
   - 📊 **Real-Time Seats REST API**: [http://localhost:8000/api/seats.php](http://localhost:8000/api/seats.php)
