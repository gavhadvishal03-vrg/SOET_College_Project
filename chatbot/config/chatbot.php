<?php
/**
 * SOET Hybrid AI Chatbot Configuration
 */

if (!defined('CHATBOT_ROOT')) {
    define('CHATBOT_ROOT', dirname(__DIR__));
}

return [
    'default_language' => 'en',
    'supported_languages' => ['en' => 'English', 'hi' => 'Hindi', 'mr' => 'Marathi'],
    'confidence_threshold' => 0.45,
    'max_history_length' => 10,
    'default_model' => 'gpt-4o-mini',
    'default_temperature' => 0.7,
    'max_tokens' => 800,
    'fallback_message' => 'I could not find exact verified details for this query in the SOET Knowledge Base. Would you like me to connect you with our Admissions Office or search general engineering topics?',
    'upload_directory' => __DIR__ . '/../knowledge/documents/'
];
