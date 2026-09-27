<?php
require_once __DIR__ . '/../../core/bootstrap.php';
Auth::requirePermission('view_visitors');

$db = Database::getInstance();
$visitor_tracker = new Visitor();

// Handle CSV Export
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    $rows = $db->fetchAll("SELECT ip_address, page_views, last_page, user_agent, visit_date, updated_at FROM visitors ORDER BY updated_at DESC LIMIT 500");
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=SOET_Visitor_Traffic_Logs_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['IP Address', 'Page Views', 'Last Visited Page', 'User Agent Details', 'Visit Date', 'Last Active Time']);
    foreach ($rows as $r) {
        fputcsv($output, [
            $r['ip_address'],
            $r['page_views'],
            $r['last_page'],
            $r['user_agent'],
            $r['visit_date'],
            $r['updated_at']
        ]);
    }
    fclose($output);
    exit;
}

$page_title = "Visitor Analytics";
include_once __DIR__ . '/../includes/header.php';

// Get summary metrics
$total_visits = $visitor_tracker->getTotalVisitors();
$today_visits = $visitor_tracker->getTodayVisitors();

$total_page_views = (int)$db->fetchOne("SELECT SUM(page_views) as total_views FROM visitors")['total_views'];
$today_page_views = (int)$db->fetchOne("SELECT SUM(page_views) as today_views FROM visitors WHERE visit_date = ?", [date('Y-m-d')])['today_views'];

// Get weekly history stats
$weekly_stats = $visitor_tracker->getWeeklyStats();

// Fetch all captured visitor logs
$visitor_logs = $db->fetchAll("SELECT * FROM visitors ORDER BY updated_at DESC LIMIT 100");
?>

<div class="d-flex justify-content-between align-items-center pt-2 pb-3 mb-4 border-bottom flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1" style="font-size: 11px;">Telemetry &amp; Audit</span>
        </div>
        <h2 class="font-display fw-extrabold text-dark mb-1">Visitor Tracking &amp; Analytics</h2>
        <p class="text-muted small mb-0">Real-time public portal telemetry, IP logging, and traffic breakdown</p>
    </div>
    <div class="d-flex gap-2 align-items-center flex-wrap">
        <a href="visitors.php?action=export_csv" class="btn btn-outline-success btn-sm rounded-pill px-3 shadow-xs">
            <i class="fa-solid fa-file-excel me-1 text-success"></i> Export Logs CSV
        </a>
        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 fw-semibold">
            <i class="fa-solid fa-signal me-1.5 text-success"></i> Live Telemetry Active
        </span>
    </div>
</div>

<!-- Metrics summary row -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3.5 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small text-uppercase fw-semibold" style="font-size: 11px; letter-spacing: 0.5px;">Unique Visitors</span>
                <div class="stat-icon-sm bg-primary-subtle text-primary rounded-3 d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>
            <h3 class="font-display fw-extrabold text-dark mb-0"><?php echo number_format($total_visits); ?></h3>
            <div class="text-muted small mt-1" style="font-size: 11px;">All-time tracked visitors</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3.5 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small text-uppercase fw-semibold" style="font-size: 11px; letter-spacing: 0.5px;">Today Unique</span>
                <div class="stat-icon-sm bg-success-subtle text-success rounded-3 d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                    <i class="fa-solid fa-user-clock"></i>
                </div>
            </div>
            <h3 class="font-display fw-extrabold text-dark mb-0"><?php echo number_format($today_visits); ?></h3>
            <div class="text-success small mt-1 fw-medium" style="font-size: 11px;"><i class="fa-solid fa-arrow-trend-up me-1"></i>Active today</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3.5 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small text-uppercase fw-semibold" style="font-size: 11px; letter-spacing: 0.5px;">Total Page Views</span>
                <div class="stat-icon-sm bg-info-subtle text-info-emphasis rounded-3 d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                    <i class="fa-solid fa-eye"></i>
                </div>
            </div>
            <h3 class="font-display fw-extrabold text-dark mb-0"><?php echo number_format($total_page_views); ?></h3>
            <div class="text-muted small mt-1" style="font-size: 11px;">Aggregated impressions</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3.5 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small text-uppercase fw-semibold" style="font-size: 11px; letter-spacing: 0.5px;">Today Impressions</span>
                <div class="stat-icon-sm bg-warning-subtle text-warning-emphasis rounded-3 d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                    <i class="fa-solid fa-chart-simple"></i>
                </div>
            </div>
            <h3 class="font-display fw-extrabold text-dark mb-0"><?php echo number_format($today_page_views); ?></h3>
            <div class="text-muted small mt-1" style="font-size: 11px;">Views since midnight</div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Weekly traffic history -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-3">
                <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-calendar-week text-primary me-2"></i>Weekly History</h5>
                <span class="badge bg-light text-muted border font-semibold">7 Days</span>
            </div>
            
            <?php if (empty($weekly_stats)): ?>
                <p class="text-muted small py-4 text-center mb-0">No traffic history logged for the last 7 days.</p>
            <?php else: ?>
                <div class="list-group list-group-flush small">
                    <?php foreach ($weekly_stats as $day): ?>
                        <div class="list-group-item bg-transparent px-0 py-2.5 d-flex justify-content-between align-items-center border-light-subtle">
                            <span class="fw-medium text-dark"><i class="fa-regular fa-calendar text-muted me-1.5"></i><?php echo formatDate($day['visit_date']); ?></span>
                            <div class="d-flex gap-1.5">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-1" title="Unique visitors"><?php echo $day['unique_visitors']; ?> Users</span>
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-2 py-1" title="Total page views"><?php echo $day['total_views']; ?> Views</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Active Visitor logs -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden h-100">
            <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom">
                <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-list-check text-primary me-2"></i>Visitor Activity Log <span class="badge bg-light text-muted border rounded-pill ms-1" style="font-size: 11px;">Recent 100</span></h5>
                <div class="input-group input-group-sm" style="max-width: 240px;">
                    <span class="input-group-text bg-light border-end-0 rounded-start-pill text-muted ps-3"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" id="visitorSearchInput" class="form-control bg-light border-start-0 rounded-end-pill pe-3" placeholder="Search IP, page...">
                </div>
            </div>
            
            <div class="table-responsive" style="max-height: 420px; overflow-y: auto;">
                <table class="table table-hover align-middle mb-0 small" id="visitorsTable">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th class="ps-4">IP Address</th>
                            <th>Page Views</th>
                            <th>Last Active Page</th>
                            <th>User Agent Details</th>
                            <th class="text-end pe-4">Last Active</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($visitor_logs)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-5">
                                    <i class="fa-solid fa-chart-line display-6 text-secondary opacity-40 mb-3 d-block"></i>
                                    No visitor logs captured yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($visitor_logs as $log): ?>
                                <tr>
                                    <td class="ps-4"><span class="badge bg-light text-dark border font-monospace"><?php echo htmlspecialchars($log['ip_address']); ?></span></td>
                                    <td><span class="badge bg-info-subtle text-info-emphasis border border-info-subtle rounded-pill px-2.5 py-1 font-semibold"><?php echo $log['page_views']; ?> views</span></td>
                                    <td><span class="text-dark fw-medium"><?php echo htmlspecialchars($log['last_page']); ?></span></td>
                                    <td class="text-muted" style="max-width: 220px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;" title="<?php echo htmlspecialchars($log['user_agent']); ?>">
                                        <?php echo htmlspecialchars($log['user_agent']); ?>
                                    </td>
                                    <td class="text-end pe-4 text-muted small"><?php echo formatDateTime($log['updated_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('visitorSearchInput');
    const table = document.getElementById('visitorsTable');
    if (searchInput && table) {
        searchInput.addEventListener('input', function() {
            const q = this.value.toLowerCase().trim();
            table.querySelectorAll('tbody tr').forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = (!q || text.includes(q)) ? '' : 'none';
            });
        });
    }
});
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
