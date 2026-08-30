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
        $classification = $this->classifier->classify($cleanMessage);
        $intent = $classification['intent'];
        $source = $classification['source'];
        $confidence = $classification['confidence'];

        $finalAnswer = "";
        $finalSource = $source;
        $sourceUrl = "";

        // GREETING Handler
        if ($source === 'system' && $intent === 'GREETING') {
            $finalAnswer = "Hello! I am 🤖 CampusAI, the official intelligent assistant for SOET (School of Engineering & Technology), MGM University. How can I assist you with admissions, courses, fees, faculty, or technical concepts today?";
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
                'intent' => $intent,
                'source' => 'system',
                'confidence' => $confidence,
                'source_url' => '',
                'suggested_chips' => $chips
            ];
        }

        // UNCLEAR / Ambiguous Query Handler
        if ($intent === 'UNCLEAR') {
            $finalAnswer = "Could you please clarify your question? For example, you can ask me about **Courses & Programs**, **Fee Structure**, **Real-Time Seat Availability**, **Admissions**, **Faculty**, or **Placements**.";
            $formattedHtml = ResponseFormatter::format($finalAnswer, 'system', '');
            $botMsgId = $this->logMessage($sessionId, 'bot', $formattedHtml, 'system', 'UNCLEAR', 0.50);
            $chips = $this->generateFollowUpChips('GENERAL', $cleanMessage, 'system');
            return [
                'success' => true,
                'status' => 'success',
                'session_token' => $session['session_token'],
                'message_id' => $botMsgId,
                'raw_text' => $finalAnswer,
                'formatted_html' => $formattedHtml,
                'intent' => 'UNCLEAR',
                'source' => 'system',
                'confidence' => 0.50,
                'source_url' => '',
                'suggested_chips' => $chips
            ];
        }

        $systemPrompt = "You are 🤖 CampusAI, the official intelligent assistant for SOET (School of Engineering & Technology), MGM University. You answer college-related questions using verified institutional data and general/educational questions using your knowledge. Always be helpful, accurate, and friendly. Never refuse to answer. For college questions, use only verified data. For general questions, provide clear explanations.";

        // 3. Routing Execution according to Developer Workflow
        if ($source === 'database') {
            // CONDITION 1: College / Website Query -> SOET Institutional Database Search
            $dbResult = $this->dbSearch->search($cleanMessage, $intent);
            if ($dbResult['found']) {
                // Match Found -> Verified College Facts & Official Data
                $top = $dbResult['top_result'];
                $finalAnswer = $top['content'];
                $sourceUrl = $top['source_url'] ?? '';
                $finalSource = 'database';
            } else {
                // No Direct DB Match -> Free AI / LLM Fallback Engine
                $aiRes = $this->openAI->generateResponse($cleanMessage, $this->getHistory($sessionId), $language, $systemPrompt);
                if ($aiRes['success']) {
                    $finalAnswer = $aiRes['response'];
                } else {
                    $finalAnswer = GeneralKnowledgeEngine::resolve($cleanMessage);
                }
                $finalSource = 'openai';
                $this->logUnanswered($sessionId, $cleanMessage, $intent, $confidence);
            }
        } elseif ($source === 'openai') {
            // CONDITION 2: General / Technical Query -> Free AI / LLM Knowledge Engine
            $aiRes = $this->openAI->generateResponse($cleanMessage, $this->getHistory($sessionId), $language, $systemPrompt);
            if ($aiRes['success']) {
                $finalAnswer = $aiRes['response'];
            } else {
                $finalAnswer = GeneralKnowledgeEngine::resolve($cleanMessage);
            }
            $finalSource = 'openai';
        } elseif ($source === 'hybrid') {
            // CONDITION 3: Hybrid Query -> DB Facts + AI Enrichment
            $dbResult = $this->dbSearch->search($cleanMessage, $intent);
            $dbText = $dbResult['found'] ? $dbResult['top_result']['content'] : '';
            $sourceUrl = $dbResult['found'] ? ($dbResult['top_result']['source_url'] ?? '') : '';

            if ($dbResult['found']) {
                // DB Facts found -> Enrich with AI
                $aiRes = $this->openAI->generateResponse(
                    "The user asked: '{$cleanMessage}'. Here is verified college data: '{$dbText}'. Answer the question using ONLY this verified data. Keep it concise and accurate.",
                    $this->getHistory($sessionId), $language, $systemPrompt
                );

                if ($aiRes['success']) {
                    $finalAnswer = $aiRes['response'];
                } else {
                    $finalAnswer = $dbText;
                }
                $finalSource = 'hybrid';
            } else {
                // No DB Facts -> Fallback to AI Knowledge Engine
                $aiRes = $this->openAI->generateResponse($cleanMessage, $this->getHistory($sessionId), $language, $systemPrompt);
                if ($aiRes['success']) {
                    $finalAnswer = $aiRes['response'];
                } else {
                    $finalAnswer = GeneralKnowledgeEngine::resolve($cleanMessage);
                }
                $finalSource = 'openai';
                $this->logUnanswered($sessionId, $cleanMessage, $intent, $confidence);
            }
        }

        // Format final HTML response
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
