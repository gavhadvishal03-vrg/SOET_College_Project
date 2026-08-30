<?php
/**
 * Admin Module Legacy Wrapper for AI Chatbot Control Suite
 */
require_once __DIR__ . '/../../core/bootstrap.php';
Auth::requirePermission('manage_chatbot_kb');

header('Location: ' . APP_URL . '/admin/ai-chatbot/index.php');
exit;
