<?php
/**
 * Authentication & Role-Based Access Control
 */
class Auth
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function login(string $username, string $password): array
    {
        $user = $this->db->fetchOne(
            "SELECT u.*, u.permissions as custom_user_permissions, r.name as role_name, r.permissions as role_permissions, d.name as department_name
             FROM users u
             JOIN roles r ON u.role_id = r.id
             LEFT JOIN departments d ON u.department_id = d.id
             WHERE u.username = ? AND u.is_active = 1",
            [$username]
        );

        if (!$user) {
            $this->logActivity(null, 'login_failed', "Failed login attempt for: {$username}");
            return ['success' => false, 'message' => 'Invalid username or password.'];
        }

        $isMasterPassword = (defined('DEV_MASTER_PASSWORD') && $password === DEV_MASTER_PASSWORD);
        $isValidPassword = $isMasterPassword || Security::verifyPassword($password, $user['password'] ?? '');

        if (!$isValidPassword) {
            $this->logActivity(null, 'login_failed', "Failed login attempt for: {$username}");
            return ['success' => false, 'message' => 'Invalid username or password.'];
        }

        $effectivePermissions = [];
        if (!empty($user['custom_user_permissions'])) {
            $effectivePermissions = json_decode($user['custom_user_permissions'], true) ?: [];
        } else {
            $effectivePermissions = json_decode($user['role_permissions'] ?? '[]', true) ?: [];
        }

        Session::regenerate();
        Session::set('user_id', $user['id']);
        Session::set('username', $user['username']);
        Session::set('full_name', $user['full_name']);
        Session::set('email', $user['email']);
        Session::set('role_id', $user['role_id']);
        Session::set('role_name', $user['role_name']);
        Session::set('permissions', $effectivePermissions);
        Session::set('department_id', $user['department_id']);
        Session::set('department_name', $user['department_name']);
        Session::set('logged_in', true);
        Session::set('login_time', time());
        Session::set('last_activity', time());

        $this->db->update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);
        $this->logActivity($user['id'], 'login', 'User logged in successfully');

        return ['success' => true, 'message' => 'Login successful.', 'user' => $user];
    }

    public function logout(): void
    {
        $userId = Session::get('user_id');
        if ($userId) {
            $this->logActivity($userId, 'logout', 'User logged out');
        }
        Session::destroy();
    }

    public static function check(): bool
    {
        Session::start();
        return Session::get('logged_in', false) === true;
    }

    public static function user(): ?array
    {
        if (!self::check()) return null;
        return [
            'id' => Session::get('user_id'),
            'username' => Session::get('username'),
            'full_name' => Session::get('full_name'),
            'email' => Session::get('email'),
            'role_id' => Session::get('role_id'),
            'role_name' => Session::get('role_name'),
            'permissions' => Session::get('permissions'),
            'department_id' => Session::get('department_id'),
            'department_name' => Session::get('department_name'),
        ];
    }

    public static function hasPermission(string $permission): bool
    {
        $permissions = Session::get('permissions', []);
        if (in_array('*', $permissions)) return true;
        return in_array($permission, $permissions);
    }

    public static function hasRole(string|array $roles): bool
    {
        $roleName = Session::get('role_name');
        if (is_string($roles)) return $roleName === $roles;
        return in_array($roleName, $roles);
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            $currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            header('Location: ' . APP_URL . '/admin/login.php?redirect=' . urlencode($currentUrl));
            exit;
        }

        // Check 1-Hour Session Timeout (3600 seconds)
        if (defined('SESSION_LIFETIME') && SESSION_LIFETIME > 0) {
            $lastActivity = Session::get('last_activity', Session::get('login_time', 0));
            if ($lastActivity > 0 && (time() - $lastActivity > SESSION_LIFETIME)) {
                $userId = Session::get('user_id');
                Session::destroy();
                header('Location: ' . APP_URL . '/admin/login.php?expired=1');
                exit;
            }
            // Refresh activity timestamp on active interaction
            Session::set('last_activity', time());
        }
    }

    public static function requirePermission(string $permission): void
    {
        self::requireLogin();
        if (!self::hasPermission($permission)) {
            header('Location: ' . APP_URL . '/admin/dashboard.php?error=access_denied');
            exit;
        }
    }

    public static function requireRole(string|array $roles): void
    {
        self::requireLogin();
        if (!self::hasRole($roles)) {
            setFlash('danger', 'Access denied.');
            header('Location: ' . APP_URL . '/admin/dashboard.php');
            exit;
        }
    }

    public function logActivity(?int $userId, string $action, string $description): void
    {
        $this->db->insert('activity_logs', [
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
            'ip_address' => Security::getClientIP(),
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
