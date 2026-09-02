<?php
/**
 * 🤖 CampusAI — Master Query Classification & Routing Engine
 * Implements strict 4-tier Classification:
 * 1. WEBSITE   -> SOET Database & Website Content (Priority 1)
 * 2. TECHNICAL -> Technical & Programming Knowledge (ChatGPT style, max 2-3 lines)
 * 3. GENERAL   -> General Knowledge (ChatGPT style, max 2-3 lines)
 * 4. MIXED     -> Combined: Website DB first + Technical/General explanation
 */

require_once __DIR__ . '/SynonymMapper.php';

class QueryClassifier
{
    private SynonymMapper $synonymMapper;

    // Direct greetings
    private array $greetingPatterns = [
        'hi', 'hello', 'hey', 'good morning', 'good afternoon', 'good evening', 'namaste', 'greetings', 'sup', 'yo'
    ];

    // Explicit institutional keywords identifying SOET MGM University
    private array $institutionalKeywords = [
        'soet', 'mgm', 'mgmu', 'college', 'campus', 'university', 'institute',
        'director', 'dean', 'dhingra', 'parminder', 'hod', 'principal'
    ];

    // Website entity synonym keywords (Academic, Admission, Fees, Faculty, Facilities)
    private array $websiteSynonymKeywords = [
        // Courses & Academics
        'course', 'courses', 'program', 'programs', 'branch', 'branches', 'stream', 'streams',
        'degree', 'degrees', 'btech', 'mtech', 'phd', 'b.tech', 'm.tech', 'syllabus', 'curriculum',
        'cse', 'ece', 'mech', 'mechanical', 'civil', 'electrical', 'applied science', 'specialization', 'department', 'departments',
        // Fees & Aid
        'fee', 'fees', 'fee structure', 'tuition', 'tuition fee', 'course fee', 'cost', 'charges',
        'scholarship', 'scholarships', 'waiver', 'concession', 'tfws',
        // Faculty & Staff
        'faculty', 'teacher', 'teachers', 'professor', 'professors', 'staff', 'lecturer', 'lecturers',
        'teaching staff', 'instructor', 'faculty list', 'who teaches',
        // Admissions & Seats
        'admission', 'admissions', 'apply', 'applying', 'enrollment', 'enroll', 'eligibility',
        'cutoff', 'cet', 'jee', 'mht cet', 'merit list', 'intake', 'capacity', 'seat', 'seats',
        'vacant seat', 'vacant seats', 'available seats', 'vacancy', 'seat availability', 'seat count',
        // Placements & Careers
        'placement', 'placements', 'highest package', 'average package', 'recruiter', 'recruiters',
        'salary package', 'ctc', 'tcs', 'infosys', 'wipro', 'capgemini', 'hiring', 'campus drive',
        // Facilities & Life
        'hostel', 'hostels', 'mess', 'canteen', 'library', 'sports', 'gym', 'bus', 'transport',
        'infrastructure', 'laboratory', 'labs', 'auditorium', 'event', 'events', 'fest', 'notice', 'notices',
        // Contacts & Institutional Info
        'contact', 'phone', 'helpline', 'mobile', 'call', 'email', 'address', 'location', 'reach us', 'where is the college',
        'vision', 'mission', 'institution', 'campusai', 'reference guide', 'training document', 'qna guide'
    ];

    // Technical domains: Coding, CS, Networking, AI, DB, Security
    private array $technicalKeywords = [
        // Programming Languages
        'python', 'java', 'c++', 'c#', 'javascript', 'typescript', 'php', 'rust', 'golang', 'go lang',
        'swift', 'kotlin', 'ruby', 'sql', 'html', 'css', 'bash', 'shell script',
        // Data Structures & Algorithms
        'binary search', 'linear search', 'bubble sort', 'quick sort', 'merge sort', 'insertion sort',
        'sorting', 'searching', 'linked list', 'doubly linked list', 'array', 'stack', 'queue',
        'deque', 'tree', 'binary tree', 'bst', 'avl tree', 'graph', 'bfs', 'dfs', 'dijkstra',
        'algorithm', 'dsa', 'recursion', 'dynamic programming', 'memoization', 'greedy algorithm',
        'time complexity', 'space complexity', 'big o', 'hash map', 'hash table',
        // OOP & Software Engineering
        'oops', 'object oriented', 'inheritance', 'polymorphism', 'encapsulation', 'abstraction',
        'interface', 'abstract class', 'method overloading', 'method overriding', 'class and object',
        'design pattern', 'singleton', 'mvc', 'agile', 'scrum', 'git', 'github', 'version control',
        'api', 'rest api', 'restful', 'graphql', 'endpoint', 'json', 'xml',
        // Systems, OS & Networks
        'operating system', 'os', 'linux', 'kernel', 'cpu process', 'os process', 'thread', 'deadlock', 'semaphore',
        'paging', 'virtual memory', 'networking', 'osi model', 'tcp/ip', 'tcp', 'udp', 'http',
        'https', 'dns', 'dhcp', 'socket', 'ip address', 'subnet', 'router', 'switch',
        // Database & Storage
        'database', 'dbms', 'rdbms', 'normalization', 'acid properties', 'transaction', 'primary key',
        'foreign key', 'indexing', 'join', 'inner join', 'outer join', 'mongodb', 'nosql', 'redis',
        // AI, Data & Cloud
        'artificial intelligence', 'ai', 'machine learning', 'ml', 'deep learning', 'neural network',
        'cnn', 'rnn', 'lstm', 'transformer', 'llm', 'nlp', 'computer vision', 'data science',
        'supervised learning', 'unsupervised learning', 'regression', 'classification',
        'cloud computing', 'aws', 'azure', 'gcp', 'docker', 'container', 'kubernetes', 'devops',
        'ci/cd', 'microservices', 'iot', 'internet of things', 'embedded system', 'arduino', 'raspberry pi',
        // Cybersecurity
        'cybersecurity', 'cryptography', 'encryption', 'decryption', 'rsa', 'aes', 'firewall',
        'sql injection', 'xss', 'phishing', 'malware', 'penetration testing',
        // Code request triggers
        'code', 'function', 'snippet', 'syntax', 'implement', 'write code', 'how to code', 'computer program', 'write a program'
    ];

    public function __construct()
    {
        $this->synonymMapper = new SynonymMapper();
    }

    public function classify(string $query): array
    {
        $rawQuery = trim($query);
        $normalized = $this->synonymMapper->normalizeQuery($rawQuery);

        // 1. Detect Direct Greeting
        if (in_array($normalized, $this->greetingPatterns) || preg_match('/^(hi|hello|hey|good morning|good afternoon|good evening|namaste|greetings)\b/i', $normalized)) {
            return [
                'category' => 'GREETING',
                'intent' => 'GREETING',
                'source' => 'system',
                'confidence' => 1.0,
                'description' => 'User greeting'
            ];
        }

        // Detect underlying intent via SynonymMapper
        $detected = $this->synonymMapper->detectIntent($rawQuery);
        $intent = $detected['intent'] ?? 'GENERAL';

        // 2. Analyze Keyword Presence with Word Boundaries
        $hasInstitutional = $this->matchesAnyKeyword($normalized, $this->institutionalKeywords);
        $hasWebsiteSynonym = $this->matchesAnyKeyword($normalized, $this->websiteSynonymKeywords);
        $hasTechnical = $this->matchesAnyKeyword($normalized, $this->technicalKeywords);

        // College-specific intent triggers
        $isCollegeIntent = in_array($intent, [
            'SEAT_AVAILABILITY', 'ADMISSION', 'PROGRAM', 'FEE', 'FACULTY',
            'PLACEMENT', 'NOTICE', 'EVENT', 'CONTACT', 'SCHOLARSHIP', 'HOSTEL', 'DIRECTOR'
        ]);

        $isWebsite = ($hasInstitutional || $hasWebsiteSynonym || $isCollegeIntent);

        // 3. Classify into the 5 Standard Categories (Rule 7):
        
        // RULE 5: DATABASE (Dynamic real-time database queries: seats, intake, vacancies, enrollments)
        if ($intent === 'SEAT_AVAILABILITY' || preg_match('/\b(vacant|intake|enrolled|registered|remaining|available seats|seats remain|seat availability|how many seats|seats are available|seats are filled|seats are vacant)\b/i', $rawQuery)) {
            return [
                'category' => 'DATABASE',
                'intent' => 'SEAT_AVAILABILITY',
                'source' => 'database',
                'confidence' => 0.99,
                'description' => 'Real-Time Database Metrics Query'
            ];
        }

        // RULE 4: HYBRID (Website entity + distinct Technical/General topic)
        // Only classify as HYBRID if there is an explicit technical concept to explain (e.g. AI, ML, Cloud)
        if ($isWebsite && $hasTechnical) {
            // If the query is purely about college course offerings/curriculum without asking for a technical definition, treat as WEBSITE
            if (preg_match('/^(what|which|list|tell me|show me)\b.*\b(programs?|courses?|degrees?|branches?|streams?)\b.*\b(offered|available|do you have|at soet)\b/i', $rawQuery)) {
                return [
                    'category' => 'WEBSITE',
                    'intent' => 'PROGRAM',
                    'source' => 'database',
                    'confidence' => 0.98,
                    'description' => 'Website Academic Programs Query'
                ];
            }

            return [
                'category' => 'HYBRID',
                'intent' => $intent !== 'UNCLEAR' ? $intent : 'HYBRID',
                'source' => 'hybrid',
                'confidence' => 0.95,
                'description' => 'Hybrid query requiring Website DB first + Technical explanation'
            ];
        }

        // RULE 3: WEBSITE (College / Website Specific)
        if ($isWebsite) {
            return [
                'category' => 'WEBSITE',
                'intent' => $intent,
                'source' => 'database',
                'confidence' => 0.96,
                'description' => 'Website & Institutional Database Query'
            ];
        }

        // RULE 2: TECHNICAL (Coding / Computer Science / Engineering)
        if ($hasTechnical || in_array($intent, ['CODING', 'TECHNICAL', 'CAREER', 'INTERVIEW'])) {
            return [
                'category' => 'TECHNICAL',
                'intent' => $intent !== 'UNCLEAR' ? $intent : 'TECHNICAL',
                'source' => 'openai',
                'confidence' => 0.94,
                'description' => 'Technical & Programming Query'
            ];
        }

        // RULE 1: GENERAL (Everyday knowledge, non-technical definitions, casual)
        return [
            'category' => 'GENERAL',
            'intent' => $intent !== 'UNCLEAR' ? $intent : 'GENERAL',
            'source' => 'openai',
            'confidence' => 0.90,
            'description' => 'General Knowledge Query'
        ];
    }

    private function matchesAnyKeyword(string $text, array $keywords): bool
    {
        foreach ($keywords as $kw) {
            $pattern = '/\b' . preg_quote($kw, '/') . '\b/i';
            if (preg_match($pattern, $text)) {
                return true;
            }
        }
        return false;
    }
}
