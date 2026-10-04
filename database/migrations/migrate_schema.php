<?php
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Starting schema migration...\n";

    // 1. Create facilities table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `facilities` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `title` varchar(150) NOT NULL,
        `category` varchar(100) DEFAULT 'Laboratory',
        `description` text NOT NULL,
        `icon` varchar(50) DEFAULT 'fa-solid fa-microchip',
        `image_path` varchar(255) DEFAULT NULL,
        `features` text DEFAULT NULL,
        `sort_order` int(11) DEFAULT 0,
        `is_active` tinyint(1) DEFAULT 1,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "- facilities table checked/created\n";

    // 2. Create placement_recruiters table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `placement_recruiters` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(150) NOT NULL,
        `category` varchar(100) DEFAULT 'Prime Recruiter',
        `logo_image` varchar(255) DEFAULT NULL,
        `icon` varchar(50) DEFAULT 'fa-solid fa-briefcase',
        `color` varchar(30) DEFAULT '#2563eb',
        `bg_color` varchar(50) DEFAULT 'rgba(37, 99, 235, 0.08)',
        `sort_order` int(11) DEFAULT 0,
        `is_active` tinyint(1) DEFAULT 1,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "- placement_recruiters table checked/created\n";

    // 3. Create homepage_activities table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `homepage_activities` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `title` varchar(150) NOT NULL,
        `badge` varchar(100) DEFAULT 'Conclave',
        `year_or_tag` varchar(50) DEFAULT '2026',
        `description` text NOT NULL,
        `image_path` varchar(255) DEFAULT NULL,
        `sort_order` int(11) DEFAULT 0,
        `is_active` tinyint(1) DEFAULT 1,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "- homepage_activities table checked/created\n";

    // Helper to add column if it doesn't exist
    $addColumnIfNotExists = function($table, $column, $definition) use ($pdo) {
        $cols = $pdo->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'")->fetchAll();
        if (empty($cols)) {
            $pdo->exec("ALTER TABLE `{$table}` ADD `{$column}` {$definition}");
            echo "  + Added column `{$column}` to `{$table}`\n";
        }
    };

    // 4. Enhance departments table
    $addColumnIfNotExists('departments', 'hod_name', "varchar(100) DEFAULT NULL");
    $addColumnIfNotExists('departments', 'hod_designation', "varchar(150) DEFAULT NULL");
    $addColumnIfNotExists('departments', 'contact_email', "varchar(100) DEFAULT NULL");
    $addColumnIfNotExists('departments', 'contact_phone', "varchar(50) DEFAULT NULL");
    $addColumnIfNotExists('departments', 'facilities_text', "text DEFAULT NULL");
    $addColumnIfNotExists('departments', 'sort_order', "int(11) DEFAULT 0");

    // 5. Enhance courses table
    $addColumnIfNotExists('courses', 'sort_order', "int(11) DEFAULT 0");
    $addColumnIfNotExists('courses', 'curriculum_overview', "text DEFAULT NULL");
    $addColumnIfNotExists('courses', 'admission_info', "text DEFAULT NULL");

    // 6. Enhance faculty table
    $addColumnIfNotExists('faculty', 'bio', "text DEFAULT NULL");
    $addColumnIfNotExists('faculty', 'sort_order', "int(11) DEFAULT 0");

    // 7. Enhance events table
    $addColumnIfNotExists('events', 'category', "varchar(100) DEFAULT 'Technical'");
    $addColumnIfNotExists('events', 'department_id', "int(11) DEFAULT NULL");
    $addColumnIfNotExists('events', 'is_featured', "tinyint(1) DEFAULT 0");
    $addColumnIfNotExists('events', 'sort_order', "int(11) DEFAULT 0");

    // 8. Enhance gallery table
    $addColumnIfNotExists('gallery', 'is_featured', "tinyint(1) DEFAULT 0");
    $addColumnIfNotExists('gallery', 'sort_order', "int(11) DEFAULT 0");

    // 9. Enhance documents table
    $addColumnIfNotExists('documents', 'sort_order', "int(11) DEFAULT 0");

    // 10. Enhance placements table
    $addColumnIfNotExists('placements', 'sort_order', "int(11) DEFAULT 0");

    // 11. Seed default facilities if empty
    $facCount = (int)$pdo->query("SELECT COUNT(*) FROM facilities")->fetchColumn();
    if ($facCount === 0) {
        $defaultFacilities = [
            [
                'title' => 'Industrial Robotics Lab',
                'category' => 'Advanced Laboratory',
                'description' => 'Equipped with industrial robotic arms, automated kinematics rigs, and computer vision systems where students build and program intelligent machines.',
                'icon' => 'fa-solid fa-robot',
                'image_path' => 'event_robotics_training.jpg',
                'features' => "Industrial Robotic Arms\nKinematics & ROS Programming\nComputer Vision Systems\nAutomated Shopfloor Rig",
                'sort_order' => 1
            ],
            [
                'title' => 'Electric Vehicle (EV) Lab',
                'category' => 'Clean Mobility & Power',
                'description' => 'Dedicated to exploring sustainable mobility, battery management systems (BMS), motor control drives, and next-generation green transportation.',
                'icon' => 'fa-solid fa-car-battery',
                'image_path' => 'soet_future_tech_banner.jpg',
                'features' => "Battery Management Systems (BMS)\nMotor Control Drives\nGreen Energy Powertrains\nEV Prototyping Bench",
                'sort_order' => 2
            ],
            [
                'title' => 'Mechatronics & Industry 4.0',
                'category' => 'Smart Automation',
                'description' => 'State-of-the-art PLC, SCADA, HMI, smart sensors, and cyber-physical systems designed for prototyping automated Industry 4.0 smart factory solutions.',
                'icon' => 'fa-solid fa-gears',
                'image_path' => 'soet_campus_aerial.jpg',
                'features' => "PLC & SCADA Controllers\nHMI Interface Programming\nSmart Sensor Networks\nCyber-Physical Automation",
                'sort_order' => 3
            ],
            [
                'title' => 'Innovation & Incubation Center',
                'category' => 'Startup & R&D Hub',
                'description' => 'The IIRC provides seed mentorship, patent guidance, and prototyping workspace for students building drones, IoT platforms, and commercial startups.',
                'icon' => 'fa-solid fa-lightbulb',
                'image_path' => 'event_supernova_group.jpg',
                'features' => "Seed Funding & Mentorship\nPatent & IPR Filing Cell\nRapid Prototyping Workshop\nStartup Co-working Space",
                'sort_order' => 4
            ],
            [
                'title' => 'Smart Classrooms & Library',
                'category' => 'Academic Infrastructure',
                'description' => 'Air-conditioned interactive smart lecture halls, central library with 45,000+ volumes, IEEE digital subscriptions, and high-speed campus Wi-Fi.',
                'icon' => 'fa-solid fa-chalkboard-user',
                'image_path' => 'gallery_6a830097c02b9_1786970263.jpg',
                'features' => "Interactive Smart Boards\n45,000+ Volumes & Journals\nIEEE & ScienceDirect Digital Access\nCampus-wide Fiber Wi-Fi",
                'sort_order' => 5
            ],
            [
                'title' => 'Holistic Campus & Hostels',
                'category' => 'Student Living & Sports',
                'description' => 'Separate modern residential hostels for boys and girls, Olympic-standard sports grounds, fitness gymnasium, and vibrant student cultural forums.',
                'icon' => 'fa-solid fa-futbol',
                'image_path' => 'activity_sports_team.jpg',
                'features' => "Separate Secure Hostels\nOlympic Size Sports Ground\nFitness Gym & Cafeteria\nCultural & Drama Amphitheater",
                'sort_order' => 6
            ]
        ];

        $insFac = $pdo->prepare("INSERT INTO facilities (title, category, description, icon, image_path, features, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
        foreach ($defaultFacilities as $f) {
            $insFac->execute([$f['title'], $f['category'], $f['description'], $f['icon'], $f['image_path'], $f['features'], $f['sort_order']]);
        }
        echo "- Seeded default facilities (" . count($defaultFacilities) . " items)\n";
    }

    // 12. Seed default placement_recruiters if empty
    $recCount = (int)$pdo->query("SELECT COUNT(*) FROM placement_recruiters")->fetchColumn();
    if ($recCount === 0) {
        $defaultRecruiters = [
            ['name' => 'Toyota Tsusho Pvt Ltd', 'category' => 'Automotive', 'icon' => 'fa-solid fa-car', 'color' => '#dc2626', 'bg_color' => 'rgba(220, 38, 38, 0.08)', 'sort_order' => 1],
            ['name' => 'Persevex', 'category' => 'IT & Software', 'icon' => 'fa-solid fa-code', 'color' => '#2563eb', 'bg_color' => 'rgba(37, 99, 235, 0.08)', 'sort_order' => 2],
            ['name' => 'Expert Global Solutions', 'category' => 'Engineering Services', 'icon' => 'fa-solid fa-gears', 'color' => '#059669', 'bg_color' => 'rgba(5, 150, 105, 0.08)', 'sort_order' => 3],
            ['name' => 'LearnFlu', 'category' => 'EdTech', 'icon' => 'fa-solid fa-graduation-cap', 'color' => '#d97706', 'bg_color' => 'rgba(217, 119, 6, 0.08)', 'sort_order' => 4],
            ['name' => 'Unlox', 'category' => 'Cyber Security', 'icon' => 'fa-solid fa-lock-open', 'color' => '#7c3aed', 'bg_color' => 'rgba(124, 58, 237, 0.08)', 'sort_order' => 5],
            ['name' => 'Green Rad Ventures', 'category' => 'Sustainability & Tech', 'icon' => 'fa-solid fa-leaf', 'color' => '#16a34a', 'bg_color' => 'rgba(22, 163, 74, 0.08)', 'sort_order' => 6],
            ['name' => 'Quess Corp Limited', 'category' => 'Enterprise Services', 'icon' => 'fa-solid fa-briefcase', 'color' => '#0284c7', 'bg_color' => 'rgba(2, 132, 199, 0.08)', 'sort_order' => 7],
            ['name' => 'TATA Consultancy Services (TCS)', 'category' => 'IT Multinational', 'icon' => 'fa-solid fa-laptop-code', 'color' => '#1e3a8a', 'bg_color' => 'rgba(30, 58, 138, 0.08)', 'sort_order' => 8],
            ['name' => 'IBM', 'category' => 'Cloud & AI', 'icon' => 'fa-solid fa-server', 'color' => '#2563eb', 'bg_color' => 'rgba(37, 99, 235, 0.08)', 'sort_order' => 9],
            ['name' => 'Tech Mahindra', 'category' => 'Telecom & IT', 'icon' => 'fa-solid fa-microchip', 'color' => '#dc2626', 'bg_color' => 'rgba(220, 38, 38, 0.08)', 'sort_order' => 10],
            ['name' => 'Deloitte', 'category' => 'Consulting', 'icon' => 'fa-solid fa-chart-pie', 'color' => '#059669', 'bg_color' => 'rgba(5, 150, 105, 0.08)', 'sort_order' => 11],
            ['name' => 'Cognizant', 'category' => 'IT Services', 'icon' => 'fa-solid fa-brain', 'color' => '#0284c7', 'bg_color' => 'rgba(2, 132, 199, 0.08)', 'sort_order' => 12],
            ['name' => 'LIEBHERR', 'category' => 'Heavy Engineering', 'icon' => 'fa-solid fa-truck-monster', 'color' => '#d97706', 'bg_color' => 'rgba(217, 119, 6, 0.08)', 'sort_order' => 13],
            ['name' => 'Infosys', 'category' => 'IT & Software', 'icon' => 'fa-solid fa-network-wired', 'color' => '#0284c7', 'bg_color' => 'rgba(2, 132, 199, 0.08)', 'sort_order' => 14],
            ['name' => 'Škoda Auto Volkswagen', 'category' => 'Automotive OEM', 'icon' => 'fa-solid fa-car-side', 'color' => '#15803d', 'bg_color' => 'rgba(21, 128, 61, 0.08)', 'sort_order' => 15],
            ['name' => 'Adobe', 'category' => 'Product Software', 'icon' => 'fa-solid fa-cube', 'color' => '#e11d48', 'bg_color' => 'rgba(225, 29, 72, 0.08)', 'sort_order' => 16],
            ['name' => 'BOSCH', 'category' => 'Automotive & IoT', 'icon' => 'fa-solid fa-gears', 'color' => '#ea580c', 'bg_color' => 'rgba(234, 88, 12, 0.08)', 'sort_order' => 17],
            ['name' => 'Atlas Copco', 'category' => 'Industrial Machinery', 'icon' => 'fa-solid fa-screwdriver-wrench', 'color' => '#0284c7', 'bg_color' => 'rgba(2, 132, 199, 0.08)', 'sort_order' => 18],
            ['name' => 'Wipro', 'category' => 'IT & Cloud', 'icon' => 'fa-solid fa-cloud-arrow-up', 'color' => '#7c3aed', 'bg_color' => 'rgba(124, 58, 237, 0.08)', 'sort_order' => 19],
            ['name' => 'Reliance Jio', 'category' => 'Telecom & 5G', 'icon' => 'fa-solid fa-tower-broadcast', 'color' => '#2563eb', 'bg_color' => 'rgba(37, 99, 235, 0.08)', 'sort_order' => 20],
            ['name' => 'IFB Industries', 'category' => 'Manufacturing', 'icon' => 'fa-solid fa-bolt', 'color' => '#b91c1c', 'bg_color' => 'rgba(185, 28, 28, 0.08)', 'sort_order' => 21],
            ['name' => 'Goodyear', 'category' => 'Tyres & Rubber', 'icon' => 'fa-solid fa-circle-notch', 'color' => '#d97706', 'bg_color' => 'rgba(217, 119, 6, 0.08)', 'sort_order' => 22],
            ['name' => 'Allied Blenders & Distillers', 'category' => 'Manufacturing', 'icon' => 'fa-solid fa-building', 'color' => '#b45309', 'bg_color' => 'rgba(180, 83, 9, 0.08)', 'sort_order' => 23],
            ['name' => 'Mahindra', 'category' => 'Automotive & Tech', 'icon' => 'fa-solid fa-car', 'color' => '#dc2626', 'bg_color' => 'rgba(220, 38, 38, 0.08)', 'sort_order' => 24],
            ['name' => 'Atos Syntel', 'category' => 'IT Services', 'icon' => 'fa-solid fa-network-wired', 'color' => '#0284c7', 'bg_color' => 'rgba(2, 132, 199, 0.08)', 'sort_order' => 25]
        ];

        $insRec = $pdo->prepare("INSERT INTO placement_recruiters (name, category, icon, color, bg_color, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
        foreach ($defaultRecruiters as $r) {
            $insRec->execute([$r['name'], $r['category'], $r['icon'], $r['color'], $r['bg_color'], $r['sort_order']]);
        }
        echo "- Seeded default placement recruiters (" . count($defaultRecruiters) . " items)\n";
    }

    // 13. Seed default homepage_activities if empty
    $actCount = (int)$pdo->query("SELECT COUNT(*) FROM homepage_activities")->fetchColumn();
    if ($actCount === 0) {
        $defaultActivities = [
            [
                'title' => 'Experts Live India Conference',
                'badge' => 'Conference',
                'year_or_tag' => '2025',
                'description' => 'Premier technological conclave bringing international Microsoft MVPs, cloud architects, and AI researchers to interact directly with SOET students.',
                'image_path' => 'event_experts_live_india.jpg',
                'sort_order' => 1
            ],
            [
                'title' => 'SUPERNOVA & Project Exhibition',
                'badge' => 'Tech Fest & Expo',
                'year_or_tag' => 'Flagship Event',
                'description' => 'Annual engineering hackathon and grand project exhibition where students showcase working IoT prototypes, robotics arms, and AI systems.',
                'image_path' => 'event_supernova_group.jpg',
                'sort_order' => 2
            ],
            [
                'title' => 'ZESTOPIA Cultural Fest',
                'badge' => 'Cultural Fest',
                'year_or_tag' => 'Zestopia',
                'description' => 'A vibrant celebration of arts, music, theatrical performances, and cultural diversity fostering student creativity beyond academics.',
                'image_path' => 'event_zestopia_cultural.jpg',
                'sort_order' => 3
            ],
            [
                'title' => 'Robotics & Automation Training',
                'badge' => 'Workshop',
                'year_or_tag' => 'Live Labs',
                'description' => 'Intensive hands-on training on ROS (Robot Operating System), kinematics simulation, PLC programming, and drone assembly in advanced robotics labs.',
                'image_path' => 'event_robotics_training.jpg',
                'sort_order' => 4
            ],
            [
                'title' => 'Industrial Plant Visit to LIEBHERR',
                'badge' => 'Industrial Visit',
                'year_or_tag' => 'On-Site',
                'description' => 'Regular on-site industrial plant tours to manufacturing leaders like LIEBHERR and Škoda Volkswagen for practical shopfloor insights.',
                'image_path' => 'visit_liebherr_industry.jpg',
                'sort_order' => 5
            ],
            [
                'title' => 'NSS Outreach & Sports Championship',
                'badge' => 'Athletics & NSS',
                'year_or_tag' => 'Championship',
                'description' => 'National Service Scheme (NSS) social outreach camps and annual inter-collegiate sports championship on Olympic-grade grounds.',
                'image_path' => 'activity_sports_team.jpg',
                'sort_order' => 6
            ]
        ];

        $insAct = $pdo->prepare("INSERT INTO homepage_activities (title, badge, year_or_tag, description, image_path, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
        foreach ($defaultActivities as $a) {
            $insAct->execute([$a['title'], $a['badge'], $a['year_or_tag'], $a['description'], $a['image_path'], $a['sort_order']]);
        }
        echo "- Seeded default homepage activities (" . count($defaultActivities) . " items)\n";
    }

    // 14. Ensure all site_settings keys exist
    $defaultSettings = [
        'college_name' => 'School of Engineering and Technology (SOET)',
        'college_short' => 'SOET MGM University',
        'college_tagline' => 'Where Technology Meets Tomorrow',
        'college_email' => 'soet@mgmu.ac.in',
        'college_phone' => '+91 9371714253',
        'college_address' => 'Mahatma Gandhi Mission University, MGM Campus, N-6 CIDCO, Chhatrapati Sambhajinagar (Aurangabad) - 431003, Maharashtra, India',
        'admission_status' => 'open',
        'admission_instructions' => 'UG 4-Year B.Tech: HSC Passed (min 45% Open, min 40% Reserved) + Non-zero score in JEE(Mains)/MHT-CET/PERA-CET/MGMU-CET. Lateral Entry: Diploma / B.Sc. / D.Voc. | B.Tech Integrated (6 Years): SSC Passed (min 50% Open, min 45% Reserved) + MGMU-CET.',
        'chatbot_greeting' => 'Welcome to SOET College Assistant! I can help you with admissions, fee structure, course curriculums, eligibility, faculty, and campus placements. How can I assist you today?',
        'director_name' => 'Dr. Parminder Kaur Dhingra',
        'director_designation' => 'Dean & Director, SOET MGM University',
        'director_message' => 'At SOET MGM University, we are committed to providing top-tier technological education with modern laboratories, expert faculty, and high-paying industry placements. Our mission is to produce innovative, ethically driven engineering professionals.',
        'university_name' => 'MGM University, Chhatrapati Sambhajinagar',
        'admission_year' => '2026-27',
        'total_intake_ay2026_27' => '380',
        'established_year' => '1982',
        'trust_legacy_years' => '42+',
        'admission_website' => 'https://admissions.mgmu.ac.in',
        'college_website' => 'https://soet.mgmu.ac.in',
        'maps_link' => 'https://maps.app.goo.gl/NUzWW6mAcYVX5Tg96',
        
        // Homepage Section Settings
        'home_hero_badge' => 'ADMISSIONS OPEN',
        'home_hero_badge_sub' => 'Academic Session 2026-27 • 380 Seats',
        'home_hero_title' => 'Where Technology Meets Tomorrow',
        'home_hero_subtitle' => "You're not just studying engineering. You're engineering the future. Empowering innovators through cutting-edge laboratories, NEP 2020 aligned curricula, and a 42-year legacy of excellence under MGM Trust.",
        'home_hero_btn1_text' => 'Apply for AY 2026-27',
        'home_hero_btn1_url' => 'admissions.php',
        'home_hero_btn2_text' => 'Explore Programs & Booklets',
        'home_hero_btn2_url' => 'courses.php',
        'home_welcome_badge' => 'MGM University • UGC 2(f) Recognized',
        'home_welcome_title' => 'Welcome to School of Engineering & Technology',
        'home_welcome_subtitle' => 'Pioneering B.Tech (4-Year after HSC) & Integrated B.Tech (6-Year after SSC) Programs',
        'home_welcome_content' => "Students at SOET MGM University have access to some of the most advanced facilities in the region. From building intelligent machines in the Industrial Robotics Lab to exploring sustainable mobility in the Electric Vehicle Lab, every space is designed to spark innovation and hands-on learning. Whether you're prototyping drones, designing Industry 4.0 systems, or launching your startup at the Innovation Incubation and Research Center, you'll find the tools and mentorship to go beyond the classroom.",
        'home_stat1_val' => '42+ Yrs',
        'home_stat1_label' => 'MGM Trust Legacy',
        'home_stat2_val' => '380',
        'home_stat2_label' => 'AY 2026-27 Intake',
        'home_stat3_val' => '15+ MoUs',
        'home_stat3_label' => 'Industry Partners',
        'home_highlight_badge' => 'ACADEMIC EXCELLENCE',
        'home_highlight_title' => 'Future-Ready Education',
        'home_highlight_subtitle' => 'Empowering student engineers through innovation, research, and ethics.',
        'home_highlight1_title' => 'NEP 2020 Aligned',
        'home_highlight1_sub' => 'Flexible Curriculum',
        'home_highlight1_icon' => 'fa-solid fa-graduation-cap',
        'home_highlight2_title' => 'Project-Based (PBL)',
        'home_highlight2_sub' => 'Experiential Learning',
        'home_highlight2_icon' => 'fa-solid fa-diagram-project',
        'home_highlight3_title' => 'Robotics & EV Labs',
        'home_highlight3_sub' => 'Mechatronics 4.0',
        'home_highlight3_icon' => 'fa-solid fa-robot',
        'home_highlight4_title' => 'Startup & Incubation',
        'home_highlight4_sub' => 'IIRC Research Center',
        'home_highlight4_icon' => 'fa-solid fa-rocket',

        // About Page Settings
        'about_hero_title' => 'About SOET',
        'about_hero_tagline' => 'Where Technology Meets Tomorrow',
        'about_hero_subtitle' => 'Empowering students to engineer the future through cutting-edge laboratories, future-ready curricula, and a 42-year legacy of educational innovation.',
        'about_overview_lead' => "You're not just studying engineering. You're engineering the future.",
        'about_overview_p1' => "The School of Engineering and Technology (SOET) at MGM University is a pioneer in offering Integrated Six-Year B.Tech. Programs (after SSC / Class 10) and Four-Year B.Tech. Programs (after HSC / Class 12) in the Marathwada region. The Integrated program allows students to begin their engineering education right after Class 10, offering an early advantage and extended time for hands-on learning, innovation, and deep exploration of engineering fundamentals. This structure empowers young aspirants to build a strong foundation and stay ahead in the competitive landscape. SOET's integrated curriculum is designed to nurture curiosity, develop critical thinking, and prepare students for advanced industry demands from an early stage.",
        'about_overview_p2' => "At the same time, SOET offers focused Four-Year B.Tech. programs after Class 12, catering to students who aspire to pursue professional engineering education immediately after HSC. These programs are aligned with the latest industry trends and include emerging domains such as Computer Science and Engineering (IoT, Cyber Security & Blockchain), Cyber Security, Electronics Engineering (VLSI Design & Technology), Robotics & Automation, and Electrical Engineering. The teaching and learning process is Project-Based backed by Research, and is powered by Collaborative methods.",
        'about_overview_p3' => "Students benefit from internship and placement opportunities through active collaborations with industries. With a legacy of over 42 years of MGM Trust, SOET is committed to producing future-ready engineers through holistic education, modern infrastructure, and a strong emphasis on innovation and ethics.",
        'about_vision' => 'To empower students as leaders of tomorrow by fostering a culture of innovation, skill development, and adaptability, bridging the gap between academic excellence and industry readiness in an ever-evolving global economy.',
        'about_mission_1' => 'To cultivate future-ready professionals through transformative training and experiential learning.',
        'about_mission_2' => 'To inspire students to embrace emerging trends and technologies, building versatile and sustainable career paths.',
        'about_mission_3' => 'To provide personalized career mentorship and foster meaningful collaborations with leading industries.',
        'about_mission_4' => 'To create an ecosystem that nurtures talent, values diversity, and encourages lifelong learning for global success.',
        'about_mgmu_title' => 'About MGM University',
        'about_mgmu_subtitle' => '42+ Years of Educational Legacy',
        'about_mgmu_text' => 'MGM University, established by the revered Mahatma Gandhi Mission Trust, a pioneer with four decades of remarkable legacy in education, research, and service, is a State Private University, approved by Government of Maharashtra MGM University Act No. XXVI of 2019.',
        'about_mgmu_ugc' => 'Granted by University Grants Commission (UGC) of India Under Section 2(f).',

        // Placement Page Settings
        'placements_stat1_val' => '12+',
        'placements_stat1_label' => '2026 Batch Placed',
        'placements_stat2_val' => '7+',
        'placements_stat2_label' => 'Active Recruiters (2026)',
        'placements_stat3_val' => '15+',
        'placements_stat3_label' => 'Industry MoUs & Partners',
        'placements_stat4_val' => '100%',
        'placements_stat4_label' => 'Verified Placement Track',

        // Social Media & Footer Settings
        'social_facebook' => 'https://www.facebook.com/share/14hsTNNRa6q/?mibextid=wwXIfr',
        'social_linkedin' => 'https://www.linkedin.com/company/mgmu-school-of-engineering-and-technology/',
        'social_instagram' => 'https://www.instagram.com/mgmu.soet?igsh=MWcxZ3A2emMxZnE5ZA==',
        'social_twitter' => '',
        'social_youtube' => '',
        'social_whatsapp' => '+919371714253',
        'footer_about_text' => 'School of Engineering and Technology (SOET), MGM University is a premier engineering institution at Chhatrapati Sambhajinagar (Aurangabad) dedicated to academic excellence, state-of-the-art labs, and shaping engineering careers.',
        'footer_copyright_text' => 'School of Engineering and Technology (SOET), MGM University. All Rights Reserved.',
        'google_maps_iframe' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3752.193234909787!2d75.35246731491374!3d19.873919986634502!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3bdba299c8f00001%3A0x6b6bb7bc0b0d39e3!2sMGM%20University!5e0!3m2!1sen!2sin!4v1628000000000'
    ];

    $insSetting = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = IF(setting_value IS NULL OR setting_value = '', VALUES(setting_value), setting_value)");
    foreach ($defaultSettings as $k => $v) {
        // check if key exists
        $row = $pdo->query("SELECT id, setting_value FROM site_settings WHERE setting_key = " . $pdo->quote($k))->fetch();
        if (!$row) {
            $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)")->execute([$k, $v]);
        } elseif (empty($row['setting_value'])) {
            $pdo->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = ?")->execute([$v, $k]);
        }
    }
    echo "- Verified and synced all site settings keys\n";

    echo "Schema migration completed successfully!\n";
} catch (Exception $e) {
    echo "Migration error: " . $e->getMessage() . "\n";
}
