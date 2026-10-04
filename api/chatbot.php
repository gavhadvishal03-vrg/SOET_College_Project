<?php
/**
 * Public Chatbot Conversation API Endpoint
 *
 * Primary AJAX gateway for public website CampusAI interactions.
 * Accepts user queries, session tokens, and target language, delegating
 * directly to the multi-tier SmartChatEngine pipeline.
 *
 * HTTP Method: POST
 * Headers: Content-Type: application/json
 * Payload:
 *   - query / message (string, required): User question/prompt
 *   - session_token (string, optional): Client conversation session identifier
 *   - language (string, optional): Language code ('en', 'hi', 'mr')
 * Response:
 *   - JSON object with 'success' (bool), 'response' (string HTML/markdown),
 *     'intent' (string), 'confidence' (float), and optional suggestion chips.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../chatbot/engine/SmartChatEngine.php';

try {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true) ?: $_POST;

    $query = trim($data['query'] ?? $data['message'] ?? '');
    $token = trim($data['session_token'] ?? '');
    $lang = trim($data['language'] ?? 'en');

    if (empty($query)) {
        echo json_encode([
            'success' => false,
            'status' => 'error',
            'response' => 'Please provide a valid question.'
        ]);
        exit;
    }

    $chatEngine = new SmartChatEngine();
    $res = $chatEngine->processMessage($query, $token, $lang);

    echo json_encode($res);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'status' => 'error',
        'response' => 'AI Service Exception: ' . $e->getMessage()
    ]);
}

