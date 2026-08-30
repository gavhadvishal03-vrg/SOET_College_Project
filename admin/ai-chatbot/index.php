<?php
require_once __DIR__ . '/../../core/bootstrap.php';
Auth::requirePermission('manage_chatbot_kb');

$page_title = "AI Chatbot Control Suite & Analytics";
include_once __DIR__ . '/../../admin/includes/header.php';

$db = Database::getInstance();

// Metrics calculations
$totalConversations = $db->count('chat_sessions');
$todayConversations = $db->count('chat_sessions', 'DATE(started_at) = CURDATE()');
$totalMessages = $db->count('chat_messages');
$unansweredCount = $db->count('unanswered_questions', "status = 'pending'");

$soetQueries = $db->count('chat_messages', "source = 'database'");
$openaiQueries = $db->count('chat_messages', "source = 'openai'");
$hybridQueries = $db->count('chat_messages', "source = 'hybrid'");

$positiveRatings = $db->count('chat_feedback', "rating = 'positive'");
$totalRatings = $db->count('chat_feedback');
$satisfactionRate = $totalRatings > 0 ? round(($positiveRatings / $totalRatings) * 100, 1) : 100;

// Fetch 7-day chat session trends
$trendRows = $db->fetchAll(
    "SELECT DATE(started_at) as chat_date, COUNT(*) as count 
     FROM chat_sessions 
     WHERE started_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) 
     GROUP BY DATE(started_at) 
     ORDER BY chat_date ASC"
);
$trendMap = [];
foreach ($trendRows as $tr) {
    $trendMap[$tr['chat_date']] = (int)$tr['count'];
}

$chartLabels = [];
$chartData = [];
for ($i = 6; $i >= 0; $i--) {
    $dateKey = date('Y-m-d', strtotime("-$i days"));
    $chartLabels[] = date('D (d M)', strtotime($dateKey));
    $chartData[] = $trendMap[$dateKey] ?? 0;
}
?>

<!-- Chatbot Admin Header Subnav -->
<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-4 border-bottom flex-wrap gap-2">
    <div>
        <h1 class="h2 fw-bold text-primary-color mb-0"><i class="fa-solid fa-robot text-warning me-2"></i>AI Chatbot Control Suite</h1>
        <small class="text-muted">Enterprise Hybrid Query Router, Knowledge Base, &amp; CampusAI Analytics</small>
    </div>
    <div class="d-flex gap-2 align-items-center flex-wrap">
        <a href="settings.php" class="btn btn-warning text-dark font-semibold"><i class="fa-solid fa-key me-1"></i> OpenAI Settings</a>
        <a href="unanswered.php" class="btn btn-outline-danger font-semibold position-relative">
            <i class="fa-solid fa-triangle-exclamation me-1"></i> Unanswered Questions
            <?php if ($unansweredCount > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?php echo $unansweredCount; ?></span>
            <?php endif; ?>
        </a>
    </div>
</div>

<!-- Navigation Pills -->
<ul class="nav nav-pills mb-4 bg-light p-2 rounded border">
    <li class="nav-item"><a class="nav-link active font-semibold" href="index.php"><i class="fa-solid fa-chart-line me-1"></i> Dashboard</a></li>
    <li class="nav-item"><a class="nav-link font-semibold" href="knowledge-base.php"><i class="fa-solid fa-book me-1"></i> Knowledge Base</a></li>
    <li class="nav-item"><a class="nav-link font-semibold" href="faq.php"><i class="fa-solid fa-circle-question me-1"></i> FAQs</a></li>
    <li class="nav-item"><a class="nav-link font-semibold" href="documents.php"><i class="fa-solid fa-file-arrow-up me-1"></i> Doc Upload &amp; Text Extractor</a></li>
    <li class="nav-item"><a class="nav-link font-semibold" href="unanswered.php"><i class="fa-solid fa-question me-1"></i> Unanswered Queue</a></li>
    <li class="nav-item"><a class="nav-link font-semibold" href="conversations.php"><i class="fa-solid fa-comments me-1"></i> Conversations</a></li>
    <li class="nav-item"><a class="nav-link font-semibold" href="feedback.php"><i class="fa-solid fa-thumbs-up me-1"></i> User Feedback</a></li>
    <li class="nav-item"><a class="nav-link font-semibold" href="settings.php"><i class="fa-solid fa-gears me-1"></i> AI Settings</a></li>
</ul>

<!-- Metrics Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 bg-primary text-white">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-white-50 mb-1 text-uppercase font-semibold">Total Chats</h6>
                    <h3 class="fw-bold mb-0"><?php echo number_format($totalConversations); ?></h3>
                </div>
                <i class="fa-solid fa-comments fs-1 text-white-50"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 bg-success text-white">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-white-50 mb-1 text-uppercase font-semibold">Today's Chats</h6>
                    <h3 class="fw-bold mb-0"><?php echo number_format($todayConversations); ?></h3>
                </div>
                <i class="fa-solid fa-calendar-day fs-1 text-white-50"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 bg-info text-white">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-white-50 mb-1 text-uppercase font-semibold">SOET DB Queries</h6>
                    <h3 class="fw-bold mb-0"><?php echo number_format($soetQueries); ?></h3>
                </div>
                <i class="fa-solid fa-building-columns fs-1 text-white-50"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 bg-dark text-white">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-white-50 mb-1 text-uppercase font-semibold">Satisfaction Rate</h6>
                    <h3 class="fw-bold mb-0 text-warning"><?php echo $satisfactionRate; ?>%</h3>
                </div>
                <i class="fa-solid fa-star fs-1 text-warning"></i>
            </div>
        </div>
    </div>
</div>

<!-- Chart Analytics Section -->
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm p-4 h-100">
            <h5 class="fw-bold text-primary-color mb-3 border-bottom pb-2"><i class="fa-solid fa-chart-area text-warning me-2"></i>7-Day Chatbot Interaction Trends</h5>
            <div style="height: 280px; position: relative;">
                <canvas id="chatTrafficChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm p-4 h-100">
            <h5 class="fw-bold text-primary-color mb-3 border-bottom pb-2"><i class="fa-solid fa-chart-pie text-warning me-2"></i>Query Source Distribution</h5>
            <div style="height: 280px; position: relative;">
                <canvas id="querySourceChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js Integration -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Traffic Chart
    const ctxTraffic = document.getElementById('chatTrafficChart').getContext('2d');
    new Chart(ctxTraffic, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($chartLabels); ?>,
            datasets: [{
                label: 'Chat Sessions',
                data: <?php echo json_encode($chartData); ?>,
                borderColor: '#1e3a8a',
                backgroundColor: 'rgba(30, 58, 138, 0.15)',
                borderWidth: 3,
                fill: true,
                tension: 0.35,
                pointRadius: 4,
                pointBackgroundColor: '#f59e0b'
            }]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });

    // Source Distribution Chart
    const ctxSource = document.getElementById('querySourceChart').getContext('2d');
    new Chart(ctxSource, {
        type: 'doughnut',
        data: {
            labels: ['SOET Verified DB', 'General AI Knowledge', 'Hybrid Merged'],
            datasets: [{
                data: [<?php echo max(1, $soetQueries); ?>, <?php echo max(1, $openaiQueries); ?>, <?php echo max(1, $hybridQueries); ?>],
                backgroundColor: ['#1e3a8a', '#10b981', '#f59e0b'],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
});
</script>

<?php include_once __DIR__ . '/../../admin/includes/footer.php'; ?>
