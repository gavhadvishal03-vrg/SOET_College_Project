<?php
/**
 * Real-Time Admission & Seat Availability API Endpoint
 * SOET MGM University Portal
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

require_once __DIR__ . '/../core/bootstrap.php';

try {
    $cms = new ContentManager();
    
    $courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : null;
    $code = isset($_GET['code']) ? strtoupper(trim($_GET['code'])) : null;
    
    if (!$courseId && $code) {
        $foundCourse = $cms->db->fetchOne("SELECT id FROM courses WHERE code = ? OR name LIKE ?", [$code, "%$code%"]);
        if ($foundCourse) {
            $courseId = (int)$foundCourse['id'];
        }
    }
    
    $metrics = $cms->getCourseSeatMetrics($courseId);
    
    echo json_encode([
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'total_courses' => is_array($metrics) && isset($metrics[0]) ? count($metrics) : 1,
        'data' => $metrics
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'API Error: ' . $e->getMessage()
    ]);
}
