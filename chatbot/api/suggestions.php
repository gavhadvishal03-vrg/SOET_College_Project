<?php
/**
 * Chatbot Public API - Quick Suggestions & Live Autocomplete Endpoint
 */

header('Content-Type: application/json');
require_once 'C:/xampp/htdocs/project/core/bootstrap.php';

$query = trim($_GET['q'] ?? '');

$defaultSuggestions = [
    [
        'label' => '🎓 Admissions & Eligibility',
        'query' => 'What is the admission eligibility for B.Tech at SOET?'
    ],
    [
        'label' => '💻 CSE Programs & Fee',
        'query' => 'Tell me about B.Tech CSE intake and annual fees'
    ],
    [
        'label' => '🏛️ Dean & Director',
        'query' => 'Who is the Dean & Director of SOET?'
    ],
    [
        'label' => '🏆 Placements & CTC',
        'query' => 'What are the highest placement salary packages at SOET?'
    ],
    [
        'label' => '🔬 Infrastructure & Labs',
        'query' => 'Tell me about the campus research labs and facilities'
    ],
    [
        'label' => '💡 Explain Binary Search',
        'query' => 'Write a C program for binary search and explain it'
    ]
];

if (empty($query)) {
    echo json_encode(['success' => true, 'suggestions' => $defaultSuggestions]);
    exit;
}

try {
    $db = Database::getInstance();
    $param = '%' . $query . '%';

    $matches = [];

    // Search FAQs
    $faqMatches = $db->fetchAll(
        "SELECT question FROM faq WHERE question LIKE ? AND status = 'active' LIMIT 4",
        [$param]
    );
    foreach ($faqMatches as $f) {
        $matches[] = [
            'label' => '❓ ' . truncate($f['question'], 45),
            'query' => $f['question']
        ];
    }

    // Search Knowledge Base
    $kbMatches = $db->fetchAll(
        "SELECT title FROM knowledge_base WHERE title LIKE ? AND status = 'active' LIMIT 4",
        [$param]
    );
    foreach ($kbMatches as $k) {
        if (!in_array($k['title'], array_column($matches, 'query'))) {
            $matches[] = [
                'label' => '📚 ' . truncate($k['title'], 45),
                'query' => $k['title']
            ];
        }
    }

    // Search Courses
    $courseMatches = $db->fetchAll(
        "SELECT name, code FROM courses WHERE (name LIKE ? OR code LIKE ?) AND is_active = 1 LIMIT 3",
        [$param, $param]
    );
    foreach ($courseMatches as $c) {
        $qText = "Tell me about " . $c['name'] . " (" . $c['code'] . ") admission, eligibility, and fees";
        $matches[] = [
            'label' => '🎓 ' . $c['name'],
            'query' => $qText
        ];
    }

    if (empty($matches)) {
        $matches = array_slice($defaultSuggestions, 0, 4);
    }

    echo json_encode(['success' => true, 'suggestions' => array_slice($matches, 0, 6)]);
} catch (Exception $e) {
    echo json_encode(['success' => true, 'suggestions' => $defaultSuggestions]);
}
