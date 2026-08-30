<?php
/**
 * Chatbot Public API - Send Message Endpoint
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../core/bootstrap.php';
require_once __DIR__ . '/../services/ChatService.php';

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

    $chatService = new ChatService();
    $result = $chatService->processMessage($message, $sessionToken, $language);

    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal Chatbot API error: ' . $e->getMessage()
    ]);
}
