<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/ContentManager.php';

$cms = new ContentManager();
$db = Database::getInstance();

$defaults = [
    // Institutional & Contact
    'site_name' => 'SOET - School of Engineering and Technology',
    'site_tagline' => 'Excellence in Engineering, Research & Industry Innovation',
    'college_name' => 'School of Engineering and Technology (SOET), MGM University',
    'contact_phone' => '+91 9371714253',
    'contact_email' => 'soet@mgmu.ac.in',
    'contact_alternate_email' => 'admissions@mgmu.ac.in',
    'contact_address' => 'Mahatma Gandhi Mission University, MGM Campus, N-6 CIDCO, Chhatrapati Sambhajinagar - 431003, Maharashtra, India',
    'college_address' => 'Mahatma Gandhi Mission University, MGM Campus, N-6 CIDCO, Chhatrapati Sambhajinagar - 431003, Maharashtra, India',
    'campus_timing' => 'Monday – Saturday: 09:00 AM – 05:30 PM (Sunday Closed)',
    'google_map_embed' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3752.1245638210344!2d75.35824967584167!3d19.87693598150033!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3bdb986708b79f29%3A0xe5a3eb87541f4a9b!2sMGM%20University!5e0!3m2!1sen!2sin!4v1700000000000!5m2!1sen!2sin',

    // Social & Footer
    'social_facebook' => 'https://facebook.com/mgmuniversity',
    'social_instagram' => 'https://instagram.com/mgmuniversity',
    'social_linkedin' => 'https://linkedin.com/school/mgmuniversity',
    'social_youtube' => 'https://youtube.com/@mgmuniversity',
    'social_twitter' => 'https://twitter.com/mgmuniversity',
    'footer_about_text' => 'MGM University School of Engineering & Technology (SOET) is a premier engineering institution in Chhatrapati Sambhajinagar committed to project-based learning, research, innovation, and global industry partnerships.',
    'copyright_text' => 'Copyright © ' . date('Y') . ' School of Engineering and Technology, MGM University. All Rights Reserved.',

    // Homepage Content
    'home_hero_badge' => 'ADMISSIONS OPEN FOR ACADEMIC YEAR 2026–2027',
    'home_hero_title' => 'Shaping Global Engineering Innovators & Industry Leaders',
    'home_hero_subtitle' => 'Empowering future-ready engineers with hands-on project based learning, advanced robotics, cyber security research labs, and top-tier MNC placements at MGM University.',
    'home_hero_btn1_text' => 'Explore Programs',
    'home_hero_btn1_url' => 'courses.php',
    'home_hero_btn2_text' => 'Apply Online',
    'home_hero_btn2_url' => 'admissions.php',
    'home_welcome_badge' => 'WELCOME TO SOET',
    'home_welcome_title' => 'Pioneering Technical Excellence in Engineering Education',
    'home_welcome_subtitle' => 'Project-Based Experiential Learning & Research Ecosystem',
    'home_welcome_content' => 'The School of Engineering and Technology (SOET) at MGM University is a premier engineering institution providing industry-aligned curriculum, cutting-edge laboratories, and strong corporate exposure. Our curriculum focuses on artificial intelligence, IoT, blockchain, VLSI design, and advanced robotics to prepare students for high-growth global careers.',
    'home_stat1_val' => '42+',
    'home_stat1_label' => 'Years Educational Legacy',
    'home_stat2_val' => '100%',
    'home_stat2_label' => 'Placement Assistance',
    'home_stat3_val' => '420+',
    'home_stat3_label' => 'AY 2026-27 Intake Capacity',
    'home_highlight_badge' => 'ACADEMIC ADVANTAGES',
    'home_highlight_title' => 'Why Choose SOET MGM University?',
    'home_highlight_subtitle' => 'Our world-class infrastructure and pedagogical framework provide students with unmatched academic and industry advantages.',
    'home_highlight1_title' => 'Project-Based Learning (PBL)',
    'home_highlight1_sub' => 'Every semester integrates practical real-world engineering problem solving, prototype development, and industrial project mentorship.',
    'home_highlight1_icon' => 'fa-laptop-code',
    'home_highlight2_title' => 'Industry 4.0 Center of Excellence',
    'home_highlight2_sub' => 'State-of-the-art supercomputing labs, robotics workstations, IoT hardware suites, and advanced VLSI design fabrication simulation.',
    'home_highlight2_icon' => 'fa-microchip',
    'home_highlight3_title' => '100% Placement & Internship Support',
    'home_highlight3_sub' => 'Dedicated Training & Placement Cell with extensive corporate tie-ups with leading global tech MNCs and high-growth startups.',
    'home_highlight3_icon' => 'fa-briefcase',
    'home_highlight4_title' => 'Research & Innovation Incubation',
    'home_highlight4_sub' => 'Dedicated MGM Innovation & Incubation Center (IIC) supporting student startups, patents, and national hackathon participation.',
    'home_highlight4_icon' => 'fa-lightbulb',

    // About SOET
    'about_hero_title' => 'About SOET MGM University',
    'about_hero_tagline' => 'Excellence in Engineering Education & Applied Research',
    'about_hero_subtitle' => 'Building ethical, innovative, and socially responsible engineering leaders through world-class academic programs and experiential learning.',
    'about_overview_lead' => 'The School of Engineering and Technology (SOET) is a constituent institute of MGM University, Chhatrapati Sambhajinagar, established to deliver transformative engineering education aligned with global industry benchmarks.',
    'about_overview_p1' => 'With state-of-the-art laboratories, modern computing infrastructure, and distinguished doctorate faculty, SOET fosters an environment of critical thinking, multidisciplinary research, and entrepreneurial spirit.',
    'about_overview_p2' => 'Our degree programs span regular 4-Year B.Tech tracks, 6-Year Integrated pathways after SSC 10th, and direct second-year lateral entry opportunities, designed in collaboration with academic and corporate experts.',
    'about_overview_p3' => 'SOET students regularly win national hackathons, publish in indexed journals, and receive employment offers from prestigious global enterprises.',
    'about_vision' => 'To be a globally recognized center of excellence in engineering education and research that produces innovative, socially conscious, and competent technocrats who contribute significantly to national development and sustainable global progress.',
    'about_mission_1' => 'Provide rigorous, outcome-based engineering education integrated with hands-on experiential learning and emerging technologies.',
    'about_mission_2' => 'Promote interdisciplinary research, innovation, and entrepreneurship in collaboration with premier industries and research organizations.',
    'about_mission_3' => 'Nurture ethical leadership, professional integrity, and environmental responsibility among engineering students.',
    'about_mission_4' => 'Foster continuous academia-industry partnerships to ensure exceptional career placement and lifelong learning.',
    'about_mgmu_title' => 'About MGM University',
    'about_mgmu_subtitle' => 'A Legacy of Trust, Educational Excellence & Nation Building',
    'about_mgmu_text' => 'MGM University, Chhatrapati Sambhajinagar, is established under the Maharashtra State Private Universities Act. Built on a four-decade legacy of the Mahatma Gandhi Mission trust, MGM University offers state-of-the-art infrastructure, sports complexes, research centers, and a vibrant campus ecosystem fostering holistic student development.',
    'about_mgmu_ugc' => 'Recognized by UGC (Govt. of India) & Approved by AICTE, New Delhi.',
    'established_year' => '1982',
    'trust_legacy_years' => '42+',

    // Admissions & Intake
    'admission_status' => 'OPEN',
    'admission_year' => '2026–27',
    'total_intake_ay2026_27' => '420',
    'admission_instructions' => 'Applications for Academic Year 2026-27 are currently open for B.Tech (4-Year), B.Tech Integrated (6-Year), and Direct Second Year Lateral Entry. Candidates must fulfill eligibility criteria and register online through the official admission portal.',

    // Placement Statistics
    'placements_stat1_val' => '₹12.00 LPA',
    'placements_stat1_label' => 'Highest Salary Package Offered',
    'placements_stat2_val' => '₹4.80 LPA',
    'placements_stat2_label' => 'Average Salary Package',
    'placements_stat3_val' => '92%+',
    'placements_stat3_label' => 'Consistent Placement Assistance Record',
    'placements_stat4_val' => '120+',
    'placements_stat4_label' => 'Corporate & Prime Recruitment Partners',

    // Director Profile
    'director_name' => 'Prof. (Dr.) S. C. Sharma',
    'director_designation' => 'Director / Dean, School of Engineering & Technology',
    'director_message' => 'Welcome to the School of Engineering and Technology at MGM University. Our mission is to inspire, educate, and empower engineering students with modern pedagogical methodologies, project-based learning, and strong industry linkages. We are dedicated to providing our students with the skills, values, and vision needed to excel in a rapidly evolving technological world.'
];

$saved = 0;
foreach ($defaults as $key => $val) {
    $existing = $db->fetchOne("SELECT setting_value FROM site_settings WHERE setting_key = ?", [$key]);
    if (!$existing || empty($existing['setting_value'])) {
        $cms->saveSetting($key, $val);
        $saved++;
    }
}

echo "Saved {$saved} default settings successfully.\n";
