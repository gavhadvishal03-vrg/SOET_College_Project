<?php
/**
 * Synchronize SOET Database with Official AY 2026-27 Documents
 */
require_once 'config/database.php';

try {
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "=== 1. UPDATING DATABASE SCHEMAS ===\n";
    
    // Check existing columns in courses
    $course_cols = $pdo->query("DESCRIBE courses")->fetchAll(PDO::FETCH_COLUMN);
    
    $pdo->exec("ALTER TABLE courses MODIFY COLUMN name VARCHAR(255) NOT NULL");
    $pdo->exec("ALTER TABLE courses MODIFY COLUMN code VARCHAR(100) NOT NULL");
    $pdo->exec("ALTER TABLE courses MODIFY COLUMN eligibility TEXT NULL");
    $pdo->exec("ALTER TABLE courses MODIFY COLUMN description TEXT NULL");
    $pdo->exec("ALTER TABLE courses MODIFY COLUMN core_courses TEXT NULL");
    $pdo->exec("ALTER TABLE courses MODIFY COLUMN career_opportunities TEXT NULL");
    
    if (!in_array('duration', $course_cols)) {
        $pdo->exec("ALTER TABLE courses ADD COLUMN duration VARCHAR(100) NULL AFTER image_path");
    } else {
        $pdo->exec("ALTER TABLE courses MODIFY COLUMN duration VARCHAR(100) NULL");
    }
    if (!in_array('program_type', $course_cols)) {
        $pdo->exec("ALTER TABLE courses ADD COLUMN program_type VARCHAR(50) DEFAULT 'regular' AFTER department_id");
    }
    if (!in_array('intake', $course_cols)) {
        $pdo->exec("ALTER TABLE courses ADD COLUMN intake INT DEFAULT 30 AFTER semester_count");
    }
    if (!in_array('brochure_document', $course_cols)) {
        $pdo->exec("ALTER TABLE courses ADD COLUMN brochure_document VARCHAR(255) NULL AFTER syllabus_pdf_year_4");
    }
    
    // Check placements table columns
    $placement_cols = $pdo->query("DESCRIBE placements")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('batch', $placement_cols)) {
        $pdo->exec("ALTER TABLE placements ADD COLUMN batch VARCHAR(50) DEFAULT '2026 Batch' AFTER placement_year");
    }
    if (!in_array('package_lpa', $placement_cols)) {
        $pdo->exec("ALTER TABLE placements ADD COLUMN package_lpa DECIMAL(5,2) NULL AFTER company_name");
    }

    // Check gallery table
    $pdo->exec("CREATE TABLE IF NOT EXISTS gallery (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(150) NOT NULL,
        image_path VARCHAR(255) NOT NULL,
        category VARCHAR(50) DEFAULT 'Campus',
        description TEXT NULL,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $gal_cols = $pdo->query("DESCRIBE gallery")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('description', $gal_cols)) {
        $pdo->exec("ALTER TABLE gallery ADD COLUMN description TEXT NULL AFTER category");
    }

    // Check departments
    echo "=== 2. UPDATING DEPARTMENTS ===\n";
    $departments = [
        ['id' => 1, 'name' => 'Computer Science & Engineering', 'code' => 'CSE', 'description' => 'Department of Computer Science & Engineering offering industry-aligned programs in Cyber Security, Data Science, IoT, and Blockchain Technology with cutting-edge computing laboratories.', 'image_path' => 'soet_future_tech_banner.jpg'],
        ['id' => 2, 'name' => 'Electronics & Telecommunication Engineering', 'code' => 'ECE', 'description' => 'Department of Electronics & Telecommunication Engineering specializing in VLSI Design & Technology, Semiconductor fabrication processes, and embedded systems.', 'image_path' => 'soet_robotics_mechatronics_lab.jpg'],
        ['id' => 3, 'name' => 'Robotics & Automation Engineering', 'code' => 'ROBOTICS', 'description' => 'Specialized department for Robotics, Mechatronics, Artificial Intelligence, Industrial Automation 4.0, and Autonomous Drone Systems.', 'image_path' => 'event_robotics_training.jpg'],
        ['id' => 4, 'name' => 'Civil Engineering', 'code' => 'CIVIL', 'description' => 'Department of Civil Engineering offering Integrated B.Tech with focus on structural engineering, green building practices, smart city infrastructure, and geotechnical solutions.', 'image_path' => 'soet_campus_aerial.jpg'],
        ['id' => 6, 'name' => 'Electrical Engineering', 'code' => 'EE', 'description' => 'Department of Electrical Engineering focusing on Smart Grids, Electric Vehicle technology, Renewable Energy systems, and Industrial Power Electronics.', 'image_path' => 'soet_robotics_mechatronics_lab.jpg'],
        ['id' => 8, 'name' => 'Fire Service & Industrial Safety Engineering', 'code' => 'SAFETY', 'description' => 'Specialized department providing professional Diploma and Advanced Diploma programs in Industrial Safety (ADIS/AIDS) and Fire Service Engineering.', 'image_path' => 'activity_nss_camp.jpg']
    ];

    foreach ($departments as $d) {
        $stmt = $pdo->prepare("INSERT INTO departments (id, name, code, description, image_path, is_active) 
            VALUES (:id, :name, :code, :description, :image_path, 1)
            ON DUPLICATE KEY UPDATE name=:name, code=:code, description=:description, image_path=:image_path, is_active=1");
        $stmt->execute($d);
    }

    echo "=== 3. UPDATING OFFICIAL COURSES / PROGRAMS (AY 2026-27) ===\n";

    $official_courses = [
        // 1. Regular B.Tech (4 Years after HSC)
        [
            'id' => 16,
            'department_id' => 1,
            'program_type' => 'regular',
            'name' => 'B.Tech Computer Science and Engineering (IoT, Cyber Security Including Blockchain Technology)',
            'code' => 'BTECH-CSE-ICBT',
            'description' => '4-Year Undergraduate Degree Program after HSC. Specializes in IoT device architecture, cryptography, cyber defense systems, and decentralized blockchain smart contracts.',
            'core_courses' => "• Embedded systems and IoT device programming\n• Cybersecurity fundamentals: cryptography, network security, ethical hacking\n• Blockchain principles: decentralized systems, smart contracts\n• Hands-on with IoT platforms like Raspberry Pi\n• Real-world applications in smart cities, healthcare, and industry 4.0",
            'career_opportunities' => "• IoT Developer / Solution Architect\n• Cyber Security Analyst / Ethical Hacker\n• Blockchain Developer / Consultant\n• Security Auditor / Network Administrator\n• Opportunities in smart infrastructure, defense, fintech, and healthcare\n• S/W Developer",
            'eligibility' => 'HSC (12th) Passed with min 45% for Open and min 40% for Reserved Category candidates. Candidate should have appeared in JEE(Mains) / MHT-CET / PERA-CET / MGMU-CET (with non-zero score). Lateral Entry: Direct Second Year after Diploma in Engg & Tech / B.Sc. / D.Voc.',
            'image_path' => 'soet_future_tech_banner.jpg',
            'duration' => '4 Years (8 Semesters)',
            'duration_years' => 4,
            'semester_count' => 8,
            'intake' => 60,
            'intake_capacity' => 60,
            'admission_status' => 'OPEN',
            'brochure_document' => 'assets/documents/BTech_Integrated_CSE_ICBT_Booklet.pdf'
        ],
        [
            'id' => 18,
            'department_id' => 1,
            'program_type' => 'regular',
            'name' => 'B.Tech Computer Science and Engineering (Cyber Security)',
            'code' => 'BTECH-CSE-CYBER',
            'description' => '4-Year Undergraduate Degree Program after HSC. In-depth program preparing cyber warriors with network security, digital forensics, ethical hacking, and defense operations.',
            'core_courses' => "• Network security, cryptography, and risk management\n• Cyber laws, digital forensics, and ethical hacking\n• Hands-on labs for malware analysis and penetration testing\n• Security compliance frameworks (ISO, NIST)\n• Collaboration with government and private cyber defense initiatives",
            'career_opportunities' => "• Cybersecurity Analyst / Engineer\n• Penetration Tester / Ethical Hacker\n• Digital Forensics Expert\n• Security Operations Center (SOC) Analyst\n• High demand in banking, e-commerce, IT services, and government sectors\n• S/W Developer",
            'eligibility' => 'HSC (12th) Passed with min 45% for Open and min 40% for Reserved Category candidates. Candidate should have appeared in JEE(Mains) / MHT-CET / PERA-CET / MGMU-CET (with non-zero score). Lateral Entry: Direct Second Year after Diploma in Engg & Tech / B.Sc. / D.Voc.',
            'image_path' => 'soet_future_tech_banner.jpg',
            'duration' => '4 Years (8 Semesters)',
            'duration_years' => 4,
            'semester_count' => 8,
            'intake' => 30,
            'intake_capacity' => 30,
            'admission_status' => 'OPEN',
            'brochure_document' => 'assets/documents/BTech_CSE_Cybersecurity_Curriculum_Booklet.pdf'
        ],
        [
            'id' => 19,
            'department_id' => 2,
            'program_type' => 'regular',
            'name' => 'B.Tech Electronics Engineering (VLSI Design and Technology)',
            'code' => 'BTECH-ECE-VLSI',
            'description' => '4-Year Undergraduate Degree Program after HSC. Industry-aligned semiconductor program covering HDL Verilog/VHDL, chip fabrication, EDA tools (Cadence/Synopsys), and ASIC design.',
            'core_courses' => "• VLSI design using HDL (Verilog/VHDL)\n• Semiconductor device physics and fabrication processes\n• Design tools like Cadence, Synopsys, Xilinx\n• Focus on low power, high-speed chip design\n• Cleanroom labs and embedded systems integration",
            'career_opportunities' => "• VLSI Design Engineer\n• ASIC / FPGA Developer\n• Semiconductor Fabrication Engineer\n• Embedded Systems Engineer\n• Careers in chip design giants like Intel, Qualcomm, AMD, Nvidia, and TSMC",
            'eligibility' => 'HSC (12th) Passed with min 45% for Open and min 40% for Reserved Category candidates. Candidate should have appeared in JEE(Mains) / MHT-CET / PERA-CET / MGMU-CET (with non-zero score). Lateral Entry: Direct Second Year after Diploma in Engg & Tech / B.Sc. / D.Voc.',
            'image_path' => 'soet_robotics_mechatronics_lab.jpg',
            'duration' => '4 Years (8 Semesters)',
            'duration_years' => 4,
            'semester_count' => 8,
            'intake' => 30,
            'intake_capacity' => 30,
            'admission_status' => 'OPEN',
            'brochure_document' => 'assets/documents/BTech_Electronics_VLSI_Design_Booklet.pdf'
        ],
        [
            'id' => 20,
            'department_id' => 6,
            'program_type' => 'regular',
            'name' => 'B.Tech Electrical Engineering',
            'code' => 'BTECH-EE',
            'description' => '4-Year Undergraduate Degree Program after HSC. Specializes in smart grids, electric vehicle powertrains, solar/wind renewable energy systems, and high-voltage power electronics.',
            'core_courses' => "• Electrical machines, power systems, and energy conversion\n• Power electronics and high-voltage engineering\n• Renewable energy systems (solar, wind)\n• Control systems and smart grid technology\n• Simulation tools like Simulink and ETAP",
            'career_opportunities' => "• Electrical Engineer (Power Distribution, Generation)\n• Control System Engineer\n• Energy Consultant / Renewable Energy Expert\n• Electrical Design Engineer\n• Opportunities in government utilities, infrastructure, manufacturing, and railways",
            'eligibility' => 'HSC (12th) Passed with min 45% for Open and min 40% for Reserved Category candidates. Candidate should have appeared in JEE(Mains) / MHT-CET / PERA-CET / MGMU-CET (with non-zero score). Lateral Entry: Direct Second Year after Diploma in Engg & Tech / B.Sc. / D.Voc.',
            'image_path' => 'soet_robotics_mechatronics_lab.jpg',
            'duration' => '4 Years (8 Semesters)',
            'duration_years' => 4,
            'semester_count' => 8,
            'intake' => 30,
            'intake_capacity' => 30,
            'admission_status' => 'OPEN',
            'brochure_document' => 'assets/documents/SOET_Admission_Brochure_2026-27.pdf'
        ],
        [
            'id' => 17,
            'department_id' => 3,
            'program_type' => 'regular',
            'name' => 'B.Tech Robotics and Automation',
            'code' => 'BTECH-ROBOTICS',
            'description' => '4-Year Undergraduate Degree Program after HSC. Integrates mechatronics, PLC/SCADA industrial automation, computer vision, robotic arms, and autonomous mobile robotics.',
            'core_courses' => "• Robotic kinematics, control systems, and path planning\n• Automation technologies: PLC, SCADA, HMI\n• AI integration with robotics (vision, learning, speech)\n• Hands-on labs with robotic arms, drones, and mobile robots\n• Applications in manufacturing, logistics, defense, and healthcare",
            'career_opportunities' => "• Robotics Engineer\n• AI Research Scientist\n• Autonomous Systems Developer (e.g., self-driving cars, drones)\n• Automation Engineer\n• Roles in healthcare, defense, manufacturing, and smart logistics",
            'eligibility' => 'HSC (12th) Passed with min 45% for Open and min 40% for Reserved Category candidates. Candidate should have appeared in JEE(Mains) / MHT-CET / PERA-CET / MGMU-CET (with non-zero score). Lateral Entry: Direct Second Year after Diploma in Engg & Tech / B.Sc. / D.Voc.',
            'image_path' => 'event_robotics_training.jpg',
            'duration' => '4 Years (8 Semesters)',
            'duration_years' => 4,
            'semester_count' => 8,
            'intake' => 30,
            'intake_capacity' => 30,
            'admission_status' => 'OPEN',
            'brochure_document' => 'assets/documents/SOET_Admission_Brochure_2026-27.pdf'
        ],

        // 2. Integrated B.Tech (6 Years after SSC)
        [
            'id' => 28,
            'department_id' => 1,
            'program_type' => 'integrated',
            'name' => 'B.Tech (Integrated) Computer Science and Engineering (IoT, Cyber Security Including Blockchain Technology)',
            'code' => 'INT-CSE-IOT',
            'description' => '6-Year Integrated B.Tech Program after SSC (10th). Direct engineering gateway starting after Class 10, combining polytechnic foundation with high-end IoT, Cyber Security, and Blockchain mastery.',
            'core_courses' => "• Embedded systems and IoT device programming\n• Cybersecurity fundamentals: cryptography, network security, ethical hacking\n• Blockchain principles: decentralized systems, smart contracts\n• Hands-on with IoT platforms like Raspberry Pi\n• Real-world applications in smart cities, healthcare, and industry 4.0",
            'career_opportunities' => "• IoT Developer / Architect\n• Cybersecurity Analyst / Engineer\n• Blockchain Developer / Consultant\n• Network Security Specialist\n• Embedded Systems Engineer\n• Roles in smart cities, healthcare, automotive, and fintech industries",
            'eligibility' => 'SSC (10th) Passed with min 50% for Open and min 45% for Reserved Category candidates + MGMU-CET. Lateral Entry: Direct Second Year after HSC Passed.',
            'image_path' => 'soet_future_tech_banner.jpg',
            'duration' => '6 Years (12 Semesters)',
            'duration_years' => 6,
            'semester_count' => 12,
            'intake' => 30,
            'intake_capacity' => 30,
            'admission_status' => 'OPEN',
            'brochure_document' => 'assets/documents/BTech_Integrated_CSE_ICBT_Booklet.pdf'
        ],
        [
            'id' => 27,
            'department_id' => 1,
            'program_type' => 'integrated',
            'name' => 'B.Tech (Integrated) Computer Science and Engineering (Data Science)',
            'code' => 'INT-CSE-DS',
            'description' => '6-Year Integrated B.Tech Program after SSC (10th). Rigorous curriculum according to NEP 2020 focusing on Python, R, SQL, Machine Learning, Deep Neural Networks, Big Data (Spark/Hadoop), and AI.',
            'core_courses' => "• Programming in Python, R, and SQL\n• Machine learning, deep learning, and artificial intelligence\n• Data visualization, data mining, and cloud computing\n• Big data tools like Hadoop, Spark, and TensorFlow\n• Industry-oriented projects and internships",
            'career_opportunities' => "• Data Scientist / Analyst\n• Machine Learning Engineer\n• Business Intelligence Developer\n• AI/ML Researcher\n• Data Engineer\n• Opportunities in finance, e-commerce, health tech, and enterprise AI",
            'eligibility' => 'SSC (10th) Passed with min 50% for Open and min 45% for Reserved Category candidates + MGMU-CET. Lateral Entry: Direct Second Year after HSC Passed.',
            'image_path' => 'soet_future_tech_banner.jpg',
            'duration' => '6 Years (12 Semesters)',
            'duration_years' => 6,
            'semester_count' => 12,
            'intake' => 60,
            'intake_capacity' => 60,
            'admission_status' => 'OPEN',
            'brochure_document' => 'assets/documents/BTech_Integrated_CSE_Data_Science_Booklet.pdf'
        ],
        [
            'id' => 29,
            'department_id' => 3,
            'program_type' => 'integrated',
            'name' => 'B.Tech (Integrated) Robotics and Artificial Intelligence',
            'code' => 'INT-ROBOTICS-AI',
            'description' => '6-Year Integrated B.Tech Program after SSC (10th). Combines foundational engineering mechanics with computer vision, deep learning algorithms, robot design, and autonomous systems.',
            'core_courses' => "• Robotics design, mechatronics, and automation\n• AI algorithms, computer vision, and natural language processing\n• Control systems, embedded programming, and sensor integration\n• Hands-on robotics labs and interdisciplinary projects\n• Exposure to neural networks and reinforcement learning",
            'career_opportunities' => "• Robotics Engineer\n• AI Research Scientist\n• Autonomous Systems Developer (e.g., self-driving cars, drones)\n• Automation Engineer\n• Roles in healthcare, defense, manufacturing, and logistics",
            'eligibility' => 'SSC (10th) Passed with min 50% for Open and min 45% for Reserved Category candidates + MGMU-CET. Lateral Entry: Direct Second Year after HSC Passed.',
            'image_path' => 'event_robotics_training.jpg',
            'duration' => '6 Years (12 Semesters)',
            'duration_years' => 6,
            'semester_count' => 12,
            'intake' => 60,
            'intake_capacity' => 60,
            'admission_status' => 'OPEN',
            'brochure_document' => 'assets/documents/BTech_Integrated_Common_Curriculum_Booklet.pdf'
        ],
        [
            'id' => 26,
            'department_id' => 4,
            'program_type' => 'integrated',
            'name' => 'B.Tech (Integrated) Civil Engineering',
            'code' => 'INT-CIVIL',
            'description' => '6-Year Integrated B.Tech Program after SSC (10th). Comprehensive curriculum in structural engineering, geotechnical analysis, transportation planning, smart infrastructure, and green building.',
            'core_courses' => "• Structural engineering, geotechnical engineering, transportation, environmental engineering\n• Surveying, construction management, and fluid mechanics\n• Use of software like AutoCAD, STAAD Pro\n• Site visits, lab work, and internships with construction firms\n• Sustainability and green building practices",
            'career_opportunities' => "• Civil Engineer (Site/Design)\n• Structural Engineer\n• Transportation Planner\n• Environmental Engineer\n• Project Manager\n• Government jobs (PWD, CPWD, Railway, Municipal Corporations), private construction sector, or entrepreneurship",
            'eligibility' => 'SSC (10th) Passed with min 50% for Open and min 45% for Reserved Category candidates + MGMU-CET. Lateral Entry: Direct Second Year after HSC Passed.',
            'image_path' => 'soet_campus_aerial.jpg',
            'duration' => '6 Years (12 Semesters)',
            'duration_years' => 6,
            'semester_count' => 12,
            'intake' => 30,
            'intake_capacity' => 30,
            'admission_status' => 'OPEN',
            'brochure_document' => 'assets/documents/BTech_Integrated_Common_Curriculum_Booklet.pdf'
        ],

        // 3. Diploma Programs
        [
            'id' => 35,
            'department_id' => 8,
            'program_type' => 'diploma',
            'name' => 'Advanced Diploma in Industrial Safety (ADIS)',
            'code' => 'ADIP-IND-SAFETY',
            'description' => '1-Year Advanced Diploma Program. Comprehensive specialization in industrial hazard control, Factories Act legal frameworks, OSHA & ISO safety standards, risk assessment, and hygiene audits.',
            'core_courses' => "• Study of safety management, accident prevention, and hazard control\n• Focus on legal frameworks like the Factories Act and Safety Standards (OSHA, ISO)\n• Training in fire safety, emergency planning, and industrial hygiene\n• Risk assessment, audits, and safety inspection methods\n• Industrial visits and real-time case studies",
            'career_opportunities' => "• Safety Officer / Safety Supervisor\n• HSE (Health, Safety & Environment) Officer\n• Safety Auditor / Compliance Officer\n• Safety Consultant in manufacturing, oil & gas, construction, and power sectors\n• High demand in public & private industries and overseas opportunities",
            'eligibility' => 'Diploma in Engineering / Technology or Degree in Science / Engineering.',
            'image_path' => 'activity_nss_camp.jpg',
            'duration' => '1 Year (2 Semesters)',
            'duration_years' => 1,
            'semester_count' => 2,
            'intake' => 10,
            'intake_capacity' => 10,
            'admission_status' => 'OPEN',
            'brochure_document' => 'assets/documents/SOET_Admission_Brochure_2026-27.pdf'
        ],
        [
            'id' => 34,
            'department_id' => 8,
            'program_type' => 'diploma',
            'name' => 'Diploma in Fire Service Engineering',
            'code' => 'DIP-FIRE-ENG',
            'description' => '2-Year Diploma Program. Professional technical training in fire science, emergency response, fire protection system design, disaster management, and live rescue operations.',
            'core_courses' => "• Study of fire science, fire behavior, and firefighting equipment\n• Fire detection systems, sprinkler systems, and emergency evacuation planning\n• Training in rescue operations, disaster management, and first aid\n• Fire safety laws, building codes, and fire control room operations\n• Practical fire drills and live training exercises",
            'career_opportunities' => "• Fire Officer / Firefighter\n• Fire & Safety Engineer\n• Fire Inspector / Fire Prevention Specialist\n• Fire Protection System Designer\n• Roles in fire departments, airports, industrial complexes, oil & gas facilities, metro rail projects",
            'eligibility' => 'SSC (10th) or HSC Passed with Science / Technical stream.',
            'image_path' => 'activity_nss_camp.jpg',
            'duration' => '2 Years (4 Semesters)',
            'duration_years' => 2,
            'semester_count' => 4,
            'intake' => 10,
            'intake_capacity' => 10,
            'admission_status' => 'OPEN',
            'brochure_document' => 'assets/documents/SOET_Admission_Brochure_2026-27.pdf'
        ],

        // 4. Direct Second Year Lateral Entry Programs
        [
            'id' => 30,
            'department_id' => 4,
            'program_type' => 'lateral_entry',
            'name' => 'B.Tech Direct 2nd Year (Lateral Entry) - Civil Engineering',
            'code' => 'DSE-CIVIL',
            'description' => 'Direct Second Year Entry into B.Tech Civil Engineering for candidates who have completed Diploma in Engineering & Technology, B.Sc. Degree, or D.Voc.',
            'core_courses' => "• Advanced Structural Analysis & Fluid Mechanics\n• Surveying & Concrete Technology\n• Geotechnical & Transportation Engineering\n• CAD & Structural Design Software",
            'career_opportunities' => "• Structural Design Engineer\n• Project & Site Engineer\n• Infrastructure Consultant\n• Government & Private Construction Sectors",
            'eligibility' => 'Passed Diploma Course in Engineering and Technology with minimum 45% (40% for Reserved Category) OR B.Sc. Degree / D.Voc. stream.',
            'image_path' => 'soet_campus_aerial.jpg',
            'duration' => '3 Years (6 Semesters)',
            'duration_years' => 3,
            'semester_count' => 6,
            'intake' => 10,
            'intake_capacity' => 10,
            'admission_status' => 'OPEN',
            'brochure_document' => 'assets/documents/SOET_Admission_Brochure_2026-27.pdf'
        ],
        [
            'id' => 31,
            'department_id' => 1,
            'program_type' => 'lateral_entry',
            'name' => 'B.Tech Direct 2nd Year (Lateral Entry) - Computer Science & Engineering (Data Science)',
            'code' => 'DSE-CSE-DS',
            'description' => 'Direct Second Year Entry into B.Tech CSE Data Science for Diploma in Engineering, B.Sc., or D.Voc. holders.',
            'core_courses' => "• Data Structures & Algorithms\n• Python, R & Statistical Computing\n• Database Management Systems & SQL\n• Machine Learning Foundations",
            'career_opportunities' => "• Data Analyst / Data Engineer\n• ML Engineer\n• Business Intelligence Developer\n• Software Engineer",
            'eligibility' => 'Passed Diploma Course in Engineering and Technology with minimum 45% (40% for Reserved Category) OR B.Sc. Degree / D.Voc. stream.',
            'image_path' => 'soet_future_tech_banner.jpg',
            'duration' => '3 Years (6 Semesters)',
            'duration_years' => 3,
            'semester_count' => 6,
            'intake' => 10,
            'intake_capacity' => 10,
            'admission_status' => 'OPEN',
            'brochure_document' => 'assets/documents/BTech_Integrated_CSE_Data_Science_Booklet.pdf'
        ],
        [
            'id' => 32,
            'department_id' => 1,
            'program_type' => 'lateral_entry',
            'name' => 'B.Tech Direct 2nd Year (Lateral Entry) - Computer Science & Engineering (ICBT / Cyber Security)',
            'code' => 'DSE-CSE-ICBT',
            'description' => 'Direct Second Year Entry into B.Tech CSE (IoT, Cyber Security & Blockchain / Cyber Security) for Diploma or B.Sc. graduates.',
            'core_courses' => "• Computer Networks & Operating Systems\n• Network Security & Cryptography\n• IoT Embedded Systems & Microcontrollers\n• Object-Oriented Software Design",
            'career_opportunities' => "• Cyber Security Analyst\n• IoT Developer\n• Systems & Network Engineer\n• Software Developer",
            'eligibility' => 'Passed Diploma Course in Engineering and Technology with minimum 45% (40% for Reserved Category) OR B.Sc. Degree / D.Voc. stream.',
            'image_path' => 'soet_future_tech_banner.jpg',
            'duration' => '3 Years (6 Semesters)',
            'duration_years' => 3,
            'semester_count' => 6,
            'intake' => 10,
            'intake_capacity' => 10,
            'admission_status' => 'OPEN',
            'brochure_document' => 'assets/documents/BTech_CSE_Cybersecurity_Curriculum_Booklet.pdf'
        ],
        [
            'id' => 33,
            'department_id' => 3,
            'program_type' => 'lateral_entry',
            'name' => 'B.Tech Direct 2nd Year (Lateral Entry) - Robotics & Automation / AI',
            'code' => 'DSE-ROBOTICS',
            'description' => 'Direct Second Year Entry into B.Tech Robotics and Automation / AI for Diploma in Engineering or B.Sc. graduates.',
            'core_courses' => "• Kinematics & Mechatronic Mechanisms\n• PLC / SCADA Programming\n• Sensors, Actuators & Microcontrollers\n• Embedded Control Systems",
            'career_opportunities' => "• Automation Engineer\n• Robotics System Integrator\n• Mechatronics Specialist\n• Control System Designer",
            'eligibility' => 'Passed Diploma Course in Engineering and Technology with minimum 45% (40% for Reserved Category) OR B.Sc. Degree / D.Voc. stream.',
            'image_path' => 'event_robotics_training.jpg',
            'duration' => '3 Years (6 Semesters)',
            'duration_years' => 3,
            'semester_count' => 6,
            'intake' => 10,
            'intake_capacity' => 10,
            'admission_status' => 'OPEN',
            'brochure_document' => 'assets/documents/SOET_Admission_Brochure_2026-27.pdf'
        ]
    ];

    foreach ($official_courses as $c) {
        $stmt = $pdo->prepare("INSERT INTO courses (id, department_id, program_type, name, code, description, core_courses, career_opportunities, eligibility, image_path, duration, duration_years, semester_count, intake, intake_capacity, admission_status, brochure_document, is_active)
            VALUES (:id, :department_id, :program_type, :name, :code, :description, :core_courses, :career_opportunities, :eligibility, :image_path, :duration, :duration_years, :semester_count, :intake, :intake_capacity, :admission_status, :brochure_document, 1)
            ON DUPLICATE KEY UPDATE 
                department_id=:department_id, program_type=:program_type, name=:name, code=:code, description=:description, core_courses=:core_courses, career_opportunities=:career_opportunities, eligibility=:eligibility, image_path=:image_path, duration=:duration, duration_years=:duration_years, semester_count=:semester_count, intake=:intake, intake_capacity=:intake_capacity, admission_status=:admission_status, brochure_document=:brochure_document, is_active=1");
        $stmt->execute($c);
        echo "Saved Course: {$c['code']} - {$c['name']} (Intake: {$c['intake']})\n";
    }

    echo "\n=== 4. UPDATING PLACEMENT DATA (2026 BATCH) ===\n";
    // Clear and insert official verified placements from Placed Student Data (2).docx
    $pdo->exec("DELETE FROM placements");

    $official_placements = [
        ['student_name' => 'Shravani Pandit', 'company_name' => 'Persevex', 'package_lpa' => 4.50, 'placement_year' => 2026, 'batch' => '2026 Batch', 'department_id' => 1, 'course_id' => 16, 'designation' => 'Associate Software Engineer'],
        ['student_name' => 'Saurav Madake', 'company_name' => 'Persevex', 'package_lpa' => 4.50, 'placement_year' => 2026, 'batch' => '2026 Batch', 'department_id' => 1, 'course_id' => 16, 'designation' => 'Software Engineer Trainee'],
        ['student_name' => 'Harish Sonwale', 'company_name' => 'Persevex', 'package_lpa' => 4.50, 'placement_year' => 2026, 'batch' => '2026 Batch', 'department_id' => 1, 'course_id' => 16, 'designation' => 'Junior Developer'],
        ['student_name' => 'Aryan Tribhuvan', 'company_name' => 'Persevex', 'package_lpa' => 4.50, 'placement_year' => 2026, 'batch' => '2026 Batch', 'department_id' => 1, 'course_id' => 16, 'designation' => 'Technical Analyst'],
        ['student_name' => 'Farhaan Khan', 'company_name' => 'Green Rad Ventures Pvt. Ltd.', 'package_lpa' => 4.00, 'placement_year' => 2026, 'batch' => '2026 Batch', 'department_id' => 1, 'course_id' => 19, 'designation' => 'Graduate Engineer Trainee'],
        ['student_name' => 'Farhaan Khan', 'company_name' => 'Quess Corp Limited', 'package_lpa' => 3.80, 'placement_year' => 2026, 'batch' => '2026 Batch', 'department_id' => 1, 'course_id' => 19, 'designation' => 'IT Operations Engineer'],
        ['student_name' => 'Mayuri Rathod', 'company_name' => 'LearnFlu', 'package_lpa' => 4.20, 'placement_year' => 2026, 'batch' => '2026 Batch', 'department_id' => 1, 'course_id' => 27, 'designation' => 'Data Analyst Associate'],
        ['student_name' => 'Komal Khatri', 'company_name' => 'LearnFlu', 'package_lpa' => 4.20, 'placement_year' => 2026, 'batch' => '2026 Batch', 'department_id' => 1, 'course_id' => 27, 'designation' => 'Business Analyst Trainee'],
        ['student_name' => 'Komal Khatri', 'company_name' => 'Unlox', 'package_lpa' => 4.50, 'placement_year' => 2026, 'batch' => '2026 Batch', 'department_id' => 1, 'course_id' => 27, 'designation' => 'Associate Data Scientist'],
        ['student_name' => 'Saurav Madake', 'company_name' => 'Toyota Tsusho Pvt Ltd', 'package_lpa' => 5.20, 'placement_year' => 2026, 'batch' => '2026 Batch', 'department_id' => 1, 'course_id' => 16, 'designation' => 'System Engineer Trainee'],
        ['student_name' => 'Abhijeet Thote', 'company_name' => 'Toyota Tsusho Pvt Ltd', 'package_lpa' => 5.20, 'placement_year' => 2026, 'batch' => '2026 Batch', 'department_id' => 1, 'course_id' => 16, 'designation' => 'Systems Engineer'],
        ['student_name' => 'Payal Shejwal', 'company_name' => 'Expert Global Solutions', 'package_lpa' => 4.00, 'placement_year' => 2026, 'batch' => '2026 Batch', 'department_id' => 2, 'course_id' => 17, 'designation' => 'Design & Embedded Engineer']
    ];

    $ins_plc = $pdo->prepare("INSERT INTO placements (student_name, company_name, package_lpa, placement_year, batch, department_id, course_id, student_image, is_active)
        VALUES (:student_name, :company_name, :package_lpa, :placement_year, :batch, :department_id, :course_id, '', 1)");
    foreach ($official_placements as $p) {
        $ins_plc->execute([
            ':student_name' => $p['student_name'],
            ':company_name' => $p['company_name'],
            ':package_lpa' => $p['package_lpa'],
            ':placement_year' => $p['placement_year'],
            ':batch' => $p['batch'],
            ':department_id' => $p['department_id'],
            ':course_id' => $p['course_id']
        ]);
        echo "Added Placement: {$p['student_name']} -> {$p['company_name']} ({$p['batch']})\n";
    }

    echo "\n=== 5. UPDATING GALLERY WITH OFFICIAL EVENT ASSETS ===\n";
    $pdo->exec("DELETE FROM gallery");
    $official_gallery = [
        ['title' => 'Experts Live India Conference 2025', 'image_path' => 'event_experts_live_india.jpg', 'category' => 'Events', 'description' => 'International tech conference hosting distinguished industry specialists and academic leaders at SOET MGM University.'],
        ['title' => 'SUPERNOVA Tech Fest - Spark The Future', 'image_path' => 'event_supernova_group.jpg', 'category' => 'Events', 'description' => 'Flagship national-level annual technical festival celebrating student innovation, coding hackathons, and hardware exhibitions.'],
        ['title' => 'Supernova Tech Event Stage', 'image_path' => 'event_supernova_tech.jpg', 'category' => 'Events', 'description' => 'Distinguished guests and leadership addressing tech enthusiasts during Supernova event.'],
        ['title' => 'Annual Project & Innovation Exhibition', 'image_path' => 'event_project_exhibition.jpg', 'category' => 'Academics', 'description' => 'Demonstration of innovative student capstone projects, smart IoT prototypes, and industrial robotics solutions.'],
        ['title' => 'ZESTOPIA 2025 Cultural Event', 'image_path' => 'event_zestopia_cultural.jpg', 'category' => 'Cultural', 'description' => 'Vibrant cultural extravaganza celebrating student artistic talents, traditional dance, and musical performances.'],
        ['title' => 'Industrial Robotics & Automation Training', 'image_path' => 'event_robotics_training.jpg', 'category' => 'Workshops', 'description' => 'Advanced hands-on specialized training workshop on industrial 6-axis robotic arms and mechatronics automation.'],
        ['title' => 'Industrial Visit to LIEBHERR', 'image_path' => 'visit_liebherr_industry.jpg', 'category' => 'Industrial Visits', 'description' => 'SOET engineering students experiencing state-of-the-art heavy engineering and manufacturing processes at Liebherr.'],
        ['title' => 'Interactive Discussion: "Garage to Global"', 'image_path' => 'event_author_discussion.jpg', 'category' => 'Seminars', 'description' => 'Inspiring session and Q&A with the author of "Garage to Global" motivating young student entrepreneurs.'],
        ['title' => 'National Service Scheme (NSS) Activities', 'image_path' => 'activity_nss_camp.jpg', 'category' => 'Community', 'description' => 'Community empowerment, environmental awareness, and rural development initiatives by SOET NSS volunteers.'],
        ['title' => 'SOET Annual Sports Championship', 'image_path' => 'activity_sports_team.jpg', 'category' => 'Sports', 'description' => 'Inter-departmental sports tournament and athletics celebration at the MGM University sports stadium complex.'],
        ['title' => 'MGM University Campus Aerial View', 'image_path' => 'soet_campus_aerial.jpg', 'category' => 'Campus', 'description' => 'Panoramic view of the state-of-the-art MGM University lush green educational campus in Chhatrapati Sambhajinagar.'],
        ['title' => 'Mechatronics & Industry 4.0 Laboratory', 'image_path' => 'soet_robotics_mechatronics_lab.jpg', 'category' => 'Laboratories', 'description' => 'Cutting-edge Industry 4.0, Mechatronics, and Robotics labs equipping students with hands-on technical skills.']
    ];

    $ins_gal = $pdo->prepare("INSERT INTO gallery (title, image_path, category, description, is_active)
        VALUES (:title, :image_path, :category, :description, 1)");
    foreach ($official_gallery as $g) {
        $ins_gal->execute($g);
        echo "Added Gallery Item: {$g['title']}\n";
    }

    echo "\n=== 6. UPDATING SITE SETTINGS ===\n";
    $settings_to_update = [
        'college_name' => 'School of Engineering and Technology (SOET)',
        'university_name' => 'MGM University, Chhatrapati Sambhajinagar',
        'college_phone' => '+91 9371714253',
        'college_email' => 'soet@mgmu.ac.in',
        'college_address' => 'Mahatma Gandhi Mission University, MGM Campus, N-6 CIDCO, Chhatrapati Sambhajinagar - 431003, Maharashtra, India',
        'website_url' => 'https://soet.mgmu.ac.in',
        'admissions_portal' => 'https://admissions.mgmu.ac.in',
        'admission_status' => 'open',
        'admission_year' => '2026-27',
        'total_intake_ay2026_27' => '380',
        'admission_instructions' => 'UG 4-Year B.Tech: HSC Passed (min 45% Open, min 40% Reserved) + Non-zero score in JEE(Mains)/MHT-CET/PERA-CET/MGMU-CET. | UG 6-Year Integrated B.Tech: SSC Passed (min 50% Open, min 45% Reserved) + MGMU-CET. | Lateral Entry (DSE): Direct 2nd Year after Diploma/B.Sc./D.Voc. Apply online at admissions.mgmu.ac.in',
        'home_hero_image' => 'soet_campus_aerial.jpg',
        'home_welcome_image' => 'soet_robotics_mechatronics_lab.jpg',
        'site_logo' => 'mgm_university_official_logo.png',
        'established_year' => '1982',
        'trust_legacy_years' => '42+'
    ];

    foreach ($settings_to_update as $k => $v) {
        $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value)
            VALUES (:k, :v)
            ON DUPLICATE KEY UPDATE setting_value=:v");
        $stmt->execute([':k' => $k, ':v' => $v]);
    }
    echo "Site settings updated successfully.\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
