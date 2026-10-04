<?php
require_once 'config/database.php';
require_once 'core/Database.php';

$db = Database::getInstance();

echo "Updating Chatbot Knowledge Base and FAQs with Brochure data...\n";

// 1. Update/Insert into chatbot_kb
$kbItems = [
    [
        'trigger_words' => 'programs,courses,branches,degrees,offerings',
        'response_text' => 'SOET MGM University offers: 1) UG 4-Year B.Tech Programs after HSC (CSE with IoT/Cyber/Blockchain, CSE Cyber Security, Electronics Engineering VLSI Design & Tech, Robotics and Automation, Electrical Engineering), 2) UG 6-Year B.Tech Integrated Programs after SSC (CSE IoT/Cyber/Blockchain, CSE Data Science, Robotics and AI, Civil Engineering), 3) Direct 2nd Year Lateral Entry, and 4) Diploma Programs (2-Year Diploma in Fire Service & Engineering, 1-Year Advanced Diploma in Industrial Safety).',
        'category' => 'courses'
    ],
    [
        'trigger_words' => 'eligibility,criteria,requirements,qualify',
        'response_text' => 'Eligibility criteria at SOET MGM University: • B.Tech (4 Years after HSC): HSC Passed with min 45% (Open) / 40% (Reserved) + appeared in JEE(Mains)/MHT-CET/PERA-CET/MGMU-CET (with non-zero score). • Lateral Entry: Direct 2nd Year after Diploma in Engg/Tech, B.Sc. Degree, or D.Voc. • B.Tech Integrated (6 Years after SSC): SSC Passed with min 50% (Open) / 45% (Reserved) + MGMU-CET. • Integrated Lateral Entry: Direct 2nd Year after HSC Passed. • Diploma in Fire Service: 10th/12th Passed. • Advanced Diploma in Industrial Safety: Diploma in Engg / B.Sc / Tech Degree.',
        'category' => 'admissions'
    ],
    [
        'trigger_words' => 'admission process,how to apply,admission steps,procedure',
        'response_text' => 'The 6-Step Admission Procedure at SOET MGM University: Step 1: Fill Application on admissions.mgmu.ac.in. Step 2: Appear for MGMU CET. Step 3: Check Merit List Displayed on Dashboard. Step 4: Verification & Submission of Original Documents. Step 5: Processing of Tuition & College Fees. Step 6: Admission Successfully Processed.',
        'category' => 'admissions'
    ],
    [
        'trigger_words' => 'collaborations,partners,mous,tieups',
        'response_text' => 'SOET MGM University has active strategic collaborations with 14+ leading industry and academic organizations: Škoda Auto Volkswagen India, Infosys, LIEBHERR, HIKAL, Atlas Copco, NASSCOM, RUCHA ENGINEERING 2.0, CIPET, Innovation Incubation Center, RAJURI STEEL, Bridgewater State University (USA), ISHRAE, MAHAVITARAN (MSEDCL), SONY, and NIELIT.',
        'category' => 'collaborations'
    ],
    [
        'trigger_words' => 'recruiters,companies,placements,hiring,salary',
        'response_text' => 'SOET MGM University campus placement prime recruiters include: Endress+Hauser, TATA Consultancy Services (TCS), IBM, Tech Mahindra, Deloitte, Cognizant, LIEBHERR, Infosys, Škoda Auto, Adobe, BOSCH, Atlas Copco, Wipro, Reliance Jio, and IFB. The highest salary package reached ₹18.5 LPA with an average package of ₹5.5 LPA.',
        'category' => 'placements'
    ],
    [
        'trigger_words' => 'labs,facilities,infrastructure,robotics lab,ev lab',
        'response_text' => 'SOET features cutting-edge specialized laboratories: 1) Industrial Robotics Lab (intelligent machines and automated kinematics), 2) Electric Vehicle (EV) Lab (sustainable mobility and battery technology), 3) Mechatronics and Industry 4.0 Lab (PLC, SCADA, HMI), 4) Low-Power High-Speed Chip Design & Cleanroom Labs, 5) Innovation Incubation and Research Center (IIRC) & Startup Cell, plus modern smart classrooms, central library, hostels, and sports complex.',
        'category' => 'facilities'
    ],
    [
        'trigger_words' => 'mgm university,trust,about university,act,ugc',
        'response_text' => 'MGM University was established by the revered Mahatma Gandhi Mission Trust, with over 42 years of remarkable legacy in education, research, and service. It is a State Private University approved by Government of Maharashtra (MGM University Act No. XXVI of 2019) and holds 2(f) recognized status granted by the University Grants Commission (UGC) of India.',
        'category' => 'general'
    ],
    [
        'trigger_words' => 'contact,phone,email,address,helpline,location',
        'response_text' => 'SOET MGM University Contact Coordinates: • Address: MGM Campus, N-6 CIDCO, Chhatrapati Sambhajinagar - 431003, Maharashtra. • Helpline Phone: +91 9371714253. • Official Email: soet@mgmu.ac.in / admissionsoet@mgmu.ac.in. • Website: soet.mgmu.ac.in | Admissions: admissions.mgmu.ac.in.',
        'category' => 'contact'
    ]
];

foreach ($kbItems as $item) {
    $existing = $db->fetchOne("SELECT id FROM chatbot_kb WHERE trigger_words = ?", [$item['trigger_words']]);
    if ($existing) {
        $db->update('chatbot_kb', $item, 'id = ?', [$existing['id']]);
    } else {
        $db->insert('chatbot_kb', $item);
    }
}
echo "chatbot_kb updated!\n";

// 2. Add/Update FAQs
$faqs = [
    [
        'question' => 'What is the vision and mission of SOET MGM University?',
        'answer' => "Vision: To empower students as leaders of tomorrow by fostering a culture of innovation, skill development, and adaptability, bridging the gap between academic excellence and industry readiness in an ever-evolving global economy.\n\nMission:\n• To cultivate future-ready professionals through transformative training and experiential learning.\n• To inspire students to embrace emerging trends and technologies, building versatile and sustainable career paths.\n• To provide personalized career mentorship and foster meaningful collaborations with leading industries.\n• To create an ecosystem that nurtures talent, values diversity, and encourages lifelong learning for global career success.",
        'category' => 'general',
        'keywords' => 'vision, mission, about soet, purpose, goals',
        'status' => 'active'
    ],
    [
        'question' => 'What are the 6 steps of the admission procedure at SOET?',
        'answer' => "The 6-Step Admission Procedure:\nStep 01: Fill Application on admissions.mgmu.ac.in\nStep 02: Appear for MGMU CET\nStep 03: Check Merit List Displayed on Dashboard\nStep 04: Verification & Submission of Documents\nStep 05: Processing of Fees\nStep 06: Admission Successfully Processed",
        'category' => 'admissions',
        'keywords' => 'admission procedure, how to take admission, 6 steps, admission flow',
        'status' => 'active'
    ],
    [
        'question' => 'What are the eligibility criteria for B.Tech (4 Years) at SOET?',
        'answer' => "First Year B.Tech (4 Years after HSC):\n• HSC Passed with Physics & Mathematics as compulsory subjects.\n• Minimum 45% for Open and Minimum 40% for Reserved Category candidates.\n• Candidate should have appeared in JEE(Mains) / MHT-CET / PERA-CET / MGMU-CET (with non-zero score).\n\nLateral Entry (Direct 2nd Year):\n• Direct Second Year admission after Diploma Course in Engineering & Technology / B.Sc. Degree / D. Voc.",
        'category' => 'admissions',
        'keywords' => 'eligibility btech, 4 year eligibility, 12th criteria, lateral entry',
        'status' => 'active'
    ],
    [
        'question' => 'What are the eligibility criteria for B.Tech Integrated (6 Years) at SOET?',
        'answer' => "First Year B.Tech Integrated (6 Years after SSC):\n• SSC (Class 10) Passed.\n• Minimum 50% for Open and Minimum 45% for Reserved Category candidates.\n• Valid score in MGMU-CET.\n\nIntegrated Lateral Entry (Direct 2nd Year):\n• Direct Second Year admission after HSC Passed.",
        'category' => 'admissions',
        'keywords' => 'integrated eligibility, 6 year btech, after 10th, ssc criteria',
        'status' => 'active'
    ],
    [
        'question' => 'What diploma programs are offered at SOET?',
        'answer' => "SOET offers two specialized technical diploma programs:\n1. Diploma in Fire Service and Engineering (2 Years) - Covers fire science, sprinkler systems, rescue operations, and disaster management.\n2. Advanced Diploma in Industrial Safety (1 Year) - Covers accident prevention, Factories Act, OSHA/ISO safety standards, risk assessment, and audits.",
        'category' => 'courses',
        'keywords' => 'diploma, fire safety, industrial safety, advanced diploma',
        'status' => 'active'
    ],
    [
        'question' => 'Who are the major recruiters and collaborators of SOET MGM University?',
        'answer' => "Collaborations (14+ MoUs): Škoda Auto Volkswagen India, Infosys, LIEBHERR, HIKAL, Atlas Copco, NASSCOM, Rucha Engineering 2.0, CIPET, Innovation Incubation Center, Rajuri Steel, Bridgewater State University (USA), ISHRAE, Mahavitaran, Sony, and NIELIT.\n\nPrime Recruiters: Endress+Hauser, TCS, IBM, Tech Mahindra, Deloitte, Cognizant, Liebherr, Infosys, Škoda, Adobe, Bosch, Atlas Copco, Wipro, Reliance Jio, and IFB.",
        'category' => 'placements',
        'keywords' => 'collaborations, recruiters, placement companies, partners, companies',
        'status' => 'active'
    ]
];

foreach ($faqs as $f) {
    $existing = $db->fetchOne("SELECT id FROM faq WHERE question = ?", [$f['question']]);
    if ($existing) {
        $db->update('faq', $f, 'id = ?', [$existing['id']]);
    } else {
        $db->insert('faq', $f);
    }
}
echo "FAQ table updated!\n";

// 3. Update knowledge_base table
$kbDocs = [
    [
        'title' => 'SOET Official Admission Brochure & Academic Programs Guide',
        'category' => 'programs',
        'content' => "School of Engineering and Technology (SOET), MGM University offers UG 4-Year B.Tech programs after HSC, UG 6-Year Integrated B.Tech programs after SSC, Direct 2nd Year Lateral Entry, and Diploma Programs. 4-Year B.Tech includes CSE (IoT, Cyber Security & Blockchain), CSE (Cyber Security), Electronics Engineering (VLSI Design & Technology), Robotics and Automation, and Electrical Engineering. 6-Year Integrated B.Tech includes CSE (IoT & Cyber Security), CSE (Data Science), Robotics & AI, and Civil Engineering. Diploma programs include Diploma in Fire Service & Engineering (2 Yrs) and Advanced Diploma in Industrial Safety (1 Yr). Teaching methodology is NEP 2020 aligned, Problem-Based Learning (PBL), and Project-Based Learning backed by Research.",
        'source_url' => '/courses.php',
        'keywords' => 'brochure, programs, btech, integrated, diploma, vlsi, robotics, cyber security, fire safety',
        'status' => 'active'
    ],
    [
        'title' => 'SOET MGM University Accreditation, Legacy and Governance',
        'category' => 'general',
        'content' => "MGM University was established by the revered Mahatma Gandhi Mission Trust, carrying over 42 years of remarkable legacy in education, research, and service. It is a State Private University approved by the Government of Maharashtra under MGM University Act No. XXVI of 2019 and holds 2(f) status recognized by the University Grants Commission (UGC) of India.",
        'source_url' => '/about.php',
        'keywords' => 'mgm trust, university act, ugc 2f, accreditation, legacy',
        'status' => 'active'
    ],
    [
        'title' => 'SOET Official Admission Procedure and Eligibility Guidelines',
        'category' => 'admissions',
        'content' => "The 6-Step Admission Flow: 1. Fill Application on admissions.mgmu.ac.in, 2. Appear for MGMU CET, 3. Check Merit List Displayed on Dashboard, 4. Verification & Submission of Documents, 5. Processing of Fees, 6. Admission Successfully Processed. Eligibility: 4-Year B.Tech requires HSC with min 45% (Open) / 40% (Reserved) + JEE/MHT-CET/PERA-CET/MGMU-CET. 6-Year Integrated B.Tech requires SSC with min 50% (Open) / 45% (Reserved) + MGMU-CET. Direct Second Year requires Diploma/B.Sc. for B.Tech, or HSC for Integrated B.Tech.",
        'source_url' => '/admissions.php',
        'keywords' => 'admission process, eligibility, mgmu cet, 6 steps, merit list, cut off',
        'status' => 'active'
    ],
    [
        'title' => 'SOET Collaborations and Prime Campus Recruiters',
        'category' => 'placements',
        'content' => "Collaborations: Škoda Auto Volkswagen, Infosys, LIEBHERR, HIKAL, Atlas Copco, NASSCOM, RUCHA ENGINEERING 2.0, CIPET, Incubation Center, RAJURI STEEL, Bridgewater State University, ISHRAE, MAHAVITARAN, SONY, NIELIT. Prime Recruiters: Endress+Hauser, TCS, IBM, Tech Mahindra, Deloitte, Cognizant, LIEBHERR, Infosys, Škoda, Adobe, BOSCH, Atlas Copco, Wipro, Reliance Jio, IFB. Highest CTC: 18.5 LPA, Average CTC: 5.5 LPA.",
        'source_url' => '/placements.php',
        'keywords' => 'placements, recruiters, collaborations, mous, highest package, average package',
        'status' => 'active'
    ]
];

foreach ($kbDocs as $doc) {
    $existing = $db->fetchOne("SELECT id FROM knowledge_base WHERE title = ?", [$doc['title']]);
    if ($existing) {
        $db->update('knowledge_base', $doc, 'id = ?', [$existing['id']]);
    } else {
        $db->insert('knowledge_base', $doc);
    }
}
echo "knowledge_base table updated!\n";

echo "ALL KNOWLEDGE BASE UPDATES COMPLETE!\n";
