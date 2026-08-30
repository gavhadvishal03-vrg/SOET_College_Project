<?php
/**
 * 🤖 CampusAI — Centralized Natural Language & Synonym Mapper Engine
 * Translates natural language variations, synonyms, spelling mistakes,
 * and abbreviations into standard canonical intents & queries.
 */

class SynonymMapper
{
    private array $config;

    public function __construct()
    {
        $configFile = __DIR__ . '/../config/synonyms.php';
        if (file_exists($configFile)) {
            $this->config = require $configFile;
        } else {
            $this->config = [];
        }
    }

    /**
     * Preprocess & Normalize User Query (Stage 1)
     */
    public function normalizeQuery(string $query): string
    {
        $clean = mb_strtolower(trim($query));
        
        // Remove excessive punctuation but preserve alphanumeric & spaces
        $clean = preg_replace('/[^\w\s\+\#\-\.\']/u', ' ', $clean);
        $clean = preg_replace('/\s+/', ' ', $clean);

        // 1. Correct Common Spelling Mistakes
        $spellingMap = $this->config['spelling_corrections'] ?? [];
        foreach ($spellingMap as $wrong => $right) {
            $clean = preg_replace('/\b' . preg_quote($wrong, '/') . '\b/i', $right, $clean);
        }

        // 2. Normalize Plurals to Singular Terms
        $pluralMap = $this->config['plural_normalizations'] ?? [];
        foreach ($pluralMap as $plural => $singular) {
            $clean = preg_replace('/\b' . preg_quote($plural, '/') . '\b/i', $singular, $clean);
        }

        return trim($clean);
    }

    /**
     * Classify Intent using Natural Language Rules & Context Disambiguation (Stage 2)
     */
    public function detectIntent(string $rawQuery): array
    {
        $rawClean = mb_strtolower(trim($rawQuery));
        $normalized = $this->normalizeQuery($rawQuery);

        // Check if query is vague/unclear or too short
        if (mb_strlen($normalized) <= 2 || in_array($normalized, ['tell me', 'what', 'how', 'info', 'information', 'details', 'help'])) {
            return [
                'intent' => 'UNCLEAR',
                'normalized_query' => $normalized,
                'confidence' => 0.3,
                'is_ambiguous' => true
            ];
        }

        // 1. Context Disambiguation Rules
        $contextRules = $this->config['context_disambiguation'] ?? [];
        foreach ($contextRules as $keyword => $rule) {
            if (mb_strpos($normalized, $keyword) !== false) {
                $hasTech = false;
                $hasCollege = false;

                foreach ($rule['tech_triggers'] as $tt) {
                    if (mb_strpos($normalized, $tt) !== false) {
                        $hasTech = true;
                        break;
                    }
                }
                foreach ($rule['college_triggers'] as $ct) {
                    if (mb_strpos($normalized, $ct) !== false) {
                        $hasCollege = true;
                        break;
                    }
                }

                if ($hasTech && !$hasCollege) {
                    return [
                        'intent' => 'CODING',
                        'normalized_query' => $normalized,
                        'confidence' => 0.95,
                        'is_ambiguous' => false
                    ];
                }
            }
        }

        // 2. Pattern Matching against Intent Rules
        $intentPatterns = $this->config['intent_patterns'] ?? [];
        foreach ($intentPatterns as $intentName => $item) {
            foreach ($item['patterns'] as $pattern) {
                if (preg_match($pattern, $rawClean) || preg_match($pattern, $normalized)) {
                    return [
                        'intent' => $intentName,
                        'normalized_query' => $normalized,
                        'confidence' => 0.95,
                        'is_ambiguous' => false
                    ];
                }
            }
        }

        return [
            'intent' => 'GENERAL_AI',
            'normalized_query' => $normalized,
            'confidence' => 0.5,
            'is_ambiguous' => false
        ];
    }
}
