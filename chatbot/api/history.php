<?php
/**
 * Chatbot Public API - Chat History Endpoint
 *
 * Retrieves all prior back-and-forth messages for a specific session token
 * to persist conversational state across page reloads.
 *
 * @endpoint GET /chatbot/api/history.php?session_token={token}
 * @response JSON {
 *     "success": bool,
 *     "history": array<[id, sender, message, source, intent, created_at]>
 * }
 *
 * @package SOET_College_Project
 * @subpackage Chatbot_API
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../core/bootstrap.php';

try {
    $token = trim($_GET['session_token'] ?? '');
    if (empty($token)) {
        echo json_encode(['success' => false, 'history' => []]);
        exit;
    }

    $db = Database::getInstance();
    $session = $db->fetchOne("SELECT id FROM chat_sessions WHERE session_token = ?", [$token]);

    if (!$session) {
        echo json_encode(['success' => false, 'history' => []]);
        exit;
    }

    $history = $db->fetchAll(
        "SELECT id, sender, message, source, intent, created_at FROM chat_messages WHERE session_id = ? ORDER BY id ASC",
        [$session['id']]
    );

    echo json_encode([
        'success' => true,
        'history' => $history
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
