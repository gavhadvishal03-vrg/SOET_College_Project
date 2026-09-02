<?php
/**
 * Content Manager - Shared CMS operations
 */
class ContentManager
{
    public Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getDepartments(bool $activeOnly = true): array
    {
        $where = $activeOnly ? 'WHERE is_active = 1' : '';
        return $this->db->fetchAll("SELECT * FROM departments {$where} ORDER BY name");
    }

    public function getDepartment(int $id): ?array
    {
        return $this->db->fetchOne("SELECT * FROM departments WHERE id = ?", [$id]);
    }

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
        $sql .= " ORDER BY d.name, c.name";
        return $this->db->fetchAll($sql, $params);
    }

    public function getFaculty(?int $departmentId = null): array
    {
        $sql = "SELECT f.*, d.name as department_name FROM faculty f JOIN departments d ON f.department_id = d.id WHERE f.is_active = 1";
        $params = [];
        if ($departmentId) {
            $sql .= " AND f.department_id = ?";
            $params[] = $departmentId;
        }
        $sql .= " ORDER BY d.id ASC,
                  CASE 
                      WHEN f.designation LIKE '%Director%' THEN 1
                      WHEN f.designation LIKE '%Head%' OR f.designation LIKE '%HOD%' THEN 2
                      WHEN f.designation LIKE '%Professor%' AND f.designation NOT LIKE '%Associate%' AND f.designation NOT LIKE '%Assistant%' THEN 3
                      WHEN f.designation LIKE '%Associate%' THEN 4
                      WHEN f.designation LIKE '%Assistant%' THEN 5
                      ELSE 6 
                  END ASC,
                  f.experience_years DESC,
                  f.name ASC";
        return $this->db->fetchAll($sql, $params);
    }

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

    public function getPublishedNews(int $limit = 10, int $offset = 0): array
    {
        return $this->db->fetchAll(
            "SELECT n.*, u.full_name as author_name FROM news n
             JOIN users u ON n.author_id = u.id
             WHERE n.status = 'published' ORDER BY n.published_at DESC LIMIT ? OFFSET ?",
            [$limit, $offset]
        );
    }

    public function getActiveNotices(int $limit = 10): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM notices WHERE is_active = 1 AND (expires_at IS NULL OR expires_at >= CURDATE())
             ORDER BY is_pinned DESC, created_at DESC LIMIT ?",
            [$limit]
        );
    }

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

    public function getSubEvents(int $eventId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM sub_events WHERE event_id = ? AND is_active = 1 ORDER BY sub_event_date ASC, sub_event_time ASC, title ASC",
            [$eventId]
        );
    }

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

    public function deleteSubEvent(int $id): bool
    {
        return $this->db->delete('sub_events', 'id = ?', [$id]) > 0;
    }

    public function getGallery(int $limit = 12): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM gallery WHERE is_active = 1 ORDER BY created_at DESC LIMIT ?",
            [$limit]
        );
    }

    public function getPlacements(): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM placements WHERE is_active = 1 ORDER BY placement_year DESC, company_name"
        );
    }

    public function getSiteSettings(): array
    {
        $rows = $this->db->fetchAll("SELECT setting_key, setting_value FROM site_settings");
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }

    public function getSetting(string $key, string $default = ''): string
    {
        $row = $this->db->fetchOne("SELECT setting_value FROM site_settings WHERE setting_key = ?", [$key]);
        return $row ? $row['setting_value'] : $default;
    }

    public function getDashboardStats(): array
    {
        return [
            'visitors' => $this->db->count('visitors'),
            'today_visitors' => $this->db->count('visitors', 'visit_date = ?', [date('Y-m-d')]),
            'admissions' => $this->db->count('admissions'),
            'pending_admissions' => $this->db->count('admissions', "status = 'pending'"),
            'faculty' => $this->db->count('faculty', 'is_active = 1'),
            'departments' => $this->db->count('departments', 'is_active = 1'),
            'courses' => $this->db->count('courses', 'is_active = 1'),
            'events' => $this->db->count('events', 'is_active = 1'),
            'blogs' => $this->db->count('blogs', "status = 'published'"),
            'pending_blogs' => $this->db->count('blogs', "status IN ('submitted','under_review','returned')"),
            'news' => $this->db->count('news', "status = 'published'"),
            'pending_news' => $this->db->count('news', "status IN ('submitted','under_review','returned')"),
            'notices' => $this->db->count('notices', 'is_active = 1'),
            'gallery' => $this->db->count('gallery', 'is_active = 1'),
            'contacts' => $this->db->count('contact_messages', "status = 'unread'"),
            'chatbot_queries' => $this->db->count('chatbot_logs'),
            'users' => $this->db->count('users', 'is_active = 1'),
        ];
    }

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
     * Synchronize course filled_seats count with confirmed/approved admissions
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
     * Fetch real-time course seat metrics for all or single course
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
     * Perform transactional admission status change with capacity check & seat sync
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
     * Fetch paginated activity and security logs
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
     * Get aggregate analytics data for Admin Dashboard charts
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
     * Get real-time system health metrics
     */
    public function getSystemHealth(): array
    {
        $uploadDirs = [
            'admissions' => UPLOAD_PATH . 'admissions',
            'blogs' => UPLOAD_PATH . 'blogs',
            'news' => UPLOAD_PATH . 'news',
            'settings' => UPLOAD_PATH . 'settings',
            'events' => UPLOAD_PATH . 'events'
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
}
