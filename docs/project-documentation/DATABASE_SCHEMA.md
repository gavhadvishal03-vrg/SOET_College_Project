# SOET College Website - Database Schema Reference

## Database Overview
- **Database Engine**: MySQL 8.0+ / MariaDB 10.4+
- **Default Port**: 3307 (fallback to 3306)
- **Character Set**: `utf8mb4`
- **Collation**: `utf8mb4_unicode_ci`
- **Total Tables**: 33 Tables

---

## Complete Table Directory

| Table Name | Description | Key Columns |
|------------|-------------|-------------|
| `activity_logs` | System audit trail & user actions | `id`, `user_id`, `action`, `description`, `ip_address`, `created_at` |
| `admissions` | Online admission applications | `id`, `application_number`, `student_name`, `email`, `phone`, `course_id`, `status`, `document_path`, `created_at` |
| `blogs` | Blog articles & student stories | `id`, `title`, `slug`, `content`, `author_name`, `author_role`, `image_path`, `status`, `created_at` |
| `chat_feedback` | User ratings on chatbot responses | `id`, `message_id`, `session_id`, `rating`, `feedback_text`, `created_at` |
| `chat_messages` | Chatbot conversation messages | `id`, `session_id`, `sender`, `message`, `source`, `intent`, `confidence`, `created_at` |
| `chat_sessions` | Chatbot visitor sessions | `id`, `session_token`, `ip_address`, `user_agent`, `started_at`, `ended_at` |
| `chatbot_categories`| Chatbot Q&A categories | `id`, `name`, `slug`, `description`, `sort_order` |
| `chatbot_settings` | AI configuration parameters | `id`, `setting_key`, `setting_value`, `description`, `updated_at` |
| `contact_messages` | Inquiries from contact page | `id`, `name`, `email`, `phone`, `subject`, `message`, `is_read`, `created_at` |
| `courses` | Degree programs & academic courses | `id`, `department_id`, `name`, `code`, `program_type`, `duration_years`, `intake_capacity`, `filled_seats`, `image_path`, `brochure_document` |
| `departments` | Academic departments | `id`, `name`, `code`, `hod_name`, `hod_designation`, `description`, `image_path`, `is_active` |
| `documents` | Official booklets & brochures | `id`, `title`, `category`, `academic_year`, `file_path`, `file_size`, `download_count`, `is_featured` |
| `event_registrations`| Public registrations for events | `id`, `event_id`, `sub_event_id`, `name`, `email`, `phone`, `college_name`, `created_at` |
| `events` | College events & workshops | `id`, `title`, `slug`, `event_type`, `start_date`, `end_date`, `venue`, `image_path`, `is_active` |
| `facilities` | Campus labs & infrastructure | `id`, `name`, `category`, `description`, `icon`, `image_path`, `sort_order`, `is_active` |
| `faculty` | Faculty members directory | `id`, `department_id`, `name`, `designation`, `qualification`, `email`, `experience_years`, `image_path`, `is_active` |
| `faq` | Frequently Asked Questions | `id`, `category_id`, `question`, `answer`, `keywords`, `is_active`, `sort_order` |
| `fees` | Year-wise tuition fees structure | `id`, `course_id`, `academic_year`, `study_year`, `tuition_fee`, `development_fee`, `other_fee`, `total_fee` |
| `gallery` | Campus photo gallery | `id`, `title`, `category`, `image_path`, `is_featured`, `sort_order`, `is_active` |
| `homepage_activities`| Homepage highlight cards | `id`, `title`, `subtitle`, `description`, `badge_text`, `image_path`, `link_url`, `sort_order` |
| `knowledge_base` | CampusAI knowledge items | `id`, `category_id`, `title`, `content`, `keywords`, `source`, `is_active` |
| `navigation_menu` | Header navigation links | `id`, `title`, `url`, `parent_id`, `icon`, `sort_order`, `is_active` |
| `news` | News articles & announcements | `id`, `title`, `slug`, `content`, `author_name`, `image_path`, `status`, `published_at` |
| `notices` | Official circulars & alerts | `id`, `title`, `content`, `target_audience`, `attachment_path`, `is_pinned`, `is_active` |
| `placement_recruiters`| Corporate recruiters & partners| `id`, `name`, `category`, `logo_image`, `icon`, `sort_order`, `is_active` |
| `placements` | Student placement achievements | `id`, `student_name`, `department_id`, `course_id`, `company_name`, `package_lpa`, `placement_year`, `student_image` |
| `roles` | RBAC user roles | `id`, `name`, `permissions`, `created_at` |
| `site_settings` | Dynamic CMS site settings | `id`, `setting_key`, `setting_value`, `created_at` |
| `sub_events` | Sub-events within major events | `id`, `event_id`, `title`, `sub_event_date`, `venue`, `coordinator`, `max_participants` |
| `unanswered_questions`| Unresolved chatbot questions | `id`, `session_id`, `question`, `category`, `confidence`, `status`, `admin_response` |
| `uploaded_documents`| Raw files ingested for AI | `id`, `filename`, `original_name`, `file_type`, `file_size`, `extracted_text`, `status` |
| `users` | Admin panel user accounts | `id`, `username`, `password`, `full_name`, `email`, `role_id`, `is_active`, `last_login` |
| `visitors` | Traffic analytics & page views | `id`, `ip_address`, `user_agent`, `page_views`, `last_page`, `visit_date` |
