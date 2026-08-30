<?php
/**
 * OpenAI API Integration Service via PHP cURL
 * Secure server-side cURL communication with OpenAI Chat Completions API
 */

class OpenAIService
{
    private Database $db;
    private string $apiKey;
    private string $model;
    private float $temperature;
    private int $maxTokens;
    private string $systemPrompt;
    private bool $isEnabled;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->loadSettings();
    }

    private function loadSettings(): void
    {
        $settingsRaw = $this->db->fetchAll("SELECT * FROM chatbot_settings");
        $settings = [];
        foreach ($settingsRaw as $s) {
            $settings[$s['setting_key']] = $s['setting_value'];
        }

        $this->apiKey = trim($settings['openai_api_key'] ?? getenv('OPENAI_API_KEY') ?: '');
        $this->model = $settings['openai_model'] ?? 'gpt-4o-mini';
        $this->temperature = (float)($settings['openai_temperature'] ?? 0.7);
        $this->maxTokens = (int)($settings['max_tokens'] ?? 800);
        $this->isEnabled = ($settings['enable_openai'] ?? '1') === '1';
        $this->systemPrompt = $settings['system_prompt'] ?? 'You are 🤖 CampusAI, the official intelligent assistant for SOET (School of Engineering & Technology), MGM University. You answer college-related questions using verified institutional data provided in the prompt context. For general/educational questions, provide clear, accurate definitions and explanations. Always be helpful and never refuse to answer. Keep responses concise but informative.';
    }

    public function generateResponse(string $userPrompt, array $conversationHistory = [], string $language = 'en', ?string $customSystemPrompt = null): array
    {
        if (!$this->isEnabled) {
            return [
                'success' => false,
                'message' => 'OpenAI AI integration is currently disabled by administrator.'
            ];
        }

        if (empty($this->apiKey)) {
            // Free Built-in AI Knowledge Engine (Zero Cost & 100% Offline Reliable)
            $freeAnswer = GeneralKnowledgeEngine::resolve($userPrompt);
            return [
                'success' => true,
                'response' => $freeAnswer,
                'source' => 'openai'
            ];
        }

        $langInstruction = "";
        if ($language === 'hi') {
            $langInstruction = " Responding language: Hindi. Keep English technical terms in Latin script where helpful.";
        } elseif ($language === 'mr') {
            $langInstruction = " Responding language: Marathi. Keep English technical terms in Latin script where helpful.";
        }

        $sysPrompt = $customSystemPrompt ?: $this->systemPrompt;
        $messages = [
            ['role' => 'system', 'content' => $sysPrompt . $langInstruction]
        ];

        // Include conversation context (last 6 messages)
        $contextSlice = array_slice($conversationHistory, -6);
        foreach ($contextSlice as $msg) {
            $role = ($msg['sender'] === 'user') ? 'user' : 'assistant';
            $messages[] = ['role' => $role, 'content' => $msg['message']];
        }

        $messages[] = ['role' => 'user', 'content' => $userPrompt];

        $payload = [
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => $this->temperature,
            'max_tokens' => $this->maxTokens
        ];

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey
            ],
            CURLOPT_TIMEOUT => 20
        ]);

        $responseJson = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlError) {
            return [
                'success' => false,
                'message' => 'Network error connecting to OpenAI API: ' . $curlError
            ];
        }

        if ($httpCode !== 200) {
            $decodedErr = json_decode($responseJson, true);
            $errMsg = $decodedErr['error']['message'] ?? "OpenAI API returned HTTP status {$httpCode}";
            return [
                'success' => false,
                'message' => 'AI Service Notice: ' . $errMsg
            ];
        }

        $decoded = json_decode($responseJson, true);
        $botMessage = $decoded['choices'][0]['message']['content'] ?? '';

        return [
            'success' => true,
            'response' => trim($botMessage),
            'usage' => $decoded['usage'] ?? []
        ];
    }

    public function isKeyConfigured(): bool
    {
        return !empty($this->apiKey);
    }
}
