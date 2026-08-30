<?php
/**
 * Chatbot Public API - User Feedback Endpoint
 */

header('Content-Type: application/json');
require_once 'C:/xampp/htdocs/project/core/bootstrap.php';
require_once 'C:/xampp/htdocs/project/chatbot/services/ChatService.php';

try {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true) ?: $_POST;

    $messageId = (int)($data['message_id'] ?? 0);
    $rating = trim($data['rating'] ?? '');
    $comment = trim($data['comment'] ?? '');

    if (!$messageId || empty($rating)) {
        echo json_encode(['success' => false, 'message' => 'Missing message_id or rating']);
        exit;
    }

    $chatService = new ChatService();
    $saved = $chatService->recordFeedback($messageId, $rating, $comment);

    echo json_encode([
        'success' => $saved,
        'message' => $saved ? 'Feedback recorded. Thank you!' : 'Unable to record feedback'
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
