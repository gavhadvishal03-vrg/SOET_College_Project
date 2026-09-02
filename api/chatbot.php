<?php
/**
 * Legacy API Chatbot Endpoint Wrapper
 * Delegates directly to the production ChatService orchestrator
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../chatbot/services/ChatService.php';

try {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true) ?: $_POST;

    $query = trim($data['query'] ?? $data['message'] ?? '');
    $token = trim($data['session_token'] ?? '');
    $lang = trim($data['language'] ?? 'en');

    if (empty($query)) {
        echo json_encode([
            'status' => 'error',
            'response' => 'Please provide a valid question.'
        ]);
        exit;
    }

    $chatService = new ChatService();
    $res = $chatService->processMessage($query, $token, $lang);

    echo json_encode([
        'success' => true,
        'status' => 'success',
        'response' => $res['formatted_html'],
        'raw' => $res['raw_text'],
        'source' => $res['source'],
        'intent' => $res['intent'],
        'session_token' => $res['session_token'],
        'suggested_chips' => $res['suggested_chips'] ?? [],
        'message_id' => $res['message_id'] ?? null
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'response' => 'AI Service Exception: ' . $e->getMessage()
    ]);
}
