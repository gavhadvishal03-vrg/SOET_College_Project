<?php
/**
 * CampusAI — Smart Master Chatbot Orchestrator (Engine v2)
 *
 * Coordinates the full conversational pipeline:
 * 1. Query Normalization: Lowercases, strips punctuation, normalizes spellings and plurals.
 * 2. Multi-lingual Detection: Identifies English, Hindi, and Marathi inputs.
 * 3. Contextual Resolution: Resolves pronouns ("its fee", "how many seats in that course") using conversation history.
 * 4. Special Domain Inquiries: Direct routing for contacts/location and campus blog/news articles.
 * 5. Core ChatService Delegation: Executes RAG database searches across KB, FAQs, courses, faculty, placements, and events.
 * 6. Hallucination Guard: Validates that generative AI responses do not invent numbers or conflicting dates.
 * 7. Fallback & Contact CTA Delivery: Provides helpful routing when confident matches are unavailable.
 *
 * @package SOET_College_Project
 * @subpackage Chatbot_Engine
 */

require_once __DIR__ . '/../../core/bootstrap.php';
require_once __DIR__ . '/../services/ChatService.php';
require_once __DIR__ . '/QueryNormalizer.php';
require_once __DIR__ . '/ContextManager.php';
require_once __DIR__ . '/HallucinationGuard.php';
require_once __DIR__ . '/ContactRetriever.php';
require_once __DIR__ . '/BlogNewsRetriever.php';
require_once __DIR__ . '/CampusAIModel.php';

class SmartChatEngine
{
    private ChatService $chatService;
    private QueryNormalizer $normalizer;
    private ContextManager $contextManager;
    private HallucinationGuard $guard;
    private ContactRetriever $contactRetriever;
    private BlogNewsRetriever $blogNews;
    private Database $db;

    /**
     * Initializes engine dependencies and database connections.
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->chatService = new ChatService();
        $this->normalizer = new QueryNormalizer();
        $this->contextManager = new ContextManager();
        $this->guard = new HallucinationGuard();
        $this->contactRetriever = new ContactRetriever();
        $this->blogNews = new BlogNewsRetriever();
    }

    /**
     * Processes an incoming visitor chat message through the complete RAG pipeline.
     *
     * @param string $message Raw query string from user.
     * @param string $sessionToken Unique session token for context tracking.
     * @param string $language Preferred response language code (default 'en').
     * @return array Formatted response payload with HTML, text, source, intent, and suggestions.
     */
    public function processMessage(string $message, string $sessionToken = '', string $language = 'en'): array
    {
        $rawMessage = trim($message);
        if (empty($rawMessage)) {
            return [
                'success'         => true,
                'status'          => 'success',
                'is_fallback'     => false,
                'formatted_html'  => '<p>Please enter a question or choose one of the suggestions below.</p>',
                'raw_text'        => 'Please enter a question.',
                'source'          => 'system',
                'intent'          => 'GENERAL',
                'suggested_chips' => $this->chatService->generateFollowUpChips('GREETING', '', 'system')
            ];
        }

        try {
            // 1. Normalize Query & Multi-Lingual Handling
            $normalizedData = $this->normalizer->normalize($rawMessage);
            $workingQuery = $normalizedData['normalized'];
            $detectedLang = $normalizedData['detected_language'] !== 'en' ? $normalizedData['detected_language'] : $language;

            // 2. Resolve Contextual Pronouns & Follow-Ups using session history
            $sessionId = 0;
            if (!empty($sessionToken)) {
                $sess = $this->db->fetchOne("SELECT id FROM chat_sessions WHERE session_token = ?", [$sessionToken]);
                if ($sess) {
                    $sessionId = (int)$sess['id'];
                    $context = $this->contextManager->getContext($sessionId, 4);
                    $workingQuery = $this->contextManager->resolveQuery($workingQuery, $context);
                }
            }

            // 3. Check Contact / Location Specific Inquiries
            if ($this->contactRetriever->isContactQuery($workingQuery) && !preg_match('/(fee|fees|admission|course|program|seat)/i', $workingQuery)) {
                $contactContent = $this->contactRetriever->buildContactResponse();
                $formattedHtml = ResponseFormatter::format($contactContent, 'WEBSITE', '/contact.php');
                
                // Process through existing ChatService to log session and messages cleanly
                $res = $this->chatService->processMessage("What is the contact information of SOET?", $sessionToken, $detectedLang);
                $res['raw_text'] = $contactContent;
                $res['formatted_html'] = $formattedHtml;
                $res['source'] = 'WEBSITE';
                $res['intent'] = 'CONTACT';
                $res['is_fallback'] = false;
                $res['engine_version'] = 'v2';
                return $res;
            }

            // 4. Check Blog & News Queries
            if ($this->blogNews->isBlogNewsQuery($workingQuery)) {
                $blogHit = $this->blogNews->search($workingQuery);
                if ($blogHit && $blogHit['found']) {
                    $formattedHtml = ResponseFormatter::format($blogHit['content'], 'WEBSITE', $blogHit['source_url']);
                    $res = $this->chatService->processMessage("Tell me about latest college updates", $sessionToken, $detectedLang);
                    $res['raw_text'] = $blogHit['content'];
                    $res['formatted_html'] = $formattedHtml;
                    $res['source'] = 'WEBSITE';
                    $res['intent'] = 'NEWS';
                    $res['is_fallback'] = false;
                    $res['engine_version'] = 'v2';
                    return $res;
                }
            }

            // 5. Delegate to Core ChatService with the resolved query
            $res = $this->chatService->processMessage($workingQuery, $sessionToken, $detectedLang);

            // 6. Anti-Hallucination & Validation Layer
            $validation = $this->guard->validate(
                $res['raw_text'] ?? '',
                $res['source'] ?? 'GENERAL_AI',
                $res['intent'] ?? 'GENERAL'
            );

            if (!$validation['is_safe'] && $validation['risk_level'] === 'high') {
                $safeAnswer = $this->guard->getSafeResponse($res['intent'] ?? 'GENERAL');
                $res['raw_text'] = strip_tags($safeAnswer);
                $res['formatted_html'] = '<div class="cb-fallback-msg"><div class="cb-fallback-icon"><i class="fa-solid fa-shield-halved"></i></div><p class="cb-fallback-text">' . $safeAnswer . '</p><div class="cb-fallback-actions"><a href="contact.php" class="cb-fallback-btn cb-fallback-btn-primary"><i class="fa-solid fa-headset me-1"></i> Contact Support</a></div></div>';
                $res['is_fallback'] = true;
                $res['status'] = 'fallback';
            }

            // 7. Sentiment & User Frustration Detection (Auto-Escalation)
            if (preg_match('/\b(useless|worst|terrible|bad bot|stupid|fake|rubbish|hate this|annoying|not helpful|waste of time)\b/i', $rawMessage)) {
                $res['formatted_html'] .= '<div class="alert alert-warning border border-warning rounded-3 mt-2 p-2.5 small mb-0"><div class="d-flex align-items-center gap-2"><i class="fa-solid fa-headset text-warning fs-5"></i><div><strong>Need immediate personal help?</strong> Our admission counselor is available at <strong>+91 9371714253</strong> or <a href="contact.php" class="alert-link">contact.php</a>.</div></div></div>';
            }

            $res['engine_version'] = 'v3';
            $res['resolved_query'] = $workingQuery;
            $res['model_engine'] = 'CampusAI Neural Engine v3';

            return $res;

        } catch (Throwable $e) {
            // Fallback gracefully to basic ChatService
            try {
                $res = $this->chatService->processMessage($rawMessage, $sessionToken, $language);
                $res['engine_version'] = 'v3_rescue';
                $res['model_engine'] = 'CampusAI Fallback Engine';
                return $res;
            } catch (Throwable $e2) {
                return [
                    'success'        => false,
                    'status'         => 'error',
                    'is_fallback'    => true,
                    'formatted_html' => '<p class="text-danger">Sorry, a temporary error occurred. Please contact the admissions office at 0240-6674999.</p>',
                    'raw_text'       => 'Temporary system error.',
                    'source'         => 'system',
                    'intent'         => 'GENERAL',
                    'suggested_chips'=> [],
                    'engine_version' => 'v3_error'
                ];
            }
        }
    }
}
