<?php
/**
 * ⚡ Spotlight Quick Search & Navigation API (Ctrl+K Command Palette)
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../core/bootstrap.php';

$query = trim($_GET['q'] ?? '');

$results = [
    'pages' => [],
    'courses' => [],
    'departments' => [],
    'faculty' => [],
    'events' => [],
    'notices' => []
];

// Quick Static Page Navigation Links
$staticPages = [
    ['title' => '🎓 Admissions 2026 Portal', 'desc' => 'Apply online, check eligibility, fee structure & seat matrix', 'url' => APP_URL . '/admissions.php', 'icon' => 'fa-graduation-cap'],
    ['title' => '💰 Course Fee Schedules', 'desc' => '4-Year tuition fees and scholarship concession calculator', 'url' => APP_URL . '/courses.php', 'icon' => 'fa-money-bill-wave'],
    ['title' => '👨‍🏫 Faculty & Research Directory', 'desc' => 'Faculty profiles, Dean & Director Dr. Parminder Kaur Dhingra', 'url' => APP_URL . '/faculty.php', 'icon' => 'fa-chalkboard-user'],
    ['title' => '🏛️ Academic Departments & Labs', 'desc' => 'CSE, AI/ML, Cyber Security, Mechanical, Civil & ECE Labs', 'url' => APP_URL . '/departments.php', 'icon' => 'fa-building-columns'],
    ['title' => '🏆 Training & Campus Placements', 'desc' => 'Top recruitment statistics, highest salary packages & hiring partners', 'url' => APP_URL . '/placements.php', 'icon' => 'fa-briefcase'],
    ['title' => '📅 Upcoming Events & Hackathons', 'desc' => 'Technical symposiums, workshops, and sports events', 'url' => APP_URL . '/events.php', 'icon' => 'fa-calendar-days'],
    ['title' => '📞 Contact & Admission Helpdesk', 'desc' => 'Campus location, phone directory, email & contact form', 'url' => APP_URL . '/contact.php', 'icon' => 'fa-phone-volume'],
    ['title' => '🤖 CampusAI Assistant', 'desc' => '24/7 Intelligent assistant for admission queries & tech help', 'url' => 'javascript:document.getElementById("soet-chatbot-trigger")?.click()', 'icon' => 'fa-robot']
];

if (empty($query)) {
    $results['pages'] = array_slice($staticPages, 0, 5);
    echo json_encode(['success' => true, 'results' => $results]);
    exit;
}

try {
    $db = Database::getInstance();
    $param = '%' . $query . '%';

    // 1. Filter Static Pages
    foreach ($staticPages as $sp) {
        if (stripos($sp['title'], $query) !== false || stripos($sp['desc'], $query) !== false) {
            $results['pages'][] = $sp;
        }
    }

    // 2. Search Courses
    $courses = $db->fetchAll(
        "SELECT name, code, duration_years, fee_year_1 FROM courses WHERE (name LIKE ? OR code LIKE ? OR description LIKE ?) AND is_active = 1 LIMIT 4",
        [$param, $param, $param]
    );
    foreach ($courses as $c) {
        $results['courses'][] = [
            'title' => $c['name'] . ' (' . $c['code'] . ')',
            'desc' => $c['duration_years'] . ' Years • ₹' . number_format($c['fee_year_1']) . '/yr',
            'url' => APP_URL . '/courses.php',
            'icon' => 'fa-book-open'
        ];
    }

    // 3. Search Departments
    $depts = $db->fetchAll(
        "SELECT name, code FROM departments WHERE (name LIKE ? OR code LIKE ?) AND is_active = 1 LIMIT 3",
        [$param, $param]
    );
    foreach ($depts as $d) {
        $results['departments'][] = [
            'title' => $d['name'] . ' Department',
            'desc' => 'Explore academic curriculum, HOD, and laboratories',
            'url' => APP_URL . '/departments.php',
            'icon' => 'fa-laptop-code'
        ];
    }

    // 4. Search Faculty
    $faculty = $db->fetchAll(
        "SELECT name, designation, qualification FROM faculty WHERE (name LIKE ? OR designation LIKE ? OR qualification LIKE ?) AND is_active = 1 LIMIT 4",
        [$param, $param, $param]
    );
    foreach ($faculty as $f) {
        $results['faculty'][] = [
            'title' => $f['name'],
            'desc' => $f['designation'] . ' • ' . $f['qualification'],
            'url' => APP_URL . '/faculty.php',
            'icon' => 'fa-user-tie'
        ];
    }

    // 5. Search Events
    $events = $db->fetchAll(
        "SELECT title, event_date, venue FROM events WHERE (title LIKE ? OR description LIKE ?) AND is_active = 1 LIMIT 3",
        [$param, $param]
    );
    foreach ($events as $e) {
        $dateStr = !empty($e['event_date']) ? date('d-M-Y', strtotime($e['event_date'])) : 'Upcoming';
        $results['events'][] = [
            'title' => $e['title'],
            'desc' => $dateStr . ' • ' . ($e['venue'] ?? 'Campus Grounds'),
            'url' => APP_URL . '/events.php',
            'icon' => 'fa-calendar-check'
        ];
    }

    // 6. Search Notices
    $notices = $db->fetchAll(
        "SELECT title, created_at FROM notices WHERE (title LIKE ? OR content LIKE ?) AND is_active = 1 LIMIT 3",
        [$param, $param]
    );
    foreach ($notices as $n) {
        $results['notices'][] = [
            'title' => $n['title'],
            'desc' => 'Circular • ' . date('d-M-Y', strtotime($n['created_at'])),
            'url' => APP_URL . '/index.php#notices',
            'icon' => 'fa-bullhorn'
        ];
    }

    echo json_encode(['success' => true, 'results' => $results]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'results' => $results, 'message' => $e->getMessage()]);
}
