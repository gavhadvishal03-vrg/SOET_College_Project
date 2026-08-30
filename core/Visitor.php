<?php
/**
 * Visitor Tracking
 */
class Visitor
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function track(string $page = ''): void
    {
        $ip = Security::getClientIP();
        $today = date('Y-m-d');
        $existing = $this->db->fetchOne(
            "SELECT id, page_views FROM visitors WHERE ip_address = ? AND visit_date = ?",
            [$ip, $today]
        );
        if ($existing) {
            $this->db->update('visitors', [
                'page_views' => $existing['page_views'] + 1,
                'last_page' => $page,
                'updated_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$existing['id']]);
        } else {
            $this->db->insert('visitors', [
                'ip_address' => $ip,
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
                'page_views' => 1,
                'last_page' => $page,
                'visit_date' => $today,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public function getTotalVisitors(): int
    {
        return $this->db->count('visitors');
    }

    public function getTodayVisitors(): int
    {
        return $this->db->count('visitors', 'visit_date = ?', [date('Y-m-d')]);
    }

    public function getTotalPageViews(): int
    {
        $res = $this->db->fetchOne("SELECT SUM(page_views) as total FROM visitors");
        return (int)($res['total'] ?? 0);
    }

    public function getWeeklyStats(): array
    {
        return $this->db->fetchAll(
            "SELECT visit_date, COUNT(*) as unique_visitors, SUM(page_views) as total_views
             FROM visitors WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
             GROUP BY visit_date ORDER BY visit_date"
        );
    }
}
