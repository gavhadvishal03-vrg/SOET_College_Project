<?php
/**
 * SOET Institutional Knowledge Base & Website Database Search Engine
 */

class DatabaseSearch
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function search(string $query, string $intent = 'GENERAL'): array
    {
        $cleanQuery = trim($query);
        $keywords = $this->extractKeywords($cleanQuery);
        $results = [];

        // 1. Dynamic Search for Real-Time Seat Availability
        if ($intent === 'SEAT_AVAILABILITY' || preg_match('/(seat|seats|intake|capacity|vacant|vacancy|filled|left in|how many seats|admission open)/i', $cleanQuery)) {
            $seatResults = $this->searchSeatAvailability($cleanQuery);
            if (!empty($seatResults)) {
                return ['found' => true, 'top_result' => $seatResults[0], 'all_results' => $seatResults];
            }
        }

        // 2. Direct Category Handlers for College-Specific Intents
        if ($intent === 'NOTICE' || preg_match('/\b(notices?|announcements?|circulars?)\b/i', $cleanQuery)) {
            $notices = $this->searchNotices($cleanQuery);
            if (!empty($notices)) return ['found' => true, 'top_result' => $notices[0], 'all_results' => $notices];
        }

        if ($intent === 'EVENT' || preg_match('/\b(events?|activities|workshops?)\b/i', $cleanQuery)) {
            $events = $this->searchEvents($cleanQuery);
            if (!empty($events)) return ['found' => true, 'top_result' => $events[0], 'all_results' => $events];
        }

        if ($intent === 'PLACEMENT' || preg_match('/\b(placements?|jobs?|recruitment|career|hiring)\b/i', $cleanQuery)) {
            $placements = $this->searchPlacements($cleanQuery);
            if (!empty($placements)) return ['found' => true, 'top_result' => $placements[0], 'all_results' => $placements];
        }

        if ($intent === 'SCHOLARSHIP' || preg_match('/\b(scholarships?|waiver|concession|discount|calculate scholarship|merit scholarship|tfws)\b/i', $cleanQuery)) {
            $scholarships = $this->searchScholarships($cleanQuery);
            if (!empty($scholarships)) return ['found' => true, 'top_result' => $scholarships[0], 'all_results' => $scholarships];
        }

        if ($intent === 'FEE' || preg_match('/\b(fee|fees|tuition|cost|charges)\b/i', $cleanQuery)) {
            $fees = $this->searchCourseFees($cleanQuery);
            if (!empty($fees)) return ['found' => true, 'top_result' => $fees[0], 'all_results' => $fees];
        }

        if ($intent === 'PROGRAM' || $intent === 'DEPARTMENT' || $intent === 'HYBRID' || preg_match('/\b(courses?|programs?|degrees?|branches?|streams?)\b/i', $cleanQuery)) {
            $courses = $this->searchCourses($cleanQuery);
            if (!empty($courses)) return ['found' => true, 'top_result' => $courses[0], 'all_results' => $courses];
        }

        if ($intent === 'FACULTY' || preg_match('/\b(faculty|professors?|teachers?|staff|hod)\b/i', $cleanQuery)) {
            $faculty = $this->searchFaculty($cleanQuery);
            if (!empty($faculty)) return ['found' => true, 'top_result' => $faculty[0], 'all_results' => $faculty];
        }

        // 3. Search Knowledge Base
        $kbHits = $this->searchKnowledgeBase($cleanQuery, $keywords);
        if (!empty($kbHits)) {
            $results = array_merge($results, $kbHits);
        }

        // 4. Search FAQs
        $faqHits = $this->searchFAQs($cleanQuery, $keywords);
        if (!empty($faqHits)) {
            $results = array_merge($results, $faqHits);
        }

        // Score and rank results
        usort($results, fn($a, $b) => $b['score'] <=> $a['score']);

        return [
            'found' => !empty($results),
            'top_result' => $results[0] ?? null,
            'all_results' => array_slice($results, 0, 3)
        ];
    }

    private function searchKnowledgeBase(string $query, array $keywords): array
    {
        $rows = $this->db->fetchAll("SELECT * FROM knowledge_base WHERE status = 'active'");
        $matches = [];

        foreach ($rows as $row) {
            $score = $this->calculateScore($query, $keywords, $row['title'] . ' ' . $row['content'] . ' ' . $row['keywords']);
            if ($score > 0) {
                $matches[] = [
                    'source_type' => 'knowledge_base',
                    'title' => $row['title'],
                    'content' => $row['content'],
                    'source_url' => $row['source_url'] ?? '/about.php',
                    'score' => $score
                ];
            }
        }
        return $matches;
    }

    private function searchFAQs(string $query, array $keywords): array
    {
        $rows = $this->db->fetchAll("SELECT * FROM faq WHERE status = 'active'");
        $matches = [];

        foreach ($rows as $row) {
            $score = $this->calculateScore($query, $keywords, $row['question'] . ' ' . $row['answer'] . ' ' . $row['keywords']);
            if ($score > 0) {
                $matches[] = [
                    'source_type' => 'faq',
                    'title' => $row['question'],
                    'content' => $row['answer'],
                    'source_url' => '/contact.php',
                    'score' => $score + 2 // Bonus weight for direct FAQ match
                ];
            }
        }
        return $matches;
    }

    private function searchCourses(string $query): array
    {
        $courses = $this->db->fetchAll(
            "SELECT c.*, d.name as dept_name FROM courses c JOIN departments d ON c.department_id = d.id WHERE c.is_active = 1 ORDER BY c.name"
        );
        
        $cleanQuery = mb_strtolower(trim($query));
        
        // General course/program/branch list query check
        if (preg_match('/(what|which|list|available|offer)\b.*\b(courses?|programs?|degrees?|branches?|streams?)/i', $cleanQuery) || preg_match('/(courses?|programs?|degrees?|branches?)\b.*\b(do you have|are available|we offer|offered)/i', $cleanQuery) || in_array($cleanQuery, ['courses', 'programs', 'branches', 'degrees'])) {
            $list = [];
            $idx = 1;
            foreach ($courses as $c) {
                $list[] = "{$idx}. **{$c['name']} ({$c['code']})** — Duration: {$c['duration_years']} Years ({$c['semester_count']} Semesters), Intake: {$c['intake_capacity']} seats";
                $idx++;
            }
            $content = "We offer the following official degree programs and academic branches:\n\n" . implode("\n", $list);
            
            return [[
                'source_type' => 'course',
                'title' => "SOET Programs & Academic Branches",
                'content' => $content,
                'source_url' => '/courses.php',
                'score' => 100
            ]];
        }

        $matches = [];
        $genericWords = ['tell', 'me', 'about', 'is', 'the', 'what', 'which', 'at', 'soet', 'mgm', 'mgmu', 'college', 'engineering', 'syllabus', 'curriculum', 'details', 'information', 'course', 'courses', 'btech', 'mtech', 'duration', 'intake', 'fees'];
        $queryWords = array_values(array_filter(explode(' ', mb_strtolower(preg_replace('/[^a-z0-9\s]/', ' ', $query)))));
        $distinctiveQueryWords = array_values(array_diff($queryWords, $genericWords));

        foreach ($courses as $c) {
            $text = mb_strtolower($c['name'] . ' ' . $c['code'] . ' ' . $c['description'] . ' ' . $c['dept_name']);
            
            // Require at least one distinctive query keyword to match if distinctive words were specified
            if (!empty($distinctiveQueryWords)) {
                $matchedDistinctive = false;
                foreach ($distinctiveQueryWords as $dw) {
                    if (strlen($dw) >= 2 && mb_strpos($text, $dw) !== false) {
                        $matchedDistinctive = true;
                        break;
                    }
                }
                if (!$matchedDistinctive) {
                    continue; // Skip course if no distinctive keyword matched (e.g. aerospace asked)
                }
            }

            $score = $this->calculateScore($query, $distinctiveQueryWords ?: $queryWords, $text);
            if ($score > 0) {
                $content = "SOET offers {$c['name']} ({$c['code']}) under {$c['dept_name']}. Duration: {$c['duration_years']} Years ({$c['semester_count']} Semesters). Intake Capacity: {$c['intake_capacity']} seats. Description: {$c['description']}.";
                $matches[] = [
                    'source_type' => 'course',
                    'title' => $c['name'],
                    'content' => $content,
                    'source_url' => '/courses.php',
                    'score' => $score
                ];
            }
        }
        return $matches;
    }

    private function searchFaculty(string $query): array
    {
        $cleanQuery = mb_strtolower(trim($query));
        
        // Dean & Director Leadership Direct Query Handler
        if (preg_match('/\b(dean|director|principal|head of college|leadership|parminder|dhingra|sen|rajesh)\b/i', $cleanQuery)) {
            $cms = new ContentManager();
            $settings = $cms->getSiteSettings();
            $directorName = $settings['director_name'] ?? 'Dr. Parminder Kaur Dhingra';
            $directorDesig = $settings['director_designation'] ?? 'Dean & Director, SOET MGM University';
            $directorMsg = $settings['director_message'] ?? 'At SOET MGM University, we are committed to providing top-tier technological education with modern laboratories, expert faculty, and high-paying industry placements.';

            $content = "SOET MGM University is led by **{$directorName}**, {$directorDesig}.\n\n"
                     . "**Dean & Director's Leadership Message:**\n"
                     . "\"{$directorMsg}\"";

            return [[
                'source_type' => 'faculty',
                'title' => "Leadership Profile - {$directorName}",
                'content' => $content,
                'source_url' => '/about.php',
                'score' => 100
            ]];
        }

        // Department specific faculty search (e.g., CSE, ECE, CIVIL, MECH)
        $deptCode = null;
        if (preg_match('/\b(cse|computer science)\b/i', $cleanQuery)) $deptCode = 'CSE';
        elseif (preg_match('/\b(ece|electronics)\b/i', $cleanQuery)) $deptCode = 'ECE';
        elseif (preg_match('/\b(civil)\b/i', $cleanQuery)) $deptCode = 'CIVIL';
        elseif (preg_match('/\b(mech|mechanical)\b/i', $cleanQuery)) $deptCode = 'MECH';
        elseif (preg_match('/\b(ee|electrical)\b/i', $cleanQuery)) $deptCode = 'EE';

        if ($deptCode) {
            $faculties = $this->db->fetchAll(
                "SELECT f.*, d.name as dept_name, d.code as dept_code FROM faculty f JOIN departments d ON f.department_id = d.id WHERE f.is_active = 1 AND d.code = ? ORDER BY f.designation DESC, f.name ASC",
                [$deptCode]
            );

            if (!empty($faculties)) {
                $list = [];
                foreach ($faculties as $f) {
                    $list[] = "• **{$f['name']}** ({$f['designation']} - {$f['dept_name']})";
                }
                $content = "The {$deptCode} faculty members are:\n" . implode("\n", $list);

                return [[
                    'source_type' => 'faculty',
                    'title' => "{$deptCode} Faculty Directory",
                    'content' => $content,
                    'source_url' => '/faculty.php',
                    'score' => 100
                ]];
            }
        }

        $faculties = $this->db->fetchAll(
            "SELECT f.*, d.name as dept_name FROM faculty f JOIN departments d ON f.department_id = d.id WHERE f.is_active = 1"
        );
        $matches = [];
        foreach ($faculties as $f) {
            $text = $f['name'] . ' ' . $f['designation'] . ' ' . $f['dept_name'] . ' ' . $f['specialization'];
            $score = $this->calculateScore($query, explode(' ', $query), $text);
            if ($score > 0) {
                $content = "Faculty Profile: {$f['name']} is {$f['designation']} in {$f['dept_name']}. Qualification: {$f['qualification']}. Specialization: {$f['specialization']}. Experience: {$f['experience_years']} Years. Email: {$f['email']}.";
                $matches[] = [
                    'source_type' => 'faculty',
                    'title' => $f['name'] . ' - ' . $f['designation'],
                    'content' => $content,
                    'source_url' => '/faculty.php',
                    'score' => $score
                ];
            }
        }
        return $matches;
    }

    private function searchPlacements(string $query): array
    {
        $placements = $this->db->fetchAll("SELECT * FROM placements ORDER BY package_lpa DESC LIMIT 10");
        if (empty($placements)) {
            return [[
                'source_type' => 'placement',
                'title' => 'SOET Training & Placements',
                'content' => 'SOET Training & Placement Cell works with top MNC recruiters including TCS, Wipro, Infosys, and Capgemini.',
                'source_url' => '/placements.php',
                'score' => 100
            ]];
        }

        $cleanQuery = mb_strtolower(trim($query));

        // Category query check
        if (in_array($cleanQuery, ['placements', 'placement', 'jobs', 'job', 'career', 'recruitment', 'hiring', 'companies']) || preg_match('/(show|get|list|tell|top)\b.*\b(placements?|jobs?|recruitment|companies)/i', $cleanQuery)) {
            $list = [];
            foreach ($placements as $p) {
                $desig = $p['designation'] ?? 'Software Engineer';
                $list[] = "• **{$p['student_name']}** — Placed at **{$p['company_name']}** with package of **{$p['package_lpa']} LPA** ({$desig})";
            }
            $content = "Here are recent SOET campus recruitment highlights:\n\n" . implode("\n", $list) . "\n\nHighest Package: 18.5 LPA | Average Package: 5.2 LPA | Placement Rate: 92%.";

            return [[
                'source_type' => 'placement',
                'title' => "SOET Campus Placement Highlights",
                'content' => $content,
                'source_url' => '/placements.php',
                'score' => 100
            ]];
        }

        $matches = [];
        foreach ($placements as $p) {
            $desig = $p['designation'] ?? 'Engineering Recruit';
            $yr = $p['placement_year'] ?? ($p['passout_year'] ?? date('Y'));
            $text = $p['student_name'] . ' ' . $p['company_name'] . ' ' . $p['package_lpa'] . ' LPA ' . $desig;
            $score = $this->calculateScore($query, explode(' ', $query), $text);
            if ($score > 0) {
                $content = "Placement Record: **{$p['student_name']}** was placed at **{$p['company_name']}** with a package of **{$p['package_lpa']} LPA** (Role: {$desig}, Year: {$yr}).";
                $matches[] = [
                    'source_type' => 'placement',
                    'title' => "Placement: " . $p['company_name'],
                    'content' => $content,
                    'source_url' => '/placements.php',
                    'score' => $score
                ];
            }
        }

        if (empty($matches)) {
            $list = [];
            foreach ($placements as $p) {
                $list[] = "• **{$p['student_name']}** — **{$p['company_name']}** ({$p['package_lpa']} LPA)";
            }
            $content = "Here are recent SOET placement records:\n\n" . implode("\n", $list);

            return [[
                'source_type' => 'placement',
                'title' => "SOET Placement Records",
                'content' => $content,
                'source_url' => '/placements.php',
                'score' => 90
            ]];
        }

        return $matches;
    }

    private function searchEvents(string $query): array
    {
        // Direct Event Registration Pass Number Lookup
        if (preg_match('/EVT-REG-[A-Z0-9-]+/i', $query, $regMatch)) {
            $passNo = strtoupper($regMatch[0]);
            $reg = $this->db->fetchOne(
                "SELECT r.*, e.title as main_event, e.event_date, e.event_time, e.venue as main_venue, s.title as sub_event_name, s.venue as sub_venue 
                 FROM event_registrations r 
                 JOIN events e ON r.event_id = e.id 
                 LEFT JOIN sub_events s ON r.sub_event_id = s.id 
                 WHERE r.registration_no = ?",
                [$passNo]
            );
            if ($reg) {
                $subTrack = !empty($reg['sub_event_name']) ? $reg['sub_event_name'] : 'Main Event Overall';
                $venue = !empty($reg['sub_venue']) ? $reg['sub_venue'] : $reg['main_venue'];
                $content = "🎟️ **Official Event Entry Pass Verified**\n"
                         . "• **Pass ID**: `{$reg['registration_no']}`\n"
                         . "• **Participant**: {$reg['name']} (Roll: " . ($reg['roll_number'] ?: 'N/A') . ")\n"
                         . "• **Main Event**: {$reg['main_event']}\n"
                         . "• **Selected Track**: {$subTrack}\n"
                         . "• **Date & Time**: " . date('d-M-Y', strtotime($reg['event_date'])) . " at " . date('h:i A', strtotime($reg['event_time'])) . "\n"
                         . "• **Venue**: {$venue}\n"
                         . "• **Status**: Registered & Confirmed";
                return [[
                    'source_type' => 'event_registration',
                    'title' => "Event Pass: {$reg['registration_no']}",
                    'content' => $content,
                    'source_url' => '/events.php',
                    'score' => 100
                ]];
            }
        }

        $cms = new ContentManager();
        $events = $this->db->fetchAll("SELECT * FROM events WHERE is_active = 1 ORDER BY event_date DESC LIMIT 10");
        if (empty($events)) {
            return [[
                'source_type' => 'event',
                'title' => 'SOET College Events',
                'content' => 'There are currently no upcoming college events scheduled. Please stay tuned for future announcements.',
                'source_url' => '/events.php',
                'score' => 100
            ]];
        }

        $cleanQuery = mb_strtolower(trim($query));

        // Category query check (e.g. "events", "sub events", "activities", "workshops", "fests")
        if (in_array($cleanQuery, ['events', 'event', 'sub event', 'sub events', 'activities', 'activity', 'workshops', 'fest', 'fests', 'competitions']) || preg_match('/(show|get|list|tell|upcoming)\b.*\b(events?|sub events?|activities|workshops?|competitions?)/i', $cleanQuery)) {
            $list = [];
            foreach ($events as $e) {
                $dateStr = !empty($e['event_date']) ? date('d-M-Y', strtotime($e['event_date'])) : 'TBA';
                $venueStr = !empty($e['venue']) ? " at {$e['venue']}" : "";
                $eventItem = "• **{$e['title']}** (Date: {$dateStr}{$venueStr})\n  {$e['description']}";

                $subEvents = $cms->getSubEvents((int)$e['id']);
                if (!empty($subEvents)) {
                    $subList = [];
                    foreach ($subEvents as $se) {
                        $seDate = !empty($se['sub_event_date']) ? date('d-M', strtotime($se['sub_event_date'])) : '';
                        $subList[] = "   📌 **{$se['title']}** ({$seDate} at {$se['venue']}): {$se['description']}";
                    }
                    $eventItem .= "\n  *Sub-Events / Tracks:*\n" . implode("\n", $subList);
                }
                $list[] = $eventItem;
            }
            $content = "Here are the official SOET college events, fests, and sub-events:\n\n" . implode("\n\n", $list);

            return [[
                'source_type' => 'event',
                'title' => "Official SOET College Events & Sub-Events",
                'content' => $content,
                'source_url' => '/events.php',
                'score' => 100
            ]];
        }

        $matches = [];
        foreach ($events as $e) {
            $subEvents = $cms->getSubEvents((int)$e['id']);
            $subText = "";
            foreach ($subEvents as $se) {
                $subText .= ' ' . $se['title'] . ' ' . $se['description'] . ' ' . $se['venue'] . ' ' . $se['rules'];
            }

            $text = $e['title'] . ' ' . $e['description'] . ' ' . ($e['venue'] ?? '') . ' ' . $subText;
            $score = $this->calculateScore($query, explode(' ', $query), $text);
            if ($score > 0) {
                $content = "Event: **{$e['title']}** (Date: {$e['event_date']}, Venue: {$e['venue']}). Details: {$e['description']}.";
                if (!empty($subEvents)) {
                    $subList = [];
                    foreach ($subEvents as $se) {
                        $subList[] = "📌 **{$se['title']}** (Date: {$se['sub_event_date']}, Venue: {$se['venue']}): {$se['description']}";
                    }
                    $content .= "\n\n**Sub-Events & Competitions:**\n" . implode("\n", $subList);
                }
                $matches[] = [
                    'source_type' => 'event',
                    'title' => $e['title'],
                    'content' => $content,
                    'source_url' => '/events.php',
                    'score' => $score
                ];
            }
        }

        if (empty($matches)) {
            $list = [];
            foreach ($events as $e) {
                $dateStr = !empty($e['event_date']) ? date('d-M-Y', strtotime($e['event_date'])) : 'TBA';
                $list[] = "• **{$e['title']}** (Date: {$dateStr})\n  {$e['description']}";
            }
            $content = "Here are the official SOET college events:\n\n" . implode("\n\n", $list);

            return [[
                'source_type' => 'event',
                'title' => "Official SOET College Events",
                'content' => $content,
                'source_url' => '/events.php',
                'score' => 90
            ]];
        }

        return $matches;
    }

    private function searchNotices(string $query): array
    {
        $notices = $this->db->fetchAll("SELECT * FROM notices WHERE is_active = 1 ORDER BY created_at DESC LIMIT 10");
        if (empty($notices)) {
            return [[
                'source_type' => 'notice',
                'title' => 'Official SOET Notices',
                'content' => 'There are currently no active administrative notices posted. Please check back later or contact the main office.',
                'source_url' => '/index.php',
                'score' => 100
            ]];
        }

        $cleanQuery = mb_strtolower(trim($query));

        // Category query check (e.g. "notices", "notice", "announcements")
        if (in_array($cleanQuery, ['notices', 'notice', 'announcement', 'announcements', 'circular', 'circulars', 'updates']) || preg_match('/(show|get|list|tell|latest)\b.*\b(notices?|announcements?|circulars?)/i', $cleanQuery)) {
            $list = [];
            foreach ($notices as $n) {
                $dateStr = date('d-M-Y', strtotime($n['created_at']));
                $list[] = "• **{$n['title']}** (Posted: {$dateStr})\n  {$n['content']}";
            }
            $content = "Here are the latest official SOET administrative notices & announcements:\n\n" . implode("\n\n", $list);

            return [[
                'source_type' => 'notice',
                'title' => "Official SOET Administrative Notices",
                'content' => $content,
                'source_url' => '/index.php',
                'score' => 100
            ]];
        }

        $matches = [];
        foreach ($notices as $n) {
            $text = $n['title'] . ' ' . $n['content'];
            $score = $this->calculateScore($query, explode(' ', $query), $text);
            if ($score > 0) {
                $content = "Announcement Notice: **{$n['title']}**. Details: {$n['content']}.";
                $matches[] = [
                    'source_type' => 'notice',
                    'title' => $n['title'],
                    'content' => $content,
                    'source_url' => '/index.php',
                    'score' => $score
                ];
            }
        }

        if (empty($matches)) {
            $list = [];
            foreach ($notices as $n) {
                $dateStr = date('d-M-Y', strtotime($n['created_at']));
                $list[] = "• **{$n['title']}** (Posted: {$dateStr})\n  {$n['content']}";
            }
            $content = "Here are the latest official SOET administrative notices:\n\n" . implode("\n\n", $list);

            return [[
                'source_type' => 'notice',
                'title' => "Official SOET Administrative Notices",
                'content' => $content,
                'source_url' => '/index.php',
                'score' => 90
            ]];
        }

        return $matches;
    }

    private function calculateScore(string $query, array $keywords, string $targetText): float
    {
        $score = 0;
        $cleanTarget = mb_strtolower($targetText);
        $cleanQuery = mb_strtolower($query);

        if (mb_strpos($cleanTarget, $cleanQuery) !== false) {
            $score += 20;
        }

        $genericCollegeWords = [
            'soet', 'mgm', 'mgmu', 'college', 'engineering', 'technology', 'university',
            'syllabus', 'department', 'details', 'information', 'about', 'tell', 'offered',
            'course', 'courses', 'program', 'programs', 'branch', 'branches'
        ];

        // Identify distinctive keywords in user query
        $distinctiveKeywords = [];
        foreach ($keywords as $kw) {
            $cleanKw = mb_strtolower(trim($kw));
            if (strlen($cleanKw) > 2 && !in_array($cleanKw, $genericCollegeWords)) {
                $distinctiveKeywords[] = $cleanKw;
            }
        }

        // If user specified distinctive keywords, at least one MUST match the target text
        if (!empty($distinctiveKeywords)) {
            $hasDistinctiveMatch = false;
            foreach ($distinctiveKeywords as $dkw) {
                if (mb_strpos($cleanTarget, $dkw) !== false) {
                    $hasDistinctiveMatch = true;
                    $score += 8;
                }
            }
            if (!$hasDistinctiveMatch) {
                return 0; // Reject match if none of the distinctive keywords matched
            }
        }

        foreach ($keywords as $kw) {
            $kw = mb_strtolower(trim($kw));
            if (strlen($kw) > 2 && mb_strpos($cleanTarget, $kw) !== false) {
                $score += 2;
            }
        }

        return $score;
    }

    private function extractKeywords(string $query): array
    {
        $stopWords = ['is', 'a', 'an', 'the', 'in', 'on', 'at', 'for', 'to', 'of', 'and', 'what', 'who', 'where', 'how', 'tell', 'me', 'about', 'can', 'you'];
        $words = preg_split('/\s+/', mb_strtolower($query));
        return array_diff($words, $stopWords);
    }

    public function searchSeatAvailability(string $query): array
    {
        $cms = new ContentManager();
        $metrics = $cms->getCourseSeatMetrics();
        $cleanQuery = mb_strtolower(trim($query));

        // Course code & alias mappings
        $aliases = [
            'cse' => ['BTECH-CSE', 'B.Tech in Computer Science & Engineering'],
            'computer science' => ['BTECH-CSE', 'B.Tech in Computer Science & Engineering'],
            'cyber security' => ['BTECH-CS', 'B.Tech in CSE (Cyber Security & Digital Forensics)'],
            'cs' => ['BTECH-CS', 'B.Tech in CSE (Cyber Security & Digital Forensics)'],
            'ai' => ['BTECH-AIML', 'B.Tech in CSE (Artificial Intelligence & Machine Learning)'],
            'aiml' => ['BTECH-AIML', 'B.Tech in CSE (Artificial Intelligence & Machine Learning)'],
            'machine learning' => ['BTECH-AIML', 'B.Tech in CSE (Artificial Intelligence & Machine Learning)'],
            'ece' => ['BTECH-ECE', 'B.Tech in Electronics & Communication Engineering'],
            'electronics' => ['BTECH-ECE', 'B.Tech in Electronics & Communication Engineering'],
            'mech' => ['BTECH-MECH', 'B.Tech in Mechanical & Automation Engineering'],
            'mechanical' => ['BTECH-MECH', 'B.Tech in Mechanical & Automation Engineering'],
            'civil' => ['BTECH-CIVIL', 'B.Tech in Civil Engineering'],
            'integrated' => ['INT-BTECH', 'Integrated 6-Year B.Tech Program (After 10th Class)'],
            'mtech' => ['MTECH-CSE', 'M.Tech in Computer Science & Engineering'],
        ];

        $matchedCourse = null;

        // 1. Try exact alias match
        foreach ($aliases as $keyword => $info) {
            if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/i', $cleanQuery)) {
                foreach ($metrics as $m) {
                    if ($m['course_code'] === $info[0]) {
                        $matchedCourse = $m;
                        break 2;
                    }
                }
            }
        }

        // 2. Try code/name substring match
        if (!$matchedCourse) {
            foreach ($metrics as $m) {
                if (mb_strpos($cleanQuery, mb_strtolower($m['course_code'])) !== false) {
                    $matchedCourse = $m;
                    break;
                }
            }
        }

        // Case A: Specific course matched and found in database
        if ($matchedCourse) {
            $name = $matchedCourse['course_name'];
            $intake = $matchedCourse['total_intake'];
            $filled = $matchedCourse['filled_seats'];
            $vacant = $matchedCourse['vacant_seats'];
            $updatedAt = $matchedCourse['last_updated'];
            $isOpen = ($matchedCourse['admission_status'] === 'OPEN' && $vacant > 0);

            $isAdmissionOpenQuery = (preg_match('/(is admission open|admission open|can i take admission)/i', $cleanQuery) !== false);

            if ($isOpen) {
                if (preg_match('/(is admission open|admission open)/i', $cleanQuery)) {
                    $content = "Yes, admission for {$name} is currently open. There are {$vacant} vacant seats out of {$intake} total seats ({$filled} filled). Last updated: {$updatedAt}.";
                } else {
                    $content = "{$name} has {$vacant} vacant seats out of {$intake} total seats. {$filled} seats are currently filled. Admission status: Open (Last updated: {$updatedAt}).";
                }
            } else {
                $content = "Admissions for {$name} are currently CLOSED. Total intake: {$intake}, Filled: {$filled}, Vacant: 0. Last updated: {$updatedAt}.";
            }

            return [[
                'source_type' => 'seats_database',
                'title' => "Seat Availability - {$name}",
                'content' => $content,
                'source_url' => '/admissions.php',
                'score' => 100
            ]];
        }

        // Check if user asked for a non-existent course (e.g., Aeronautical, Chemical, Biotech)
        $nonExistentCourses = ['aeronautical', 'aerospace', 'chemical', 'biotech', 'biotechnology', 'textile', 'marine', 'automobile', 'mining', 'petroleum', 'agriculture', 'pharmacy'];
        $requestedNonExistent = null;
        foreach ($nonExistentCourses as $nec) {
            if (mb_strpos($cleanQuery, $nec) !== false) {
                $requestedNonExistent = ucfirst($nec) . " Engineering";
                break;
            }
        }

        // Case B: Requested non-existent course
        if ($requestedNonExistent) {
            $availableList = [];
            foreach ($metrics as $m) {
                $statusBadge = ($m['admission_status'] === 'OPEN' && $m['vacant_seats'] > 0) ? 'Open' : 'Closed';
                $availableList[] = "• **{$m['course_name']} ({$m['course_code']})**: Total Intake: {$m['total_intake']} | Filled: {$m['filled_seats']} | Vacant: {$m['vacant_seats']} | Admission: {$statusBadge}";
            }
            $listStr = implode("\n", $availableList);

            $content = "We do not offer a degree program named **'{$requestedNonExistent}'**. Here are the official SOET degree programs and their real-time seat availability:\n\n{$listStr}";

            return [[
                'source_type' => 'seats_database',
                'title' => "Course Not Found - Available Programs",
                'content' => $content,
                'source_url' => '/courses.php',
                'score' => 95
            ]];
        }

        // Case C: General query about all seat availability
        $allSeatsList = [];
        foreach ($metrics as $m) {
            $statusBadge = ($m['admission_status'] === 'OPEN' && $m['vacant_seats'] > 0) ? 'Open' : 'Closed';
            $allSeatsList[] = "• **{$m['course_name']} ({$m['course_code']})**: Intake: {$m['total_intake']} | Filled: {$m['filled_seats']} | Vacant: {$m['vacant_seats']} | Status: {$statusBadge}";
        }
        $summaryStr = implode("\n", $allSeatsList);
        $currentTime = date('d-M-Y, g:i A');

        $content = "Here is the real-time seat availability across all SOET degree programs (Last updated: {$currentTime}):\n\n{$summaryStr}\n\nTo apply online, please visit our admissions portal or contact the admissions desk.";

        return [[
            'source_type' => 'seats_database',
            'title' => "SOET Real-Time Seat Availability Summary",
            'content' => $content,
            'source_url' => '/admissions.php',
            'score' => 90
        ]];
    }

    private function searchCourseFees(string $query): array
    {
        $courses = $this->db->fetchAll("SELECT * FROM courses WHERE is_active = 1");
        $cleanQuery = mb_strtolower(trim($query));
        $matches = [];

        foreach ($courses as $c) {
            $code = mb_strtolower($c['code']);
            $name = mb_strtolower($c['name']);

            // Match course code or name substring
            if (mb_strpos($cleanQuery, $code) !== false || (mb_strpos($cleanQuery, 'cse') !== false && mb_strpos($code, 'cse') !== false) || (mb_strpos($cleanQuery, 'ece') !== false && mb_strpos($code, 'ece') !== false) || (mb_strpos($cleanQuery, 'civil') !== false && mb_strpos($code, 'civil') !== false) || (mb_strpos($cleanQuery, 'mech') !== false && mb_strpos($code, 'mech') !== false) || (mb_strpos($cleanQuery, 'aiml') !== false && mb_strpos($code, 'aiml') !== false)) {
                
                $y1 = number_format($c['fee_year_1']);
                $total = number_format($c['fee_year_1'] + $c['fee_year_2'] + $c['fee_year_3'] + $c['fee_year_4']);

                $content = "The current {$c['name']} ({$c['code']}) tuition fee is ₹{$y1} per academic year (Total 4-Year Tuition Fee: ₹{$total}). Government SC/ST/OBC and MGM Merit Scholarships are available.";

                $matches[] = [
                    'source_type' => 'fee_database',
                    'title' => "Fee Structure - {$c['name']}",
                    'content' => $content,
                    'source_url' => '/courses.php',
                    'score' => 98
                ];
                break;
            }
        }

        if (empty($matches)) {
            $feeList = [];
            foreach ($courses as $c) {
                $y1 = number_format($c['fee_year_1']);
                $feeList[] = "• **{$c['name']} ({$c['code']})**: ₹{$y1} / year";
            }
            $listStr = implode("\n", $feeList);

            $content = "Here is the annual tuition fee breakdown for SOET degree programs:\n\n{$listStr}\n\nGovernment SC/ST/OBC scholarships and MGM Merit Scholarships are available.";

            $matches[] = [
                'source_type' => 'fee_database',
                'title' => "SOET Annual Tuition Fee Structure",
                'content' => $content,
                'source_url' => '/courses.php',
                'score' => 85
            ];
        }

        return $matches;
    }

    public function searchScholarships(string $query): array
    {
        $clean = mb_strtolower($query);
        // Check if marks or percentage mentioned (e.g. 85%, 90 marks, 75 percent)
        if (preg_match('/(\d{2,3})(?:\s*%(?:\s*in\s*pcm|\s*in\s*12th)?|\s*percent)/i', $clean, $m)) {
            $pct = (float)$m[1];
            $tier = "Standard";
            $waiver = "0%";
            $deduction = 0;
            if ($pct >= 90) {
                $tier = "🌟 Platinum Merit Scholarship";
                $waiver = "30% Tuition Fee Waiver";
                $deduction = 45000;
            } elseif ($pct >= 80) {
                $tier = "🥇 Gold Merit Scholarship";
                $waiver = "20% Tuition Fee Waiver";
                $deduction = 30000;
            } elseif ($pct >= 70) {
                $tier = "🥈 Silver Merit Scholarship";
                $waiver = "10% Tuition Fee Waiver";
                $deduction = 15000;
            }

            $baseFee = 150000;
            $netFee = max(0, $baseFee - $deduction);

            $content = "### 🎓 SOET Merit Scholarship Evaluation (Score: {$pct}%)\n\n"
                     . "| Parameter | Details |\n"
                     . "|---|---|\n"
                     . "| **Applicant Score** | {$pct}% in 10+2 / PCM |\n"
                     . "| **Eligible Tier** | {$tier} |\n"
                     . "| **Fee Waiver** | **{$waiver}** |\n"
                     . "| **Standard Annual Fee** | ₹" . number_format($baseFee) . " |\n"
                     . "| **Annual Scholarship Benefit** | - ₹" . number_format($deduction) . " |\n"
                     . "| **Net Annual Tuition Fee** | **₹" . number_format($netFee) . "** |\n\n"
                     . "> ℹ️ *Scholarship is applicable for all four academic years subject to maintaining minimum 7.5 CGPA without active backlogs.*\n\n"
                     . "🔗 [Apply with Scholarship](/admissions.php) | [Download Brochure](/assets/img/hero/hero-bg.jpg)";

            return [[
                'source_type' => 'scholarship_evaluator',
                'title' => "Merit Scholarship Evaluation ({$pct}%)",
                'content' => $content,
                'source_url' => '/admissions.php',
                'score' => 100
            ]];
        }

        $content = "### 🎓 SOET MGM University Scholarship Schemes (2026-27)\n\n"
                 . "| Scholarship Category | Eligibility Criteria | Tuition Waiver |\n"
                 . "|---|---|---|\n"
                 . "| **Platinum Merit** | ≥ 90% in PCM / MHT-CET ≥ 95 %ile | **30% Waiver** (₹45,000/yr) |\n"
                 . "| **Gold Merit** | 80% to 89.9% in PCM | **20% Waiver** (₹30,000/yr) |\n"
                 . "| **Silver Merit** | 70% to 79.9% in PCM | **10% Waiver** (₹15,000/yr) |\n"
                 . "| **Sports Excellence** | National / State Level Representation | **Up to 50% Waiver** |\n"
                 . "| **Government Quotas** | SC/ST/OBC/EBC/TFWS Schemes | **As per DTE Maharashtra Norms** |\n\n"
                 . "💡 *To calculate your exact fee, ask: 'Calculate scholarship for 85%' or 'What is my scholarship with 92%?'*\n\n"
                 . "🔗 [Apply for Admission](/admissions.php) | [Contact Admission Cell](/contact.php)";

        return [[
            'source_type' => 'scholarship_info',
            'title' => "SOET Scholarship Schemes & Eligibility",
            'content' => $content,
            'source_url' => '/admissions.php',
            'score' => 100
        ]];
    }
}
