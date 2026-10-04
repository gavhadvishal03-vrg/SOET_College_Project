<?php
/**
 * CampusAI Chatbot Control Suite & Analytics Hub (Admin Panel)
 *
 * Central dashboard for the AI Chatbot subsystem. Displays:
 * 1. Interaction metrics: total chat sessions, daily conversations, total messages.
 * 2. RAG source routing distribution (Database / OpenAI / Hybrid / Fallback).
 * 3. User satisfaction rates from thumbs up/down feedback.
 * 4. 7-day visual session trends via Chart.js.
 * 5. Pending unanswered questions counter alerts.
 *
 * @package SOET_College_Project
 * @subpackage Admin_AI_Chatbot
 */

require_once __DIR__ . '/../../core/bootstrap.php';
Auth::requirePermission('manage_chatbot_kb');

$page_title = "AI Chatbot Control Suite & Analytics";
include_once __DIR__ . '/../../admin/includes/header.php';

$db = Database::getInstance();

// -------------------------------------------------------------------------
// Calculate Chatbot Summary Metrics
// -------------------------------------------------------------------------
$totalConversations = $db->count('chat_sessions');
$todayConversations = $db->count('chat_sessions', 'DATE(started_at) = CURDATE()');
$totalMessages = $db->count('chat_messages');
$unansweredCount = $db->count('unanswered_questions', "status = 'pending'");

// RAG Query Source Breakdown
$soetQueries = $db->count('chat_messages', "source = 'database'");
$openaiQueries = $db->count('chat_messages', "source = 'openai'");
$hybridQueries = $db->count('chat_messages', "source = 'hybrid'");

// User Satisfaction Calculation
$positiveRatings = $db->count('chat_feedback', "rating = 'positive'");
$totalRatings = $db->count('chat_feedback');
$satisfactionRate = $totalRatings > 0 ? round(($positiveRatings / $totalRatings) * 100, 1) : 100;

// -------------------------------------------------------------------------
// Fetch 7-Day Chat Session Trends for Chart.js
// -------------------------------------------------------------------------
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
<div class="d-flex justify-content-between align-items-center pt-2 pb-3 mb-4 border-bottom flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1" style="font-size: 14px;">Cognitive Intelligence</span>
        </div>
        <h2 class="font-display fw-extrabold text-dark mb-1">CampusAI Hub &amp; Analytics</h2>
        <p class="text-muted small mb-0">Enterprise Hybrid Query Router, Knowledge Base, &amp; CampusAI Analytics</p>
    </div>
    <div class="d-flex gap-2 align-items-center flex-wrap">
        <a href="settings.php" class="btn btn-outline-warning text-dark btn-sm rounded-pill px-3 shadow-xs font-semibold">
            <i class="fa-solid fa-key me-1 text-warning"></i> AI API Settings
        </a>
        <a href="unanswered.php" class="btn btn-outline-danger btn-sm rounded-pill px-3 position-relative font-semibold shadow-xs">
            <i class="fa-solid fa-triangle-exclamation me-1"></i> Unanswered Questions
            <?php if ($unansweredCount > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-white" style="font-size: 14px;"><?php echo $unansweredCount; ?></span>
            <?php endif; ?>
        </a>
    </div>
</div>

<!-- Navigation Pills -->
<div class="card border-0 shadow-sm rounded-4 bg-white p-2 mb-4">
    <ul class="nav nav-pills gap-1 flex-wrap" id="aiSubNav">
        <li class="nav-item"><a class="nav-link active rounded-pill font-semibold px-3 py-2 text-sm" href="index.php"><i class="fa-solid fa-chart-line me-1.5"></i> Dashboard</a></li>
        <li class="nav-item"><a class="nav-link rounded-pill font-semibold px-3 py-2 text-sm text-secondary" href="knowledge-base.php"><i class="fa-solid fa-book me-1.5 text-primary"></i> Knowledge Base</a></li>
        <li class="nav-item"><a class="nav-link rounded-pill font-semibold px-3 py-2 text-sm text-secondary" href="faq.php"><i class="fa-solid fa-circle-question me-1.5 text-info"></i> FAQs</a></li>
        <li class="nav-item"><a class="nav-link rounded-pill font-semibold px-3 py-2 text-sm text-secondary" href="documents.php"><i class="fa-solid fa-file-arrow-up me-1.5 text-warning"></i> Documents Extractor</a></li>
        <li class="nav-item"><a class="nav-link rounded-pill font-semibold px-3 py-2 text-sm text-secondary" href="unanswered.php"><i class="fa-solid fa-question me-1.5 text-danger"></i> Unanswered</a></li>
        <li class="nav-item"><a class="nav-link rounded-pill font-semibold px-3 py-2 text-sm text-secondary" href="conversations.php"><i class="fa-solid fa-comments me-1.5 text-success"></i> Conversations</a></li>
        <li class="nav-item"><a class="nav-link rounded-pill font-semibold px-3 py-2 text-sm text-secondary" href="feedback.php"><i class="fa-solid fa-thumbs-up me-1.5 text-warning"></i> Feedback</a></li>
        <li class="nav-item"><a class="nav-link rounded-pill font-semibold px-3 py-2 text-sm text-secondary" href="settings.php"><i class="fa-solid fa-gears me-1.5 text-muted"></i> AI Config</a></li>
    </ul>
</div>

<!-- Metrics Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3.5 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small text-uppercase fw-semibold" style="font-size: 14px; letter-spacing: 0.5px;">Total Chats</span>
                <div class="stat-icon-sm bg-primary-subtle text-primary rounded-3 d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                    <i class="fa-solid fa-comments"></i>
                </div>
            </div>
            <h3 class="font-display fw-extrabold text-dark mb-0"><?php echo number_format($totalConversations); ?></h3>
            <div class="text-muted small mt-1" style="font-size: 14px;"><?php echo number_format($totalMessages); ?> total messages</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3.5 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small text-uppercase fw-semibold" style="font-size: 14px; letter-spacing: 0.5px;">Today's Chats</span>
                <div class="stat-icon-sm bg-success-subtle text-success rounded-3 d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                    <i class="fa-solid fa-calendar-day"></i>
                </div>
            </div>
            <h3 class="font-display fw-extrabold text-dark mb-0"><?php echo number_format($todayConversations); ?></h3>
            <div class="text-success small mt-1 fw-medium" style="font-size: 14px;"><i class="fa-solid fa-arrow-trend-up me-1"></i>Active sessions today</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3.5 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small text-uppercase fw-semibold" style="font-size: 14px; letter-spacing: 0.5px;">DB Facts Served</span>
                <div class="stat-icon-sm bg-info-subtle text-info-emphasis rounded-3 d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                    <i class="fa-solid fa-building-columns"></i>
                </div>
            </div>
            <h3 class="font-display fw-extrabold text-dark mb-0"><?php echo number_format($soetQueries); ?></h3>
            <div class="text-muted small mt-1" style="font-size: 14px;">Institutional verified data</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3.5 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small text-uppercase fw-semibold" style="font-size: 14px; letter-spacing: 0.5px;">Satisfaction Rate</span>
                <div class="stat-icon-sm bg-warning-subtle text-warning-emphasis rounded-3 d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                    <i class="fa-solid fa-star"></i>
                </div>
            </div>
            <h3 class="font-display fw-extrabold text-warning-emphasis mb-0"><?php echo $satisfactionRate; ?>%</h3>
            <div class="text-muted small mt-1" style="font-size: 14px;">Based on <?php echo $totalRatings; ?> ratings</div>
        </div>
    </div>
</div>

<!-- Chart Analytics Section -->
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-3">
                <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-chart-area text-primary me-2"></i>7-Day Chatbot Interaction Trends</h5>
                <span class="badge bg-light text-muted border font-semibold">Weekly View</span>
            </div>
            <div style="height: 280px; position: relative;">
                <canvas id="chatTrafficChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-3">
                <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-chart-pie text-primary me-2"></i>Query Source Routing</h5>
                <span class="badge bg-light text-muted border font-semibold">RAG Mix</span>
            </div>
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
                backgroundColor: 'rgba(30, 58, 138, 0.08)',
                borderWidth: 2.5,
                fill: true,
                tension: 0.35,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointBackgroundColor: '#f59e0b'
            }]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } },
                x: { grid: { display: false } }
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
                borderWidth: 3,
                borderColor: '#ffffff'
            }]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, padding: 15 } }
            },
            cutout: '68%'
        }
    });
});
</script>

<?php include_once __DIR__ . '/../../admin/includes/footer.php'; ?>
