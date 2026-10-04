<?php
/**
 * Visitor Tracking & Traffic Analytics
 *
 * Records unique daily visitor sessions, page view counts, referrer information,
 * and compiles aggregate traffic metrics for the administrative dashboard.
 */
class Visitor
{
    /** @var Database Database instance */
    private Database $db;

    /**
     * Initializes the Visitor tracking service.
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Records a page view for the current visitor's IP address on today's date.
     * Upserts records to track total hits per day per unique IP.
     *
     * @param string $page The relative script path or page name being visited
     * @return void
     */
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

    /**
     * Counts the total number of recorded visitor sessions across all time.
     *
     * @return int Total visitor records
     */
    public function getTotalVisitors(): int
    {
        return $this->db->count('visitors');
    }

    /**
     * Counts the number of unique visitors recorded today.
     *
     * @return int Today's visitor count
     */
    public function getTodayVisitors(): int
    {
        return $this->db->count('visitors', 'visit_date = ?', [date('Y-m-d')]);
    }

    /**
     * Calculates the grand total of page views across all visitor records.
     *
     * @return int Total cumulative page views
     */
    public function getTotalPageViews(): int
    {
        $res = $this->db->fetchOne("SELECT SUM(page_views) as total FROM visitors");
        return (int)($res['total'] ?? 0);
    }

    /**
     * Retrieves daily visitor and view counts for the preceding 7 days.
     *
     * @return array Array of daily stats objects with visit_date, unique_visitors, and total_views
     */
    public function getWeeklyStats(): array
    {
        return $this->db->fetchAll(
            "SELECT visit_date, COUNT(*) as unique_visitors, SUM(page_views) as total_views
             FROM visitors WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
             GROUP BY visit_date ORDER BY visit_date"
        );
    }
}
