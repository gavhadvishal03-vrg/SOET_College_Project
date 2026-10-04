<?php
/**
 * SOET Institutional Knowledge Base & Website Database Search Engine
 *
 * Implements high-precision semantic and keyword fulltext searches across
 * institutional database repositories:
 * 1. Real-Time Seat Matrix: Approved intake, filled seats, and vacancies per branch.
 * 2. Academic Programs & Syllabi: 4-Year B.Tech, 6-Year Integrated B.Tech, DSE Lateral Entry, syllabus PDFs.
 * 3. Fee Schedules & Scholarships: Tuition breakdown, merit concessions, TFWS waivers.
 * 4. Faculty & Leadership Profiles: Dean/Director credentials, HODs, research specializations.
 * 5. Placement & Recruiter Intelligence: Highest/average LPA packages, visiting corporate partners.
 * 6. Campus Facilities & Hostels: Labs, libraries, mess, Wi-Fi, sports amenities.
 * 7. Verified Knowledge Base & FAQ Entries: Canonical Q&A articles and parsed training docs.
 *
 * @package SOET_College_Project
 * @subpackage Chatbot_Services
 */

class DatabaseSearch
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Executes domain-specific search cascading through database entities and knowledge articles.
     *
     * @param string $query Normalized user query text.
     * @param string $intent Classified query intent.
     * @return array Result set containing 'found', 'top_result', and 'all_results'.
     */
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
        if (preg_match('/\b(brochures?|leaflets?|booklets?|prospectus|curriculum booklet|admission booklet|download brochure|download pdf|download document)\b/i', $cleanQuery)) {
            $docs = $this->searchDocuments($cleanQuery);
            if (!empty($docs)) return ['found' => true, 'top_result' => $docs[0], 'all_results' => $docs];
        }

        if (preg_match('/\b(facilities|facility|infrastructure|laborator(y|ies)|labs|hostels?|canteen|mess|sports|gym|library|wi-fi)\b/i', $cleanQuery) || preg_match('/\b(campus\s*(facilities|life|amenities|infrastructure|hostel|mess))\b/i', $cleanQuery)) {
            $facilities = $this->searchFacilities($cleanQuery);
            if (!empty($facilities)) return ['found' => true, 'top_result' => $facilities[0], 'all_results' => $facilities];
        }

        if ($intent === 'NOTICE' || preg_match('/\b(notices?|announcements?|circulars?)\b/i', $cleanQuery)) {
            $notices = $this->searchNotices($cleanQuery);
            if (!empty($notices)) return ['found' => true, 'top_result' => $notices[0], 'all_results' => $notices];
        }

        if ($intent === 'EVENT' || preg_match('/\b(events?|activities|workshops?)\b/i', $cleanQuery)) {
            $events = $this->searchEvents($cleanQuery);
            if (!empty($events)) return ['found' => true, 'top_result' => $events[0], 'all_results' => $events];
        }

        if ($intent === 'PLACEMENT' || preg_match('/\b(placements?|jobs?|recruitment|career|hiring|companies|recruiter)\b/i', $cleanQuery)) {
            $placements = $this->searchPlacements($cleanQuery);
            if (!empty($placements)) return ['found' => true, 'top_result' => $placements[0], 'all_results' => $placements];
        }

        if ($intent === 'SCHOLARSHIP' || preg_match('/\b(scholarships?|waiver|concession|discount|calculate scholarship|merit scholarship|tfws)\b/i', $cleanQuery)) {
            $scholarships = $this->searchScholarships($cleanQuery);
            if (!empty($scholarships)) return ['found' => true, 'top_result' => $scholarships[0], 'all_results' => $scholarships];
        }

        if ($intent === 'ADMISSION' || preg_match('/\b(admission|admissions|how to apply|apply for admission|apply online|eligibility|admission requirements|admission process)\b/i', $cleanQuery)) {
            $admissions = $this->searchAdmissions($cleanQuery);
            if (!empty($admissions)) return ['found' => true, 'top_result' => $admissions[0], 'all_results' => $admissions];
        }

        if ($intent === 'FEE' || preg_match('/\b(fee|fees|tuition|cost|charges)\b/i', $cleanQuery)) {
            $fees = $this->searchCourseFees($cleanQuery);
            if (!empty($fees)) return ['found' => true, 'top_result' => $fees[0], 'all_results' => $fees];
        }

        if (preg_match('/\b(departments?|academic departments)\b/i', $cleanQuery)) {
            $depts = $this->searchDepartments($cleanQuery);
            if (!empty($depts)) return ['found' => true, 'top_result' => $depts[0], 'all_results' => $depts];
        }

        if (preg_match('/\b(syllabus|curriculum|subject list|course structure|download syllabus|syllabus pdf)\b/i', $cleanQuery)) {
            $syllabus = $this->searchSyllabus($cleanQuery);
            if (!empty($syllabus)) return ['found' => true, 'top_result' => $syllabus[0], 'all_results' => $syllabus];
        }

        if ($intent === 'PROGRAM' || $intent === 'DEPARTMENT' || $intent === 'HYBRID' || preg_match('/\b(courses?|programs?|degrees?|branches?|streams?|engineering|b\.?tech|diploma)\b/i', $cleanQuery)) {
            $courses = $this->searchCourses($cleanQuery);
            if (!empty($courses)) return ['found' => true, 'top_result' => $courses[0], 'all_results' => $courses];
        }

        if ($intent === 'FACULTY' || preg_match('/\b(faculty|professors?|teachers?|staff|hod)\b/i', $cleanQuery)) {
            $faculty = $this->searchFaculty($cleanQuery);
            if (!empty($faculty)) return ['found' => true, 'top_result' => $faculty[0], 'all_results' => $faculty];
        }

        // 3. Search Knowledge Base, FAQs, and Training Documents (Priority 3)
        $kbHits = $this->searchKnowledgeBase($cleanQuery, $keywords);
        if (!empty($kbHits)) {
            $results = array_merge($results, $kbHits);
        }

        $faqHits = $this->searchFAQs($cleanQuery, $keywords);
        if (!empty($faqHits)) {
            $results = array_merge($results, $faqHits);
        }

        $docHits = $this->searchTrainingDocuments($cleanQuery, $keywords);
        if (!empty($docHits)) {
            $results = array_merge($results, $docHits);
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

    private function searchTrainingDocuments(string $query, array $keywords): array
    {
        $rows = $this->db->fetchAll("SELECT * FROM uploaded_documents WHERE extracted_text IS NOT NULL AND extracted_text != ''");
        $matches = [];

        foreach ($rows as $row) {
            // Extract highest-scoring 2-3 sentence paragraph from document
            $paragraphs = preg_split('/\n{2,}|\r\n\r\n/', $row['extracted_text']);
            $bestPara = '';
            $bestParaScore = 0;
            foreach ($paragraphs as $para) {
                $pScore = $this->calculateScore($query, $keywords, $para);
                if ($pScore > $bestParaScore) {
                    $bestParaScore = $pScore;
                    $bestPara = trim($para);
                }
            }

            if ($bestParaScore >= 4 && !empty($bestPara)) {
                $matches[] = [
                    'source_type' => 'training_knowledge',
                    'title' => 'Training Document: ' . $row['original_name'],
                    'content' => $bestPara,
                    'source_url' => '/chatbot/train/' . $row['filename'],
                    'score' => $bestParaScore + 1
                ];
            }
        }
        return $matches;
    }

    private function searchCourses(string $query): array
    {
        $courses = $this->db->fetchAll(
            "SELECT c.*, d.name as dept_name FROM courses c JOIN departments d ON c.department_id = d.id WHERE c.is_active = 1 ORDER BY c.program_type ASC, c.name ASC"
        );
        
        $cleanQuery = mb_strtolower(trim($query));
        
        // 1. Integrated Programs specific query
        if (preg_match('/\b(integrated|6\s*years?|after\s*10th|after\s*ssc|post\s*10th|school\s*pass)\b/i', $cleanQuery) && !preg_match('/\b(regular|4\s*year|lateral|direct)\b/i', $cleanQuery)) {
            $intCourses = array_filter($courses, fn($c) => strtolower($c['program_type']) === 'integrated');
            $list = [];
            $idx = 1;
            foreach ($intCourses as $c) {
                $list[] = "{$idx}. **{$c['name']} ({$c['code']})** — Duration: {$c['duration_years']} Yrs, Intake: {$c['intake_capacity']} seats, Fee: ₹" . number_format($c['fee_year_1']) . "/yr";
                $idx++;
            }
            $content = "### 🚀 SOET B.Tech (Integrated) 6-Year Programs (After 10th SSC)\n\n"
                     . "Designed for 10th passed students to complete Diploma + B.Tech seamlessly in 6 years (Eligibility: SSC Passed with min 50% Open, 45% Reserved + MGMU-CET):\n\n"
                     . implode("\n", $list) . "\n\n"
                     . "🔗 [Apply for Integrated Admission](/admissions.php) | [View Program Details](/courses.php)";

            return [[
                'source_type' => 'course',
                'title' => "SOET Integrated 6-Year B.Tech Programs",
                'content' => $content,
                'source_url' => '/courses.php',
                'score' => 100
            ]];
        }

        // 2. Direct Second Year / Lateral Entry specific query
        if (preg_match('/\b(direct\s*2nd|direct\s*second|lateral|dse|after\s*diploma|diploma\s*students?|diploma\s*entry|second\s*year)\b/i', $cleanQuery)) {
            $dseCourses = array_filter($courses, fn($c) => strtolower($c['program_type']) === 'lateral_entry');
            $list = [];
            $idx = 1;
            foreach ($dseCourses as $c) {
                $list[] = "{$idx}. **{$c['name']} ({$c['code']})** — Duration: {$c['duration_years']} Yrs, Intake: {$c['intake_capacity']} seats, Fee: ₹" . number_format($c['fee_year_1']) . "/yr";
                $idx++;
            }
            $content = "### ⚡ SOET Direct 2nd Year (Lateral Entry) Programs (After Diploma / B.Sc)\n\n"
                     . "Allows Engineering Diploma and B.Sc. graduates direct admission to 3rd Semester (Eligibility: Min 45% Open, 40% Reserved):\n\n"
                     . implode("\n", $list) . "\n\n"
                     . "🔗 [Apply for Lateral Entry](/admissions.php) | [Fee Details](/courses.php)";

            return [[
                'source_type' => 'course',
                'title' => "SOET Direct Second Year Lateral Entry Programs",
                'content' => $content,
                'source_url' => '/courses.php',
                'score' => 100
            ]];
        }

        // 2.5 Non-existent courses check
        $nonExistentCourses = ['aeronautical', 'aerospace', 'chemical', 'biotech', 'biotechnology', 'textile', 'marine', 'automobile', 'mining', 'petroleum', 'agriculture', 'pharmacy'];
        foreach ($nonExistentCourses as $nec) {
            if (mb_strpos($cleanQuery, $nec) !== false) {
                $requestedNonExistent = ucfirst($nec) . " Engineering";
                $content = "SOET MGM University does not currently offer a degree in **'{$requestedNonExistent}'**.\n\n"
                         . "We offer approved programs in **CSE (IoT & Cyber / Blockchain)**, **Robotics & Automation**, **CSE (Cyber Security)**, **Electronics Engineering (VLSI Design)**, **Electrical Engineering**, plus **Integrated 6-Year B.Tech** and **Direct 2nd Year Lateral Entry** programs.\n\n"
                         . "🔗 [Explore All Available Programs](/courses.php)";

                return [[
                    'source_type' => 'course',
                    'title' => "Program Not Offered - Available Programs",
                    'content' => $content,
                    'source_url' => '/courses.php',
                    'score' => 95
                ]];
            }
        }

        // 3. Regular 4-Year B.Tech specific query
        if (preg_match('/\b(regular|4\s*years?|after\s*12th|after\s*hsc|post\s*12th)\b/i', $cleanQuery) && !preg_match('/\b(integrated|6\s*year|lateral|direct)\b/i', $cleanQuery)) {
            $regCourses = array_filter($courses, fn($c) => strtolower($c['program_type']) === 'regular');
            $list = [];
            $idx = 1;
            foreach ($regCourses as $c) {
                $list[] = "{$idx}. **{$c['name']} ({$c['code']})** — Duration: {$c['duration_years']} Yrs, Intake: {$c['intake_capacity']} seats, Fee: ₹" . number_format($c['fee_year_1']) . "/yr";
                $idx++;
            }
            $content = "### 🌟 SOET Regular 4-Year B.Tech Degree Programs (After 12th HSC)\n\n"
                     . "Approved by MGM University (Eligibility: HSC Passed with min 45% Open, 40% Reserved + JEE/MHT-CET/PERA-CET):\n\n"
                     . implode("\n", $list) . "\n\n"
                     . "🔗 [Apply for B.Tech Admission](/admissions.php) | [View Syllabi](/departments.php)";

            return [[
                'source_type' => 'course',
                'title' => "SOET Regular 4-Year B.Tech Programs",
                'content' => $content,
                'source_url' => '/courses.php',
                'score' => 100
            ]];
        }

        // 4. General course/program/branch list query check
        if (preg_match('/(what|which|list|available|offer)\b.*\b(courses?|programs?|degrees?|branches?|streams?)/i', $cleanQuery) || preg_match('/(courses?|programs?|degrees?|branches?)\b.*\b(do you have|are available|we offer|offered)/i', $cleanQuery) || in_array($cleanQuery, ['courses', 'programs', 'branches', 'degrees'])) {
            $regList = [];
            $intList = [];
            $dseList = [];
            $dipList = [];

            foreach ($courses as $c) {
                $pType = strtolower($c['program_type']);
                $item = "• **{$c['name']} ({$c['code']})** — {$c['duration_years']} Yrs | Intake: {$c['intake_capacity']} | Fee: ₹" . number_format($c['fee_year_1']) . "/yr";
                if ($pType === 'regular') $regList[] = $item;
                elseif ($pType === 'integrated') $intList[] = $item;
                elseif ($pType === 'lateral_entry') $dseList[] = $item;
                else $dipList[] = $item;
            }

            $content = "### 🎓 Official SOET Degree Programs & Academic Branches (AY 2026-27)\n\n"
                     . "**1. Regular B.Tech Programs (4 Years - After 12th HSC | 180 Seats):**\n" . implode("\n", $regList) . "\n\n"
                     . "**2. Integrated B.Tech Programs (6 Years - After 10th SSC | 180 Seats):**\n" . implode("\n", $intList) . "\n\n"
                     . "**3. Direct Second Year Lateral Entry (3 Years - After Diploma | 40 Seats):**\n" . implode("\n", $dseList) . "\n\n"
                     . "**4. Diploma & Advanced Diploma (20 Seats):**\n" . implode("\n", $dipList) . "\n\n"
                     . "> ℹ️ *Approved First-Year Intake: 380 Seats across 11 programs. Total Institutional Capacity: 420 Seats.*";
            
            return [[
                'source_type' => 'course',
                'title' => "SOET Programs & Academic Branches",
                'content' => $content,
                'source_url' => '/courses.php',
                'score' => 100
            ]];
        }

        $matches = [];
        $genericWords = ['tell', 'me', 'about', 'is', 'the', 'what', 'which', 'at', 'soet', 'mgm', 'mgmu', 'college', 'engineering', 'syllabus', 'curriculum', 'details', 'information', 'course', 'courses', 'btech', 'mtech', 'duration', 'intake', 'fees', 'branch', 'branches', 'for', 'program', 'programs', 'stream', 'in', 'of'];
        $queryWords = array_values(array_filter(explode(' ', mb_strtolower(preg_replace('/[^a-z0-9\s]/', ' ', $query)))));
        $distinctiveQueryWords = array_values(array_diff($queryWords, $genericWords));

        foreach ($courses as $c) {
            $text = mb_strtolower($c['name'] . ' ' . $c['code'] . ' ' . $c['description'] . ' ' . $c['dept_name'] . ' ' . $c['program_type']);
            
            // Require at least one distinctive query keyword to match with word boundaries
            if (!empty($distinctiveQueryWords)) {
                $matchedDistinctive = false;
                foreach ($distinctiveQueryWords as $dw) {
                    if (strlen($dw) >= 3 && preg_match('/\b' . preg_quote($dw, '/') . '\b/i', $text)) {
                        $matchedDistinctive = true;
                        break;
                    }
                }
                if (!$matchedDistinctive) {
                    continue;
                }
            }

            $score = $this->calculateScore($query, $distinctiveQueryWords ?: $queryWords, $text);
            if ($score > 0) {
                $content = "### {$c['name']} ({$c['code']})\n\n"
                         . "• **Department**: {$c['dept_name']}\n"
                         . "• **Program Type**: " . ucfirst(str_replace('_', ' ', $c['program_type'])) . "\n"
                         . "• **Duration**: {$c['duration_years']} Years ({$c['semester_count']} Semesters)\n"
                         . "• **Approved Intake**: {$c['intake_capacity']} Seats\n"
                         . "• **Annual Tuition Fee**: ₹" . number_format($c['fee_year_1']) . "/year\n"
                         . "• **Eligibility**: {$c['eligibility']}\n\n"
                         . "{$c['description']}";

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

    public function searchAdmissions(string $query): array
    {
        $cms = new ContentManager();
        $courses = $cms->getCourses(null, true);
        $totalIntake = array_sum(array_column($courses, 'intake_capacity'));

        $content = "### 🎓 SOET MGM University Admissions & Eligibility (AY 2026-27)\n\n"
                 . "**1. Regular B.Tech (4 Years - 180 Seats):**\n"
                 . "Passed 10+2 / HSC with min 45% aggregate in Physics, Mathematics & Chemistry/CS (40% for SC/ST/OBC) + Non-zero score in JEE(Main) / MHT-CET / PERA-CET / MGMU-CET.\n\n"
                 . "**2. Integrated B.Tech (6 Years - 180 Seats):**\n"
                 . "Passed 10th / SSC with min 50% aggregate (45% for SC/ST/OBC) + MGMU-CET.\n\n"
                 . "**3. Direct Second Year Lateral Entry (3 Years - 40 Seats):**\n"
                 . "Passed Diploma in Engineering / B.Sc. / D.Voc. with min 45% aggregate (40% for reserved categories).\n\n"
                 . "**4. Diploma & Advanced Diploma (20 Seats):**\n"
                 . "Fire Service Engg: 10th/12th Passed | ADIS: Diploma/Degree in Engg with min 50%.\n\n"
                 . "💡 *Approved First-Year Intake: 380 seats. You can submit and track admission applications online at [Admissions Portal](/admissions.php).*\n\n"
                 . "📞 **Admission Helpline**: 0240-6674999 | +91 93566 21102";

        return [[
            'source_type' => 'admission',
            'title' => 'SOET Engineering Admissions & Eligibility',
            'content' => $content,
            'source_url' => '/admissions.php',
            'score' => 100
        ]];
    }

    public function searchDepartments(string $query): array
    {
        $departments = $this->db->fetchAll("SELECT * FROM departments ORDER BY name ASC");
        if (empty($departments)) {
            return [];
        }

        $list = [];
        $idx = 1;
        foreach ($departments as $d) {
            $list[] = "{$idx}. **{$d['name']} ({$d['code']})**";
            $idx++;
        }

        return [[
            'source_type' => 'department',
            'title' => 'SOET Academic Departments',
            'content' => "SOET MGM University features the following academic departments:\n" . implode("\n", $list),
            'source_url' => '/departments.php',
            'score' => 100
        ]];
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
                $exp = getFacultyExperience($f);
                $content = "Faculty Profile: {$f['name']} is {$f['designation']} in {$f['dept_name']}. Qualification: {$f['qualification']}. Specialization: {$f['specialization']}. Experience: {$exp} Years. Email: {$f['email']}.";
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
        $placements = $this->db->fetchAll("SELECT * FROM placements WHERE is_active = 1 ORDER BY package_lpa DESC");
        if (empty($placements)) {
            return [[
                'source_type' => 'placement',
                'title' => 'SOET Training & Placements',
                'content' => 'SOET Training & Placement Cell works with leading MNC recruiters including Capgemini, TCS, Infosys, and Cognizant.',
                'source_url' => '/placements.php',
                'score' => 100
            ]];
        }

        $cleanQuery = mb_strtolower(trim($query));
        $maxPackage = max(array_column($placements, 'package_lpa'));
        $avgPackage = round(array_sum(array_column($placements, 'package_lpa')) / count($placements), 2);
        $totalPlaced = count($placements);
        $companies = array_unique(array_column($placements, 'company_name'));
        $companiesStr = implode(', ', array_slice($companies, 0, 6));

        // Check for specific company match in database
        foreach ($companies as $comp) {
            if (stripos($cleanQuery, mb_strtolower($comp)) !== false || (stripos($comp, 'toyota') !== false && stripos($cleanQuery, 'toyota') !== false) || (stripos($comp, 'persevex') !== false && stripos($cleanQuery, 'persevex') !== false)) {
                $compPlaced = array_filter($placements, fn($p) => stripos($p['company_name'], $comp) !== false || (stripos($comp, 'toyota') !== false && stripos($p['company_name'], 'toyota') !== false));
                $list = [];
                foreach ($compPlaced as $p) {
                    $desig = $p['designation'] ?? 'Engineering Associate';
                    $list[] = "• **{$p['student_name']}** — **{$p['company_name']}** with package of **{$p['package_lpa']} LPA** ({$desig}, Batch: {$p['placement_year']})";
                }
                $content = "### 🏢 Placements at {$comp} (Batch 2026)\n\n"
                         . "Verified students recruited by **{$comp}**:\n\n"
                         . implode("\n", $list) . "\n\n"
                         . "🔗 [View All Placements](/placements.php)";

                return [[
                    'source_type' => 'placement',
                    'title' => "Placements at {$comp}",
                    'content' => $content,
                    'source_url' => '/placements.php',
                    'score' => 100
                ]];
            }
        }

        // Check for specific external company query not in our current verified records
        $commonCompanies = ['capgemini', 'tcs', 'tata consultancy', 'infosys', 'wipro', 'cognizant', 'accenture', 'amazon', 'google', 'microsoft', 'tech mahindra', 'ibm', 'l&t', 'larsen'];
        foreach ($commonCompanies as $cc) {
            if (stripos($cleanQuery, $cc) !== false) {
                $compName = ucwords($cc);
                $content = "Currently, our verified Batch 2026 records do not list student placements specifically for **{$compName}**.\n\n"
                         . "Our active verified hiring partners for Batch 2026 include **Toyota Tsusho Pvt Ltd** (Highest: ₹5.20 LPA), **Persevex** (₹4.50 LPA), **Unlox**, **LearnFlu**, **Green Rad Ventures**, and **Expert Global Solutions**.\n\n"
                         . "🔗 [View Full Placement Report](/placements.php)";

                return [[
                    'source_type' => 'placement',
                    'title' => "Recruiter Verification: {$compName}",
                    'content' => $content,
                    'source_url' => '/placements.php',
                    'score' => 100
                ]];
            }
        }

        // General Placement highlights query
        if (in_array($cleanQuery, ['placements', 'placement', 'jobs', 'job', 'career', 'recruitment', 'hiring', 'companies', 'placed students']) || preg_match('/(show|get|list|tell|top|which students|which companies|highest package|average package|campus placement)\b.*\b(placements?|jobs?|recruitment|companies|placed|package|salary|record)/i', $cleanQuery) || preg_match('/\b(placement|placements|highest package|avg package|package)\b/i', $cleanQuery)) {
            $list = [];
            foreach ($placements as $p) {
                $desig = $p['designation'] ?? 'Engineering Associate';
                $list[] = "• **{$p['student_name']}** — **{$p['company_name']}** with package of **{$p['package_lpa']} LPA** ({$desig}, Batch: {$p['placement_year']})";
            }
            $content = "### 🏆 SOET Campus Recruitment & Placements (Batch 2026)\n\n"
                     . "SOET Training & Placement Cell actively connects engineering students with leading global recruiters.\n\n"
                     . "**Key Placement Statistics:**\n"
                     . "• **Highest Package**: ₹{$maxPackage} LPA\n"
                     . "• **Average Package**: ₹{$avgPackage} LPA\n"
                     . "• **Key Hiring Partners**: {$companiesStr}\n"
                     . "• **Total Verified Placements (Batch 2026)**: {$totalPlaced} students\n\n"
                     . "**Recent Placed Students:**\n" . implode("\n", $list) . "\n\n"
                     . "🔗 [View Full Placement Report](/placements.php)";

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
            $yr = $p['placement_year'] ?? date('Y');
            $text = $p['student_name'] . ' ' . $p['company_name'] . ' ' . $p['package_lpa'] . ' LPA ' . $desig;
            $score = $this->calculateScore($query, explode(' ', $query), $text);
            if ($score > 0) {
                $content = "Placement Record: **{$p['student_name']}** was placed at **{$p['company_name']}** with a salary package of **{$p['package_lpa']} LPA** (Designation: {$desig}, Batch: {$yr}).";
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
            $content = "Here are recent SOET placement records (Batch 2026):\n\n" . implode("\n", $list);

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
            'soet', 'mgm', 'mgmu', 'college', 'engineering', 'technology', 'university', 'campus', 'institute',
            'syllabus', 'department', 'details', 'information', 'about', 'tell', 'offered', 'available', 'facility',
            'facilities', 'student', 'students', 'course', 'courses', 'program', 'programs', 'branch', 'branches', 'number',
            'give', 'show', 'need', 'want', 'please', 'know', 'find', 'get', 'what', 'which', 'where', 'when', 'how'
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
                if (preg_match('/\b' . preg_quote($dkw, '/') . '\b/i', $cleanTarget)) {
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

        // Course code & alias mappings aligned with official 15 programs
        $aliases = [
            'icbt' => ['BTECH-CSE-ICBT', 'B.Tech CSE (IoT, Cyber Security Incl. Blockchain)'],
            'blockchain' => ['BTECH-CSE-ICBT', 'B.Tech CSE (IoT, Cyber Security Incl. Blockchain)'],
            'iot' => ['BTECH-CSE-ICBT', 'B.Tech CSE (IoT, Cyber Security Incl. Blockchain)'],
            'cse icbt' => ['BTECH-CSE-ICBT', 'B.Tech CSE (IoT, Cyber Security Incl. Blockchain)'],
            'robotics' => ['BTECH-ROBOTICS', 'B.Tech Robotics and Automation'],
            'automation' => ['BTECH-ROBOTICS', 'B.Tech Robotics and Automation'],
            'robotics and automation' => ['BTECH-ROBOTICS', 'B.Tech Robotics and Automation'],
            'cyber security' => ['BTECH-CSE-CYBER', 'B.Tech CSE (Cyber Security)'],
            'cyber' => ['BTECH-CSE-CYBER', 'B.Tech CSE (Cyber Security)'],
            'cse cyber' => ['BTECH-CSE-CYBER', 'B.Tech CSE (Cyber Security)'],
            'vlsi' => ['BTECH-ECE-VLSI', 'B.Tech Electronics Engineering (VLSI Design and Technology)'],
            'vlsi design' => ['BTECH-ECE-VLSI', 'B.Tech Electronics Engineering (VLSI Design and Technology)'],
            'electronics' => ['BTECH-ECE-VLSI', 'B.Tech Electronics Engineering (VLSI Design and Technology)'],
            'ece' => ['BTECH-ECE-VLSI', 'B.Tech Electronics Engineering (VLSI Design and Technology)'],
            'ee' => ['BTECH-EE', 'B.Tech Electrical Engineering'],
            'electrical' => ['BTECH-EE', 'B.Tech Electrical Engineering'],
            'integrated civil' => ['INT-CIVIL', 'B.Tech (Integrated) Civil Engineering'],
            'integrated data science' => ['INT-CSE-DS', 'B.Tech (Integrated) CSE (Data Science)'],
            'integrated ds' => ['INT-CSE-DS', 'B.Tech (Integrated) CSE (Data Science)'],
            'data science' => ['INT-CSE-DS', 'B.Tech (Integrated) CSE (Data Science)'],
            'integrated icbt' => ['INT-CSE-IOT', 'B.Tech (Integrated) CSE (IoT, Cyber Security Incl. Blockchain)'],
            'integrated iot' => ['INT-CSE-IOT', 'B.Tech (Integrated) CSE (IoT, Cyber Security Incl. Blockchain)'],
            'integrated robotics' => ['INT-ROBOTICS-AI', 'B.Tech (Integrated) Robotics and AI'],
            'integrated ai' => ['INT-ROBOTICS-AI', 'B.Tech (Integrated) Robotics and AI'],
            'dse civil' => ['DSE-CIVIL', 'B.Tech Direct 2nd Year - Civil Engineering'],
            'dse data science' => ['DSE-CSE-DS', 'B.Tech Direct 2nd Year - CSE (Data Science)'],
            'dse ds' => ['DSE-CSE-DS', 'B.Tech Direct 2nd Year - CSE (Data Science)'],
            'dse icbt' => ['DSE-CSE-ICBT', 'B.Tech Direct 2nd Year - CSE (ICBT / Cyber)'],
            'dse robotics' => ['DSE-ROBOTICS', 'B.Tech Direct 2nd Year - Robotics & AI'],
            'fire service' => ['DIP-FIRE-ENG', 'Diploma in Fire Service Engineering'],
            'industrial safety' => ['ADIP-IND-SAFETY', 'Advanced Diploma in Industrial Safety'],
            'adis' => ['ADIP-IND-SAFETY', 'Advanced Diploma in Industrial Safety'],
            'cse' => ['BTECH-CSE-ICBT', 'B.Tech CSE (IoT, Cyber Security Incl. Blockchain)'],
            'computer science' => ['BTECH-CSE-ICBT', 'B.Tech CSE (IoT, Cyber Security Incl. Blockchain)'],
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
                if (mb_strpos($cleanQuery, mb_strtolower($m['course_code'])) !== false || mb_strpos($cleanQuery, mb_strtolower($m['course_name'])) !== false) {
                    $matchedCourse = $m;
                    break;
                }
            }
        }

        // Case A: Specific course matched and found in database
        if ($matchedCourse) {
            $name = $matchedCourse['course_name'];
            $code = $matchedCourse['course_code'];
            $intake = $matchedCourse['total_intake'];
            $filled = $matchedCourse['filled_seats'];
            $vacant = $matchedCourse['vacant_seats'];
            $updatedAt = $matchedCourse['last_updated'];
            $isOpen = ($matchedCourse['admission_status'] === 'OPEN' && $vacant > 0);

            if ($isOpen) {
                $content = "### 📊 Seat Status: {$name} ({$code})\n\n"
                         . "• **Approved Intake Capacity**: **{$intake} Seats**\n"
                         . "• **Currently Vacant**: **{$vacant} Seats**\n"
                         . "• **Confirmed Admissions**: {$filled} Seats\n"
                         . "• **Admission Status**: 🟢 **OPEN**\n"
                         . "• **Last Updated**: {$updatedAt}\n\n"
                         . "🔗 [Apply Online Now](/admissions.php)";
            } else {
                $content = "Admissions for **{$name} ({$code})** are currently CLOSED. Total intake: {$intake}, Filled: {$filled}, Vacant: 0.";
            }

            return [[
                'source_type' => 'seats_database',
                'title' => "Seat Availability - {$name}",
                'content' => $content,
                'source_url' => '/admissions.php',
                'score' => 100
            ]];
        }

        // Check if user asked for a non-existent course
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
            $content = "SOET MGM University does not currently offer **'{$requestedNonExistent}'**.\n\n"
                     . "We offer approved programs in **CSE (IoT & Cyber / Blockchain)**, **Robotics & Automation**, **CSE (Cyber Security)**, **Electronics Engineering (VLSI Design)**, **Electrical Engineering**, plus **Integrated 6-Year B.Tech** and **Direct 2nd Year Lateral Entry** programs.\n\n"
                     . "🔗 [Explore All Available Programs](/courses.php)";

            return [[
                'source_type' => 'seats_database',
                'title' => "Course Not Found - Available Programs",
                'content' => $content,
                'source_url' => '/courses.php',
                'score' => 95
            ]];
        }

        // Case C: General query about all seat availability
        $firstYearIntake = 0;
        $dseIntake = 0;
        $totalIntake = 0;

        $regList = [];
        $intList = [];
        $dseList = [];
        $dipList = [];

        foreach ($metrics as $m) {
            $totalIntake += (int)$m['total_intake'];
            $statusBadge = ($m['admission_status'] === 'OPEN' && $m['vacant_seats'] > 0) ? 'Open' : 'Closed';
            $item = "• **{$m['course_name']} ({$m['course_code']})**: Intake: {$m['total_intake']} | Vacant: {$m['vacant_seats']} | Status: {$statusBadge}";

            if (str_starts_with($m['course_code'], 'BTECH-')) {
                $regList[] = $item;
                $firstYearIntake += (int)$m['total_intake'];
            } elseif (str_starts_with($m['course_code'], 'INT-')) {
                $intList[] = $item;
                $firstYearIntake += (int)$m['total_intake'];
            } elseif (str_starts_with($m['course_code'], 'DSE-')) {
                $dseList[] = $item;
                $dseIntake += (int)$m['total_intake'];
            } else {
                $dipList[] = $item;
                $firstYearIntake += (int)$m['total_intake'];
            }
        }

        $currentTime = date('d-M-Y, g:i A');

        $content = "### 📊 SOET Real-Time Seat Matrix & Intake (AY 2026-27)\n\n"
                 . "**1. Regular B.Tech (4 Years - HSC Entry | 180 Seats):**\n" . implode("\n", $regList) . "\n\n"
                 . "**2. Integrated B.Tech (6 Years - SSC 10th Entry | 180 Seats):**\n" . implode("\n", $intList) . "\n\n"
                 . "**3. Direct Second Year Lateral Entry (3 Years - Diploma Entry | 40 Seats):**\n" . implode("\n", $dseList) . "\n\n"
                 . "**4. Diploma & Advanced Diploma (20 Seats):**\n" . implode("\n", $dipList) . "\n\n"
                 . "> ℹ️ **Intake Summary**: Approved First-Year Freshmen Intake is **{$firstYearIntake} seats** across 11 degree programs (Total Institutional Capacity: **{$totalIntake} seats**). Admissions are currently **OPEN**.\n\n"
                 . "🔗 [Apply for Online Admission](/admissions.php) | [Contact Admission Desk](/contact.php)";

        return [[
            'source_type' => 'seats_database',
            'title' => "SOET Real-Time Seat Availability Summary",
            'content' => $content,
            'source_url' => '/admissions.php',
            'score' => 90
        ]];
    }

    public function searchDocuments(string $query): array
    {
        $cms = new ContentManager();
        $docs = $cms->getOfficialDocuments();
        if (empty($docs)) {
            return [];
        }

        $cleanQuery = mb_strtolower(trim($query));
        $matches = [];
        $list = [];

        foreach ($docs as $d) {
            $url = APP_URL . '/' . ltrim($d['file_path'], '/');
            $sizeStr = !empty($d['file_size']) ? $d['file_size'] : 'PDF Document';
            $itemStr = "• **{$d['title']}** (" . ucfirst($d['category']) . ", {$sizeStr}) — [📄 Download PDF]({$url})";
            $list[] = $itemStr;

            $text = mb_strtolower($d['title'] . ' ' . $d['category'] . ' ' . ($d['description'] ?? ''));
            $score = $this->calculateScore($cleanQuery, explode(' ', $cleanQuery), $text);
            if ($score > 0) {
                $matches[] = [
                    'source_type' => 'document',
                    'title' => $d['title'],
                    'content' => "### 📄 Official Document: {$d['title']}\n\n**Category**: " . ucfirst($d['category']) . "\n**File Size**: {$sizeStr}\n**Description**: {$d['description']}\n\n🔗 [Download Official PDF]({$url})",
                    'source_url' => $url,
                    'score' => $score + 15
                ];
            }
        }

        if (!empty($matches)) {
            usort($matches, fn($a, $b) => $b['score'] <=> $a['score']);
            return $matches;
        }

        $content = "### 📚 Official SOET Booklets, Leaflets & Brochures\n\nHere are the official institutional PDF documents available for direct download:\n\n" . implode("\n", $list);

        return [[
            'source_type' => 'document',
            'title' => "SOET Official Documents & Booklets",
            'content' => $content,
            'source_url' => '/courses.php',
            'score' => 95
        ]];
    }

    public function searchFacilities(string $query): array
    {
        $clean = mb_strtolower(trim($query));
        $cms = new ContentManager();
        $dbFacilities = $cms->getFacilities(true);

        if (!empty($dbFacilities)) {
            // Check for specific facility matches in database
            foreach ($dbFacilities as $f) {
                $fName = $f['title'] ?? ($f['name'] ?? 'Facility');
                $fTitle = mb_strtolower($fName);
                $fDesc = mb_strtolower($f['description'] ?? '');
                $fCat = mb_strtolower($f['category'] ?? '');
                if (mb_strpos($clean, $fTitle) !== false || (!empty($fCat) && mb_strpos($clean, $fCat) !== false)) {
                    return [[
                        'source_type' => 'facility',
                        'title' => $fName,
                        'content' => "### 🏛️ {$fName} (" . ucfirst($f['category'] ?? 'Campus Facility') . ")\n\n{$f['description']}\n\n🔗 [Explore Campus Life](/about.php)",
                        'source_url' => '/about.php',
                        'score' => 100
                    ]];
                }
            }

            // Return combined list of active facilities from database
            $list = [];
            foreach (array_slice($dbFacilities, 0, 8) as $f) {
                $fName = $f['title'] ?? ($f['name'] ?? 'Facility');
                $list[] = "• **{$fName}** (" . ucfirst($f['category'] ?? 'Facility') . "): {$f['description']}";
            }
            $allDesc = "### 🏛️ SOET Campus Infrastructure & Facilities\n\n"
                     . "SOET MGM University provides world-class campus infrastructure:\n\n"
                     . implode("\n", $list) . "\n\n"
                     . "🔗 [Explore Campus Life](/about.php)";

            return [[
                'source_type' => 'facility',
                'title' => "SOET Campus Infrastructure & Facilities",
                'content' => $allDesc,
                'source_url' => '/about.php',
                'score' => 95
            ]];
        }
        
        $facilities = [
            'labs' => [
                'title' => '🔬 Advanced Specialized Laboratories',
                'desc' => 'SOET features state-of-the-art research labs including: IoT & Embedded Systems Lab, Robotics & AI Lab, VLSI Design & Simulation Lab, AI/ML Supercomputing Lab, Cloud & Network Security Lab, CAD/CAM Center, Materials & Structures Lab, and Environmental Engineering Lab.'
            ],
            'hostel' => [
                'title' => '🏢 In-Campus Hostels & Accommodation',
                'desc' => 'Separate, secure in-campus hostels for boys and girls with 24/7 security, high-speed Wi-Fi, reading rooms, gym, water purifiers, and hygienic dining mess.'
            ],
            'sports' => [
                'title' => '⚽ Olympic-Standard Sports Complex',
                'desc' => 'MGM University campus features an international cricket stadium, football ground, synthetic basketball courts, indoor badminton courts, lawn tennis, Olympic swimming pool, and fully equipped fitness gymnasium.'
            ],
            'library' => [
                'title' => '📚 Central Library & Digital Knowledge Repository',
                'desc' => 'Over 50,000+ technical volumes, 100+ national & international journals, IEEE Xplore digital library access, DELNET subscription, and air-conditioned digital reading hall.'
            ],
            'canteen' => [
                'title' => '☕ Food Court & Cafeteria',
                'desc' => 'Hygienic campus cafeteria serving multi-cuisine vegetarian meals, snacks, fresh juices, and beverages from 8:00 AM to 8:00 PM.'
            ]
        ];

        foreach ($facilities as $key => $f) {
            if (mb_strpos($clean, $key) !== false || ($key === 'labs' && (mb_strpos($clean, 'lab') !== false || mb_strpos($clean, 'laborator') !== false))) {
                return [[
                    'source_type' => 'facility',
                    'title' => $f['title'],
                    'content' => "### {$f['title']}\n\n{$f['desc']}\n\n🔗 [Explore Campus Life](/about.php)",
                    'source_url' => '/about.php',
                    'score' => 100
                ]];
            }
        }

        $allDesc = "### 🏛️ SOET Campus Infrastructure & Facilities\n\n"
                 . "SOET MGM University provides world-class campus infrastructure:\n\n"
                 . "• **Advanced Research Labs**: IoT, Robotics & AI, VLSI Design, Cloud Security & CAD/CAM Centers.\n"
                 . "• **In-Campus Hostels**: 24/7 security, Wi-Fi, study halls, and dining mess for boys & girls.\n"
                 . "• **Central Library**: 50,000+ volumes, IEEE digital access & research repository.\n"
                 . "• **MGM Sports Complex**: Olympic-size swimming pool, cricket stadium, indoor courts & gymnasium.\n"
                 . "• **Wi-Fi Campus & Smart Classrooms**: High-speed fiber-optic network and ICT-enabled auditoriums.\n\n"
                 . "🔗 [Explore Campus Life](/about.php)";

        return [[
            'source_type' => 'facility',
            'title' => "SOET Campus Infrastructure & Facilities",
            'content' => $allDesc,
            'source_url' => '/about.php',
            'score' => 90
        ]];
    }

    private function searchCourseFees(string $query): array
    {
        $cms = new ContentManager();
        $activeAY = $cms->getActiveAcademicYear();
        $courses = $this->db->fetchAll("SELECT * FROM courses WHERE is_active = 1 ORDER BY sort_order ASC, name ASC");
        $cleanQuery = mb_strtolower(trim($query));
        $matches = [];

        foreach ($courses as $c) {
            $code = mb_strtolower($c['code']);
            $name = mb_strtolower($c['name']);

            // Match course code or name substring
            if (mb_strpos($cleanQuery, $code) !== false || 
               (mb_strpos($cleanQuery, 'icbt') !== false && mb_strpos($code, 'icbt') !== false) || 
               (mb_strpos($cleanQuery, 'vlsi') !== false && mb_strpos($code, 'vlsi') !== false) || 
               (mb_strpos($cleanQuery, 'cyber') !== false && mb_strpos($code, 'cyber') !== false) || 
               (mb_strpos($cleanQuery, 'robotics') !== false && mb_strpos($code, 'robotics') !== false) || 
               (mb_strpos($cleanQuery, 'data science') !== false && mb_strpos($name, 'data science') !== false) || 
               (mb_strpos($cleanQuery, 'civil') !== false && mb_strpos($code, 'civil') !== false) || 
               (mb_strpos($cleanQuery, 'fire') !== false && mb_strpos($code, 'fire') !== false) || 
               (mb_strpos($cleanQuery, 'safety') !== false && mb_strpos($code, 'safety') !== false) || 
               (mb_strpos($cleanQuery, 'adis') !== false && mb_strpos($code, 'safety') !== false) || 
               (mb_strpos($cleanQuery, 'electrical') !== false && mb_strpos($code, 'ee') !== false)) {
                
                $ywFees = $cms->getCourseYearWiseFees((int)$c['id'], $activeAY);
                $yearLines = [];
                $totalCost = 0;

                if (!empty($ywFees)) {
                    foreach ($ywFees as $yf) {
                        $totalCost += (float)$yf['total_fee'];
                        $yearLines[] = "• **{$yf['year_name']}** ({$yf['semesters_included']}): ₹" . number_format($yf['total_fee']) . " (Tuition: ₹" . number_format($yf['tuition_fee']) . " | Dev: ₹" . number_format($yf['development_fee']) . " | Exam/Other: ₹" . number_format($yf['exam_fee'] + $yf['other_fee']) . ")";
                    }
                } else {
                    $duration = (int)($c['duration_years'] ?: 4);
                    for ($y = 1; $y <= $duration; $y++) {
                        $feeVal = (float)($c["fee_year_{$y}"] ?? $c['fee_year_1']);
                        $totalCost += $feeVal;
                        $yearLines[] = "• **Year {$y}**: ₹" . number_format($feeVal) . " / year";
                    }
                }

                $content = "### 💳 Official Year-Wise Fee Structure: {$c['name']} ({$c['code']})\n\n"
                         . "**Academic Session**: {$activeAY}\n"
                         . "**Duration**: {$c['duration_years']} Years ({$c['semester_count']} Semesters)\n\n"
                         . "**Year-Wise Breakdown:**\n" . implode("\n", $yearLines) . "\n\n"
                         . "💰 **Total Multi-Year Degree Fee**: **₹" . number_format($totalCost) . "**\n\n"
                         . "> ℹ️ *Includes Tuition Fee, Development Fee, and Examination Fee (₹2,500/semester). Government SC/ST/OBC and MGM Merit Scholarships apply.*\n\n"
                         . "🔗 [View Full Programs & Fee Breakdown](/courses.php) | [Apply Online](/admissions.php)";

                $matches[] = [
                    'source_type' => 'fee_database',
                    'title' => "Fee Structure - {$c['name']}",
                    'content' => $content,
                    'source_url' => '/courses.php',
                    'score' => 100
                ];
                break;
            }
        }

        if (empty($matches)) {
            $regList = [];
            $intList = [];
            $dseList = [];
            $dipList = [];

            foreach ($courses as $c) {
                $pType = strtolower($c['program_type']);
                $feeStr = "₹" . number_format($c['fee_year_1']) . "/yr";
                $item = "• **{$c['name']} ({$c['code']})**: {$feeStr} ({$c['duration_years']} Yrs)";
                if ($pType === 'regular') $regList[] = $item;
                elseif ($pType === 'integrated') $intList[] = $item;
                elseif ($pType === 'lateral_entry') $dseList[] = $item;
                else $dipList[] = $item;
            }

            $content = "### 💳 SOET Year-Wise Annual Fee Matrix (A.Y. {$activeAY})\n\n"
                     . "**1. Regular 4-Year B.Tech Programs (After 12th HSC):**\n" . implode("\n", $regList) . "\n\n"
                     . "**2. Integrated 6-Year B.Tech Programs (After 10th SSC):**\n" . implode("\n", $intList) . "\n\n"
                     . "**3. Direct Second Year Lateral Entry (3 Years - After Diploma):**\n" . implode("\n", $dseList) . "\n\n"
                     . "**4. Diploma & Advanced Diploma Programs:**\n" . implode("\n", $dipList) . "\n\n"
                     . "> ℹ️ *Government SC/ST/OBC/EBC scholarships and MGM Merit Scholarships (10% to 30% tuition fee waiver) are available for eligible students.*\n\n"
                     . "🔗 [View Complete Fee Details](/courses.php) | [Scholarship Calculator](/admissions.php)";

            $matches[] = [
                'source_type' => 'fee_database',
                'title' => "SOET Annual Tuition Fee Structure",
                'content' => $content,
                'source_url' => '/courses.php',
                'score' => 90
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

    public function searchSyllabus(string $query): array
    {
        $courses = $this->db->fetchAll("SELECT c.*, d.name as dept_name FROM courses c JOIN departments d ON c.department_id = d.id WHERE c.is_active = 1");
        $cleanQuery = mb_strtolower(trim($query));
        $matches = [];

        foreach ($courses as $c) {
            $code = mb_strtolower($c['code']);
            $name = mb_strtolower($c['name']);

            if (mb_strpos($cleanQuery, $code) !== false ||
                (mb_strpos($cleanQuery, 'cse') !== false && mb_strpos($code, 'cse') !== false) ||
                (mb_strpos($cleanQuery, 'civil') !== false && mb_strpos($code, 'civil') !== false) ||
                (mb_strpos($cleanQuery, 'mech') !== false && mb_strpos($code, 'mech') !== false) ||
                (mb_strpos($cleanQuery, 'ece') !== false && mb_strpos($code, 'ece') !== false) ||
                (mb_strpos($cleanQuery, 'ee') !== false && mb_strpos($code, 'ee') !== false) ||
                (mb_strpos($cleanQuery, 'robotics') !== false && mb_strpos($name, 'robotics') !== false) ||
                (mb_strpos($cleanQuery, 'vlsi') !== false && mb_strpos($name, 'vlsi') !== false)) {

                $pdfLinks = [];
                for ($yr = 1; $yr <= (int)$c['duration_years']; $yr++) {
                    $col = "syllabus_pdf_year_{$yr}";
                    if (!empty($c[$col])) {
                        $pdfUrl = uploadUrl('syllabus', $c[$col]);
                        $pdfLinks[] = "• **Year {$yr} Syllabus**: [📄 Download PDF]({$pdfUrl})";
                    }
                }

                $pdfList = !empty($pdfLinks) ? implode("\n", $pdfLinks) : "• Official course syllabi are available on the [Departments & Programs](/departments.php) page.";

                $content = "### 📚 Official Syllabus — {$c['name']} ({$c['code']})\n\n"
                         . "The curriculum is structured across {$c['duration_years']} Years ({$c['semester_count']} Semesters) aligned with NEP 2020 guidelines:\n\n"
                         . "{$pdfList}\n\n"
                         . "🔗 [View Departments & Programs Details](/departments.php) | [Fee Structure](/courses.php)";

                $matches[] = [
                    'source_type' => 'syllabus_database',
                    'title'       => "Syllabus - {$c['name']}",
                    'content'     => $content,
                    'source_url'  => '/departments.php',
                    'score'       => 100
                ];
                break;
            }
        }

        if (empty($matches)) {
            $content = "### 📚 SOET Academic Curriculum & Syllabi\n\n"
                     . "All SOET B.Tech engineering programs follow the AICTE & NEP 2020 aligned semester curriculum with dedicated practical labs, industry internships, and minor specializations.\n\n"
                     . "You can download year-wise syllabus PDFs for all 18 engineering programs directly on our [Departments & Programs](/departments.php) page.";

            $matches[] = [
                'source_type' => 'syllabus_database',
                'title'       => "SOET Academic Syllabi",
                'content'     => $content,
                'source_url'  => '/departments.php',
                'score'       => 85
            ];
        }

        return $matches;
    }
}

