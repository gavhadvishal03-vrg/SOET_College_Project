<?php
/**
 * Content Manager - Shared CMS Repository Operations
 *
 * Central data access and domain service layer for managing academic departments,
 * courses, faculty hierarchies, blogs, news, events, notices, gallery items,
 * site settings, official documents, seat quotas, and administrative stats.
 */
class ContentManager
{
    /** @var Database Database connection instance */
    public Database $db;

    /**
     * Initializes the ContentManager with the singleton Database instance.
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Retrieves academic departments ordered by sort order and name.
     *
     * @param bool $activeOnly When true, returns only active departments (default: true)
     * @return array List of department records
     */
    public function getDepartments(bool $activeOnly = true): array
    {
        $where = $activeOnly ? 'WHERE is_active = 1' : '';
        return $this->db->fetchAll("SELECT * FROM departments {$where} ORDER BY CASE WHEN code = 'ASH' OR name LIKE '%Applied Science%' THEN 1 ELSE 0 END ASC, sort_order ASC, id ASC");
    }

    /**
     * Fetches a single department record by its primary key ID.
     *
     * @param int $id Department ID
     * @return array|null Department data or null if not found
     */
    public function getDepartment(int $id): ?array
    {
        return $this->db->fetchOne("SELECT * FROM departments WHERE id = ?", [$id]);
    }

    /**
     * Retrieves academic programs/courses with joined department information.
     *
     * @param int|null $departmentId Filter by specific department ID if provided
     * @param bool $activeOnly When true, returns only active courses
     * @return array List of course records with department names
     */
    public function getCourses(?int $departmentId = null, bool $activeOnly = true): array
    {
        $sql = "SELECT c.*, d.name as department_name FROM courses c JOIN departments d ON c.department_id = d.id";
        $params = [];
        $conditions = [];
        if ($departmentId) {
            $conditions[] = "c.department_id = ?";
            $params[] = $departmentId;
        }
        if ($activeOnly) {
            $conditions[] = "c.is_active = 1";
        }
        if ($conditions) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }
        $sql .= " ORDER BY c.sort_order ASC, d.name ASC, c.name ASC";
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Retrieves campus facilities filtered optionally by active state and category.
     *
     * @param bool $activeOnly When true, returns only active facilities
     * @param string|null $category Filter by facility category (e.g. 'Lab', 'Sports', 'Hostel')
     * @return array List of facility records
     */
    public function getFacilities(bool $activeOnly = true, ?string $category = null): array
    {
        $sql = "SELECT * FROM facilities";
        $conditions = [];
        $params = [];
        if ($activeOnly) {
            $conditions[] = "is_active = 1";
        }
        if ($category) {
            $conditions[] = "category = ?";
            $params[] = $category;
        }
        if ($conditions) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }
        $sql .= " ORDER BY sort_order ASC, id ASC";
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Fetches a single facility record by ID.
     *
     * @param int $id Facility ID
     * @return array|null Facility data or null if not found
     */
    public function getFacility(int $id): ?array
    {
        return $this->db->fetchOne("SELECT * FROM facilities WHERE id = ?", [$id]);
    }

    /**
     * Retrieves corporate placement recruiters / hiring partners.
     *
     * @param bool $activeOnly Filter active recruiters
     * @param string|null $category Optional industry sector or tier category
     * @return array List of recruiter records
     */
    public function getPlacementRecruiters(bool $activeOnly = true, ?string $category = null): array
    {
        $sql = "SELECT * FROM placement_recruiters";
        $conditions = [];
        $params = [];
        if ($activeOnly) {
            $conditions[] = "is_active = 1";
        }
        if ($category) {
            $conditions[] = "category = ?";
            $params[] = $category;
        }
        if ($conditions) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }
        $sql .= " ORDER BY sort_order ASC, name ASC";
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Fetches a single placement recruiter by ID.
     *
     * @param int $id Recruiter ID
     * @return array|null Recruiter record or null
     */
    public function getPlacementRecruiter(int $id): ?array
    {
        return $this->db->fetchOne("SELECT * FROM placement_recruiters WHERE id = ?", [$id]);
    }

    /**
     * Retrieves promotional homepage activities / highlights.
     *
     * @param bool $activeOnly Filter active items
     * @return array List of homepage activity records
     */
    public function getHomepageActivities(bool $activeOnly = true): array
    {
        $where = $activeOnly ? 'WHERE is_active = 1' : '';
        return $this->db->fetchAll("SELECT * FROM homepage_activities {$where} ORDER BY sort_order ASC, id ASC");
    }

    /**
     * Fetches a single homepage activity record by ID.
     *
     * @param int $id Activity ID
     * @return array|null Activity record or null
     */
    public function getHomepageActivity(int $id): ?array
    {
        return $this->db->fetchOne("SELECT * FROM homepage_activities WHERE id = ?", [$id]);
    }

    /**
     * Retrieves active faculty members with joined department details.
     * Applies a strategic academic leadership ordering: Directors/Deans first,
     * followed by HODs, Professors, Associate Professors, and Assistant Professors.
     *
     * @param int|null $departmentId Filter by department ID or show all if null
     * @return array Ordered list of faculty member records
     */
    public function getFaculty(?int $departmentId = null): array
    {
        $sql = "SELECT f.*, COALESCE(d.name, 'ALL') as department_name, COALESCE(d.code, 'ALL') as department_code 
                FROM faculty f 
                LEFT JOIN departments d ON f.department_id = d.id 
                WHERE f.is_active = 1";
        $params = [];
        if ($departmentId) {
            $sql .= " AND f.department_id = ?";
            $params[] = $departmentId;
        }
        $sql .= " ORDER BY 
                  CASE 
                      WHEN f.department_id IS NULL OR f.department_id = 0 OR d.code = 'ALL' OR d.code = 'DIRECTOR' OR f.designation LIKE '%Director%' OR f.designation LIKE '%Dean%' THEN 1 
                      WHEN d.code = 'ASH' OR d.name LIKE '%Applied Science%' THEN 3
                      ELSE 2 
                  END ASC,
                  d.sort_order ASC,
                  d.id ASC,
                  CASE 
                      WHEN f.designation LIKE '%Head%' OR f.designation LIKE '%HOD%' THEN 1
                      WHEN f.designation LIKE '%Professor%' AND f.designation NOT LIKE '%Associate%' AND f.designation NOT LIKE '%Assistant%' THEN 2
                      WHEN f.designation LIKE '%Associate%' THEN 3
                      WHEN f.designation LIKE '%Assistant%' THEN 4
                      ELSE 5 
                  END ASC,
                  f.sort_order ASC,
                  f.id ASC";
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Fetches paginated published blog posts along with author and department details.
     *
     * @param int $limit Number of blog records per page
     * @param int $offset Pagination offset
     * @return array List of published blog articles
     */
    public function getPublishedBlogs(int $limit = 10, int $offset = 0): array
    {
        return $this->db->fetchAll(
            "SELECT b.*, u.full_name as author_name, d.name as department_name
             FROM blogs b JOIN users u ON b.author_id = u.id
             LEFT JOIN departments d ON b.department_id = d.id
             WHERE b.status = 'published' ORDER BY b.published_at DESC LIMIT ? OFFSET ?",
            [$limit, $offset]
        );
    }

    /**
     * Fetches paginated published news and press announcements.
     *
     * @param int $limit Number of news items to retrieve
     * @param int $offset Pagination offset
     * @return array List of published news items
     */
    public function getPublishedNews(int $limit = 10, int $offset = 0): array
    {
        return $this->db->fetchAll(
            "SELECT n.*, u.full_name as author_name FROM news n
             JOIN users u ON n.author_id = u.id
             WHERE n.status = 'published' ORDER BY n.published_at DESC LIMIT ? OFFSET ?",
            [$limit, $offset]
        );
    }

    /**
     * Retrieves active, non-expired campus notices with pinned items shown first.
     *
     * @param int $limit Max number of notices to return
     * @return array List of active notices
     */
    public function getActiveNotices(int $limit = 10): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM notices WHERE is_active = 1 AND (expires_at IS NULL OR expires_at >= CURDATE())
             ORDER BY is_pinned DESC, created_at DESC LIMIT ?",
            [$limit]
        );
    }

    /**
     * Retrieves upcoming campus events along with nested sub-event agendas.
     *
     * @param int $limit Max events to return
     * @return array List of events with attached 'sub_events' array
     */
    public function getUpcomingEvents(int $limit = 10): array
    {
        $events = $this->db->fetchAll(
            "SELECT * FROM events WHERE event_date >= CURDATE() AND is_active = 1
             ORDER BY event_date ASC LIMIT ?",
            [$limit]
        );
        foreach ($events as &$e) {
            $e['sub_events'] = $this->getSubEvents((int)$e['id']);
        }
        return $events;
    }

    /**
     * Fetches sub-events / schedule breakdown for a parent event.
     *
     * @param int $eventId Parent event ID
     * @return array List of sub-events
     */
    public function getSubEvents(int $eventId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM sub_events WHERE event_id = ? AND is_active = 1 ORDER BY sub_event_date ASC, sub_event_time ASC, title ASC",
            [$eventId]
        );
    }

    /**
     * Inserts or updates a sub-event schedule record.
     *
     * @param array $data Sub-event record fields
     * @return int Sub-event ID
     */
    public function saveSubEvent(array $data): int
    {
        if (isset($data['id']) && $data['id'] > 0) {
            $id = (int)$data['id'];
            unset($data['id']);
            $this->db->update('sub_events', $data, 'id = ?', [$id]);
            return $id;
        } else {
            return $this->db->insert('sub_events', $data);
        }
    }

    /**
     * Deletes a sub-event record by ID.
     *
     * @param int $id Sub-event ID
     * @return bool True on success
     */
    public function deleteSubEvent(int $id): bool
    {
        return $this->db->delete('sub_events', 'id = ?', [$id]) > 0;
    }

    /**
     * Retrieves active campus photo gallery images.
     *
     * @param int $limit Maximum images to fetch
     * @return array List of gallery items
     */
    public function getGallery(int $limit = 12): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM gallery WHERE is_active = 1 ORDER BY created_at DESC LIMIT ?",
            [$limit]
        );
    }

    /**
     * Retrieves student placement records ordered by sort order, placement year, and package.
     *
     * @param bool $activeOnly Whether to return active records only
     * @param int|null $deptId Optional department ID filter
     * @param int|null $year Optional placement year filter
     * @return array List of placement records with department and course metadata
     */
    public function getPlacements(bool $activeOnly = true, ?int $deptId = null, ?int $year = null): array
    {
        $sql = "SELECT p.*, d.name AS department_name, d.code AS department_code, c.name AS course_name 
                FROM placements p 
                LEFT JOIN departments d ON p.department_id = d.id 
                LEFT JOIN courses c ON p.course_id = c.id";
        $conditions = [];
        $params = [];
        if ($activeOnly) {
            $conditions[] = "p.is_active = 1";
        }
        if ($deptId) {
            $conditions[] = "p.department_id = ?";
            $params[] = $deptId;
        }
        if ($year) {
            $conditions[] = "p.placement_year = ?";
            $params[] = $year;
        }
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
        $sql .= " ORDER BY p.sort_order ASC, p.package_lpa DESC, p.student_name ASC";
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Retrieves all global website settings as an associative key => value map.
     *
     * @return array Key-value pairs of site settings
     */
    public function getSiteSettings(): array
    {
        $rows = $this->db->fetchAll("SELECT setting_key, setting_value FROM site_settings");
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }

    /**
     * Retrieves a single site setting value by key, with fallback.
     *
     * @param string $key Setting key name
     * @param string $default Fallback value if key is not configured
     * @return string Setting value
     */
    public function getSetting(string $key, string $default = ''): string
    {
        $row = $this->db->fetchOne("SELECT setting_value FROM site_settings WHERE setting_key = ?", [$key]);
        return ($row && isset($row['setting_value']) && $row['setting_value'] !== '') ? (string)$row['setting_value'] : $default;
    }

    /**
     * Inserts or updates a global website setting in the site_settings table.
     *
     * @param string $key Setting key name
     * @param string $value Setting value to store
     * @return bool True on success
     */
    public function saveSetting(string $key, string $value): bool
    {
        $exists = $this->db->fetchOne("SELECT id FROM site_settings WHERE setting_key = ?", [$key]);
        if ($exists) {
            return $this->db->update('site_settings', ['setting_value' => $value], 'setting_key = ?', [$key]) >= 0;
        } else {
            return $this->db->insert('site_settings', ['setting_key' => $key, 'setting_value' => $value]) > 0;
        }
    }

    /**
     * Retrieves downloadable official institutional documents, brochures, and syllabus files.
     *
     * @param string|null $category Filter by document category (e.g. 'Syllabus', 'Brochure')
     * @param bool $activeOnly Filter active documents
     * @return array List of document records
     */
    public function getOfficialDocuments(?string $category = null, bool $activeOnly = true): array
    {
        $sql = "SELECT * FROM documents";
        $params = [];
        $conds = [];
        if ($activeOnly) {
            $conds[] = "is_active = 1";
        }
        if ($category) {
            $conds[] = "category = ?";
            $params[] = $category;
        }
        if ($conds) {
            $sql .= " WHERE " . implode(' AND ', $conds);
        }
        $sql .= " ORDER BY is_featured DESC, sort_order ASC, id ASC";
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Aggregates real-time counts across all modules for the administrative dashboard.
     * Computes statistics for visitors, admissions, faculty, departments, courses,
     * total intake capacity, recruiters, events, blogs, news, chatbot interactions, etc.
     *
     * @return array Associative array of metric names to integer counts
     */
    public function getDashboardStats(): array
    {
        $totalIntake = (int)($this->db->fetchOne("SELECT SUM(intake_capacity) as total FROM courses WHERE is_active = 1")['total'] ?? 0);
        $placedCompanies = (int)($this->db->fetchOne("SELECT COUNT(DISTINCT company_name) as total FROM placements WHERE is_active = 1")['total'] ?? 0);

        return [
            'visitors' => $this->db->count('visitors'),
            'today_visitors' => $this->db->count('visitors', 'visit_date = ?', [date('Y-m-d')]),
            'admissions' => $this->db->count('admissions'),
            'pending_admissions' => $this->db->count('admissions', "status = 'pending'"),
            'faculty' => $this->db->count('faculty', 'is_active = 1'),
            'departments' => $this->db->count('departments', 'is_active = 1'),
            'courses' => $this->db->count('courses', 'is_active = 1'),
            'regular_courses' => $this->db->count('courses', "is_active = 1 AND LOWER(program_type) = 'regular'"),
            'integrated_courses' => $this->db->count('courses', "is_active = 1 AND LOWER(program_type) = 'integrated'"),
            'dse_courses' => $this->db->count('courses', "is_active = 1 AND (LOWER(program_type) LIKE '%direct%' OR LOWER(program_type) LIKE '%lateral%')"),
            'diploma_courses' => $this->db->count('courses', "is_active = 1 AND LOWER(program_type) = 'diploma'"),
            'total_intake' => $totalIntake,
            'placements' => $this->db->count('placements', 'is_active = 1'),
            'placement_companies' => $placedCompanies,
            'recruiters' => $this->db->count('placement_recruiters', 'is_active = 1'),
            'facilities' => $this->db->count('facilities', 'is_active = 1'),
            'events' => $this->db->count('events', 'is_active = 1'),
            'gallery' => $this->db->count('gallery', 'is_active = 1'),
            'documents' => $this->db->count('documents', 'is_active = 1'),
            'blogs' => $this->db->count('blogs', "status = 'published'"),
            'pending_blogs' => $this->db->count('blogs', "status IN ('submitted','under_review','returned')"),
            'news' => $this->db->count('news', "status = 'published'"),
            'pending_news' => $this->db->count('news', "status IN ('submitted','under_review','returned')"),
            'notices' => $this->db->count('notices', 'is_active = 1'),
            'contacts' => $this->db->count('contact_messages', "status = 'unread'"),
            'knowledge_base' => $this->db->count('knowledge_base', "status = 'active'"),
            'faq' => $this->db->count('faq', "status = 'active'"),
            'unanswered_questions' => $this->db->count('unanswered_questions', "status = 'pending'"),
            'chatbot_queries' => $this->db->count('chat_messages', "sender = 'user'"),
            'users' => $this->db->count('users', 'is_active = 1'),
        ];
    }

    /**
     * Calculates database pagination metrics (total records, total pages, current offset).
     *
     * @param string $table Database table name
     * @param string $where Optional SQL WHERE condition
     * @param array $params Parameter values for the WHERE condition
     * @param int $page Current 1-indexed page number
     * @param int $perPage Items per page limit
     * @return array Pagination data structure with total, page, per_page, total_pages, and offset
     */
    public function paginate(string $table, string $where = '1=1', array $params = [], int $page = 1, int $perPage = ITEMS_PER_PAGE): array
    {
        $total = $this->db->count($table, $where, $params);
        $offset = ($page - 1) * $perPage;
        $totalPages = max(1, ceil($total / $perPage));
        return [
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages,
            'offset' => $offset,
        ];
    }

    /**
     * Synchronizes a course's filled_seats count with the current number of confirmed/approved admissions.
     *
     * @param int $courseId The target course primary key ID
     * @return int The updated confirmed seats count
     */
    public function syncCourseSeats(int $courseId): int
    {
        $confirmedCount = (int)$this->db->fetchOne(
            "SELECT COUNT(*) as cnt FROM admissions WHERE course_id = ? AND status IN ('confirmed', 'approved')",
            [$courseId]
        )['cnt'];

        $this->db->update('courses', [
            'filled_seats' => $confirmedCount,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$courseId]);

        return $confirmedCount;
    }

    /**
     * Fetches real-time seat metrics, intake capacity, and vacant seat count for courses.
     * If courseId is provided, returns single associative array; otherwise returns array of all courses.
     *
     * @param int|null $courseId Optional specific course ID filter
     * @return array Course seat statistics including total_intake, filled_seats, vacant_seats, is_open
     */
    public function getCourseSeatMetrics(?int $courseId = null): array
    {
        $sql = "SELECT c.id as course_id, c.name as course_name, c.code as course_code, 
                       c.intake_capacity, c.filled_seats, c.admission_status, c.updated_at,
                       d.name as department_name,
                       (SELECT COUNT(*) FROM admissions a WHERE a.course_id = c.id AND a.status = 'pending') as pending_applications,
                       (SELECT COUNT(*) FROM admissions a WHERE a.course_id = c.id AND a.status = 'cancelled') as cancelled_applications
                FROM courses c
                JOIN departments d ON c.department_id = d.id
                WHERE c.is_active = 1";
        
        $params = [];
        if ($courseId) {
            $sql .= " AND c.id = ?";
            $params[] = $courseId;
        }
        
        $sql .= " ORDER BY c.name";
        
        $rows = $this->db->fetchAll($sql, $params);
        $result = [];
        
        foreach ($rows as $row) {
            $intake = (int)$row['intake_capacity'];
            $filled = (int)$row['filled_seats'];
            $vacant = max(0, $intake - $filled);
            $status = strtoupper($row['admission_status'] ?? 'OPEN');
            
            if ($filled >= $intake && $intake > 0) {
                $status = 'CLOSED';
            }
            
            $lastUpdated = date('d-M-Y, g:i A', strtotime($row['updated_at']));
            
            $item = [
                'course_id' => (int)$row['course_id'],
                'course_name' => $row['course_name'],
                'course_code' => $row['course_code'],
                'department_name' => $row['department_name'],
                'total_intake' => $intake,
                'filled_seats' => $filled,
                'vacant_seats' => $vacant,
                'pending_applications' => (int)$row['pending_applications'],
                'cancelled_applications' => (int)$row['cancelled_applications'],
                'admission_status' => $status,
                'is_open' => ($status === 'OPEN' && $vacant > 0),
                'last_updated' => $lastUpdated,
                'raw_updated_at' => $row['updated_at']
            ];
            
            if ($courseId) return $item;
            $result[] = $item;
        }
        
        return $result;
    }

    /**
     * Performs a transactional admission status change with capacity verification and seat synchronization.
     * Prevents over-allocation by verifying available seats under a database row lock (FOR UPDATE).
     *
     * @param int $appId Admission application primary key ID
     * @param string $status New status ('confirmed', 'approved', 'rejected', 'pending', 'cancelled')
     * @param string|null $remarks Optional administrative notes/remarks
     * @return array Status array with success flag, human-readable message, and application context
     */
    public function updateAdmissionStatusWithSeats(int $appId, string $status, ?string $remarks = null): array
    {
        $pdo = $this->db->getConnection();
        $pdo->beginTransaction();
        
        try {
            $app = $this->db->fetchOne("SELECT * FROM admissions WHERE id = ? FOR UPDATE", [$appId]);
            if (!$app) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'Admission application not found.'];
            }
            
            $courseId = (int)$app['course_id'];
            $course = $this->db->fetchOne("SELECT * FROM courses WHERE id = ? FOR UPDATE", [$courseId]);
            
            if (!$course) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'Associated course not found.'];
            }

            $isConfirming = in_array($status, ['confirmed', 'approved']);
            $wasConfirmed = in_array($app['status'], ['confirmed', 'approved']);
            
            // Check capacity when attempting to confirm an unconfirmed application
            if ($isConfirming && !$wasConfirmed) {
                $currentConfirmed = (int)$this->db->fetchOne(
                    "SELECT COUNT(*) as cnt FROM admissions WHERE course_id = ? AND status IN ('confirmed', 'approved')",
                    [$courseId]
                )['cnt'];
                
                $intake = (int)$course['intake_capacity'];
                
                if ($currentConfirmed >= $intake) {
                    $pdo->rollBack();
                    return [
                        'success' => false,
                        'message' => "Cannot confirm admission: Total approved intake capacity of {$intake} seats for {$course['name']} ({$course['code']}) has been reached."
                    ];
                }
            }
            
            // Update application status
            $this->db->update('admissions', [
                'status' => $status,
                'remarks' => $remarks,
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$appId]);
            
            // Re-sync filled seats for course
            $newFilled = (int)$this->db->fetchOne(
                "SELECT COUNT(*) as cnt FROM admissions WHERE course_id = ? AND status IN ('confirmed', 'approved')",
                [$courseId]
            )['cnt'];
            
            $this->db->update('courses', [
                'filled_seats' => $newFilled,
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$courseId]);
            
            $pdo->commit();
            
            return [
                'success' => true,
                'message' => "Application #{$app['application_number']} status updated to " . ucfirst($status) . ". Seat counts synchronized.",
                'course_name' => $course['name'],
                'course_code' => $course['code'],
                'email' => $app['email'],
                'student_name' => $app['student_name']
            ];
            
        } catch (Exception $e) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Transaction failed: ' . $e->getMessage()];
        }
    }

    /**
     * Retrieves paginated system activity and security audit logs with user/role details.
     *
     * @param int $limit Maximum number of log entries to retrieve
     * @param int $offset Query offset
     * @param string|null $action Optional filter by action type (e.g. 'login', 'create')
     * @param string|null $search Optional search keyword for description, IP, username
     * @return array List of log entries
     */
    public function getActivityLogs(int $limit = 50, int $offset = 0, ?string $action = null, ?string $search = null): array
    {
        $sql = "SELECT l.*, u.username, u.full_name, r.name as role_name 
                FROM activity_logs l 
                LEFT JOIN users u ON l.user_id = u.id 
                LEFT JOIN roles r ON u.role_id = r.id 
                WHERE 1=1";
        $params = [];
        
        if (!empty($action)) {
            $sql .= " AND l.action = ?";
            $params[] = $action;
        }
        
        if (!empty($search)) {
            $sql .= " AND (l.description LIKE ? OR l.ip_address LIKE ? OR u.username LIKE ? OR u.full_name LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $sql .= " ORDER BY l.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Compiles aggregate analytics dataset for interactive dashboard charts.
     * Includes 14-day visitor traffic trends, course application metrics, and top chatbot intents.
     *
     * @return array Structure containing traffic_trend, admissions_by_course, and chatbot_intents
     */
    public function getAnalyticsSummary(): array
    {
        // 14-day traffic trend
        $trafficTrend = $this->db->fetchAll(
            "SELECT visit_date, SUM(page_views) as views, COUNT(DISTINCT ip_address) as visitors 
             FROM visitors 
             WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 13 DAY) 
             GROUP BY visit_date 
             ORDER BY visit_date ASC"
        );

        // Admissions breakdown by course
        $admissionsByCourse = $this->db->fetchAll(
            "SELECT c.code as course_code, c.name as course_name, 
                    c.intake_capacity, c.filled_seats,
                    COUNT(a.id) as total_applications,
                    SUM(CASE WHEN a.status IN ('confirmed', 'approved') THEN 1 ELSE 0 END) as confirmed_count,
                    SUM(CASE WHEN a.status = 'pending' THEN 1 ELSE 0 END) as pending_count
             FROM courses c 
             LEFT JOIN admissions a ON c.id = a.course_id 
             WHERE c.is_active = 1 
             GROUP BY c.id 
             ORDER BY c.code ASC"
        );

        // Chatbot intent & source breakdown
        $chatbotIntents = $this->db->fetchAll(
            "SELECT intent, COUNT(*) as count 
             FROM chat_messages 
             WHERE sender = 'user' 
             GROUP BY intent 
             ORDER BY count DESC 
             LIMIT 6"
        );

        return [
            'traffic_trend' => $trafficTrend,
            'admissions_by_course' => $admissionsByCourse,
            'chatbot_intents' => $chatbotIntents
        ];
    }

    /**
     * Diagnoses runtime server environment and upload directory permissions.
     *
     * @return array System health metrics including PHP version, disk usage, directory writable status
     */
    public function getSystemHealth(): array
    {
        $uploadDirs = [
            'admissions'  => UPLOAD_PATH . 'admissions',
            'blogs'       => UPLOAD_PATH . 'blogs',
            'courses'     => UPLOAD_PATH . 'courses',
            'departments' => UPLOAD_PATH . 'departments',
            'documents'   => UPLOAD_PATH . 'documents',
            'events'      => UPLOAD_PATH . 'events',
            'facilities'  => UPLOAD_PATH . 'facilities',
            'faculty'     => UPLOAD_PATH . 'faculty',
            'gallery'     => UPLOAD_PATH . 'gallery',
            'logos'       => UPLOAD_PATH . 'logos',
            'news'        => UPLOAD_PATH . 'news',
            'notices'     => UPLOAD_PATH . 'notices',
            'placements'  => UPLOAD_PATH . 'placements',
            'settings'    => UPLOAD_PATH . 'settings',
        ];

        $uploadStatus = [];
        foreach ($uploadDirs as $name => $path) {
            $exists = is_dir($path);
            $writable = $exists && is_writable($path);
            $uploadStatus[$name] = [
                'exists' => $exists,
                'writable' => $writable
            ];
        }

        $freeDisk = @disk_free_space(__DIR__);
        $totalDisk = @disk_total_space(__DIR__);
        $diskUsagePercent = ($totalDisk > 0 && $freeDisk > 0) ? round((($totalDisk - $freeDisk) / $totalDisk) * 100, 1) : 0;

        return [
            'php_version' => PHP_VERSION,
            'pdo_driver' => 'MySQL / MariaDB (InnoDB)',
            'timezone' => date_default_timezone_get(),
            'server_time' => date('Y-m-d H:i:s'),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time') . 's',
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'disk_free_gb' => $freeDisk ? round($freeDisk / 1024 / 1024 / 1024, 2) : 'N/A',
            'disk_usage_percent' => $diskUsagePercent,
            'upload_directories' => $uploadStatus,
            'session_lifetime' => (SESSION_LIFETIME === 0) ? 'Persistent (Explicit Logout)' : (SESSION_LIFETIME . 's')
        ];
    }

    /**
     * Retrieves all distinct academic years recorded in the fees table.
     *
     * @return array List of distinct academic year strings (e.g. ['2026-2027', '2025-2026'])
     */
    public function getAllDistinctAcademicYears(): array
    {
        $rows = $this->db->fetchAll("SELECT DISTINCT academic_year FROM fees WHERE academic_year IS NOT NULL AND academic_year != '' ORDER BY academic_year DESC");
        $years = array_column($rows, 'academic_year');
        if (empty($years)) {
            $years = ['2026-2027', '2025-2026'];
        }
        return $years;
    }

    /**
     * Determines the currently active academic year for public display.
     * Reads from site settings or falls back to the latest recorded academic year.
     *
     * @return string Active academic year (e.g. '2026-2027')
     */
    public function getActiveAcademicYear(): string
    {
        $setting = $this->getSetting('active_academic_year');
        if (!empty($setting)) {
            return $setting;
        }
        $admYear = $this->getSetting('admission_year', '2026-27');
        if (strpos($admYear, '2026') !== false) {
            return '2026-2027';
        }
        $years = $this->getAllDistinctAcademicYears();
        return $years[0] ?? '2026-2027';
    }

    /**
     * Retrieves year-wise tuition and fee breakdown for a specific academic course and year.
     *
     * @param int $courseId Target course ID
     * @param string|null $academicYear Academic year string or null to use active year
     * @return array List of fee breakdown records ordered by study year
     */
    public function getCourseYearWiseFees(int $courseId, ?string $academicYear = null): array
    {
        $ay = $academicYear ?: $this->getActiveAcademicYear();
        $fees = $this->db->fetchAll(
            "SELECT f.*, c.name as course_name, c.code as course_code, c.program_type, c.duration_years 
             FROM fees f 
             JOIN courses c ON f.course_id = c.id 
             WHERE f.course_id = ? AND f.academic_year = ? AND f.is_active = 1 
             ORDER BY f.study_year ASC",
            [$courseId, $ay]
        );

        if (empty($fees)) {
            // Fallback to most recent available academic year
            $fees = $this->db->fetchAll(
                "SELECT f.*, c.name as course_name, c.code as course_code, c.program_type, c.duration_years 
                 FROM fees f 
                 JOIN courses c ON f.course_id = c.id 
                 WHERE f.course_id = ? AND f.is_active = 1 
                 ORDER BY f.academic_year DESC, f.study_year ASC",
                [$courseId]
            );
        }

        return $fees;
    }

    /**
     * Fetches filtered fee structures with joined course and department details for the admin manager.
     *
     * @param int|null $courseId Optional course ID filter
     * @param string|null $academicYear Optional academic year filter
     * @param bool $activeOnly When true, returns only active fee structures
     * @param string|null $programType Optional program type filter (e.g. 'Regular', 'Integrated')
     * @return array Filtered list of fee structure records
     */
    public function getFeeStructures(?int $courseId = null, ?string $academicYear = null, bool $activeOnly = false, ?string $programType = null): array
    {
        $sql = "SELECT f.*, c.name as course_name, c.code as course_code, c.program_type, c.duration_years, d.name as department_name 
                FROM fees f 
                JOIN courses c ON f.course_id = c.id 
                JOIN departments d ON c.department_id = d.id";
        $conditions = [];
        $params = [];

        if ($courseId) {
            $conditions[] = "f.course_id = ?";
            $params[] = $courseId;
        }
        if ($academicYear && $academicYear !== 'all') {
            $conditions[] = "f.academic_year = ?";
            $params[] = $academicYear;
        }
        if ($programType && $programType !== 'all') {
            $conditions[] = "c.program_type = ?";
            $params[] = $programType;
        }
        if ($activeOnly) {
            $conditions[] = "f.is_active = 1";
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $sql .= " ORDER BY f.academic_year DESC, c.sort_order ASC, c.name ASC, f.study_year ASC";
        return $this->db->fetchAll($sql, $params);
    }
}

