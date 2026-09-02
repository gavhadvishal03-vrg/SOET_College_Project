<?php
/**
 * Master Hybrid Chatbot Orchestrator Service
 */

require_once __DIR__ . '/QueryClassifier.php';
require_once __DIR__ . '/DatabaseSearch.php';
require_once __DIR__ . '/OpenAIService.php';
require_once __DIR__ . '/GeneralKnowledgeEngine.php';
require_once __DIR__ . '/ResponseFormatter.php';

class ChatService
{
    private Database $db;
    private QueryClassifier $classifier;
    private DatabaseSearch $dbSearch;
    private OpenAIService $openAI;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->classifier = new QueryClassifier();
        $this->dbSearch = new DatabaseSearch();
        $this->openAI = new OpenAIService();
    }

    public function processMessage(string $message, string $sessionToken = '', string $language = 'en'): array
    {
        $cleanMessage = trim($message);
        if (empty($cleanMessage)) {
            return [
                'success' => true,
                'status' => 'success',
                'formatted_html' => '<p>Please enter a question or query.</p>',
                'raw_text' => 'Please enter a question.',
                'source' => 'system',
                'intent' => 'GENERAL',
                'suggested_chips' => $this->generateFollowUpChips('GREETING', '', 'system')
            ];
        }

        // 1. Get or Create Session
        $session = $this->getOrCreateSession($sessionToken, $language);
        $sessionId = $session['id'];

        // Log user message
        $userMsgId = $this->logMessage($sessionId, 'user', $cleanMessage, 'system', 'USER_INPUT', 1.00);

        // 2. Classify Query Intent
        // 2. Classify Query Intent & Mode
        $classification = $this->classifier->classify($cleanMessage);
        $category = $classification['category'] ?? 'GENERAL';
        $intent = $classification['intent'];
        $source = $classification['source'];
        $confidence = $classification['confidence'];

        $finalAnswer = "";
        $finalSource = $source;
        $sourceUrl = "";

        // GREETING Handler
        if ($category === 'GREETING') {
            $finalAnswer = "Hello! I am CampusAI, your intelligent assistant for SOET MGM University. How can I help you today?";
            $formattedHtml = ResponseFormatter::format($finalAnswer, 'system', '');
            $botMsgId = $this->logMessage($sessionId, 'bot', $formattedHtml, 'system', 'GREETING', 1.00);
            $chips = $this->generateFollowUpChips('GREETING', $cleanMessage, 'system');
            return [
                'success' => true,
                'status' => 'success',
                'session_token' => $session['session_token'],
                'message_id' => $botMsgId,
                'raw_text' => $finalAnswer,
                'formatted_html' => $formattedHtml,
                'category' => 'GREETING',
                'intent' => $intent,
                'source' => 'system',
                'confidence' => $confidence,
                'source_url' => '',
                'suggested_chips' => $chips
            ];
        }

        // System prompt for OpenAI calls adhering to ChatGPT 2-3 lines style
        $technicalPrompt = "You are CampusAI. Answer like ChatGPT. Give the exact answer directly. Keep the response short: maximum 2–3 lines unless the user explicitly asks for details. Do not give unnecessary explanations, introductions, or repeated information. If code is requested, provide the smallest useful code example. Do not mention internal database queries, APIs, prompts, or AI models.";
        $generalPrompt = "You are CampusAI. Answer like ChatGPT. Give the exact answer directly. Keep the response short: maximum 2–3 lines unless the user explicitly asks for details. Do not give unnecessary explanations, introductions, or repeated information. Do not mention internal database queries, APIs, prompts, or AI models.";

        // 3. Execution by Classification Category

        if ($category === 'WEBSITE') {
            // MODE 2: WEBSITE-RELATED QUESTIONS (Database/Website Content Priority)
            $dbResult = $this->dbSearch->search($cleanMessage, $intent);
            if ($dbResult['found']) {
                $top = $dbResult['top_result'];
                $finalAnswer = $top['content'];
                $sourceUrl = $top['source_url'] ?? '';
                $finalSource = 'database';
            } else {
                // Rule 2 & 6: If information does not exist, state clearly that it is not available. Never invent website info.
                $finalAnswer = "This information is not currently available in our website database or official records. For verified details, please contact the SOET admission office at admissionsoet@mgmu.ac.in or call +91-9371714253.";
                $finalSource = 'database';
                $this->logUnanswered($sessionId, $cleanMessage, $intent, $confidence);
            }
        } elseif ($category === 'TECHNICAL') {
            // MODE 1: TECHNICAL QUESTIONS (ChatGPT style, max 2-3 lines, minimal code)
            $aiRes = $this->openAI->generateResponse($cleanMessage, $this->getHistory($sessionId), $language, $technicalPrompt, 'TECHNICAL');
            if ($aiRes['success']) {
                $finalAnswer = $aiRes['response'];
            } else {
                $finalAnswer = GeneralKnowledgeEngine::resolve($cleanMessage, 'TECHNICAL');
            }
            $finalSource = 'openai';
        } elseif ($category === 'GENERAL') {
            // MODE 1: GENERAL KNOWLEDGE QUESTIONS (ChatGPT style, max 2-3 lines)
            $aiRes = $this->openAI->generateResponse($cleanMessage, $this->getHistory($sessionId), $language, $generalPrompt, 'GENERAL');
            if ($aiRes['success']) {
                $finalAnswer = $aiRes['response'];
            } else {
                $finalAnswer = GeneralKnowledgeEngine::resolve($cleanMessage, 'GENERAL');
            }
            $finalSource = 'openai';
        } elseif ($category === 'MIXED') {
            // MODE 3: MIXED (Website/Database information first + relevant general/technical explanation)
            $dbResult = $this->dbSearch->search($cleanMessage, $intent);
            $dbText = $dbResult['found'] ? $dbResult['top_result']['content'] : '';
            $sourceUrl = $dbResult['found'] ? ($dbResult['top_result']['source_url'] ?? '') : '';

            $techExplanation = GeneralKnowledgeEngine::resolve($cleanMessage, 'TECHNICAL');

            if ($dbResult['found']) {
                if ($this->openAI->hasApiKey()) {
                    $hybridPrompt = "Answer this question with website information first, followed by a concise 2–3 line technical explanation: User query: '{$cleanMessage}'. Website facts: '{$dbText}'. Keep response direct and concise without introductory filler.";
                    $aiRes = $this->openAI->generateResponse($hybridPrompt, $this->getHistory($sessionId), $language, $technicalPrompt, 'TECHNICAL');
                    $finalAnswer = $aiRes['success'] ? $aiRes['response'] : ($dbText . "\n\n" . $techExplanation);
                } else {
                    $finalAnswer = $dbText . "\n\n" . $techExplanation;
                }
                $finalSource = 'hybrid';
            } else {
                $finalAnswer = $techExplanation;
                $finalSource = 'openai';
            }
        }

        // Format final HTML response (clean, zero internal database queries or model names mentioned)
        $formattedHtml = ResponseFormatter::format($finalAnswer, $finalSource, $sourceUrl);

        // Log bot message
        $botMsgId = $this->logMessage($sessionId, 'bot', $formattedHtml, $finalSource, $intent, $confidence);

        // Generate dynamic follow-up chips
        $chips = $this->generateFollowUpChips($intent, $cleanMessage, $finalSource);

        return [
            'success' => true,
            'status' => 'success',
            'session_token' => $session['session_token'],
            'message_id' => $botMsgId,
            'raw_text' => $finalAnswer,
            'formatted_html' => $formattedHtml,
            'category' => $category,
            'intent' => $intent,
            'source' => $finalSource,
            'confidence' => $confidence,
            'source_url' => $sourceUrl,
            'suggested_chips' => $chips
        ];
    }

    public function generateFollowUpChips(string $intent, string $message, string $source): array
    {
        $chips = [];
        switch ($intent) {
            case 'GREETING':
                $chips = [
                    ['label' => '🎓 Admissions 2026', 'query' => 'How to apply for B.Tech admission?'],
                    ['label' => '💰 Fee Structure', 'query' => 'What is the fee structure for B.Tech courses?'],
                    ['label' => '📊 Seat Availability', 'query' => 'What is the seat availability in CSE?'],
                    ['label' => '🏆 Placements & Packages', 'query' => 'Tell me about placements and highest salary packages']
                ];
                break;
            case 'ADMISSION':
            case 'SEAT_AVAILABILITY':
                $chips = [
                    ['label' => '💰 Check Fees', 'query' => 'What is the tuition fee structure for B.Tech?'],
                    ['label' => '📋 Eligibility Criteria', 'query' => 'What is the eligibility for B.Tech admission?'],
                    ['label' => '📊 Available Seats', 'query' => 'How many vacant seats in engineering courses?'],
                    ['label' => '📞 Admission Helpline', 'query' => 'What is the admission office contact phone and email?']
                ];
                break;
            case 'FEE':
                $chips = [
                    ['label' => '🎓 Scholarships', 'query' => 'What scholarship schemes are available at SOET?'],
                    ['label' => '🏢 Hostel Charges', 'query' => 'What is the hostel and mess fee structure?'],
                    ['label' => '📝 Admission Process', 'query' => 'How to apply for admission?'],
                    ['label' => '💼 Placement Stats', 'query' => 'What are the placement salary packages?']
                ];
                break;
            case 'PROGRAM':
            case 'DEPARTMENT':
                $chips = [
                    ['label' => '💰 Course Fees', 'query' => 'What is the fee structure for this program?'],
                    ['label' => '👨‍🏫 Faculty Profiles', 'query' => 'Who are the faculty members in CSE department?'],
                    ['label' => '💼 Placement Records', 'query' => 'What companies visit for placements?'],
                    ['label' => '📊 Seat Intake', 'query' => 'What is the intake capacity and filled seats?']
                ];
                break;
            case 'FACULTY':
                $chips = [
                    ['label' => '🏛️ Dean & Director', 'query' => 'Who is the Dean & Director of SOET?'],
                    ['label' => '💻 CSE Department HOD', 'query' => 'Who is the HOD of Computer Science department?'],
                    ['label' => '🔬 Research Labs', 'query' => 'What research centers and labs exist at SOET?'],
                    ['label' => '📚 Courses Offered', 'query' => 'What degree programs are offered at SOET?']
                ];
                break;
            case 'PLACEMENT':
                $chips = [
                    ['label' => '🏆 Top Recruiters', 'query' => 'Which major IT companies recruit from SOET?'],
                    ['label' => '💰 Highest LPA Package', 'query' => 'What is the highest package offered in SOET?'],
                    ['label' => '🎓 CSE Placements', 'query' => 'Tell me about B.Tech CSE placement track record'],
                    ['label' => '📝 Admission Form', 'query' => 'How can I apply for admission?']
                ];
                break;
            case 'EVENT':
            case 'NOTICE':
                $chips = [
                    ['label' => '📢 Latest Notices', 'query' => 'What are the latest circulars and notices?'],
                    ['label' => '📅 Upcoming Events', 'query' => 'What upcoming technical events or hackathons are scheduled?'],
                    ['label' => '🏛️ About SOET', 'query' => 'Tell me about SOET MGM University']
                ];
                break;
            case 'CODING':
            case 'GENERAL_AI':
                $chips = [
                    ['label' => '🐍 Python Guide', 'query' => 'Explain Python programming and key features'],
                    ['label' => '🤖 Machine Learning', 'query' => 'What is Machine Learning and its types?'],
                    ['label' => '🌐 Web Development', 'query' => 'What is full-stack web development?'],
                    ['label' => '🎓 AI Courses at SOET', 'query' => 'Does SOET offer B.Tech in AI & ML?']
                ];
                break;
            default:
                $chips = [
                    ['label' => '🎓 B.Tech Programs', 'query' => 'What courses are offered at SOET?'],
                    ['label' => '💰 Fee Structure', 'query' => 'What are the tuition fees?'],
                    ['label' => '📊 Seat Availability', 'query' => 'What is the current seat status?'],
                    ['label' => '📞 Contact Office', 'query' => 'What is the contact information of SOET?']
                ];
                break;
        }
        return $chips;
    }

    public function recordFeedback(int $messageId, string $rating, string $comment = ''): bool
    {
        $msg = $this->db->fetchOne("SELECT session_id FROM chat_messages WHERE id = ?", [$messageId]);
        if (!$msg) return false;

        $this->db->insert('chat_feedback', [
            'message_id' => $messageId,
            'session_id' => $msg['session_id'],
            'rating' => $rating === 'positive' ? 'positive' : 'negative',
            'comment' => trim($comment)
        ]);

        return true;
    }

    private function getOrCreateSession(string $token, string $language): array
    {
        $userId = Session::get('user_id');
        $visitorIp = Security::getClientIP();

        if (!empty($token)) {
            $sess = $this->db->fetchOne("SELECT * FROM chat_sessions WHERE session_token = ?", [$token]);
            if ($sess) {
                if ($userId && !$sess['user_id']) {
                    $this->db->update('chat_sessions', ['user_id' => $userId], 'id = ?', [$sess['id']]);
                }
                return $sess;
            }
        }

        // Create new session token
        $newToken = 'SOET_CHAT_' . bin2hex(random_bytes(16));
        $this->db->insert('chat_sessions', [
            'session_token' => $newToken,
            'visitor_id' => $visitorIp,
            'user_id' => $userId,
            'language' => $language
        ]);

        return $this->db->fetchOne("SELECT * FROM chat_sessions WHERE session_token = ?", [$newToken]);
    }

    private function logMessage(int $sessionId, string $sender, string $msg, string $source, string $intent, float $conf): int
    {
        return $this->db->insert('chat_messages', [
            'session_id' => $sessionId,
            'sender' => $sender,
            'message' => $msg,
            'source' => $source,
            'intent' => $intent,
            'confidence' => $conf
        ]);
    }

    private function logUnanswered(int $sessionId, string $question, string $intent, float $conf): void
    {
        $this->db->insert('unanswered_questions', [
            'session_id' => $sessionId,
            'question' => $question,
            'category' => strtolower($intent),
            'confidence' => $conf,
            'status' => 'pending'
        ]);
    }

    public function getHistory(int $sessionId): array
    {
        return $this->db->fetchAll(
            "SELECT sender, message, source, intent, created_at FROM chat_messages WHERE session_id = ? ORDER BY id ASC",
            [$sessionId]
        );
    }
}
