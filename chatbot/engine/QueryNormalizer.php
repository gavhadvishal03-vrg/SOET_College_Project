<?php
/**
 * CampusAI — Smart Query Normalizer & Multi-Lingual Understanding Engine
 *
 * Pre-processes natural language inputs prior to RAG search:
 * 1. Multi-lingual Detection: Identifies Devanagari script and transliterated Hindi/Marathi keywords.
 * 2. Transliteration Mapping: Converts Marathi/Hindi phrases to canonical English search terms.
 * 3. Short Query Expansion: Expands one-word inputs ("fees", "admission", "cse") into complete interrogative queries.
 * 4. Academic Acronym Expansion: Resolves "CSE", "ECE", "MECH", "AIML", "DSE" to full department program titles.
 * 5. Typo & Levenshtein Correction: Corrects misspelt technical and college vocabulary.
 *
 * @package SOET_College_Project
 * @subpackage Chatbot_Engine
 */

class QueryNormalizer
{
    private array $vocabulary = [
        'admission', 'admissions', 'placement', 'placements', 'scholarship', 'scholarships',
        'eligibility', 'department', 'departments', 'faculty', 'professor', 'professors',
        'tuition', 'semester', 'semesters', 'university', 'computer', 'electrical',
        'mechanical', 'electronics', 'engineering', 'syllabus', 'curriculum',
        'examination', 'laboratory', 'infrastructure', 'hostel', 'canteen', 'library',
        'contact', 'location', 'package', 'packages', 'intake', 'capacity', 'vacant', 'vacancy'
    ];

    private array $multiLingualReplacements = [
        // Hindi / Marathi phrases -> English queries
        '/\b(kitni|kiti|kitna|how much)\s+(fees?|fee|shulk|fe)\b/i' => 'how much fee',
        '/\b(fees?|fee)\s+(kitni|kiti|kitna|ahe|aahe|hai)\b/i' => 'how much fee',
        '/\b(admission|pravesh)\s+(kaise|kasa|kashi|hoti|ghaycha|milnar|milel)\b/i' => 'how to apply for admission',
        '/\b(kaise|kasa|kashi)\s+(admission|pravesh)\s*(gheu|milel|hogi)?\b/i' => 'how to apply for admission',
        '/\b(kab|kevha|keva)\s+(hogi|chalu|ahe|aahe)\s*(admission|pravesh)?\b/i' => 'when is admission',
        '/\b(kaun\s*sa|konta|konti)\s+(course|branch|program)\b/i' => 'which course',
        '/\b(mala|mujhe)\s+(admission|information|mahiti|jankari)\s*(havi|chahiye|pahije)?\b/i' => 'i want admission details',
        '/\b(kuthe|kaha|kidhar)\s+(ahe|aahe|hai|located)\b/i' => 'where is college location',
        '/\b(kiti|kitne)\s+(seats?|jaga)\s*(ahet|hai|khali)?\b/i' => 'how many seats vacant',
        '/\b(koni|kaun)\s+(shikavto|padhate|teachers?)\b/i' => 'who is the faculty teacher',
        '/\b(milnar|milel|milega)\s+(ka|kya)\b/i' => 'is available',
        '/\b(hostel|mess)\s+(ahe|aahe|hai|suvidha)\b/i' => 'hostel facilities',
    ];

    private array $abbreviationMap = [
        'cse'   => 'Computer Science and Engineering (CSE)',
        'ece'   => 'Electronics and Telecommunication Engineering (ECE)',
        'ee'    => 'Electrical Engineering (EE)',
        'mech'  => 'Mechanical Engineering (MECH)',
        'civil' => 'Civil Engineering (CIVIL)',
        'ash'   => 'Applied Science and Humanities (ASH)',
        'vlsi'  => 'VLSI Design Electronics',
        'iot'   => 'Internet of Things (IoT)',
        'aiml'  => 'Artificial Intelligence and Machine Learning (AI & ML)',
        'ds'    => 'Data Science',
        'dse'   => 'Direct Second Year Engineering (DSE)',
        'int'   => 'Integrated B.Tech',
    ];

    private array $shortQueryExpansions = [
        'fees'        => 'What is the fee structure for B.Tech courses?',
        'fee'         => 'What is the fee structure for B.Tech courses?',
        'fees?'       => 'What is the fee structure for B.Tech courses?',
        'admission'   => 'What is the B.Tech admission process and eligibility criteria?',
        'admissions'  => 'What is the B.Tech admission process and eligibility criteria?',
        'admission?'  => 'What is the B.Tech admission process and eligibility criteria?',
        'placement'   => 'What are the placement records and highest package at SOET?',
        'placements'  => 'What are the placement records and highest package at SOET?',
        'faculty'     => 'Who are the faculty members and professors at SOET?',
        'courses'     => 'What engineering courses and programs are offered at SOET?',
        'programs'    => 'What engineering courses and programs are offered at SOET?',
        'hostel'      => 'What are the hostel and mess facilities at SOET?',
        'contact'     => 'What is the college contact number, email, and address?',
        'location'    => 'Where is SOET MGM University located and how to reach?',
        'scholarship' => 'What scholarship schemes are available for students?',
        'seats'       => 'What is the seat availability and vacant seats in B.Tech?',
        'seat'        => 'What is the seat availability and vacant seats in B.Tech?',
        'dean'        => 'Who is the Dean and Director of SOET MGM University?',
        'director'    => 'Who is the Dean and Director of SOET MGM University?',
        'events'      => 'What are the upcoming college events and activities?',
        'notices'     => 'What are the latest administrative circulars and notices?',
    ];

    public function normalize(string $query): array
    {
        $original = trim($query);
        $clean = mb_strtolower($original);

        // Detect language
        $lang = 'en';
        if (preg_match('/[\x{0900}-\x{097F}]/u', $original) || preg_match('/\b(kiti|aahe|ahe|kasa|kashi|kuthe|mala|pahije|kitni|kaise|kaun|kaha|mujhe|chahiye|shulk|pravesh)\b/i', $original)) {
            $lang = preg_match('/\b(kiti|aahe|ahe|kasa|kashi|kuthe|mala|pahije|gheu|milel)\b/i', $original) ? 'mr' : 'hi';
        }

        // Check exact short query expansion
        $trimmedLower = trim($clean, "? \t\n\r\0\x0B");
        $isShort = false;
        if (isset($this->shortQueryExpansions[$trimmedLower])) {
            return [
                'original'          => $original,
                'normalized'        => $this->shortQueryExpansions[$trimmedLower],
                'detected_language' => $lang,
                'expanded_terms'    => [$trimmedLower],
                'is_short_query'    => true
            ];
        }

        // Single course code query check (e.g. "cse", "civil", "vlsi")
        if (isset($this->abbreviationMap[$trimmedLower])) {
            return [
                'original'          => $original,
                'normalized'        => 'Tell me about ' . $this->abbreviationMap[$trimmedLower] . ' program details and fees',
                'detected_language' => $lang,
                'expanded_terms'    => [$trimmedLower, $this->abbreviationMap[$trimmedLower]],
                'is_short_query'    => true
            ];
        }

        // Apply Multilingual Replacements
        foreach ($this->multiLingualReplacements as $pattern => $replacement) {
            $clean = preg_replace($pattern, $replacement, $clean);
        }

        // Remove non-alphanumeric except basic symbols
        $clean = preg_replace('/[^\w\s\+\#\-\.\']/u', ' ', $clean);
        $clean = preg_replace('/\s+/', ' ', $clean);
        $words = explode(' ', trim($clean));

        // Typo & Levenshtein correction for words >= 5 chars
        $correctedWords = [];
        $expandedTerms = [];

        foreach ($words as $word) {
            $wordLower = strtolower($word);

            // Check if abbreviation can be expanded
            if (isset($this->abbreviationMap[$wordLower])) {
                $expandedTerms[] = $this->abbreviationMap[$wordLower];
            }

            if (strlen($wordLower) >= 5 && !in_array($wordLower, $this->vocabulary)) {
                $bestMatch = null;
                $minDistance = 999;

                foreach ($this->vocabulary as $vocab) {
                    $dist = levenshtein($wordLower, $vocab);
                    if ($dist <= 2 && $dist < $minDistance) {
                        $minDistance = $dist;
                        $bestMatch = $vocab;
                    }
                }

                if ($bestMatch && $minDistance <= 2) {
                    $correctedWords[] = $bestMatch;
                    continue;
                }
            }

            $correctedWords[] = $word;
        }

        $normalizedQuery = implode(' ', $correctedWords);

        // Append abbreviation context if found
        if (!empty($expandedTerms)) {
            $normalizedQuery .= ' (' . implode(', ', array_unique($expandedTerms)) . ')';
        }

        return [
            'original'          => $original,
            'normalized'        => trim($normalizedQuery),
            'detected_language' => $lang,
            'expanded_terms'    => $expandedTerms,
            'is_short_query'    => count($words) <= 2
        ];
    }
}
