<?php
/**
 * Master Hybrid Chatbot Orchestrator Service
 *
 * Coordinates multi-tier retrieval augmented generation (RAG):
 * 1. Intent Classification: Categorizes queries into GREETING, DATABASE, WEBSITE, TECHNICAL, GENERAL, or HYBRID.
 * 2. Live Database Search: Directly queries seat availability, courses, faculty, fees, placements, events.
 * 3. Knowledge Base & FAQ Search: Scans verified training articles and canonical Q&A pairs.
 * 4. General Knowledge Engine & OpenAI LLM: Resolves conceptual, technical, or programming questions.
 * 5. Formatting & Presentation: Renders clean HTML with markdown tables, source citation badges, and contextual follow-up chips.
 * 6. Audit Logging: Stores transcripts, feedback ratings, and logs unanswered queries.
 *
 * @package SOET_College_Project
 * @subpackage Chatbot_Services
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

    /**
     * Initializes core database and AI service dependencies.
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->classifier = new QueryClassifier();
        $this->dbSearch = new DatabaseSearch();
        $this->openAI = new OpenAIService();
    }

    /**
     * Processes a user message and returns the appropriate multi-tier RAG response.
     *
     * @param string $message User query text.
     * @param string $sessionToken Session UUID token for conversation memory.
     * @param string $language ISO language code ('en', 'hi', 'mr').
     * @return array Complete response structure with HTML, source badges, and follow-up chips.
     */
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

        if ($category === 'DATABASE') {
            // PRIORITY 1: LIVE DATABASE (Seats, intake, vacancies, enrollments, real-time records)
            $dbResult = $this->dbSearch->search($cleanMessage, 'SEAT_AVAILABILITY');
            if ($dbResult['found']) {
                $top = $dbResult['top_result'];
                $finalAnswer = $top['content'];
                $sourceUrl = $top['source_url'] ?? '';
                $finalSource = 'DATABASE';
            } else {
                $cms = new ContentManager();
                $metrics = $cms->getCourseSeatMetrics();
                $totalVacant = array_sum(array_column($metrics, 'vacant_seats'));
                $totalIntake = array_sum(array_column($metrics, 'total_intake'));
                $finalAnswer = "Currently, {$totalVacant} engineering seats remain vacant across all programs out of a total intake capacity of {$totalIntake} seats. Admissions are officially open.";
                $finalSource = 'DATABASE';
            }
        } elseif ($category === 'WEBSITE') {
            // PRIORITY 1 -> 2 -> 3 WATERFALL FOR WEBSITE INQUIRIES
            $dbResult = $this->dbSearch->search($cleanMessage, $intent);
            if ($dbResult['found']) {
                $top = $dbResult['top_result'];
                $finalAnswer = $top['content'];
                $sourceUrl = $top['source_url'] ?? '';

                $srcType = $top['source_type'] ?? '';
                if (in_array($srcType, ['seat', 'course', 'department', 'faculty', 'fee', 'admission', 'event', 'notice', 'placement', 'scholarship', 'seats_database', 'fee_database', 'syllabus_database', 'document', 'facility'])) {
                    $finalSource = 'DATABASE';
                } elseif (in_array($srcType, ['website', 'about', 'contact', 'page'])) {
                    $finalSource = 'WEBSITE';
                } else {
                    $finalSource = 'TRAINING_KNOWLEDGE';
                }
            } else {
                // NO-HALLUCINATION FALLBACK: log unanswered + return fallback flag
                $this->logUnanswered($sessionId, $cleanMessage, $intent, $confidence, $session['session_token'], 'WEBSITE');
                $formattedHtml = $this->buildFallbackHtml($cleanMessage);
                $botMsgId = $this->logMessage($sessionId, 'bot', $formattedHtml, 'FALLBACK', $intent, $confidence);
                $chips = [
                    ['label' => '📞 Contact Admissions', 'query' => 'What is the SOET admissions office contact?'],
                    ['label' => '💬 WhatsApp Support', 'query' => 'What is the SOET WhatsApp number?'],
                    ['label' => '🎓 B.Tech Programs', 'query' => 'What programs are offered at SOET?'],
                ];
                return [
                    'success'         => true,
                    'status'          => 'fallback',
                    'is_fallback'     => true,
                    'session_token'   => $session['session_token'],
                    'message_id'      => $botMsgId,
                    'raw_text'        => "Sorry, I couldn't find a reliable answer to your question. Please contact our support team.",
                    'formatted_html'  => $formattedHtml,
                    'category'        => $category,
                    'intent'          => $intent,
                    'source'          => 'FALLBACK',
                    'confidence'      => $confidence,
                    'source_url'      => '',
                    'suggested_chips' => $chips,
                ];
            }
        } elseif ($category === 'TECHNICAL') {
            // PRIORITY 4: TECHNICAL QUESTIONS (General AI Knowledge, ChatGPT style, max 2-3 lines, minimal code)
            $aiRes = $this->openAI->generateResponse($cleanMessage, $this->getHistory($sessionId), $language, $technicalPrompt, 'TECHNICAL');
            if ($aiRes['success']) {
                $finalAnswer = $aiRes['response'];
            } else {
                $finalAnswer = GeneralKnowledgeEngine::resolve($cleanMessage, 'TECHNICAL');
                // If GKE also fails to find anything meaningful, log as unanswered
                if (empty(trim(strip_tags($finalAnswer)))) {
                    $this->logUnanswered($sessionId, $cleanMessage, $intent, $confidence, $session['session_token'], 'TECHNICAL');
                }
            }
            $finalSource = 'GENERAL_AI';
        } elseif ($category === 'GENERAL') {
            // PRIORITY 4: GENERAL KNOWLEDGE QUESTIONS (General AI Knowledge, ChatGPT style, max 2-3 lines)
            $aiRes = $this->openAI->generateResponse($cleanMessage, $this->getHistory($sessionId), $language, $generalPrompt, 'GENERAL');
            if ($aiRes['success']) {
                $finalAnswer = $aiRes['response'];
            } else {
                $finalAnswer = GeneralKnowledgeEngine::resolve($cleanMessage, 'GENERAL');
                if (empty(trim(strip_tags($finalAnswer)))) {
                    $this->logUnanswered($sessionId, $cleanMessage, $intent, $confidence, $session['session_token'], 'GENERAL');
                }
            }
            $finalSource = 'GENERAL_AI';
        } elseif ($category === 'HYBRID' || $category === 'MIXED') {
            // HYBRID FALLBACK: Database/Website/Training Knowledge first + general/technical AI explanation
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
                $finalSource = 'MIXED';
            } else {
                $finalAnswer = $techExplanation;
                $finalSource = 'GENERAL_AI';
            }
        }

        // If finalAnswer is empty across any path, trigger fallback
        if (empty(trim(strip_tags($finalAnswer)))) {
            $this->logUnanswered($sessionId, $cleanMessage, $intent, $confidence, $session['session_token'], $category);
            $formattedHtml = $this->buildFallbackHtml($cleanMessage);
            $botMsgId = $this->logMessage($sessionId, 'bot', $formattedHtml, 'FALLBACK', $intent, $confidence);
            return [
                'success'         => true,
                'status'          => 'fallback',
                'is_fallback'     => true,
                'session_token'   => $session['session_token'],
                'message_id'      => $botMsgId,
                'raw_text'        => "Sorry, I couldn't find a reliable answer. Please contact our support team.",
                'formatted_html'  => $formattedHtml,
                'category'        => $category,
                'intent'          => $intent,
                'source'          => 'FALLBACK',
                'confidence'      => $confidence,
                'source_url'      => '',
                'suggested_chips' => [
                    ['label' => '📞 Contact Admissions', 'query' => 'What is the SOET admissions office contact?'],
                    ['label' => '💬 WhatsApp Support',    'query' => 'What is the SOET WhatsApp number?'],
                    ['label' => '🎓 B.Tech Programs',     'query' => 'What programs are offered at SOET?'],
                ],
            ];
        }

        // Universal confidence-based fallback safety net (below 0.15 threshold = unreliable)
        if ($confidence < 0.15 && $category === 'WEBSITE') {
            $this->logUnanswered($sessionId, $cleanMessage, $intent, $confidence, $session['session_token'], $category);
            $formattedHtml = $this->buildFallbackHtml($cleanMessage);
            $botMsgId = $this->logMessage($sessionId, 'bot', $formattedHtml, 'FALLBACK', $intent, $confidence);
            return [
                'success'         => true,
                'status'          => 'fallback',
                'is_fallback'     => true,
                'session_token'   => $session['session_token'],
                'message_id'      => $botMsgId,
                'raw_text'        => "Sorry, I couldn't find a reliable answer. Please contact our support team.",
                'formatted_html'  => $formattedHtml,
                'category'        => $category,
                'intent'          => $intent,
                'source'          => 'FALLBACK',
                'confidence'      => $confidence,
                'source_url'      => '',
                'suggested_chips' => [
                    ['label' => '📞 Contact Admissions', 'query' => 'What is the SOET admissions office contact?'],
                    ['label' => '🎓 B.Tech Programs',    'query' => 'What programs are offered at SOET?'],
                ],
            ];
        }

        // Format final HTML response (clean, zero internal database queries or model names mentioned)
        $formattedHtml = ResponseFormatter::format($finalAnswer, $finalSource, $sourceUrl);

        // Log bot message
        $botMsgId = $this->logMessage($sessionId, 'bot', $formattedHtml, $finalSource, $intent, $confidence);

        // Generate dynamic follow-up chips
        $chips = $this->generateFollowUpChips($intent, $cleanMessage, $finalSource);

        return [
            'success'         => true,
            'status'          => 'success',
            'is_fallback'     => false,
            'session_token'   => $session['session_token'],
            'message_id'      => $botMsgId,
            'raw_text'        => $finalAnswer,
            'formatted_html'  => $formattedHtml,
            'category'        => $category,
            'intent'          => $intent,
            'source'          => $finalSource,
            'confidence'      => $confidence,
            'source_url'      => $sourceUrl,
            'suggested_chips' => $chips,
        ];
    }

    /**
     * Build the standardised fallback HTML shown to the user
     */
    private function buildFallbackHtml(string $question): string
    {
        return <<<HTML
<div class="cb-fallback-msg">
  <div class="cb-fallback-icon"><i class="fa-solid fa-circle-exclamation"></i></div>
  <p class="cb-fallback-text mb-3">
    Sorry, I couldn't find a reliable answer. Please contact our support team.
  </p>
  <div class="cb-fallback-actions">
    <a href="contact.php" class="cb-fallback-btn cb-fallback-btn-primary" target="_blank">
      <i class="fa-solid fa-headset me-1"></i> Contact Support
    </a>
  </div>
</div>
HTML;
    }

    /**
     * Generates dynamic contextual follow-up query suggestion chips based on intent.
     *
     * @param string $intent Detected user query intent.
     * @param string $message Original user message.
     * @param string $source Selected response source.
     * @return array List of suggestions with label and query strings.
     */
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

    /**
     * Stores user feedback rating (positive/negative) and optional comments.
     *
     * @param int $messageId Database ID of evaluated bot message.
     * @param string $rating 'positive' or 'negative'.
     * @param string $comment Optional feedback remark text.
     * @return bool Success status.
     */
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

    /**
     * Retrieves an existing chat session or creates a new one with a unique token.
     *
     * @param string $token Session token.
     * @param string $language Preferred language.
     * @return array Session record array.
     */
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

    /**
     * Logs an individual chat message entry into chat_messages table.
     *
     * @param int $sessionId Database session ID.
     * @param string $sender 'user' or 'bot'.
     * @param string $msg Message content text/HTML.
     * @param string $source Source tag ('system', 'database', 'openai', etc.).
     * @param string $intent Classified intent.
     * @param float $conf Confidence score.
     * @return int Inserted message row ID.
     */
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

    /**
     * Logs a low-confidence or unanswerable query to unanswered_questions table for administrative review.
     *
     * @param int $sessionId Chat session ID.
     * @param string $question Unresolved user question.
     * @param string $intent Classified intent.
     * @param float $conf Confidence score.
     * @param string $sessionToken Session UUID token.
     * @param string $sourceCategory Source category classification.
     * @return void
     */
    private function logUnanswered(int $sessionId, string $question, string $intent, float $conf, string $sessionToken = '', string $sourceCategory = 'WEBSITE'): void
    {
        // Capture full request context
        $ip       = Security::getClientIP();
        $ua       = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
        $pageUrl  = substr($_SERVER['HTTP_REFERER'] ?? ($_SERVER['HTTP_ORIGIN'] ?? ''), 0, 500);

        // Avoid duplicate pending entries for same question in last 10 mins
        $existing = $this->db->fetchOne(
            "SELECT id FROM unanswered_questions WHERE question = ? AND status = 'pending' AND created_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)",
            [$question]
        );
        if ($existing) {
            return; // Skip duplicate
        }

        $this->db->insert('unanswered_questions', [
            'session_id'         => $sessionId,
            'ip_address'         => $ip,
            'user_agent'         => $ua,
            'page_url'           => $pageUrl,
            'conversation_token' => $sessionToken,
            'question'           => $question,
            'category'           => strtolower($intent),
            'source_category'    => $sourceCategory,
            'confidence'         => $conf,
            'status'             => 'pending',
        ]);
    }

    /**
     * Retrieves all recorded back-and-forth messages in a session for conversational memory.
     *
     * @param int $sessionId Database session ID.
     * @return array List of message associative arrays ordered chronologically.
     */
    public function getHistory(int $sessionId): array
    {
        return $this->db->fetchAll(
            "SELECT sender, message, source, intent, created_at FROM chat_messages WHERE session_id = ? ORDER BY id ASC",
            [$sessionId]
        );
    }
}
