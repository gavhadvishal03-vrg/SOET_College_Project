# 🏛️ School of Engineering and Technology (SOET)
## Enterprise College Web Portal, Real-Time Admission System & 🤖 CampusAI Engine

A production-ready institutional web application and dynamic Content Management System (CMS) designed for MGM University's School of Engineering & Technology (SOET). 

Features **real-time seat availability tracking**, **transactional admission workflows**, **a 4-stage natural language AI chatbot (CampusAI)**, **editorial publishing pipelines**, and **9 role-based access control (RBAC) admin management suites**.

---

## 🌟 Key Functional Architecture

### 🎓 Real-Time Admission & Seat Intake Integration
- **Transactional Seat Allocation**: `ContentManager.php` handles transactional seat recount and synchronization using `SELECT ... FOR UPDATE` locks to prevent mathematical drift or over-admission (`filled_seats >= intake_capacity`).
- **Live Metric Formula**: Calculates real-time vacancy using `Vacant Seats = MAX(0, Intake Capacity - Filled Seats)`.
- **Public & API Integration**: `admissions.php` renders real-time vacant seat counts and status badges (`OPEN`/`FULL`) in course dropdowns, while `/api/seats.php` exposes a RESTful JSON endpoint.
- **Admin Seat Matrix**: Real-time capacity dashboard inside `admin/modules/admissions.php` allows capacity editing, status toggles, and 1-click confirmation/cancellation with email notifications.

### 🤖 CampusAI Natural Language Engine (`/chatbot`)
- **Stage 1: Preprocessor & Normalizer (`SynonymMapper.php`)**:
  Cleans punctuation, converts common spelling errors (`cmputer` $\rightarrow$ `computer`), normalizes plurals (`courses` $\rightarrow$ `course`), and expands abbreviations (`cse` $\rightarrow$ `computer science`).
- **Stage 2: Contextual Intent Classifier (`QueryClassifier.php` + `synonyms.php`)**:
  Uses surrounding words for context disambiguation (e.g., *"python program"* $\rightarrow$ `CODING` vs *"programs offered"* $\rightarrow$ `PROGRAM`). Prompts `UNCLEAR` queries with structured clarification choices.
- **Stage 3: Verified Database Priority Search (`DatabaseSearch.php`)**:
  Directly queries `courses`, `admissions`, `faculty`, `departments`, `fees`, `placements`, `notices`, `events`, and `news` MySQL tables. Bypasses generic scoring for category queries (`notices`, `events`, `fees`).
- **Stage 4: Response Formatter & Source Badge Renderer (`ResponseFormatter.php`)**:
  Renders formatted HTML with verified badges (`🤖 CampusAI — SOET Verified Data`, `🤖 CampusAI — AI Knowledge`, `🤖 CampusAI — DB + AI Hybrid`).
- **Stage 5: Admin Feedback & Unanswered Queue (`admin/ai-chatbot/unanswered.php`)**:
  Automatically logs questions requiring administrative review or negative ratings to the **Unanswered Questions Queue** for 1-click publishing to Knowledge Base & FAQ.

---

## 📁 Repository Directory Structure

```
C:\xampp\htdocs\project\
├── admin/                         # Control Panel & Management Suites
│   ├── ai-chatbot/                # 🤖 CampusAI Admin Control Suite
│   │   ├── index.php              # Analytics & Performance Metrics
│   │   ├── knowledge-base.php     # Institutional Knowledge Base Editor
│   │   ├── faq.php                # Structured FAQ Manager
│   │   ├── documents.php          # PDF/TXT Text Extractor & Indexer
│   │   ├── unanswered.php         # Unanswered Questions Queue & 1-Click Publisher
│   │   ├── conversations.php      # Real-Time Conversation Transcripts
│   │   ├── feedback.php           # User Rating & Thumbs Up/Down Logs
│   │   └── settings.php           # OpenAI API Key & Prompt Configuration
│   ├── includes/                  # Header, Sidebar, Footer Partials
│   ├── modules/                   # 17 Functional CMS Modules (Admissions, Courses, etc.)
│   ├── dashboard.php              # Central Admin Metric Dashboard
│   ├── login.php                  # Authentication Screen
│   └── logout.php                 # Session Termination
│
├── api/                           # RESTful JSON Endpoints
│   ├── chatbot.php                # AI Chatbot Asynchronous Gateway
│   └── seats.php                  # Real-Time Admission Seat Availability API
│
├── assets/                        # Web Assets & Media Uploads
│   ├── css/                       # Primary Portal & Component Stylesheets
│   ├── js/                        # Client Scripts & Dynamic Interactions
│   └── uploads/                   # Media Upload Storage (Admissions, Gallery, Faculty)
│
├── chatbot/                       # 🤖 CampusAI Engine Suite
│   ├── api/                       # Chat Gateway Endpoints (send.php, feedback.php)
│   ├── assets/                    # Chat Widget CSS & JavaScript
│   ├── config/                    # Chatbot Config & Central Synonym Dictionary
│   └── services/                  # Core PHP NLP Pipeline
│       ├── SynonymMapper.php      # 4-Stage Preprocessor & Normalizer
│       ├── QueryClassifier.php    # Contextual Intent Classification
│       ├── DatabaseSearch.php     # Real-Time Institutional MySQL Search Engine
│       ├── OpenAIService.php      # GPT LLM Integration Service
│       ├── GeneralKnowledgeEngine # Fallback Knowledge Repository
│       └── ResponseFormatter.php  # Source Badge & HTML Renderer
│
├── config/                        # System Configurations
│   ├── app.php                    # Application Environment Constants
│   └── database.php               # Singleton MySQL Database Connection
│
├── core/                          # Core Framework Architecture (OOP)
│   ├── Auth.php                   # Authentication & 9-Role RBAC Authorization
│   ├── ContentManager.php         # Data Layer & Transactional Seat Sync
│   ├── Database.php               # PDO Singleton Wrapper
│   ├── Mailer.php                 # SMTP Email Notification Service
│   ├── Security.php               # XSS, CSRF, & Input Sanitization
│   ├── Session.php                # Session Handler
│   └── Visitor.php                # Traffic & Page View Recorder
│
├── database/                      # MySQL Database Master Dump
│   └── schema.sql                 # Complete Production Schema & Seed Data (360KB+)
│
├── includes/                      # Public Page Partials
│   ├── chatbot-ui.php             # Floating CampusAI Widget Partial
│   ├── footer.php                 # Public Footer
│   ├── functions.php              # Global Helper Utility Functions
│   └── header.php                 # Public Navigation Header
│
├── .htaccess                      # Apache Security Directives & Clean Routing
├── index.php                      # Homepage
├── about.php                      # College Overview
├── admissions.php                 # Real-Time Online Admission Portal
├── courses.php                    # Degree Programs & Fee Matrix
├── departments.php                # Academic Departments Overview
├── events.php                     # Campus Events & Workshops
├── faculty.php                    # Faculty Directory
├── gallery.php                    # Filterable Photo Gallery
├── news.php                       # News & Announcements
├── placements.php                 # Placement Statistics & Recruiters
└── README.md                      # Complete Project Specifications
```

---

## 🔐 Admin Roles & Credentials

- **Default Super Admin**: `superadmin`
- **Default Password**: `admin123`
- **Supported RBAC Roles**: `Super Admin`, `Admin`, `HOD`, `Teacher`, `Student`, `Admission Manager`, `Content & News Manager`, `Site & Data Manager`, `Chatbot & Support Officer`.

---

## ⚡ Quick Start & Running Live

1. **Start XAMPP Apache & MySQL Services**.
2. **Import Database Dump**:
   Import `database/schema.sql` into MySQL database `soet_db`.
3. **Configuration**:
   Verify database credentials in `config/database.php`.
4. **Launch Local Server**:
   Double click `serve.bat` or run:
   ```bash
   php -S localhost:8000 -t C:\xampp\htdocs\project
   ```
5. **Access Application**:
   - 🌐 **Public Web Portal**: [http://localhost:8000](http://localhost:8000)
   - 🔒 **Admin Control Panel**: [http://localhost:8000/admin/login.php](http://localhost:8000/admin/login.php)
   - 📊 **Real-Time Seats REST API**: [http://localhost:8000/api/seats.php](http://localhost:8000/api/seats.php)
