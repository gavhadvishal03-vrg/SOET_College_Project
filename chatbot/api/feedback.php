<?php
/**
 * Chatbot Public API - User Feedback Endpoint
 *
 * Records user quality ratings (thumbs up/down) and optional comments
 * evaluating individual chatbot responses.
 *
 * @endpoint POST /chatbot/api/feedback.php
 * @payload JSON or Form-Data {
 *     "message_id": int,
 *     "rating": string ("positive"|"negative"),
 *     "comment": string (optional feedback text)
 * }
 * @response JSON {
 *     "success": bool,
 *     "message": string
 * }
 *
 * @package SOET_College_Project
 * @subpackage Chatbot_API
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../core/bootstrap.php';
require_once __DIR__ . '/../services/ChatService.php';

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
