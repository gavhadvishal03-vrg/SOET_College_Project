<?php
/**
 * CampusAI — Conversation Context & Short-Term Memory Manager
 *
 * Tracks multi-turn conversational history within active chat sessions
 * to resolve contextual pronouns and ambiguous follow-up questions:
 * - "What is the fee?" -> Resolves to "What is the fee for Computer Science (CSE)?"
 * - "Who is the HOD?" -> Resolves to "Who is the HOD of Mechanical Engineering?"
 * - "How many seats are left in this branch?" -> Resolves with previously identified entity.
 *
 * @package SOET_College_Project
 * @subpackage Chatbot_Engine
 */

require_once __DIR__ . '/../../core/bootstrap.php';

class ContextManager
{
    private Database $db;

    private array $entityKeywords = [
        'CSE'              => ['cse', 'computer science', 'data science', 'cyber security', 'blockchain'],
        'ECE'              => ['ece', 'electronics', 'vlsi', 'telecommunication', 'chip'],
        'EE'               => ['ee', 'electrical', 'power system', 'smart grid'],
        'MECH'             => ['mech', 'mechanical', 'robotics', 'automation'],
        'CIVIL'            => ['civil', 'infrastructure', 'structural'],
        'ASH'              => ['ash', 'applied science', 'humanities', 'first year'],
        'ADMISSION'        => ['admission', 'apply', 'eligibility', 'cutoff', 'quota'],
        'HOSTEL'           => ['hostel', 'mess', 'canteen', 'accommodation'],
        'PLACEMENTS'       => ['placement', 'package', 'salary', 'recruiter', 'job'],
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Fetch the last N user messages from chat_messages for context
     */
    public function getContext(int $sessionId, int $lastN = 4): array
    {
        if ($sessionId <= 0) {
            return [];
        }

        try {
            $rows = $this->db->fetchAll(
                "SELECT message, intent, source, created_at FROM chat_messages 
                 WHERE session_id = ? AND sender = 'user' 
                 ORDER BY id DESC LIMIT ?",
                [$sessionId, $lastN]
            );
            return array_reverse($rows);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Extract dominant entity from a message (e.g. 'CSE', 'ECE', 'Hostel', etc.)
     */
    public function extractEntity(string $message): ?string
    {
        $clean = mb_strtolower($message);
        foreach ($this->entityKeywords as $entity => $keywords) {
            foreach ($keywords as $kw) {
                if (preg_match('/\b' . preg_quote($kw, '/') . '\b/i', $clean)) {
                    return $entity;
                }
            }
        }
        return null;
    }

    /**
     * Resolve contextual pronouns and short follow-up questions
     */
    public function resolveQuery(string $currentQuery, array $context): string
    {
        $clean = trim($currentQuery);
        $cleanLower = mb_strtolower($clean);

        // If the query already specifies an explicit entity, no context substitution needed
        $currentEntity = $this->extractEntity($clean);
        if ($currentEntity !== null && !in_array($cleanLower, ['what is the fee', 'fee?', 'fees?', 'how many seats', 'who teaches it', 'what is the eligibility'])) {
            return $clean;
        }

        // Look back through context to find the most recent mentioned entity
        $lastEntity = null;
        for ($i = count($context) - 1; $i >= 0; $i--) {
            $prevMsg = $context[$i]['message'] ?? '';
            $found = $this->extractEntity($prevMsg);
            if ($found) {
                $lastEntity = $found;
                break;
            }
        }

        if (!$lastEntity) {
            return $clean;
        }

        // Follow-up patterns that need entity attachment
        $followUpPatterns = [
            '/^(what is the|tell me|show)\s+(fees?|fee structure|cost|tuition)\??$/i' => "What is the fee structure for {$lastEntity}?",
            '/^(fees?|fee|tuition)\??$/i' => "What is the fee structure for {$lastEntity}?",
            '/^(how many|what is the)\s+(seats?|intake|vacanc(y|ies))\??$/i' => "What is the seat availability and vacant seats in {$lastEntity}?",
            '/^(seats?|vacanc(y|ies))\??$/i' => "What is the seat availability and vacant seats in {$lastEntity}?",
            '/^(who teaches|who is the hod|faculty|professors?)\??$/i' => "Who are the faculty members and professors in {$lastEntity} department?",
            '/^(eligibility|criteria|requirements?)\??$/i' => "What is the admission eligibility criteria for {$lastEntity}?",
            '/\b(for this|in this|for that|in that|of this|of that|for it|of it)\b/i' => "for {$lastEntity}",
        ];

        foreach ($followUpPatterns as $pattern => $replacement) {
            if (preg_match($pattern, $clean)) {
                if (strpos($replacement, '$') !== false || strpos($replacement, 'for ') === 0) {
                    return preg_replace($pattern, $replacement, $clean);
                }
                return $replacement;
            }
        }

        // If very short and generic, append the entity
        if (strlen($clean) <= 25 && preg_match('/\b(fee|fees|seats?|faculty|syllabus|duration|package|placement)\b/i', $clean)) {
            return $clean . " for " . $lastEntity;
        }

        return $clean;
    }
}
