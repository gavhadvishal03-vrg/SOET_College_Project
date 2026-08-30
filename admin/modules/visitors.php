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

<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-4 border-bottom flex-wrap gap-2">
    <div>
        <h1 class="h2 fw-bold text-primary-color mb-0"><i class="fa-solid fa-chart-line text-warning me-2"></i>Visitor Tracking &amp; Analytics</h1>
        <small class="text-muted">Real-time public portal telemetry, IP logging, and traffic breakdown</small>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <a href="visitors.php?action=export_csv" class="btn btn-sm btn-success">
            <i class="fa-solid fa-file-excel me-1"></i> Export Logs CSV
        </a>
        <span class="badge bg-success p-2"><i class="fa-solid fa-signal me-1"></i> Tracking Live</span>
    </div>
</div>

<!-- Metrics summary row -->
<div class="row g-3 mb-4 text-center">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm p-3 bg-white">
            <h3 class="fw-bold text-primary-color mb-0"><?php echo number_format($total_visits); ?></h3>
            <small class="text-muted text-uppercase fw-semibold">Unique Visitors</small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm p-3 bg-white">
            <h3 class="fw-bold text-primary-color mb-0"><?php echo number_format($today_visits); ?></h3>
            <small class="text-muted text-uppercase fw-semibold">Today Unique</small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm p-3 bg-white">
            <h3 class="fw-bold text-primary-color mb-0"><?php echo number_format($total_page_views); ?></h3>
            <small class="text-muted text-uppercase fw-semibold">Total Page Views</small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm p-3 bg-white">
            <h3 class="fw-bold text-primary-color mb-0"><?php echo number_format($today_page_views); ?></h3>
            <small class="text-muted text-uppercase fw-semibold">Today Views</small>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Weekly traffic history -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm p-4 h-100">
            <h5 class="fw-bold text-primary-color mb-3 border-bottom pb-2"><i class="fa-solid fa-calendar-week text-warning me-2"></i>Weekly Traffic Log</h5>
            
            <?php if (empty($weekly_stats)): ?>
                <p class="text-muted small">No traffic history logged for the last 7 days.</p>
            <?php else: ?>
                <div class="list-group list-group-flush small">
                    <?php foreach ($weekly_stats as $day): ?>
                        <div class="list-group-item bg-transparent px-0 d-flex justify-content-between align-items-center">
                            <span><i class="fa-regular fa-calendar me-1"></i><?php echo formatDate($day['visit_date']); ?></span>
                            <div>
                                <span class="badge bg-primary me-1" title="Unique visitors"><?php echo $day['unique_visitors']; ?> Users</span>
                                <span class="badge bg-secondary" title="Total page views"><?php echo $day['total_views']; ?> Views</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Active Visitor logs -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm p-4">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 border-bottom pb-2">
                <h5 class="fw-bold text-primary-color mb-0"><i class="fa-solid fa-list-check text-warning me-2"></i>Captured Visitor Logs (Last 100)</h5>
                <input type="text" id="visitorSearchInput" class="form-control form-control-sm" placeholder="Search IP, page..." style="width: 220px;">
            </div>
            
            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-hover align-middle mb-0 small" id="visitorsTable">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th>IP Address</th>
                            <th>Page Views</th>
                            <th>Last Active Page</th>
                            <th>User Agent Details</th>
                            <th>Last Visit Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($visitor_logs)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted p-4">No visitor logs captured.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($visitor_logs as $log): ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($log['ip_address']); ?></code></td>
                                    <td><span class="badge bg-info text-dark"><?php echo $log['page_views']; ?> views</span></td>
                                    <td><span class="text-secondary font-semibold"><?php echo htmlspecialchars($log['last_page']); ?></span></td>
                                    <td class="text-muted" style="max-width: 180px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;" title="<?php echo htmlspecialchars($log['user_agent']); ?>">
                                        <?php echo htmlspecialchars($log['user_agent']); ?>
                                    </td>
                                    <td><?php echo formatDateTime($log['updated_at']); ?></td>
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
