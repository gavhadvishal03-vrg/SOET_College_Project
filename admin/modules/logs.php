<?php
/**
 * System Activity & Security Audit Logs Viewer Module
 *
 * Provides a read-only searchable audit trail for administrators:
 * - Records logins, logouts, administrative CRUD actions, and IP addresses
 * - Filter by action category (e.g. login, create_course, update_status)
 * - Keyword search across description, IP address, username, and full name
 * - Paginated tabular log stream
 *
 * Required Permission: 'manage_users'
 */
$page_title = "Activity & Security Audit Logs";
include_once __DIR__ . '/../includes/header.php';

Auth::requirePermission('manage_users');

$cms = new ContentManager();
$db = Database::getInstance();

$actionFilter = isset($_GET['action_filter']) ? trim($_GET['action_filter']) : '';
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

// Distinct actions for filter dropdown
$distinctActions = $db->fetchAll("SELECT DISTINCT action FROM activity_logs ORDER BY action ASC");

// Total logs count with filters
$countSql = "SELECT COUNT(*) as cnt FROM activity_logs l 
             LEFT JOIN users u ON l.user_id = u.id 
             WHERE 1=1";
$params = [];
if (!empty($actionFilter)) {
    $countSql .= " AND l.action = ?";
    $params[] = $actionFilter;
}
if (!empty($searchQuery)) {
    $countSql .= " AND (l.description LIKE ? OR l.ip_address LIKE ? OR u.username LIKE ? OR u.full_name LIKE ?)";
    $term = "%{$searchQuery}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}
$totalLogs = (int)($db->fetchOne($countSql, $params)['cnt'] ?? 0);
$totalPages = max(1, ceil($totalLogs / $perPage));

$logs = $cms->getActivityLogs($perPage, $offset, $actionFilter ?: null, $searchQuery ?: null);
?>

<div class="d-flex justify-content-between align-items-center pt-2 pb-3 mb-4 border-bottom flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1" style="font-size: 14px;">Security &amp; Compliance</span>
        </div>
        <h2 class="font-display fw-extrabold text-dark mb-1">Security &amp; Audit Trail</h2>
        <p class="text-muted small mb-0">Monitor authentication attempts, administrative operations, and user activity in real-time.</p>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <a href="logs.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-xs"><i class="fa-solid fa-rotate me-1 text-primary"></i> Refresh Logs</a>
    </div>
</div>

<!-- Filters Bar -->
<div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
    <div class="card-body p-3.5">
        <form method="GET" action="logs.php" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0 rounded-start-pill text-muted ps-3"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" name="search" class="form-control bg-light border-start-0 rounded-end-pill pe-3" placeholder="Search by description, IP, or user..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                </div>
            </div>
            <div class="col-md-4">
                <select name="action_filter" class="form-select form-select-sm rounded-pill px-3 bg-light">
                    <option value="">-- All Activity Actions --</option>
                    <?php foreach ($distinctActions as $act): ?>
                        <option value="<?php echo htmlspecialchars($act['action']); ?>" <?php echo $actionFilter === $act['action'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $act['action']))); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary rounded-pill px-3.5 flex-grow-1 font-semibold shadow-xs"><i class="fa-solid fa-filter me-1"></i> Filter</button>
                <?php if (!empty($searchQuery) || !empty($actionFilter)): ?>
                    <a href="logs.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3">Clear</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Logs Table Card -->
<div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
    <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom">
        <div class="d-flex align-items-center gap-2">
            <div class="stat-icon-sm bg-primary-subtle text-primary rounded-3 d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <h5 class="mb-0 fw-bold text-dark">Audit Records</h5>
        </div>
        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-3 py-1.5 fw-semibold"><?php echo number_format($totalLogs); ?> Total Records</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($logs)): ?>
            <div class="p-5 text-center text-muted">
                <i class="fa-solid fa-clipboard-check display-6 mb-3 text-secondary opacity-40 d-block"></i>
                <h6>No activity logs match your filter criteria.</h6>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4" style="width: 170px;">Timestamp</th>
                            <th style="width: 150px;">Action Type</th>
                            <th>Description</th>
                            <th style="width: 180px;">User Account</th>
                            <th class="text-end pe-4" style="width: 140px;">IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): 
                            $badgeClass = 'bg-secondary-subtle text-secondary border-secondary-subtle';
                            if (strpos($log['action'], 'login') !== false) {
                                $badgeClass = ($log['action'] === 'login_failed') ? 'bg-danger-subtle text-danger border-danger-subtle' : 'bg-success-subtle text-success border-success-subtle';
                            } elseif (strpos($log['action'], 'create') !== false || strpos($log['action'], 'insert') !== false) {
                                $badgeClass = 'bg-primary-subtle text-primary border-primary-subtle';
                            } elseif (strpos($log['action'], 'update') !== false || strpos($log['action'], 'edit') !== false) {
                                $badgeClass = 'bg-info-subtle text-info-emphasis border-info-subtle';
                            } elseif (strpos($log['action'], 'delete') !== false) {
                                $badgeClass = 'bg-danger-subtle text-danger border-danger-subtle';
                            }
                        ?>
                            <tr>
                                <td class="ps-4 text-nowrap text-muted"><?php echo formatDateTime($log['created_at']); ?></td>
                                <td>
                                    <span class="badge <?php echo $badgeClass; ?> border rounded-pill px-2.5 py-1 font-semibold">
                                        <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $log['action']))); ?>
                                    </span>
                                </td>
                                <td><span class="text-dark fw-medium"><?php echo htmlspecialchars($log['description']); ?></span></td>
                                <td>
                                    <?php if ($log['username']): ?>
                                        <div class="fw-semibold text-dark"><?php echo htmlspecialchars($log['full_name'] ?: $log['username']); ?></div>
                                        <small class="text-muted d-block"><?php echo htmlspecialchars($log['role_name'] ?? 'User'); ?></small>
                                    <?php else: ?>
                                        <span class="text-muted fst-italic">System / Guest</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4"><span class="badge bg-light text-dark border font-monospace"><?php echo htmlspecialchars($log['ip_address']); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if ($totalPages > 1): ?>
        <div class="card-footer bg-white py-3 px-4 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span class="text-muted small">Showing page <strong><?php echo $page; ?></strong> of <strong><?php echo $totalPages; ?></strong></span>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link rounded-start-pill" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($searchQuery); ?>&action_filter=<?php echo urlencode($actionFilter); ?>">Previous</a>
                </li>
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($searchQuery); ?>&action_filter=<?php echo urlencode($actionFilter); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                    <a class="page-link rounded-end-pill" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($searchQuery); ?>&action_filter=<?php echo urlencode($actionFilter); ?>">Next</a>
                </li>
            </ul>
        </div>
    <?php endif; ?>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
