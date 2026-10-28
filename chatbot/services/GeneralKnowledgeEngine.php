<?php
/**
 * CampusAI — High-Precision Knowledge & Direct Response Engine
 *
 * Provides instant, zero-API-cost answers for technical, engineering,
 * programming, and general concepts using a comprehensive dictionary:
 * - Programming Languages: Python, Java, C++, C, JavaScript, PHP, SQL, Rust.
 * - Data Structures & Algorithms: Binary search, Quick sort, Merge sort, Stacks, Queues, Graphs.
 * - Core Systems & Hardware: Operating Systems, Linux, Docker, Git, Networking, VLSI, Semiconductors.
 * - Emerging Technologies: Artificial Intelligence, Machine Learning, Deep Learning, IoT, Robotics.
 * - Direct Concise Answers: Strict 2-3 sentence explanations with optional minimal code snippets.
 *
 * @package SOET_College_Project
 * @subpackage Chatbot_Services
 */

class GeneralKnowledgeEngine
{
    private static array $topicDatabase = [
        // COMPUTING & PROGRAMMING LANGUAGES
        'python' => [
            'desc' => "Python is a high-level, interpreted programming language known for its clean syntax and readability. It is widely used in web development, data science, automation, and artificial intelligence."
        ],
        'java' => [
            'desc' => "Java is an object-oriented, class-based language built on the 'Write Once, Run Anywhere' (WORA) principle via the JVM. It is widely used in enterprise backend systems and Android app development."
        ],
        'c++' => [
            'desc' => "C++ is a high-performance compiled programming language that supports procedural and object-oriented paradigms. It is commonly used in game engines, operating systems, and low-latency systems."
        ],
        'cpp' => [
            'desc' => "C++ (C Plus Plus) is a high-performance compiled programming language supporting procedural and object-oriented programming, widely used in game engines, operating systems, and competitive coding."
        ],
        'blockchain' => [
            'desc' => "Blockchain is a decentralized, distributed digital ledger that securely records transactions across a network of computers. Once recorded, data inside a block cannot be altered retrospectively without network consensus."
        ],
        'quantum computing' => [
            'desc' => "Quantum computing is a multidisciplinary field utilizing principles of quantum mechanics—such as superposition and entanglement—to perform complex computations exponentially faster than classical computers for specific problem classes."
        ],
        'object-oriented programming' => [
            'desc' => "Object-Oriented Programming (OOP) is a programming paradigm structured around objects and classes, centered on four core pillars: Encapsulation, Abstraction, Inheritance, and Polymorphism."
        ],
        'c' => [
            'desc' => "C is a foundational procedural programming language that provides low-level memory access and efficient execution. It is widely used for operating system kernels, embedded devices, and compilers."
        ],
        'javascript' => [
            'desc' => "JavaScript is a lightweight, dynamic scripting language primarily used to build interactive client-side web applications and scalable backends via Node.js."
        ],
        'php' => [
            'desc' => "PHP is a popular server-side scripting language designed specifically for web development, powering dynamic web applications, content management systems, and RESTful APIs."
        ],
        'sql' => [
            'desc' => "SQL (Structured Query Language) is the standard domain-specific language used for querying, inserting, updating, and managing structured data in relational database systems."
        ],
        'html' => [
            'desc' => "HTML (HyperText Markup Language) is the standard markup language used to structure web pages and their content using semantic elements and tags."
        ],
        'css' => [
            'desc' => "CSS (Cascading Style Sheets) is a stylesheet language used to describe the visual presentation, styling, layout, and responsive design of HTML documents."
        ],
        'rust' => [
            'desc' => "Rust is a modern systems programming language that ensures memory safety and thread concurrency without requiring a garbage collector."
        ],

        // DATA STRUCTURES & ALGORITHMS
        'binary search' => [
            'desc' => "Binary search is an efficient O(log n) algorithm that finds a target value in a sorted array by repeatedly dividing the search interval in half.",
            'code' => "int search(int a[], int n, int x) {\n    int l = 0, r = n - 1;\n    while (l <= r) {\n        int m = l + (r - l) / 2;\n        if (a[m] == x) return m;\n        if (a[m] < x) l = m + 1; else r = m - 1;\n    }\n    return -1;\n}"
        ],
        'linear search' => [
            'desc' => "Linear search sequentially checks each element of a list until a match is found or the end is reached, running in O(n) time complexity.",
            'code' => "int linearSearch(int a[], int n, int x) {\n    for (int i = 0; i < n; i++) if (a[i] == x) return i;\n    return -1;\n}"
        ],
        'bubble sort' => [
            'desc' => "Bubble sort is a simple comparison-based sorting algorithm that repeatedly steps through the list, swapping adjacent elements that are out of order (O(n²) time complexity).",
            'code' => "void bubbleSort(int a[], int n) {\n    for (int i = 0; i < n-1; i++)\n        for (int j = 0; j < n-i-1; j++)\n            if (a[j] > a[j+1]) { int t = a[j]; a[j] = a[j+1]; a[j+1] = t; }\n}"
        ],
        'quick sort' => [
            'desc' => "Quick sort is a divide-and-conquer algorithm that selects a pivot element and partitions the array into sub-arrays of smaller and greater elements, averaging O(n log n) time."
        ],
        'merge sort' => [
            'desc' => "Merge sort is a stable divide-and-conquer algorithm that recursively divides an array into halves, sorts them, and merges them back together in guaranteed O(n log n) time."
        ],
        'stack' => [
            'desc' => "A stack is a linear data structure following the Last-In, First-Out (LIFO) principle, supporting push (insertion) and pop (removal) operations at the top element."
        ],
        'queue' => [
            'desc' => "A queue is a linear data structure following the First-In, First-Out (FIFO) principle, where elements are enqueued at the rear and dequeued from the front."
        ],
        'linked list' => [
            'desc' => "A linked list is a linear data collection where elements (nodes) store a data value and a pointer reference to the next node in the sequence."
        ],
        'tree' => [
            'desc' => "A tree is a non-linear hierarchical data structure consisting of nodes connected by edges, starting from a single root node with subtrees of children."
        ],
        'graph' => [
            'desc' => "A graph is a non-linear data structure consisting of a finite set of vertices (nodes) and edges connecting pairs of vertices, representing complex relationships."
        ],
        'recursion' => [
            'desc' => "Recursion is a programming technique in which a function calls itself directly or indirectly to solve smaller subproblems until a base terminating condition is reached."
        ],
        'dsa' => [
            'desc' => "Data Structures & Algorithms (DSA) is the study of organizing data efficiently in memory and designing step-by-step procedures to solve computational problems with optimal time and space complexity."
        ],

        // OBJECT-ORIENTED PROGRAMMING
        'oops' => [
            'desc' => "Object-Oriented Programming (OOP) is a programming paradigm structured around objects and classes, centered on four core pillars: Encapsulation, Abstraction, Inheritance, and Polymorphism."
        ],
        'inheritance' => [
            'desc' => "Inheritance is an OOP mechanism where a child class inherits fields and methods from an existing parent class, enabling code reuse and hierarchical classification."
        ],
        'polymorphism' => [
            'desc' => "Polymorphism allows objects or methods to take on multiple forms, typically through method overloading (compile-time) and method overriding (run-time)."
        ],
        'encapsulation' => [
            'desc' => "Encapsulation is the bundling of data and the methods that operate on that data into a single class while restricting direct external access using private access modifiers."
        ],
        'abstraction' => [
            'desc' => "Abstraction is the concept of hiding complex internal implementation details and exposing only the essential features of an object through interfaces and abstract classes."
        ],

        // SYSTEMS, OS & NETWORKING
        'operating system' => [
            'desc' => "An operating system (OS) is core system software that manages computer hardware, memory, processes, storage, and device drivers while providing an interface for users and applications."
        ],
        'linux' => [
            'desc' => "Linux is an open-source, Unix-like operating system kernel known for stability, multi-user security, and modularity, powering most cloud servers, supercomputers, and Android devices."
        ],
        'database' => [
            'desc' => "A database is an organized collection of structured data stored electronically in a computer system, managed and queried via a Database Management System (DBMS)."
        ],
        'dbms' => [
            'desc' => "A Database Management System (DBMS) is software that manages database creation, storage, maintenance, and concurrent access while ensuring ACID transaction guarantees."
        ],
        'api' => [
            'desc' => "An Application Programming Interface (API) is a set of rules and protocols enabling two distinct software applications to communicate and exchange data seamlessly."
        ],
        'rest api' => [
            'desc' => "A REST API is an architectural style for network communication using standard HTTP methods (GET, POST, PUT, DELETE) and stateless JSON/XML data exchange."
        ],
        'docker' => [
            'desc' => "Docker is an open-source containerization platform that packages applications and their dependencies into lightweight, standalone containers that run consistently across any environment."
        ],
        'git' => [
            'desc' => "Git is a distributed version control system that tracks changes in source code files, allowing multiple developers to collaborate via branching, merging, and commits."
        ],
        'networking' => [
            'desc' => "Computer networking is the practice of connecting computing devices to exchange data and share resources over wired or wireless media using communication protocols like TCP/IP."
        ],
        'tcp/ip' => [
            'desc' => "TCP/IP is the foundational communications suite of the Internet, where TCP ensures reliable, in-order packet delivery and IP handles routing addresses across networks."
        ],
        'cybersecurity' => [
            'desc' => "Cybersecurity is the discipline of protecting computer systems, networks, devices, and digital assets from unauthorized access, cyberattacks, data theft, and damage."
        ],
        'cyber security' => [
            'desc' => "Cyber Security is the practice of defending electronic systems, networks, programs, and data from digital attacks, unauthorized access, ransomware, and vulnerabilities."
        ],
        'cloud computing' => [
            'desc' => "Cloud computing provides on-demand access to computing resources—including virtual servers, storage, databases, and software—over the internet with pay-as-you-go pricing (e.g., AWS, Azure, GCP)."
        ],
        'iot' => [
            'desc' => "The Internet of Things (IoT) refers to a network of physical objects embedded with sensors, processing ability, and wireless connectivity to collect and exchange data in real time."
        ],
        'internet of things' => [
            'desc' => "The Internet of Things (IoT) connects physical devices—such as sensors, home automation systems, and industrial machines—to the internet for real-time monitoring, telemetry, and automated control."
        ],
        'vlsi' => [
            'desc' => "VLSI (Very Large Scale Integration) is the engineering process of creating integrated circuits (ICs) by combining millions or billions of MOS transistors onto a single silicon semiconductor microchip."
        ],
        'vlsi design' => [
            'desc' => "VLSI (Very Large Scale Integration) Design involves creating microelectronic semiconductor integrated circuits by combining millions of MOS transistors onto a single silicon chip using HDLs (Verilog/VHDL) and physical synthesis."
        ],
        'very large scale integration' => [
            'desc' => "Very Large Scale Integration (VLSI) is the technology of integrating hundreds of thousands to billions of transistors onto a single microchip to create microprocessors and complex digital hardware."
        ],
        'robotics' => [
            'desc' => "Robotics is an interdisciplinary branch of engineering and computer science involving the design, construction, operation, and programming of autonomous or semi-autonomous robots to execute tasks."
        ],
        'automation' => [
            'desc' => "Automation is the application of control systems, programmable logic controllers (PLCs), sensors, and software algorithms to operate industrial equipment and processes with minimal human intervention."
        ],
        'robotics and automation' => [
            'desc' => "Robotics and Automation integrates mechanical systems, electronics, microcontrollers, and artificial intelligence to design smart robotic systems for manufacturing, healthcare, and industrial automation."
        ],
        'icbt' => [
            'desc' => "ICBT stands for Internet of Things, Cyber Security, and Blockchain Technology—an interdisciplinary engineering domain combining smart connected devices, digital defense, and decentralized ledgers."
        ],
        'embedded systems' => [
            'desc' => "An embedded system is a dedicated computer hardware and software combination designed to perform a specific dedicated function within a larger mechanical or electronic system."
        ],
        'semiconductor' => [
            'desc' => "A semiconductor is a material with electrical conductivity between a conductor and an insulator (typically silicon or germanium), forming the foundation of modern transistors, diodes, and ICs."
        ],
        'artificial intelligence' => [
            'desc' => "Artificial Intelligence (AI) is the branch of computer science dedicated to developing systems capable of performing tasks that typically require human cognition, such as reasoning, learning, and perception."
        ],
        'ai' => [
            'desc' => "Artificial Intelligence (AI) simulates human cognitive abilities in software to automate complex tasks including natural language processing, predictive analysis, computer vision, and decision-making."
        ],
        'machine learning' => [
            'desc' => "Machine learning is a subset of AI where algorithms learn statistical patterns from data to make predictions or decisions without being explicitly programmed."
        ],
        'deep learning' => [
            'desc' => "Deep learning is an advanced branch of machine learning that utilizes multi-layered artificial neural networks (CNNs, RNNs, Transformers) to model complex representations from large datasets."
        ],
        'data science' => [
            'desc' => "Data science is an interdisciplinary field that combines statistics, mathematics, specialized programming, and machine learning to extract actionable insights from structured and unstructured data."
        ],
        'generative ai' => [
            'desc' => "Generative AI refers to artificial intelligence models capable of generating new text, images, audio, synthetic data, or code based on patterns learned from training data."
        ],
        'chatgpt' => [
            'desc' => "ChatGPT is an artificial intelligence chatbot developed by OpenAI, trained on large language models (LLMs) to engage in natural dialogue, write and debug code, and summarize complex topics."
        ],
        'internet' => [
            'desc' => "The Internet is a global network of interconnected computer networks that communicate and exchange information using the Internet Protocol Suite (TCP/IP)."
        ],
        'digital transformation' => [
            'desc' => "Digital transformation is the strategic adoption of digital technologies across all areas of an enterprise, fundamentally restructuring operational efficiency and customer value delivery."
        ],
        'mysql' => [
            'desc' => "MySQL is an open-source Relational Database Management System (RDBMS) that stores data in structured tables and uses Structured Query Language (SQL) for database operations."
        ],
        'difference between html and css' => [
            'desc' => "HTML provides the structural markup and semantic content of a webpage, whereas CSS controls the presentation, visual styling, colors, and responsive layout of those HTML elements."
        ],
        'difference between java and python' => [
            'desc' => "Java is a statically typed, compiled object-oriented language prioritizing strict type safety and high performance, whereas Python is a dynamically typed, interpreted language emphasizing developer productivity and readable syntax."
        ],
        'class' => [
            'desc' => "In object-oriented programming, a class is an extensible code blueprint that defines the member variables (state) and methods (behavior) that its created objects will possess."
        ],
        'object' => [
            'desc' => "An object is a specific, self-contained instance of a class in memory that encapsulates concrete state data and executable methods defined by its class."
        ],
        'primary key' => [
            'desc' => "A primary key is a column or set of columns in a relational database table that uniquely identifies each individual row, strictly enforcing non-null and uniqueness constraints."
        ],
        'foreign key' => [
            'desc' => "A foreign key is a column in a relational table that points to the primary key of another table, establishing a referential integrity link between the two tables."
        ],
        'normalization' => [
            'desc' => "Database normalization is the systematic design process of structuring relational tables to eliminate data redundancy and prevent insertion, update, and deletion anomalies (1NF to BCNF)."
        ],
        'sql query' => [
            'desc' => "An SQL query is a declarative command written in SQL (e.g., SELECT, INSERT, UPDATE, DELETE) to retrieve, filter, modify, or manage structured data within a database."
        ],
        'crud' => [
            'desc' => "CRUD is an acronym for Create, Read, Update, and Delete—the four foundational operations implemented in persistent storage and RESTful database applications."
        ],
        'github' => [
            'desc' => "GitHub is a cloud-based development and hosting platform built around Git, facilitating source code version control, pull requests, code reviews, and project collaboration."
        ],
        'ip address' => [
            'desc' => "An IP address (Internet Protocol address) is a unique numerical label (IPv4 or IPv6) assigned to each device connected to a computer network that uses the Internet Protocol for communication."
        ],

        // GENERAL EVERYDAY CONCEPTS
        'car' => [
            'desc' => "An automobile (car) is a four-wheeled motor vehicle designed for passenger road transportation, powered by an internal combustion engine or an electric motor."
        ],
        'automobile' => [
            'desc' => "An automobile is a wheeled road vehicle powered by an engine or electric traction motor, engineered with powertrain, transmission, steering, and braking systems."
        ],
        'electric vehicle' => [
            'desc' => "An electric vehicle (EV) operates on an electric motor powered by rechargeable lithium-ion battery packs rather than consuming gasoline or diesel fuel."
        ],
        'ev' => [
            'desc' => "An Electric Vehicle (EV) is a road vehicle powered by an electric motor and rechargeable battery pack, delivering zero tailpipe emissions and high energy efficiency."
        ],
        'dinner' => [
            'desc' => "Dinner is the primary evening meal. At SOET MGM University, the campus hostel mess serves dinner daily from 7:30 PM to 9:30 PM with balanced vegetarian thali options."
        ],
        'lunch' => [
            'desc' => "Lunch is the midday meal. At SOET MGM University, the campus canteen and hostel mess serve lunch daily from 12:30 PM to 2:30 PM."
        ],
        'breakfast' => [
            'desc' => "Breakfast is the first meal of the day. Fresh breakfast, tea, and snacks are available at the SOET campus canteen every morning from 8:00 AM to 10:00 AM."
        ],
        'sports' => [
            'desc' => "MGM University features an Olympic-standard Sports Complex with a cricket stadium, football ground, basketball and badminton courts, indoor games, and a modern fitness gym."
        ],
        'hostel' => [
            'desc' => "SOET MGM University provides secure in-campus hostels for boys and girls with 24/7 security, Wi-Fi connectivity, study areas, and hygienic dining mess facilities."
        ]
    ];

    /**
     * Resolve general & technical questions directly in 2-3 lines like ChatGPT
     */
    public static function resolve(string $query, string $category = 'GENERAL'): string
    {
        $cleanQuery = mb_strtolower(trim($query));

        // 1. Conversational Politeness & Small Talk
        if (preg_match('/^(thanks|thank you|thanku|thx|tq)\b/i', $cleanQuery)) {
            return "You're welcome! Feel free to ask if you need anything else.";
        }
        if (preg_match('/^(good night|goodnight|gn)\b/i', $cleanQuery)) {
            return "Good night! Rest well. Feel free to chat with CampusAI anytime.";
        }
        if (preg_match('/^(how are you|how do you do)\b/i', $cleanQuery)) {
            return "I'm doing well and ready to assist you. How can I help today?";
        }
        if (preg_match('/\b(who are you|your name|what are you)\b/i', $cleanQuery)) {
            return "I am CampusAI, an intelligent assistant that provides verified SOET MGM University information and answers general & technical questions.";
        }

        // 2. Temporal Queries (Time, Date, Day, Year) - Specific checks that do not collide with 'time complexity'
        if ((preg_match('/\b(what is the date|what is today\'?s date|today date|current date|current time|what time is it|what is the time|what day is today|what year is this|which year is this)\b/i', $cleanQuery) || preg_match('/^(date|today|time|clock)\??$/i', $cleanQuery)) && !preg_match('/\b(complexity|series|table|sharing|quantum)\b/i', $cleanQuery)) {
            $now = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
            if (preg_match('/\b(time|clock)\b/i', $cleanQuery)) {
                return "The current time is " . $now->format('h:i:s A') . " IST (" . $now->format('l, F j, Y') . ").";
            }
            if (preg_match('/\b(year)\b/i', $cleanQuery)) {
                return "The current year is " . $now->format('Y') . ".";
            }
            if (preg_match('/\b(day)\b/i', $cleanQuery)) {
                return "Today is " . $now->format('l') . ", " . $now->format('F j, Y') . ".";
            }
            return "Today's date is " . $now->format('l, F j, Y') . ", and the current time is " . $now->format('h:i A') . " IST.";
        }

        // 3. Mathematical Calculations (e.g. 15 + 35, 10 * 20)
        if (preg_match('/(?:what is|calculate|compute)?\s*(\d+(?:\.\d+)?)\s*([\+\-\*\/\%])\s*(\d+(?:\.\d+)?)/i', $cleanQuery, $mathMatches)) {
            $num1 = (float)$mathMatches[1];
            $op = $mathMatches[2];
            $num2 = (float)$mathMatches[3];
            $res = null;
            switch ($op) {
                case '+': $res = $num1 + $num2; break;
                case '-': $res = $num1 - $num2; break;
                case '*': $res = $num1 * $num2; break;
                case '/': $res = ($num2 != 0) ? $num1 / $num2 : 'undefined (division by zero)'; break;
                case '%': $res = ($num2 != 0) ? fmod($num1, $num2) : 'undefined'; break;
            }
            if ($res !== null) {
                return "`" . $num1 . " " . $op . " " . $num2 . " = " . $res . "`";
            }
        }

        // 4. Code Request Detection
        $isCodeRequested = (bool)preg_match('/\b(code|function|program|snippet|write|syntax|example|implement)\b/i', $cleanQuery);

        // 5. Match Topic Database
        $keys = array_keys(self::$topicDatabase);
        usort($keys, fn($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        foreach ($keys as $key) {
            $pattern = preg_match('/[a-z0-9]$/i', $key)
                ? '/\b' . preg_quote($key, '/') . '\b/i'
                : '/\b' . preg_quote($key, '/') . '(?!\w)/i';

            if (preg_match($pattern, $cleanQuery)) {
                $item = self::$topicDatabase[$key];
                $output = $item['desc'];

                // Append smallest code example if available and code was requested
                if ($isCodeRequested && !empty($item['code'])) {
                    $output .= "\n\n```c\n" . $item['code'] . "\n```";
                }
                return $output;
            }
        }

        // 6. Native Neural AI Model Inference (Own Model Engine)
        if (file_exists(__DIR__ . '/../engine/CampusAIModel.php')) {
            require_once __DIR__ . '/../engine/CampusAIModel.php';
            $neuralResult = CampusAIModel::getInstance()->infer($query);
            if ($neuralResult['success'] && !empty($neuralResult['text'])) {
                return $neuralResult['text'];
            }
        }

        // 7. If still not matched, return empty string to allow fallback handling
        return "";
    }
}
