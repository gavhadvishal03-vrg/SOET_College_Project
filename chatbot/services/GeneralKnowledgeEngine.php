<?php
/**
 * 🤖 CampusAI — Comprehensive Built-in Knowledge & General Query Engine
 * Intelligent Intent Analysis & Response Synthesizer
 * Prevents rigid technical templates for casual/dining/lifestyle/conversational queries.
 */

class GeneralKnowledgeEngine
{
    private static array $topicDatabase = [
        // AUTOMOTIVE & VEHICLES
        'car' => [
            'title' => 'Automobiles & Automotive Engineering',
            'content' => "An **Automobile (Car)** is a wheeled passenger motor vehicle powered by an engine or electric motor.\n\nKey Engineering Systems:\n• **Powertrain & Engine**: Internal Combustion Engines (Gasoline/Diesel) or Electric Motors (EV Lithium Battery Packs).\n• **Transmission**: Transfers engine power to wheels (Manual, Automatic, CVT, Dual-Clutch).\n• **Chassis & Suspension**: Structural frame supporting vehicle weight and dampening road shocks (MacPherson, Double Wishbone).\n• **Braking System**: Hydraulic Disc Brakes, Anti-Lock Braking System (ABS), & Regenerative Braking in EVs.\n• **Automotive Electronics**: Engine Control Unit (ECU), Sensors, ADAS Autonomous Driving, & Telematics."
        ],
        'automobile' => [
            'title' => 'Automobiles & Automotive Engineering',
            'content' => "An **Automobile** is a wheeled motor vehicle used for land transportation.\n\nKey Subsystems:\n• Internal Combustion Engine & Electric Vehicle (EV) Powertrains\n• Transmission & Differential Gear Systems\n• Steering, Suspension, & Brakes (ABS, EBD)\n• Automotive Mechatronics & Electronic Control Units (ECUs)"
        ],
        'electric vehicle' => [
            'title' => 'Electric Vehicles (EVs)',
            'content' => "An **Electric Vehicle (EV)** uses one or more electric motors powered by rechargeable lithium-ion battery packs.\n\nKey Advantages:\n• Zero Direct Tailpipe Emissions\n• High Instant Torque & Acceleration\n• Lower Maintenance & Regenerative Braking Energy Recovery"
        ],
        'ev' => [
            'title' => 'Electric Vehicles (EVs)',
            'content' => "Electric Vehicles (EVs) operate on electric powertrains utilizing battery packs (Li-ion, LFP) and electric traction motors instead of internal combustion engines."
        ],

        // ENGINEERING DISCIPLINES
        'mechanical engineering' => [
            'title' => 'Mechanical Engineering',
            'content' => "Mechanical Engineering applies engineering physics, mathematics, and materials science to design, analyze, manufacture, and maintain mechanical systems.\n\nCore Domains:\n• CAD/CAM Modeling & Product Design\n• Thermodynamics & Heat Transfer\n• Fluid Mechanics & Hydraulics\n• Industrial Robotics & Mechatronics"
        ],
        'mechanical' => [
            'title' => 'Mechanical Engineering Overview',
            'content' => "Mechanical Engineering encompasses machine design, thermal sciences, manufacturing processes, automation, and robotics."
        ],
        'civil engineering' => [
            'title' => 'Civil Engineering',
            'content' => "Civil Engineering deals with the design, construction, and maintenance of infrastructure including roads, bridges, dams, airports, and smart buildings.\n\nCore Disciplines:\n• Structural Engineering & Earthquake Mechanics\n• Geotechnical & Foundation Engineering\n• Environmental & Water Resource Systems\n• Building Information Modeling (BIM)"
        ],
        'civil' => [
            'title' => 'Civil Engineering Overview',
            'content' => "Civil Engineering focuses on structural analysis, construction management, smart infrastructure, and surveying."
        ],
        'electrical engineering' => [
            'title' => 'Electrical Engineering',
            'content' => "Electrical Engineering covers electricity generation, power transmission, renewable energy, microcontrollers, and electrical drives."
        ],
        'electronics' => [
            'title' => 'Electronics & Telecommunication (ECE)',
            'content' => "ECE studies semiconductor devices, VLSI integrated circuit design, embedded microcontrollers, digital signal processing, and 5G wireless networks."
        ],

        // COMPUTING & PROGRAMMING
        'computer science' => [
            'title' => 'Computer Science & Engineering (CSE)',
            'content' => "Computer Science encompasses computation, software engineering, algorithms, database systems, artificial intelligence, and network architecture."
        ],
        'binary search' => [
            'title' => 'Binary Search Algorithm',
            'content' => "Binary Search is an efficient algorithm that searches a sorted array by repeatedly dividing the search interval in half. Time Complexity: O(log n).\n\n```c\nint binarySearch(int arr[], int size, int target) {\n    int low = 0, high = size - 1;\n    while (low <= high) {\n        int mid = low + (high - low) / 2;\n        if (arr[mid] == target) return mid;\n        if (arr[mid] < target) low = mid + 1;\n        else high = mid - 1;\n    }\n    return -1;\n}\n```"
        ],
        'dbms' => [
            'title' => 'Database Management Systems (DBMS)',
            'content' => "A DBMS is software used to store, retrieve, and manage data securely.\n\nCore Concepts:\n- ACID Properties: Atomicity, Consistency, Isolation, Durability.\n- Normalization: Reducing redundancy (1NF, 2NF, 3NF, BCNF).\n- SQL: Structured Query Language for relational databases (MySQL, PostgreSQL, Oracle)."
        ],
        'python' => [
            'title' => 'Python Programming Language',
            'content' => "Python is a high-level, interpreted language famous for code readability and rich ecosystem.\n\nApplications:\n- Data Science & ML (Pandas, NumPy, Scikit-learn, TensorFlow)\n- Web Backend (Django, Flask, FastAPI)\n- Scripting & Automation"
        ],
        'java' => [
            'title' => 'Java Programming',
            'content' => "Java is a class-based, object-oriented language ('Write Once, Run Anywhere' via JVM).\n\nOOP Pillars:\n1. Encapsulation: Hiding internal data state.\n2. Inheritance: Code reuse via parent-child classes.\n3. Polymorphism: Method overloading and overriding.\n4. Abstraction: Hiding implementation details via Interfaces and Abstract Classes."
        ],
        'artificial intelligence' => [
            'title' => 'Artificial Intelligence (AI)',
            'content' => "Artificial Intelligence is the branch of computer science devoted to creating systems capable of performing tasks that typically require human intelligence, such as learning, reasoning, problem solving, perception, and natural language understanding."
        ],
        'ai' => [
            'title' => 'Artificial Intelligence (AI)',
            'content' => "Artificial Intelligence (AI) simulates human cognitive functions in software. Applications include Machine Learning, Neural Networks, Natural Language Processing (NLP), Computer Vision, and Robotics."
        ],
        'machine learning' => [
            'title' => 'Machine Learning (ML)',
            'content' => "Machine Learning allows algorithms to automatically learn patterns from data without explicit programming.\n\nCategories:\n- Supervised: Learning with labeled datasets (Regression, Classification).\n- Unsupervised: Clustering unlabeled data (k-Means, PCA).\n- Reinforcement: Learning via reward and penalty loops."
        ],
        'deep learning' => [
            'title' => 'Deep Learning & Neural Networks',
            'content' => "Deep Learning uses multi-layered artificial neural networks inspired by the human brain. Architectures include CNNs (Computer Vision), RNNs/LSTMs (Time-series), and Transformers (LLMs & Language Tasks)."
        ],
        'cloud computing' => [
            'title' => 'Cloud Computing',
            'content' => "Cloud computing offers on-demand network access to shared computing resources (servers, storage, databases).\n\nModels:\n- IaaS: AWS EC2, Azure VMs\n- PaaS: Heroku, Google App Engine\n- SaaS: Google Workspace, Microsoft 365"
        ],
        'cybersecurity' => [
            'title' => 'Cybersecurity',
            'content' => "Cybersecurity protects systems, networks, and data from digital attacks, authorization breaches, and malware.\n\nCore Principles (CIA Triad):\n- Confidentiality: Protecting sensitive data.\n- Integrity: Preventing unauthorized modifications.\n- Availability: Ensuring service uptime."
        ],
        'data science' => [
            'title' => 'Data Science',
            'content' => "Data Science combines statistics, computer science, and domain expertise to extract meaningful insights from data pipelines."
        ],
        'web development' => [
            'title' => 'Web Development',
            'content' => "Web development encompasses building websites and web apps:\n- Frontend: User interface (HTML5, CSS3, JavaScript, React)\n- Backend: Server logic, APIs, and databases (PHP, Node.js, Python, MySQL)\n- Full-Stack: Comprehensive end-to-end development."
        ],
        'react' => [
            'title' => 'React Frontend Library',
            'content' => "React is a open-source JavaScript library developed by Meta for building component-driven user interfaces utilizing a Virtual DOM for high-performance rendering."
        ],

        // GENERAL SCIENCE & MATH
        'physics' => [
            'title' => 'Physics Overview',
            'content' => "Physics is the fundamental natural science studying matter, energy, motion, forces, space, and time."
        ],
        'chemistry' => [
            'title' => 'Chemistry Overview',
            'content' => "Chemistry studies the composition, structure, properties, and reactions of matter and chemical substances."
        ],
        'mathematics' => [
            'title' => 'Mathematics',
            'content' => "Mathematics encompasses numbers, quantities, structures, algebra, calculus, geometry, and logical reasoning."
        ],
        'mgm' => [
            'title' => 'MGM University',
            'content' => "Mahatma Gandhi Mission (MGM) University is a pioneering self-financed university in Chhatrapati Sambhajinagar (Aurangabad), Maharashtra, committed to educational excellence and technological research."
        ],
        'soet' => [
            'title' => 'SOET - School of Engineering & Technology',
            'content' => "SOET is the flagship institute of engineering at MGM University offering B.Tech, M.Tech, and Ph.D. programs in CSE, Mechanical, Civil, ECE, Electrical, and Applied Sciences."
        ]
    ];

    public static function resolve(string $query): string
    {
        $cleanQuery = mb_strtolower(trim($query));

        // --- 1. FOOD, DINING & LIFESTYLE QUERIES ---
        if (preg_match('/\b(dinner|dinner time|dinner menu|mess dinner)\b/i', $cleanQuery)) {
            return "🍽️ **Dinner & Campus Dining**\n\n"
                 . "**Dinner** is the primary evening meal. At SOET MGM University, the campus canteen and hostel mess serve fresh, hygienic meals for students, hostellers, and faculty.\n\n"
                 . "• **Hostel Mess Dinner Hours**: 7:30 PM – 9:30 PM daily.\n"
                 . "• **Menu Highlights**: Balanced vegetarian thali including roti, rice, dal, seasonal vegetables, salad, and weekend specials.\n\n"
                 . "💡 *If you need information about hostel room admissions or campus dining facilities, ask 🤖 CampusAI!*";
        }

        if (preg_match('/\b(lunch|lunch time|canteen lunch)\b/i', $cleanQuery)) {
            return "🍱 **Lunch & Campus Canteen**\n\n"
                 . "**Lunch** is served daily at the MGM University campus canteen and hostel mess.\n\n"
                 . "• **Canteen Lunch Hours**: 12:30 PM – 2:30 PM.\n"
                 . "• **Facilities**: Food court, fresh fruit juices, vegetarian meals, and snacks.\n\n"
                 . "💡 *Feel free to ask CampusAI about campus facilities or hostel admission details!*";
        }

        if (preg_match('/\b(breakfast|morning snacks|tea|coffee)\b/i', $cleanQuery)) {
            return "☕ **Breakfast & Morning Refreshment**\n\n"
                 . "Fresh breakfast, tea, coffee, and snacks are available at the SOET campus canteen every morning from **8:00 AM – 10:00 AM**.";
        }

        if (preg_match('/\b(food|canteen|mess|cafeteria|eating)\b/i', $cleanQuery)) {
            return "🍔 **Campus Dining & Canteen Facilities**\n\n"
                 . "SOET MGM University provides hygienic campus canteens and dining halls serving fresh vegetarian meals, breakfasts, beverages, and snacks daily for students and staff.";
        }

        // --- 2. CONVERSATIONAL POLITENESS & GREETINGS ---
        if (preg_match('/\b(thanks|thank you|thanku|thx|tq)\b/i', $cleanQuery)) {
            return "😊 **You're very welcome!** I'm happy to help. Feel free to ask if you need anything else about SOET college, admissions, courses, or general queries! 🚀";
        }

        if (preg_match('/\b(good night|goodnight|gn)\b/i', $cleanQuery)) {
            return "🌙 **Good Night!** Rest well. Feel free to chat with 🤖 **CampusAI** anytime!";
        }

        if (preg_match('/\b(good morning|gm)\b/i', $cleanQuery)) {
            return "☀️ **Good Morning!** Welcome to SOET MGM University. How can 🤖 **CampusAI** assist you today?";
        }

        if (preg_match('/\b(sports|cricket|football|basketball|badminton|gym|games)\b/i', $cleanQuery)) {
            return "🏆 **Sports & Campus Recreation**\n\n"
                 . "MGM University boasts a premier Sports Complex featuring a cricket stadium, football ground, basketball & badminton courts, indoor games, and a modern fitness gym for students.";
        }

        if (preg_match('/\b(hostel|room|stay|accommodation)\b/i', $cleanQuery)) {
            return "🏢 **Campus Hostel & Accommodation**\n\n"
                 . "SOET MGM University offers safe, fully-equipped in-campus hostels for boys and girls with 24/7 security, high-speed Wi-Fi, study rooms, and nutritious mess dining.\n\n"
                 . "💡 *To apply for hostel allocation, contact the SOET admission office or submit an inquiry via CampusAI!*";
        }

        // --- 3. DATE, TIME, DAY, YEAR DYNAMIC TEMPORAL QUERIES ---
        if (preg_match('/\b(date|today|time|day|year|month)\b/i', $cleanQuery)) {
            $now = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
            
            if (preg_match('/\b(time|clock)\b/i', $cleanQuery)) {
                return "🕒 **Current Time (IST)**:\n\n• **Time**: " . $now->format('h:i:s A') . " IST\n• **Date**: " . $now->format('l, F j, Y') . "\n• **Timezone**: Indian Standard Time (UTC+05:30)";
            }
            
            if (preg_match('/\b(year)\b/i', $cleanQuery)) {
                return "📅 **Current Year**:\n\nThe current year is **" . $now->format('Y') . "**.";
            }

            if (preg_match('/\b(month)\b/i', $cleanQuery)) {
                return "📅 **Current Month**:\n\nThe current month is **" . $now->format('F Y') . "**.";
            }

            if (preg_match('/\b(day)\b/i', $cleanQuery)) {
                return "📅 **Today's Day**:\n\nToday is **" . $now->format('l') . "** (" . $now->format('F j, Y') . ").";
            }

            return "📅 **Today's Date & Time**:\n\n"
                 . "• **Date**: " . $now->format('l, F j, Y') . "\n"
                 . "• **Current Time**: " . $now->format('h:i A') . " IST\n"
                 . "• **Year**: " . $now->format('Y') . "\n\n"
                 . "How can 🤖 **CampusAI** assist you further today?";
        }

        // --- 4. MATHEMATICAL CALCULATION EVALUATOR ---
        if (preg_match('/(?:what is|calculate|compute)?\s*(\d+(?:\.\d+)?)\s*([\+\-\*\/\%])\s*(\d+(?:\.\d+)?)/i', $cleanQuery, $mathMatches)) {
            $num1 = (float)$mathMatches[1];
            $op = $mathMatches[2];
            $num2 = (float)$mathMatches[3];
            $res = null;

            switch ($op) {
                case '+': $res = $num1 + $num2; break;
                case '-': $res = $num1 - $num2; break;
                case '*': $res = $num1 * $num2; break;
                case '/': $res = ($num2 != 0) ? $num1 / $num2 : 'Division by zero error'; break;
                case '%': $res = ($num2 != 0) ? fmod($num1, $num2) : 'Modulus by zero error'; break;
            }

            if ($res !== null) {
                return "🧮 **Mathematical Calculation**:\n\n`" . $num1 . " " . $op . " " . $num2 . " = " . $res . "`";
            }
        }

        // --- 5. CONVERSATIONAL & IDENTITY QUERIES ---
        if (preg_match('/\b(who are you|your name|what are you|who built you|who created you)\b/i', $cleanQuery)) {
            return "🤖 **I am CampusAI**, the official intelligent assistant for SOET (School of Engineering & Technology), MGM University!\n\nI can help you with:\n- **College Info**: Courses, Fee Structure, Admissions, Faculty, Seat Availability, Notices, Events, & Placements.\n- **General Knowledge**: Programming, Science, IT concepts, Mathematics, and General Questions.";
        }

        if (preg_match('/\b(how are you|how do you do|are you fine)\b/i', $cleanQuery)) {
            return "😊 **I'm doing great and fully operational!** Thank you for asking. How can I assist you with SOET college information or general queries today?";
        }

        // --- 6. DIRECT TOPIC DATABASE MATCH ---
        $keys = array_keys(self::$topicDatabase);
        usort($keys, function($a, $b) {
            return mb_strlen($b) <=> mb_strlen($a);
        });

        foreach ($keys as $key) {
            $data = self::$topicDatabase[$key];
            if (preg_match('/\b' . preg_quote($key, '/') . '\b/i', $cleanQuery)) {
                return "### " . $data['title'] . "\n\n" . $data['content'];
            }
        }

        // --- 7. DYNAMIC INTENT-AWARE QUESTION SYNTHESIZER ---
        return self::synthesizeGeneralResponse($query);
    }

    private static function synthesizeGeneralResponse(string $query): string
    {
        $clean = trim($query);
        $cleanTopic = preg_replace('/^(what is|who is|explain|tell me about|how to|define|where is|information on|details of)\s+/i', '', $clean);
        $topicTitle = ucwords(trim($cleanTopic));

        if (empty($topicTitle)) {
            $topicTitle = 'General Question';
        }

        // Check if query is about technical/academic terms vs general conversational terms
        $isTechnical = preg_match('/\b(engineering|technology|science|algorithm|software|hardware|network|data|code|math|physics|chemistry|system|circuit|design|model|structure)\b/i', $clean);

        if ($isTechnical) {
            return "### 📚 Educational & Technical Overview: {$topicTitle}\n\n"
                 . "**{$topicTitle}** is an important technical concept:\n\n"
                 . "• **Core Concept**: {$topicTitle} represents a key area of technical study, innovation, and practical application.\n"
                 . "• **Applications**: It plays a vital role across modern engineering, software development, research, and industry.\n\n"
                 . "💡 *If you'd like specific information about SOET engineering programs, admissions, fees, or faculty, ask CampusAI!*";
        } else {
            return "💡 **Overview regarding {$topicTitle}**\n\n"
                 . "Regarding **{$topicTitle}**, if you're asking in the context of **SOET MGM University** (such as campus facilities, student activities, courses, or events), 🤖 **CampusAI** is here to help!\n\n"
                 . "Feel free to ask specific questions about SOET admissions, fee structures, faculty profiles, or campus life!";
        }
    }
}
