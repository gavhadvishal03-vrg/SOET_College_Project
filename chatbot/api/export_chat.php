<?php
/**
 * 🤖 CampusAI — Export Conversation Transcript Endpoint
 */

require_once __DIR__ . '/../../core/bootstrap.php';
require_once __DIR__ . '/../services/ChatService.php';

$token = trim($_GET['session_token'] ?? '');
if (empty($token)) {
    die("Error: Session token required.");
}

$db = Database::getInstance();
$session = $db->fetchOne("SELECT * FROM chat_sessions WHERE session_token = ?", [$token]);

if (!$session) {
    die("Error: Chat session not found.");
}

$messages = $db->fetchAll(
    "SELECT sender, message, source, intent, created_at FROM chat_messages WHERE session_id = ? ORDER BY id ASC",
    [$session['id']]
);

$filename = "CampusAI_Chat_Transcript_" . date('Y-m-d_His') . ".txt";

header('Content-Type: text/plain; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

echo "======================================================================\n";
echo "🤖 CampusAI — SOET MGM University Chatbot Conversation Transcript\n";
echo "Session Token: " . $session['session_token'] . "\n";
echo "Date / Time  : " . date('Y-m-d H:i:s') . "\n";
echo "Language     : " . strtoupper($session['language'] ?? 'EN') . "\n";
echo "======================================================================\n\n";

if (empty($messages)) {
    echo "No messages recorded in this conversation.\n";
} else {
    foreach ($messages as $msg) {
        $senderLabel = $msg['sender'] === 'user' ? '👤 Visitor' : '🤖 CampusAI';
        $timeStr = date('H:i:s', strtotime($msg['created_at']));
        $plainText = strip_tags(html_entity_decode($msg['message']));
        // Clean multiple newlines
        $plainText = preg_replace("/\n{3,}/", "\n\n", trim($plainText));

        echo "[$timeStr] $senderLabel:\n";
        echo $plainText . "\n";
        echo "----------------------------------------------------------------------\n";
    }
}

echo "\n--- End of Official Transcript | SOET MGM University CampusAI ---\n";
exit;
