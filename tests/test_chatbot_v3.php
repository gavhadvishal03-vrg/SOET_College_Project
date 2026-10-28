<?php
/**
 * Test Suite for CampusAI Chatbot Engine v3 & Native AI Model
 */

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../chatbot/engine/SmartChatEngine.php';
require_once __DIR__ . '/../chatbot/engine/CampusAIModel.php';

echo "========================================================\n";
echo "🤖 CampusAI Chatbot v3 Engine Verification Test Suite\n";
echo "========================================================\n\n";

$engine = new SmartChatEngine();

$testCases = [
    [
        'category' => 'Greeting',
        'query' => 'Hello',
        'expected_source' => 'system'
    ],
    [
        'category' => 'Database / Seat Matrix',
        'query' => 'how many vacant seats in CSE?',
        'expected_source' => 'DATABASE'
    ],
    [
        'category' => 'Website / Fee Structure',
        'query' => 'What is the fee structure for B.Tech courses?',
        'expected_source' => 'DATABASE'
    ],
    [
        'category' => 'Website / Faculty & Leadership',
        'query' => 'Who is the Dean of SOET?',
        'expected_source' => 'DATABASE'
    ],
    [
        'category' => 'Technical / CampusAI Model',
        'query' => 'What is binary search?',
        'expected_source' => 'CAMPUS_AI_MODEL'
    ],
    [
        'category' => 'Technical / Code Generation',
        'query' => 'write python code for binary search',
        'expected_source' => 'CAMPUS_AI_MODEL'
    ],
    [
        'category' => 'Technical / Modern Language',
        'query' => 'What is typescript?',
        'expected_source' => 'CAMPUS_AI_MODEL'
    ],
    [
        'category' => 'Math Computation',
        'query' => 'calculate 50 * 25',
        'expected_source' => 'CAMPUS_AI_MODEL'
    ],
    [
        'category' => 'Temporal Clock / Time',
        'query' => 'What is the current time?',
        'expected_source' => 'CAMPUS_AI_MODEL'
    ],
    [
        'category' => 'Multi-lingual Transliteration',
        'query' => 'kiti seats ahet CSE madhe',
        'expected_source' => 'DATABASE'
    ],
    [
        'category' => 'Sentiment Auto-Escalation',
        'query' => 'This bot is useless and terrible',
        'expected_source' => 'system'
    ]
];

$passed = 0;
$total = count($testCases);

foreach ($testCases as $i => $tc) {
    echo "Test " . ($i + 1) . ": [" . $tc['category'] . "] Query: '" . $tc['query'] . "'\n";
    $result = $engine->processMessage($tc['query']);

    $source = $result['source'] ?? 'UNKNOWN';
    $rawText = strip_tags($result['raw_text'] ?? '');
    $engineVer = $result['engine_version'] ?? 'N/A';
    $isFallback = $result['is_fallback'] ?? false;

    echo "  -> Source: " . $source . "\n";
    echo "  -> Engine Version: " . $engineVer . "\n";
    echo "  -> Success: " . ($result['success'] ? 'YES' : 'NO') . "\n";
    echo "  -> Fallback: " . ($isFallback ? 'YES' : 'NO') . "\n";
    echo "  -> Answer snippet: " . mb_substr($rawText, 0, 95) . "...\n";

    if ($result['success'] && !empty($rawText) && !$isFallback) {
        echo "  [PASS]\n\n";
        $passed++;
    } else {
        echo "  [CHECK]\n\n";
    }
}

echo "========================================================\n";
echo "SUMMARY: {$passed} of {$total} tests PASSED!\n";
echo "========================================================\n";
