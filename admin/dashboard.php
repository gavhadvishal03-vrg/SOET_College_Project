<?php
$page_title = "Admin Dashboard & Control Center";
include_once __DIR__ . '/includes/header.php';
Auth::requireLogin();

$cms = new ContentManager();
$stats = $cms->getDashboardStats();
$analytics = $cms->getAnalyticsSummary();
$health = $cms->getSystemHealth();

// Fetch latest visitor logs (last 5)
$latest_visitors = $cms->db->fetchAll("SELECT * FROM visitors ORDER BY updated_at DESC LIMIT 5");

// Fetch pending admissions (last 5)
$pending_admissions_list = $cms->db->fetchAll(
    "SELECT a.*, c.code as course_code FROM admissions a 
     JOIN courses c ON a.course_id = c.id 
     WHERE a.status = 'pending' ORDER BY a.created_at DESC LIMIT 5"
);

// Fetch pending reviews (blogs and news)
$pending_blogs_list = $cms->db->fetchAll(
    "SELECT b.*, u.full_name as author_name FROM blogs b 
     JOIN users u ON b.author_id = u.id 
     WHERE b.status IN ('submitted', 'under_review') ORDER BY b.created_at DESC LIMIT 3"
);

$pending_news_list = $cms->db->fetchAll(
    "SELECT n.*, u.full_name as author_name FROM news n 
     JOIN users u ON n.author_id = u.id 
     WHERE n.status IN ('submitted', 'under_review') ORDER BY n.created_at DESC LIMIT 3"
);

// Chart Data Preparation
$trafficLabels = [];
$trafficViews = [];
$trafficVisitors = [];
foreach ($analytics['traffic_trend'] as $row) {
    $trafficLabels[] = date('d M', strtotime($row['visit_date']));
    $trafficViews[] = (int)$row['views'];
    $trafficVisitors[] = (int)$row['visitors'];
}

$courseLabels = [];
$courseIntake = [];
$courseFilled = [];
foreach ($analytics['admissions_by_course'] as $c) {
    $courseLabels[] = $c['course_code'];
    $courseIntake[] = (int)$c['intake_capacity'];
    $courseFilled[] = (int)$c['filled_seats'];
}

$intentLabels = [];
$intentCounts = [];
foreach ($analytics['chatbot_intents'] as $intent) {
    $intentLabels[] = ucfirst(strtolower($intent['intent']));
    $intentCounts[] = (int)$intent['count'];
}
?>

<!-- Header Toolbar -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4 border-bottom">
    <div>
        <h1 class="h2 fw-bold text-primary-color mb-1">
            <i class="fa-solid fa-gauge-high text-warning me-2"></i>Executive Control Dashboard
        </h1>
        <p class="text-muted small mb-0">SOET MGM University Institutional Administration & AI Analytics Suite</p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0 d-flex gap-2 align-items-center">
        <span class="badge bg-light text-dark p-2 border shadow-xs">
            <i class="fa-regular fa-clock me-1 text-warning"></i> <?php echo date('d M Y, h:i A'); ?>
        </span>
        <?php if (Auth::hasPermission('manage_users') || Auth::hasRole('Super Admin')): ?>
            <a href="<?php echo APP_URL; ?>/admin/modules/logs.php" class="btn btn-sm btn-outline-secondary">
                <i class="fa-solid fa-shield-halved me-1"></i> Audit Logs
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Quick Action Shortcuts -->
<div class="row g-2 mb-4">
    <div class="col-6 col-md-3 col-xl-2">
        <a href="<?php echo APP_URL; ?>/admin/modules/admissions.php" class="btn btn-outline-primary w-100 py-2 d-flex flex-column align-items-center justify-content-center shadow-xs">
            <i class="fa-solid fa-id-card fa-lg mb-1 text-primary"></i>
            <span class="small font-semibold">Review Admissions</span>
        </a>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <a href="<?php echo APP_URL; ?>/admin/modules/courses.php?action=add" class="btn btn-outline-primary w-100 py-2 d-flex flex-column align-items-center justify-content-center shadow-xs">
            <i class="fa-solid fa-book-medical fa-lg mb-1 text-success"></i>
            <span class="small font-semibold">New Course</span>
        </a>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <a href="<?php echo APP_URL; ?>/admin/modules/notices.php?action=add" class="btn btn-outline-primary w-100 py-2 d-flex flex-column align-items-center justify-content-center shadow-xs">
            <i class="fa-solid fa-bullhorn fa-lg mb-1 text-warning"></i>
            <span class="small font-semibold">Post Notice</span>
        </a>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <a href="<?php echo APP_URL; ?>/admin/modules/events.php?action=add" class="btn btn-outline-primary w-100 py-2 d-flex flex-column align-items-center justify-content-center shadow-xs">
            <i class="fa-solid fa-calendar-plus fa-lg mb-1 text-info"></i>
            <span class="small font-semibold">Add Event</span>
        </a>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <a href="<?php echo APP_URL; ?>/admin/ai-chatbot/index.php" class="btn btn-outline-primary w-100 py-2 d-flex flex-column align-items-center justify-content-center shadow-xs">
            <i class="fa-solid fa-robot fa-lg mb-1 text-warning"></i>
            <span class="small font-semibold">AI Chatbot Suite</span>
        </a>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <a href="<?php echo APP_URL; ?>/admin/modules/backup.php" class="btn btn-outline-primary w-100 py-2 d-flex flex-column align-items-center justify-content-center shadow-xs">
            <i class="fa-solid fa-database fa-lg mb-1 text-secondary"></i>
            <span class="small font-semibold">Backup DB</span>
        </a>
    </div>
</div>

<!-- Key Metric Counters Grid -->
<div class="row g-3 mb-4">
    <!-- Visitors -->
    <div class="col-sm-6 col-xl-3">
        <div class="card admin-stat-card bg-white p-3 border-0 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">Total Visitors</span>
                    <h3 class="fw-bold text-primary-color mb-0 mt-1"><?php echo number_format($stats['visitors']); ?></h3>
                    <small class="text-success"><i class="fa-solid fa-arrow-trend-up me-1"></i>Today: <?php echo number_format($stats['today_visitors']); ?></small>
                </div>
                <div class="p-3 rounded bg-primary bg-opacity-10 text-primary">
                    <i class="fa-solid fa-users-viewfinder fa-2x"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Admissions Portal -->
    <div class="col-sm-6 col-xl-3">
        <div class="card admin-stat-card bg-white p-3 border-0 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">Admissions Portal</span>
                    <h3 class="fw-bold text-primary-color mb-0 mt-1"><?php echo number_format($stats['admissions']); ?></h3>
                    <small class="text-warning"><i class="fa-solid fa-clock-rotate-left me-1"></i>Pending: <?php echo number_format($stats['pending_admissions']); ?></small>
                </div>
                <div class="p-3 rounded bg-warning bg-opacity-10 text-warning">
                    <i class="fa-solid fa-id-card fa-2x"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Faculty & Departments -->
    <div class="col-sm-6 col-xl-3">
        <div class="card admin-stat-card bg-white p-3 border-0 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">Academic Staff</span>
                    <h3 class="fw-bold text-primary-color mb-0 mt-1"><?php echo number_format($stats['faculty']); ?></h3>
                    <small class="text-secondary">Depts: <?php echo $stats['departments']; ?> | Courses: <?php echo $stats['courses']; ?></small>
                </div>
                <div class="p-3 rounded bg-success bg-opacity-10 text-success">
                    <i class="fa-solid fa-user-tie fa-2x"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- CampusAI Usage -->
    <div class="col-sm-6 col-xl-3">
        <div class="card admin-stat-card bg-white p-3 border-0 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">CampusAI Usage</span>
                    <h3 class="fw-bold text-primary-color mb-0 mt-1"><?php echo number_format($stats['chatbot_queries']); ?></h3>
                    <small class="text-info"><i class="fa-solid fa-circle-check me-1"></i>Conversations Active</small>
                </div>
                <div class="p-3 rounded bg-info bg-opacity-10 text-info">
                    <i class="fa-solid fa-robot fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Interactive Analytics Charts Grid -->
<div class="row g-4 mb-4">
    <!-- Traffic Trends (14-day Line Chart) -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                <h5 class="mb-0 fw-bold text-primary-color">
                    <i class="fa-solid fa-chart-line text-warning me-2"></i>14-Day Portal Traffic Trends
                </h5>
                <span class="badge bg-light text-muted border">Daily Aggregates</span>
            </div>
            <div class="card-body">
                <div style="height: 270px;">
                    <canvas id="trafficChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Chatbot Top Topics Doughnut -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                <h5 class="mb-0 fw-bold text-primary-color">
                    <i class="fa-solid fa-chart-pie text-warning me-2"></i>AI Inquiries by Intent
                </h5>
                <span class="badge bg-light text-muted border">Top Intents</span>
            </div>
            <div class="card-body">
                <div style="height: 270px;" class="d-flex align-items-center justify-content-center">
                    <canvas id="intentChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Program Seat Occupancy & Capacity Bar Chart -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                <h5 class="mb-0 fw-bold text-primary-color">
                    <i class="fa-solid fa-chart-simple text-warning me-2"></i>Degree Programs Seat Capacity vs. Filled Seats
                </h5>
                <a href="<?php echo APP_URL; ?>/admin/modules/admissions.php" class="btn btn-xs btn-outline-primary py-1 px-2 rounded" style="font-size: 11px;">Manage Seats</a>
            </div>
            <div class="card-body">
                <div style="height: 230px;">
                    <canvas id="seatsChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Operational Queues & System Health -->
<div class="row g-4 mb-4">
    <!-- Left Column: Pending Admissions & Visitors -->
    <div class="col-lg-7">
        <!-- Pending Admissions Applications -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                <h5 class="mb-0 fw-bold text-primary-color"><i class="fa-solid fa-user-clock text-warning me-2"></i>Pending Admissions Review</h5>
                <a href="<?php echo APP_URL; ?>/admin/modules/admissions.php" class="btn btn-xs btn-outline-primary py-1 px-2 rounded" style="font-size: 11px;">View All</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($pending_admissions_list)): ?>
                    <div class="p-4 text-center text-muted">
                        <p class="mb-0"><i class="fa-solid fa-circle-check text-success me-1"></i> No pending admission applications to review.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th>App No</th>
                                    <th>Student Name</th>
                                    <th>Course</th>
                                    <th class="text-center">12th %</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_admissions_list as $app): ?>
                                    <tr>
                                        <td class="fw-bold"><?php echo htmlspecialchars($app['application_number']); ?></td>
                                        <td><?php echo htmlspecialchars($app['student_name']); ?></td>
                                        <td><span class="badge bg-secondary"><?php echo htmlspecialchars($app['course_code']); ?></span></td>
                                        <td class="text-center"><?php echo $app['percentage_12th']; ?>%</td>
                                        <td class="text-end">
                                            <a href="<?php echo APP_URL; ?>/admin/modules/admissions.php?action=view&id=<?php echo $app['id']; ?>" class="btn btn-xs btn-primary py-0.5 px-2 rounded" style="font-size: 10px;">Review</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Visitor Traffic -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-primary-color"><i class="fa-solid fa-chart-bar text-warning me-2"></i>Live Visitor Pulse</h5>
                <a href="<?php echo APP_URL; ?>/admin/modules/visitors.php" class="btn btn-xs btn-outline-secondary py-1 px-2 rounded" style="font-size: 11px;">Full Logs</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>IP Address</th>
                                <th>Date</th>
                                <th>Views</th>
                                <th>Last Active Page</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($latest_visitors as $v): ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($v['ip_address']); ?></code></td>
                                    <td><?php echo formatDate($v['visit_date']); ?></td>
                                    <td><span class="badge bg-info text-dark"><?php echo $v['page_views']; ?> views</span></td>
                                    <td><span class="text-muted font-semibold"><?php echo htmlspecialchars($v['last_page']); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Editorial Queues & System Health -->
    <div class="col-lg-5">
        <!-- System Health Widget -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-primary-color"><i class="fa-solid fa-server text-warning me-2"></i>System Health & Environment</h5>
                <span class="badge bg-success"><i class="fa-solid fa-check me-1"></i>Operational</span>
            </div>
            <div class="card-body p-3 small">
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="text-muted">PHP Version:</span>
                    <strong class="text-dark"><?php echo $health['php_version']; ?></strong>
                </div>
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="text-muted">Database Engine:</span>
                    <strong class="text-dark"><?php echo $health['pdo_driver']; ?></strong>
                </div>
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="text-muted">Session Mode:</span>
                    <span class="badge bg-light text-dark border"><?php echo $health['session_lifetime']; ?></span>
                </div>
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="text-muted">Memory Limit / Upload Max:</span>
                    <span class="text-dark"><?php echo $health['memory_limit']; ?> / <?php echo $health['upload_max_filesize']; ?></span>
                </div>
                <div class="mt-2 pt-1">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Disk Storage Free:</span>
                        <strong><?php echo $health['disk_free_gb']; ?> GB</strong>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $health['disk_usage_percent']; ?>%;" aria-valuenow="<?php echo $health['disk_usage_percent']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Blogs Review -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                <h5 class="mb-0 fw-bold text-primary-color"><i class="fa-solid fa-pen-nib text-warning me-2"></i>Blog Editorial Queue</h5>
                <span class="badge bg-danger"><?php echo $stats['pending_blogs']; ?> Pending</span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($pending_blogs_list)): ?>
                    <div class="p-3 text-center text-muted small">
                        <p class="mb-0">No blogs submitted for review.</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($pending_blogs_list as $blog): ?>
                            <div class="list-group-item p-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-muted small">By <?php echo htmlspecialchars($blog['author_name']); ?></span>
                                    <?php echo statusBadge($blog['status']); ?>
                                </div>
                                <h6 class="fw-bold mb-1 text-primary-color"><?php echo htmlspecialchars($blog['title']); ?></h6>
                                <a href="<?php echo APP_URL; ?>/admin/modules/blogs.php?action=review&id=<?php echo $blog['id']; ?>" class="btn btn-xs btn-outline-warning py-0.5 px-2 rounded mt-1" style="font-size: 11px;">Review Content</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- News Review -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                <h5 class="mb-0 fw-bold text-primary-color"><i class="fa-solid fa-newspaper text-warning me-2"></i>News Editorial Queue</h5>
                <span class="badge bg-danger"><?php echo $stats['pending_news']; ?> Pending</span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($pending_news_list)): ?>
                    <div class="p-3 text-center text-muted small">
                        <p class="mb-0">No news articles submitted for review.</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($pending_news_list as $news): ?>
                            <div class="list-group-item p-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-muted small"><?php echo formatDate($news['created_at']); ?></span>
                                    <?php echo statusBadge($news['status']); ?>
                                </div>
                                <h6 class="fw-bold mb-1 text-primary-color"><?php echo htmlspecialchars($news['title']); ?></h6>
                                <a href="<?php echo APP_URL; ?>/admin/modules/news.php?action=review&id=<?php echo $news['id']; ?>" class="btn btn-xs btn-outline-warning py-0.5 px-2 rounded mt-1" style="font-size: 11px;">Review Article</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js Library & Chart Initializations -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Traffic Trends Chart
    const trafficCtx = document.getElementById('trafficChart')?.getContext('2d');
    if (trafficCtx) {
        new Chart(trafficCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($trafficLabels); ?>,
                datasets: [
                    {
                        label: 'Page Views',
                        data: <?php echo json_encode($trafficViews); ?>,
                        borderColor: '#0d6efd',
                        backgroundColor: 'rgba(13, 110, 253, 0.1)',
                        fill: true,
                        tension: 0.35,
                        borderWidth: 2,
                        pointRadius: 3
                    },
                    {
                        label: 'Unique Visitors',
                        data: <?php echo json_encode($trafficVisitors); ?>,
                        borderColor: '#bfa15f',
                        backgroundColor: 'transparent',
                        borderDash: [4, 4],
                        tension: 0.35,
                        borderWidth: 2,
                        pointRadius: 3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11 } } }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // 2. Intent Distribution Doughnut Chart
    const intentCtx = document.getElementById('intentChart')?.getContext('2d');
    if (intentCtx) {
        new Chart(intentCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(!empty($intentLabels) ? $intentLabels : ['General', 'Admission', 'Fees', 'Courses']); ?>,
                datasets: [{
                    data: <?php echo json_encode(!empty($intentCounts) ? $intentCounts : [40, 25, 20, 15]); ?>,
                    backgroundColor: ['#0d233a', '#bfa15f', '#0d6efd', '#198754', '#ffc107', '#6c757d'],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } }
                },
                cutout: '68%'
            }
        });
    }

    // 3. Seats Capacity vs Filled Bar Chart
    const seatsCtx = document.getElementById('seatsChart')?.getContext('2d');
    if (seatsCtx) {
        new Chart(seatsCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($courseLabels); ?>,
                datasets: [
                    {
                        label: 'Total Intake Capacity',
                        data: <?php echo json_encode($courseIntake); ?>,
                        backgroundColor: 'rgba(13, 35, 58, 0.25)',
                        borderColor: '#0d233a',
                        borderWidth: 1,
                        borderRadius: 4
                    },
                    {
                        label: 'Filled / Approved Seats',
                        data: <?php echo json_encode($courseFilled); ?>,
                        backgroundColor: '#bfa15f',
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11 } } }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }
});
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
