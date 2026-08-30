<?php
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

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4 border-bottom">
    <div>
        <h1 class="h2 fw-bold text-primary-color"><i class="fa-solid fa-shield-halved text-warning me-2"></i>Activity & Security Audit Trail</h1>
        <p class="text-muted small mb-0">Monitor authentication attempts, administrative operations, and user activity in real-time.</p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0 d-flex gap-2">
        <a href="logs.php" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-rotate me-1"></i> Refresh</a>
    </div>
</div>

<!-- Filters Bar -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" action="logs.php" class="row g-2 align-items-center">
            <div class="col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search by description, IP, or user..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="action_filter" class="form-select form-select-sm">
                    <option value="">-- All Actions --</option>
                    <?php foreach ($distinctActions as $act): ?>
                        <option value="<?php echo htmlspecialchars($act['action']); ?>" <?php echo $actionFilter === $act['action'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $act['action']))); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-primary w-100"><i class="fa-solid fa-filter me-1"></i> Filter</button>
            </div>
            <?php if (!empty($searchQuery) || !empty($actionFilter)): ?>
                <div class="col-md-2">
                    <a href="logs.php" class="btn btn-sm btn-outline-secondary w-100">Clear</a>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Logs Table Card -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
        <h5 class="mb-0 fw-bold text-primary-color"><i class="fa-solid fa-list-check text-warning me-2"></i>Log Records</h5>
        <span class="badge bg-secondary"><?php echo number_format($totalLogs); ?> Total Records</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($logs)): ?>
            <div class="p-5 text-center text-muted">
                <i class="fa-solid fa-clipboard-check fa-3x mb-3 text-secondary opacity-50"></i>
                <h6>No activity logs match your criteria.</h6>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 160px;">Timestamp</th>
                            <th style="width: 140px;">Action</th>
                            <th>Description</th>
                            <th style="width: 180px;">User</th>
                            <th style="width: 130px;">IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): 
                            $badgeClass = 'bg-secondary';
                            if (strpos($log['action'], 'login') !== false) {
                                $badgeClass = ($log['action'] === 'login_failed') ? 'bg-danger' : 'bg-success';
                            } elseif (strpos($log['action'], 'create') !== false || strpos($log['action'], 'insert') !== false) {
                                $badgeClass = 'bg-primary';
                            } elseif (strpos($log['action'], 'update') !== false || strpos($log['action'], 'edit') !== false) {
                                $badgeClass = 'bg-info text-dark';
                            } elseif (strpos($log['action'], 'delete') !== false) {
                                $badgeClass = 'bg-danger';
                            }
                        ?>
                            <tr>
                                <td class="text-nowrap text-muted"><?php echo formatDateTime($log['created_at']); ?></td>
                                <td>
                                    <span class="badge <?php echo $badgeClass; ?>">
                                        <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $log['action']))); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($log['description']); ?></td>
                                <td>
                                    <?php if ($log['username']): ?>
                                        <span class="fw-semibold text-dark"><?php echo htmlspecialchars($log['full_name'] ?: $log['username']); ?></span>
                                        <small class="text-muted d-block"><?php echo htmlspecialchars($log['role_name'] ?? 'User'); ?></small>
                                    <?php else: ?>
                                        <span class="text-muted font-italic">System / Guest</span>
                                    <?php endif; ?>
                                </td>
                                <td><code><?php echo htmlspecialchars($log['ip_address']); ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if ($totalPages > 1): ?>
        <div class="card-footer bg-white py-3 border-top d-flex justify-content-between align-items-center">
            <span class="text-muted small">Showing page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($searchQuery); ?>&action_filter=<?php echo urlencode($actionFilter); ?>">Previous</a>
                </li>
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($searchQuery); ?>&action_filter=<?php echo urlencode($actionFilter); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($searchQuery); ?>&action_filter=<?php echo urlencode($actionFilter); ?>">Next</a>
                </li>
            </ul>
        </div>
    <?php endif; ?>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
