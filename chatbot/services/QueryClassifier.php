<?php
/**
 * Query Classification & Routing Engine
 * Identifies query intents, keywords, confidence, and target response source (Database vs OpenAI vs Hybrid)
 */

class QueryClassifier
{
    private array $soetKeywords = [
        'soet', 'mgm', 'mgmu', 'college', 'university', 'campus', 'dean', 'director',
        'parminder', 'kaur', 'dhingra', 'admission', 'admissions', 'apply', 'application', 'eligibility',
        'fees', 'fee', 'tuition', 'scholarship', 'course', 'courses', 'program', 'programs',
        'btech', 'mtech', 'phd', 'cse', 'ece', 'mech', 'mechanical', 'civil', 'department',
        'departments', 'faculty', 'professor', 'teacher', 'hod', 'placement', 'placements',
        'recruiter', 'recruiters', 'salary', 'package', 'ctc', 'tcs', 'infosys', 'wipro',
        'event', 'events', 'notice', 'notices', 'news', 'blog', 'blogs', 'gallery',
        'lab', 'labs', 'laboratory', 'infrastructure', 'hostel', 'library', 'contact',
        'address', 'location', 'phone', 'faq', 'cutoff', 'cet', 'jee',
        'engineering', 'technology', 'sports', 'nss', 'ncc', 'hackathon', 'seminar',
        'alumni', 'convocation', 'canteen', 'bus', 'transport', 'research', 'accreditation',
        'naac', 'aicte', 'affiliated', 'graduation', 'examination', 'exam', 'result',
        'marksheet', 'attendance', 'timetable', 'schedule'
    ];

    // Only keywords that indicate the user is asking about a GENERAL/TECHNICAL concept (not college)
    private array $generalAiKeywords = [
        'explain', 'how to write', 'code example', 'java', 'python', 'c++',
        'html', 'css', 'javascript', 'sql', 'database concept', 'normalization', 'binary search',
        'algorithm', 'blockchain', 'crypto', 'machine learning', 'deep learning', 'iot',
        'quantum', 'math', 'calculus', 'matrix', 'career advice',
        'prepare for interview', 'rsa', 'encryption', 'dsa', 'sorting', 'stack', 'queue',
        'artificial intelligence', 'cloud computing', 'cybersecurity', 'data science',
        'web development', 'react', 'angular', 'node', 'docker', 'kubernetes', 'git',
        'agile', 'scrum', 'devops', 'linux', 'operating system', 'compiler', 'networking',
        'tcp', 'http', 'graphql', 'microservices', 'linked list', 'tree', 'graph',
        'dynamic programming', 'recursion', 'oops', 'inheritance', 'polymorphism'
    ];

    // College-specific intents that should ALWAYS go to database, never hybrid
    private array $collegeIntents = [
        'ADMISSION', 'SEAT_AVAILABILITY', 'PROGRAM', 'DEPARTMENT', 'FACULTY', 'FEE', 'PLACEMENT',
        'EVENT', 'NOTICE', 'CONTACT', 'EXAM', 'FACILITY', 'RESEARCH'
    ];

    private array $greetingPatterns = [
        'hi', 'hello', 'hey', 'good morning', 'good afternoon', 'good evening', 'namaste', 'greetings'
    ];

    private array $spellingMap = [
        'cmputer' => 'computer',
        'scnce' => 'science',
        'faclty' => 'faculty',
        'admssion' => 'admission',
        'stucture' => 'structure',
        'cource' => 'course',
        'cources' => 'courses',
        'plcmnt' => 'placement',
        'plcmnts' => 'placements',
        'conctact' => 'contact',
        'eligiblity' => 'eligibility',
        'dept' => 'department'
    ];

    private SynonymMapper $synonymMapper;

    public function __construct()
    {
        require_once __DIR__ . '/SynonymMapper.php';
        $this->synonymMapper = new SynonymMapper();
    }

    public function classify(string $query): array
    {
        $cleanQuery = $this->synonymMapper->normalizeQuery($query);

        // Detect Greeting
        if (in_array($cleanQuery, $this->greetingPatterns) || preg_match('/^(hi|hello|hey|good morning|good afternoon|good evening|namaste)\b/i', $cleanQuery)) {
            return [
                'intent' => 'GREETING',
                'confidence' => 1.0,
                'source' => 'system',
                'description' => 'User greeting'
            ];
        }

        // Run Stage 2 Synonym & Intent Detection Engine
        $mapped = $this->synonymMapper->detectIntent($query);
        $intent = $mapped['intent'];

        // Ambiguous / Unclear Query Handling
        if ($intent === 'UNCLEAR') {
            return [
                'intent' => 'UNCLEAR',
                'confidence' => 0.3,
                'source' => 'system',
                'description' => 'Unclear query requiring clarification'
            ];
        }

        $hasSoet = $this->checkKeywordMatches($cleanQuery, $this->soetKeywords);
        $hasGeneral = $this->checkKeywordMatches($cleanQuery, $this->generalAiKeywords);

        // KEY RULE: If the intent is college-specific, ALWAYS route to database
        if (in_array($intent, $this->collegeIntents)) {
            return [
                'intent' => $intent,
                'confidence' => 0.95,
                'source' => 'database',
                'description' => 'SOET Institutional Knowledge Base Query'
            ];
        }

        // Only mark as hybrid if both keyword sets match AND the intent is NOT college-specific
        if ($hasSoet && $hasGeneral) {
            return [
                'intent' => $intent,
                'confidence' => 0.88,
                'source' => 'hybrid',
                'description' => 'Mixed query requiring SOET DB + General AI explanation'
            ];
        }

        if ($hasSoet) {
            return [
                'intent' => $intent,
                'confidence' => 0.94,
                'source' => 'database',
                'description' => 'SOET Institutional Knowledge Base Query'
            ];
        }

        return [
            'intent' => $intent,
            'confidence' => 0.90,
            'source' => 'openai',
            'description' => 'General Educational / Programming Query'
        ];
    }

    private function checkKeywordMatches(string $query, array $keywords): bool
    {
        foreach ($keywords as $kw) {
            if (mb_strpos($query, $kw) !== false) {
                return true;
            }
        }
        return false;
    }

    private function detectIntentCategory(string $query): string
    {
        if (preg_match('/(seat|seats|intake|capacity|vacant|vacancy|filled|left in|how many seats|admission open|seats available|seat status)/i', $query)) {
            return 'SEAT_AVAILABILITY';
        }
        if (preg_match('/(admission|apply|eligibility|percentage|12th|cet|jee|criteria|form)/i', $query)) {
            return 'ADMISSION';
        }
        if (preg_match('/(course|btech|mtech|degree|syllabus|curriculum)/i', $query)) {
            return 'PROGRAM';
        }
        if (preg_match('/(department|computer science|civil|mech|ece)/i', $query)) {
            return 'DEPARTMENT';
        }
        if (preg_match('/(faculty|professor|teacher|hod|dean|director|staff|rajesh)/i', $query)) {
            return 'FACULTY';
        }
        if (preg_match('/(fee|fees|tuition|cost|amount|scholarship|concession)/i', $query)) {
            return 'FEE';
        }
        if (preg_match('/(placement|placements|salary|package|ctc|recruiter|company)/i', $query)) {
            return 'PLACEMENT';
        }
        if (preg_match('/(event|fest|workshop|calendar|competition)/i', $query)) {
            return 'EVENT';
        }
        if (preg_match('/(notice|announcement|circular)/i', $query)) {
            return 'NOTICE';
        }
        if (preg_match('/(contact|location|address|phone|where is|reach)/i', $query)) {
            return 'CONTACT';
        }
        if (preg_match('/(exam|result|marksheet|attendance|timetable|schedule)/i', $query)) {
            return 'EXAM';
        }
        if (preg_match('/(hostel|canteen|bus|transport|infrastructure|sports|gym)/i', $query)) {
            return 'FACILITY';
        }
        if (preg_match('/(research|paper|publication|journal|thesis|dissertation)/i', $query)) {
            return 'RESEARCH';
        }
        if (preg_match('/(code|java|python|c\+\+|c#|php|ruby|swift|kotlin|rust|go lang|typescript|binary search|dsa|sql|normalization|blockchain|rsa)/i', $query)) {
            return 'CODING';
        }
        if (preg_match('/(career|job|hiring|internship|startup|freelance)/i', $query)) {
            return 'CAREER';
        }
        if (preg_match('/(interview)/i', $query)) {
            return 'INTERVIEW';
        }
        if (preg_match('/(resume)/i', $query)) {
            return 'RESUME';
        }
        return 'GENERAL_AI';
    }
}
