# SOET College Website - API Reference Guide

## Base URL
`http://localhost/project/api/` or `http://localhost:8000/api/`

---

## 1. Unified Chatbot Query API

### `POST /api/chatbot.php`
Processes a student/applicant question through the CampusAI Multi-Tier Retrieval Engine.

#### Request Headers
```http
Content-Type: application/json
```

#### Request Body
```json
{
  "query": "What are the eligibility criteria for B.Tech CSE?",
  "session_token": "optional-session-token-uuid",
  "language": "en"
}
```

#### Successful Response (`200 OK`)
```json
{
  "success": true,
  "status": "success",
  "response": "For B.Tech CSE (4-Year UG), candidates must have passed 10+2 (HSC) with Physics & Mathematics along with Chemistry/Biotechnology/Biology/Technical Vocational with minimum 45% (Open) or 40% (Reserved) + Non-zero score in JEE(Mains)/MHT-CET/MGMU-CET.",
  "intent": "admission_eligibility",
  "confidence": 0.98,
  "source": "database",
  "session_token": "6a97821bc34e...",
  "suggestions": [
    "What is the fee structure for CSE?",
    "How many seats are available in CSE?",
    "How do I apply for admission?"
  ]
}
```

---

## 2. Real-Time Seat Availability API

### `GET /api/seats.php`
Fetches real-time seat intake capacity, filled seats, and vacancies across all programs.

#### Request Parameters
- `course_id` (optional, integer): Return metrics for a specific degree program.

#### Successful Response (`200 OK`)
```json
{
  "success": true,
  "data": [
    {
      "course_id": 16,
      "course_name": "B.Tech Computer Science & Engineering",
      "course_code": "BTECH-CSE",
      "department_name": "Department of Computer Science & Engineering",
      "total_intake": 120,
      "filled_seats": 94,
      "vacant_seats": 26,
      "admission_status": "OPEN",
      "is_open": true,
      "last_updated": "01-Oct-2026, 10:45 PM"
    }
  ]
}
```

---

## 3. Quick Search API

### `GET /api/quick_search.php`
Performs dynamic search across programs, faculty, departments, and events.

#### Request Parameters
- `q` (required, string): Search query string.

#### Successful Response (`200 OK`)
```json
{
  "success": true,
  "results": {
    "courses": [
      { "id": 16, "name": "B.Tech Computer Science & Engineering", "url": "courses.php#course-16" }
    ],
    "departments": [
      { "id": 1, "name": "Department of Computer Science & Engineering", "url": "departments.php#dept-1" }
    ],
    "faculty": [
      { "id": 2, "name": "Mr. Danish Ali", "designation": "Assistant Professor", "url": "faculty.php" }
    ]
  }
}
```

---

## 4. Chatbot Internal Endpoints (`/chatbot/api/*`)

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/chatbot/api/send.php` | `POST` | Primary conversational message processor |
| `/chatbot/api/suggestions.php` | `GET` | Smart contextual question suggestions |
| `/chatbot/api/feedback.php` | `POST` | User thumbs up/down rating submission |
| `/chatbot/api/history.php` | `GET` | Session chat message history retrieval |
| `/chatbot/api/export_chat.php` | `GET` | Export session conversation transcript |
