<?php
/**
 * Chatbot Public API - Send Message Endpoint
 *
 * Primary AJAX endpoint receiving user messages from the web widget.
 *
 * @endpoint POST /chatbot/api/send.php
 * @payload JSON or Form-Data {
 *     "message": string (User query text),
 *     "session_token": string (UUID for conversation tracking),
 *     "language": string (ISO 639-1 code: "en", "hi", "mr")
 * }
 * @response JSON {
 *     "success": bool,
 *     "response": string (HTML or markdown text),
 *     "source": string ("database"|"openai"|"hybrid"|"general"|"fallback"),
 *     "intent": string,
 *     "confidence": float,
 *     "suggestions": array
 * }
 *
 * @package SOET_College_Project
 * @subpackage Chatbot_API
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../core/bootstrap.php';
require_once __DIR__ . '/../engine/SmartChatEngine.php';

try {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);

    if (!$data) {
        $data = $_POST;
    }

    $message = trim($data['message'] ?? $data['query'] ?? '');
    $sessionToken = trim($data['session_token'] ?? '');
    $language = trim($data['language'] ?? 'en');

    if (empty($message)) {
        echo json_encode([
            'success' => false,
            'message' => 'Query message parameter cannot be empty.'
        ]);
        exit;
    }

    $chatEngine = new SmartChatEngine();
    $result = $chatEngine->processMessage($message, $sessionToken, $language);

    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal Chatbot API error: ' . $e->getMessage()
    ]);
}
