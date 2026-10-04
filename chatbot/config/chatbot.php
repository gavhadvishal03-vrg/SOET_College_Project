<?php
/**
 * SOET Hybrid AI Chatbot Configuration
 *
 * Defines core runtime hyperparameters, multilingual support, confidence thresholds,
 * conversational memory depth, default language model routing, and document upload directories.
 *
 * @package SOET_College_Project
 * @subpackage Chatbot_Config
 */

if (!defined('CHATBOT_ROOT')) {
    define('CHATBOT_ROOT', dirname(__DIR__));
}

return [
    // Default language and supported localization codes
    'default_language' => 'en',
    'supported_languages' => ['en' => 'English', 'hi' => 'Hindi', 'mr' => 'Marathi'],

    // Minimum confidence score required to serve database match before falling back
    'confidence_threshold' => 0.45,

    // Maximum number of prior messages retained in conversational short-term context
    'max_history_length' => 10,

    // Default OpenAI language model and sampling hyperparameters
    'default_model' => 'gpt-4o-mini',
    'default_temperature' => 0.7,
    'max_tokens' => 800,

    // Canonical fallback message when no verified match is identified
    'fallback_message' => 'I could not find exact verified details for this query in the SOET Knowledge Base. Would you like me to connect you with our Admissions Office or search general engineering topics?',

    // File storage path for ingested knowledge documents
    'upload_directory' => __DIR__ . '/../knowledge/documents/'
];

