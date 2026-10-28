<?php
/**
 * Authentication & Role-Based Access Control (RBAC)
 *
 * Manages user logins, session establishment, credential verification,
 * permission resolution, route protection guards, 1-hour session timeout enforcement,
 * and persistent activity audit logging.
 */
class Auth
{
    /** @var Database Database instance */
    private Database $db;

    /**
     * Initializes the Auth service with the database connection.
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Authenticates a user with username and password.
     * Supports standard Bcrypt password verification and developer master password fallback.
     * Resolves user permissions from custom user overrides or assigned role definitions.
     *
     * @param string $username User login username
     * @param string $password User login password
     * @return array Array with success status, message, and authenticated user details
     */
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

    /**
     * Terminates the active user session and logs the logout event.
     *
     * @return void
     */
    public function logout(): void
    {
        $userId = Session::get('user_id');
        if ($userId) {
            $this->logActivity($userId, 'logout', 'User logged out');
        }
        Session::destroy();
    }

    /**
     * Checks if a user is currently authenticated in the active session.
     *
     * @return bool True if logged in, false otherwise
     */
    public static function check(): bool
    {
        Session::start();
        return Session::get('logged_in', false) === true;
    }

    /**
     * Retrieves the currently logged in user's profile details from the session.
     *
     * @return array|null User details array or null if unauthenticated
     */
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

    /**
     * Checks whether the current user has a specific permission or wildcard ('*') access.
     *
     * @param string $permission Permission slug to check (e.g. 'manage_courses')
     * @return bool True if permitted, false otherwise
     */
    public static function hasPermission(string $permission): bool
    {
        $permissions = Session::get('permissions', []);
        if (in_array('*', $permissions)) return true;
        return in_array($permission, $permissions);
    }

    /**
     * Checks whether the current user belongs to a specific role or set of roles.
     *
     * @param string|array $roles Single role name or array of permitted role names
     * @return bool True if user possesses one of the specified roles
     */
    public static function hasRole(string|array $roles): bool
    {
        $roleName = Session::get('role_name');
        if (is_string($roles)) return $roleName === $roles;
        return in_array($roleName, $roles);
    }

    /**
     * Route guard: Requires the user to be logged in and enforces the 1-hour session timeout.
     * Redirects to the login page if unauthenticated or session has expired.
     *
     * @return void
     */
    public static function requireLogin(): void
    {
        if (!self::check()) {
            $isHttps = (
                (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') ||
                (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') ||
                (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on') ||
                (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
            );
            $scheme = $isHttps ? 'https' : 'http';
            $host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost';
            if (str_contains($host, ',')) {
                $host = trim(explode(',', $host)[0]);
            }
            $uri = $_SERVER['REQUEST_URI'] ?? '/admin/dashboard.php';
            $currentUrl = "{$scheme}://{$host}{$uri}";
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

    /**
     * Route guard: Requires login and verifies that the user holds a specific permission.
     *
     * @param string $permission Required permission slug
     * @return void
     */
    public static function requirePermission(string $permission): void
    {
        self::requireLogin();
        if (!self::hasPermission($permission)) {
            header('Location: ' . APP_URL . '/admin/dashboard.php?error=access_denied');
            exit;
        }
    }

    /**
     * Route guard: Requires login and verifies that the user belongs to an authorized role.
     *
     * @param string|array $roles Single role name or list of allowed roles
     * @return void
     */
    public static function requireRole(string|array $roles): void
    {
        self::requireLogin();
        if (!self::hasRole($roles)) {
            setFlash('danger', 'Access denied.');
            header('Location: ' . APP_URL . '/admin/dashboard.php');
            exit;
        }
    }

    /**
     * Inserts an audit trail entry into the activity_logs database table.
     *
     * @param int|null $userId ID of the acting user, or null if unauthenticated
     * @param string $action Action key (e.g. 'login', 'create_course')
     * @param string $description Detailed human-readable description of the event
     * @return void
     */
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
