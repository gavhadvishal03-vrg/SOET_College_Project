<?php
require_once __DIR__ . '/../../core/bootstrap.php';
Auth::requireLogin();

// Only Super Admin can allocate admins, assign roles & module permissions
if (!Auth::hasRole('Super Admin')) {
    setFlash('danger', 'Access denied. Only Super Admin can allocate user roles and module permissions.');
    redirect(APP_URL . '/admin/dashboard.php');
}

$db = Database::getInstance();
$roles = $db->fetchAll("SELECT * FROM roles ORDER BY id");
$depts = $db->fetchAll("SELECT * FROM departments ORDER BY name");

// Master Admin Module Map for RBAC Allocation
$masterModuleMap = [
    'manage_news' => ['name' => 'News Portal & Review', 'icon' => 'fa-solid fa-newspaper', 'desc' => 'Review, publish, return or delete news articles'],
    'manage_blogs' => ['name' => 'Blog Posts & Review', 'icon' => 'fa-solid fa-pen-nib', 'desc' => 'Review, publish, return or delete blog posts'],
    'view_contacts' => ['name' => 'Contact Us Inquiries', 'icon' => 'fa-solid fa-envelope', 'desc' => 'View visitor messages & log response emails'],
    'manage_admissions' => ['name' => 'Admissions Manager', 'icon' => 'fa-solid fa-graduation-cap', 'desc' => 'Verify, approve, or reject student applications'],
    'manage_courses' => ['name' => 'Courses & Programs', 'icon' => 'fa-solid fa-book-open', 'desc' => 'Manage degree programs, syllabi & fees'],
    'manage_departments' => ['name' => 'Departments Manager', 'icon' => 'fa-solid fa-building-columns', 'desc' => 'Manage departments & faculty assignments'],
    'manage_faculty' => ['name' => 'Faculty Directory', 'icon' => 'fa-solid fa-chalkboard-user', 'desc' => 'Manage faculty profiles & photos'],
    'manage_events' => ['name' => 'Events Calendar', 'icon' => 'fa-solid fa-calendar-days', 'desc' => 'Schedule, edit, or delete campus events'],
    'manage_notices' => ['name' => 'Notices & Circulars', 'icon' => 'fa-solid fa-bullhorn', 'desc' => 'Post announcements & official circulars'],
    'manage_placements' => ['name' => 'Placements Cell', 'icon' => 'fa-solid fa-briefcase', 'desc' => 'Log student placement records & LPA salaries'],
    'manage_fees' => ['name' => 'Fee Structure Manager', 'icon' => 'fa-solid fa-indian-rupee-sign', 'desc' => 'Manage semester fee schedules'],
    'manage_gallery' => ['name' => 'Gallery & Media', 'icon' => 'fa-solid fa-images', 'desc' => 'Upload campus photos & media albums'],
    'manage_chatbot_kb' => ['name' => 'CampusAI Chatbot Suite', 'icon' => 'fa-solid fa-robot', 'desc' => 'Manage KB entries, FAQs, Doc Extractor & Transcripts'],
    'manage_users' => ['name' => 'User Allocations & RBAC', 'icon' => 'fa-solid fa-users-gear', 'desc' => 'Allocate user accounts & module permissions'],
    'manage_settings' => ['name' => 'Backup & Site Settings', 'icon' => 'fa-solid fa-sliders', 'desc' => 'Site branding, SMTP & DB backups']
];

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$edit_user = null;

// Handle CSV Export
if ($action === 'export_csv') {
    $rows = $db->fetchAll(
        "SELECT u.full_name, u.username, u.email, r.name as role_name, d.name as department_name, u.is_active, u.last_login, u.created_at 
         FROM users u 
         JOIN roles r ON u.role_id = r.id 
         LEFT JOIN departments d ON u.department_id = d.id 
         ORDER BY r.id, u.full_name"
    );
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=SOET_System_Users_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Full Name', 'Username', 'Email', 'Role', 'Assigned Department', 'Status', 'Last Login', 'Created At']);
    foreach ($rows as $r) {
        fputcsv($output, [
            $r['full_name'],
            $r['username'],
            $r['email'],
            $r['role_name'],
            $r['department_name'] ?: 'Central Administration',
            $r['is_active'] ? 'Active' : 'Inactive',
            $r['last_login'] ?: 'Never',
            $r['created_at']
        ]);
    }
    fclose($output);
    exit;
}

$page_title = "User Allocations & RBAC Manager";
include_once __DIR__ . '/../includes/header.php';

if ($action === 'edit' && isset($_GET['id'])) {
    $edit_user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [(int)$_GET['id']]);
}

// Handle Add/Edit Form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_user'])) {
    $csrf = $_POST[CSRF_TOKEN_NAME] ?? '';
    $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : null;
    $username = trim($_POST['username']);
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $role_id = (int)$_POST['role_id'];
    $dept_id = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
    $password = $_POST['password'] ?? '';
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Module permissions allocation array
    $selected_modules = $_POST['module_permissions'] ?? [];
    if (!is_array($selected_modules)) {
        $selected_modules = [];
    }

    if (!Security::validateCSRF($csrf)) {
        setFlash('danger', 'CSRF validation failed.');
    } elseif (empty($username) || empty($full_name) || empty($email) || empty($role_id)) {
        setFlash('danger', 'Please fill in all mandatory fields.');
    } else {
        $permissions_json = json_encode(array_values($selected_modules));

        if ($user_id) {
            // Edit User
            $update_data = [
                'username' => $username,
                'full_name' => $full_name,
                'email' => $email,
                'role_id' => $role_id,
                'department_id' => $dept_id,
                'permissions' => $permissions_json,
                'is_active' => $is_active
            ];
            if (!empty($password)) {
                $update_data['password'] = Security::hashPassword($password);
            }
            $db->update('users', $update_data, 'id = ?', [$user_id]);
            setFlash('success', "User allocation for {$full_name} updated successfully.");
            redirect('users.php');
        } else {
            // Add User
            if (empty($password)) {
                setFlash('danger', 'Password is required for new user allocation.');
            } else {
                $exists = $db->fetchOne("SELECT id FROM users WHERE username = ? OR email = ?", [$username, $email]);
                if ($exists) {
                    setFlash('danger', 'Username or Email already registered.');
                } else {
                    $db->insert('users', [
                        'username' => $username,
                        'password' => Security::hashPassword($password),
                        'full_name' => $full_name,
                        'email' => $email,
                        'role_id' => $role_id,
                        'department_id' => $dept_id,
                        'permissions' => $permissions_json,
                        'is_active' => $is_active,
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                    setFlash('success', "User {$full_name} added and module allocations configured successfully.");
                    redirect('users.php');
                }
            }
        }
    }
}

// Handle Delete User
if ($action === 'delete' && isset($_GET['id'])) {
    $target_id = (int)$_GET['id'];
    if ($target_id === (int)Session::get('user_id')) {
        setFlash('danger', 'You cannot delete yourself!');
    } else {
        $db->delete('users', 'id = ?', [$target_id]);
        setFlash('success', 'User deleted successfully.');
    }
    redirect('users.php');
}

// Fetch all users
$users = $db->fetchAll(
    "SELECT u.*, u.permissions as user_permissions, r.name as role_name, r.permissions as role_permissions, d.name as department_name 
     FROM users u 
     JOIN roles r ON u.role_id = r.id 
     LEFT JOIN departments d ON u.department_id = d.id 
     ORDER BY r.id, u.full_name"
);
?>

<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-4 border-bottom flex-wrap gap-2">
    <div>
        <h1 class="h2 fw-bold text-primary-color mb-0"><i class="fa-solid fa-users-gear text-warning me-2"></i>User Allocations &amp; RBAC Manager</h1>
        <small class="text-muted">Manage staff accounts, department assignments, and fine-grained module access controls</small>
    </div>
    <?php if ($action === 'list'): ?>
        <div class="d-flex gap-2 align-items-center">
            <a href="users.php?action=export_csv" class="btn btn-sm btn-success">
                <i class="fa-solid fa-file-excel me-1"></i> Export CSV
            </a>
            <a href="users.php?action=add" class="btn btn-primary btn-sm font-semibold"><i class="fa-solid fa-user-plus me-1"></i> Allocate New User</a>
        </div>
    <?php else: ?>
        <a href="users.php" class="btn btn-outline-secondary btn-sm font-semibold"><i class="fa-solid fa-angle-left me-1"></i> Back to User List</a>
    <?php endif; ?>
</div>

<?php if ($action === 'list'): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="fw-bold mb-0 text-primary-color"><i class="fa-solid fa-shield-halved me-2 text-warning"></i>System User Accounts (<?php echo count($users); ?>)</h5>
            <input type="text" id="userSearchInput" class="form-control form-control-sm" placeholder="Search user by name, email, role..." style="width: 250px;">
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0" id="usersTable">
                    <thead class="table-dark">
                        <tr>
                            <th>User Name</th>
                            <th>Username</th>
                            <th>Email Address</th>
                            <th>System Role</th>
                            <th>Assigned Dept</th>
                            <th>Allocated Modules (RBAC)</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <?php 
                            $effectivePerms = [];
                            if (!empty($u['user_permissions'])) {
                                $effectivePerms = json_decode($u['user_permissions'], true) ?: [];
                            } else {
                                $effectivePerms = json_decode($u['role_permissions'] ?? '[]', true) ?: [];
                            }
                            $isSuper = in_array('*', $effectivePerms);
                            $moduleCount = $isSuper ? count($masterModuleMap) : count($effectivePerms);
                            ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-primary-color"><?php echo htmlspecialchars($u['full_name']); ?></div>
                                    <small class="text-muted"><i class="fa-regular fa-clock me-1"></i>Last login: <?php echo formatDateTime($u['last_login']); ?></small>
                                </td>
                                <td><code><?php echo htmlspecialchars($u['username']); ?></code></td>
                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                                <td>
                                    <?php 
                                    $roleBadge = ($u['role_name'] === 'Super Admin') ? 'bg-danger' : (($u['role_name'] === 'Admin') ? 'bg-primary' : 'bg-success');
                                    ?>
                                    <span class="badge <?php echo $roleBadge; ?>"><?php echo htmlspecialchars($u['role_name']); ?></span>
                                </td>
                                <td><span class="text-secondary small font-semibold"><?php echo htmlspecialchars($u['department_name'] ?: 'Central Admin'); ?></span></td>
                                <td>
                                    <?php if ($isSuper): ?>
                                        <span class="badge bg-warning text-dark"><i class="fa-solid fa-crown me-1"></i> All 16 Modules (Master Access)</span>
                                    <?php else: ?>
                                        <span class="badge bg-info text-dark"><i class="fa-solid fa-cubes me-1"></i> <?php echo $moduleCount; ?> / <?php echo count($masterModuleMap); ?> Modules Allocated</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center"><?php echo statusBadge($u['is_active'] ? 'active' : 'inactive'); ?></td>
                                <td class="text-end text-nowrap">
                                    <a href="users.php?action=edit&id=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline-primary font-semibold me-1" title="Edit Allocation & RBAC"><i class="fa-solid fa-pen-to-square me-1"></i> Edit RBAC</a>
                                    <?php if ($u['id'] !== (int)Session::get('user_id')): ?>
                                        <a href="users.php?action=delete&id=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline-danger btn-delete-confirm font-semibold" title="Delete User" onclick="return confirm('Delete this user account?');"><i class="fa-solid fa-trash-can me-1"></i> Delete</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('userSearchInput');
        const table = document.getElementById('usersTable');
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
<?php else: ?>
    <!-- Add/Edit User Form with RBAC Module Allocations Checklist -->
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card border-0 shadow-sm p-4">
                <h4 class="fw-bold text-primary-color mb-4"><i class="fa-solid fa-user-gear text-warning me-2"></i><?php echo $edit_user ? 'Edit User Allocation & Module RBAC' : 'Allocate New User Account & RBAC Permissions'; ?></h4>
                <form method="POST" action="users.php">
                    <?php echo Security::csrfField(); ?>
                    <?php if ($edit_user): ?>
                        <input type="hidden" name="user_id" value="<?php echo $edit_user['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Full Name *</label>
                            <input type="text" name="full_name" class="form-control" required value="<?php echo $edit_user ? htmlspecialchars($edit_user['full_name']) : ''; ?>" placeholder="e.g. Dr. Rajesh Sharma">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Username *</label>
                            <input type="text" name="username" class="form-control" required value="<?php echo $edit_user ? htmlspecialchars($edit_user['username']) : ''; ?>" placeholder="e.g. rajesh_sharma">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Email Address *</label>
                            <input type="email" name="email" class="form-control" required value="<?php echo $edit_user ? htmlspecialchars($edit_user['email']) : ''; ?>" placeholder="e.g. rajesh.sharma@mgmu.ac.in">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Password <?php echo $edit_user ? '<small class="text-muted">(leave blank to keep unchanged)</small>' : '*'; ?></label>
                            <input type="password" name="password" class="form-control" <?php echo $edit_user ? '' : 'required'; ?> placeholder="••••••••">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font-semibold">System Role *</label>
                            <select name="role_id" class="form-select" required>
                                <option value="">Select Role...</option>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?php echo $r['id']; ?>" <?php echo ($edit_user && $edit_user['role_id'] == $r['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($r['name']); ?> (<?php echo htmlspecialchars($r['description']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Department Assignment (Optional)</label>
                            <select name="department_id" class="form-select">
                                <option value="">Central Administration / General</option>
                                <?php foreach ($depts as $d): ?>
                                    <option value="<?php echo $d['id']; ?>" <?php echo ($edit_user && $edit_user['department_id'] == $d['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($d['name']); ?> (<?php echo htmlspecialchars($d['code']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 mt-4">
                            <h5 class="fw-bold text-primary-color border-bottom pb-2 mb-3">
                                <i class="fa-solid fa-lock-open text-warning me-2"></i>Granular Module Access Allocations
                            </h5>
                            <p class="text-muted small">Select individual administrative modules this user is permitted to manage.</p>
                            
                            <?php 
                            $assignedModules = [];
                            if ($edit_user && !empty($edit_user['permissions'])) {
                                $assignedModules = json_decode($edit_user['permissions'], true) ?: [];
                            }
                            ?>
                            <div class="row g-3">
                                <?php foreach ($masterModuleMap as $modKey => $modInfo): ?>
                                    <?php $checked = in_array($modKey, $assignedModules) || in_array('*', $assignedModules); ?>
                                    <div class="col-md-4">
                                        <div class="border rounded p-3 bg-light h-100 shadow-xs">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="module_permissions[]" value="<?php echo $modKey; ?>" id="mod_<?php echo $modKey; ?>" <?php echo $checked ? 'checked' : ''; ?>>
                                                <label class="form-check-label fw-bold text-primary-color" for="mod_<?php echo $modKey; ?>">
                                                    <i class="<?php echo $modInfo['icon']; ?> text-warning me-1"></i> <?php echo $modInfo['name']; ?>
                                                </label>
                                            </div>
                                            <small class="text-muted d-block mt-1 ps-4" style="font-size: 11px;"><?php echo $modInfo['desc']; ?></small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="col-12 mt-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="isActive" <?php echo (!$edit_user || $edit_user['is_active']) ? 'checked' : ''; ?>>
                                <label class="form-check-label font-semibold" for="isActive">Account Active &amp; Login Allowed</label>
                            </div>
                        </div>

                        <div class="col-12 text-end mt-4">
                            <a href="users.php" class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" name="save_user" class="btn btn-primary px-4"><i class="fa-solid fa-floppy-disk me-1"></i> Save User Allocation</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
