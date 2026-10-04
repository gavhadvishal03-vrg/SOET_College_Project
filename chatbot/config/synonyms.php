<?php
/**
 * CampusAI — Centralized Synonym & Intent Mapping Configuration
 *
 * Multi-tier natural language processing dictionary:
 * 1. Spelling Corrections: Common student misspellings mapped to canonical forms.
 * 2. Plural to Singular Normalization: Standardizing word endings for SQL fulltext matching.
 * 3. Synonym Group Mappings: Expanding queries with relevant domain vocabulary.
 * 4. Natural Language Intent Patterns: Regex heuristics classifying queries into intents (SEAT_AVAILABILITY, PROGRAM, FEE, FACULTY, ADMISSION, PLACEMENT, etc.).
 * 5. Context Disambiguation Rules: Distinguishing academic terms (e.g. computer program vs academic degree program).
 *
 * @package SOET_College_Project
 * @subpackage Chatbot_Config
 */

return [

    // 1. Spelling Corrections
    'spelling_corrections' => [
        'cmputer'    => 'computer',
        'scnce'      => 'science',
        'faclty'     => 'faculty',
        'admssion'   => 'admission',
        'admisn'     => 'admission',
        'stucture'   => 'structure',
        'cource'     => 'course',
        'cources'    => 'courses',
        'plcmnt'     => 'placement',
        'plcmnts'    => 'placements',
        'conctact'   => 'contact',
        'eligiblity' => 'eligibility',
        'dept'       => 'department',
        'prof'       => 'professor',
        'profs'      => 'professors',
        'sub'        => 'subject',
        'subjs'      => 'subjects',
        'fee'        => 'fee',
        'fees'       => 'fees',
        'intgrated'  => 'integrated',
        'integratd'  => 'integrated',
        'leterel'    => 'lateral',
        'latral'     => 'lateral',
        'brochur'    => 'brochure',
        'brochue'    => 'brochure',
        'curriculam' => 'curriculum',
        'leafelt'    => 'leaflet',
        'robotcs'    => 'robotics',
        'electoncs'  => 'electronics',
        'syllbus'    => 'syllabus',
        'boklet'     => 'booklet',
    ],

    // 2. Plural to Singular Normalization
    'plural_normalizations' => [
        'courses'         => 'course',
        'programs'        => 'program',
        'degrees'         => 'degree',
        'branches'        => 'branch',
        'streams'         => 'stream',
        'specializations' => 'specialization',
        'departments'     => 'department',
        'professors'      => 'professor',
        'teachers'        => 'teacher',
        'lecturers'       => 'lecturer',
        'fees'            => 'fee',
        'seats'           => 'seat',
        'vacancies'       => 'vacancy',
        'requirements'    => 'requirement',
        'qualifications'  => 'qualification',
        'placements'      => 'placement',
        'events'          => 'event',
        'facilities'      => 'facility',
        'amenities'       => 'amenity',
        'photos'          => 'photo',
        'images'          => 'image',
        'documents'       => 'document',
        'brochures'       => 'brochure',
        'leaflets'        => 'leaflet',
        'booklets'        => 'booklet',
        'laboratories'    => 'laboratory',
        'labs'            => 'lab',
    ],

    // 3. Synonym Group Mappings
    'synonym_groups' => [
        'program' => [
            'program', 'course', 'degree', 'academic program', 'curriculum', 'study', 'studies', 'discipline', 'branch', 'stream'
        ],
        'branch' => [
            'branch', 'stream', 'specialization', 'department', 'track', 'field', 'engineering discipline'
        ],
        'admission' => [
            'admission', 'enrollment', 'registration', 'apply', 'applying', 'join', 'enroll', 'seat booking', 'admission process', 'how to join'
        ],
        'fee' => [
            'fee', 'fee structure', 'tuition fee', 'course fee', 'charges', 'cost', 'pay', 'payment', 'price', 'expenses', 'annual fee'
        ],
        'faculty' => [
            'faculty', 'teacher', 'professor', 'staff', 'lecturer', 'instructor', 'faculty member', 'teaching staff', 'hod', 'director', 'dean'
        ],
        'seat' => [
            'seat', 'intake', 'capacity', 'available seat', 'vacancy', 'seat count', 'intake capacity', 'seat matrix', 'approved seats'
        ],
        'vacant_seat' => [
            'vacant seat', 'available seat', 'seat left', 'remaining seat', 'vacancy', 'seats open', 'open seat'
        ],
        'filled_seat' => [
            'filled seat', 'occupied seat', 'admitted student', 'confirmed admission', 'taken seat'
        ],
        'eligibility' => [
            'eligibility', 'qualification', 'admission criteria', 'requirement', 'prerequisite', 'marks needed', 'cutoff', 'criteria'
        ],
        'placement' => [
            'placement', 'job placement', 'career', 'recruitment', 'hiring', 'package', 'salary', 'company', 'recruiter', 'placed student', 'ctc'
        ],
        'campus' => [
            'campus', 'college campus', 'institute campus', 'premises', 'grounds'
        ],
        'facility' => [
            'facility', 'infrastructure', 'amenities', 'campus facility', 'hostel', 'canteen', 'lab', 'sports', 'gym', 'bus', 'library', 'mess'
        ],
        'document' => [
            'document', 'brochure', 'leaflet', 'booklet', 'prospectus', 'pdf', 'handbook', 'curriculum booklet'
        ],
        'contact' => [
            'contact', 'phone', 'mobile', 'helpline', 'reach us', 'call', 'email', 'number', 'address', 'location'
        ],
        'location' => [
            'location', 'address', 'campus location', 'where is the college', 'where is'
        ],
        'scholarship' => [
            'scholarship', 'financial aid', 'student scholarship', 'fee concession', 'concession', 'waiver', 'merit scholarship'
        ],
        'hostel' => [
            'hostel', 'accommodation', 'residence', 'stay', 'mess', 'hostel facility'
        ],
        'library' => [
            'library', 'books section', 'learning resources', 'book', 'digital library'
        ],
        'event' => [
            'event', 'activity', 'programs', 'college event', 'fest', 'workshop', 'seminar', 'hackathon', 'symposium'
        ],
        'gallery' => [
            'gallery', 'photo', 'image', 'campus photo', 'picture', 'album'
        ]
    ],

    // 4. Natural Language Intent Patterns (Stage 2)
    'intent_patterns' => [

        'SEAT_AVAILABILITY' => [
            'patterns' => [
                '/\b(how many|what is the|any)\b.*\b(seats?|vacanc(y|ies)|intake|capacity)\b/i',
                '/\b(seats?|vacanc(y|ies))\b.*\b(left|available|remaining|open|filled|occupied)\b/i',
                '/\b(is|are)\b.*\b(admission|admissions)\b.*\b(open|closed|possible|still possible)\b/i',
                '/\b(remaining|available|vacant|filled)\b.*\b(intake|capacity|seats?)\b/i',
                '/\b(how many|number of)\b.*\b(admissions?|students?)\b.*\b(possible|left|admitted)\b/i',
                '/\b(seat|seats|intake|capacity|vacant|vacancy|filled|left in|seats available|seat status|seat matrix)\b/i'
            ]
        ],

        'PROGRAM' => [
            'patterns' => [
                '/\b(what|which)\b.*\b(programs?|courses?|degrees?|branches?|streams?)\b.*\b(do you offer|are available|can i study|have|choose)\b/i',
                '/\b(tell me|show me|list)\b.*\b(available|all)?\b.*\b(programs?|courses?|degrees?|branches?|streams?)\b/i',
                '/\b(what|which)\b.*\b(degrees?|courses?|branches?)\b.*\b(can i|to)\b.*\b(study|take|choose)\b/i',
                '/\b(integrated|6\s*years?|after\s*10th|lateral|direct\s*2nd|dse|regular\s*btech|diploma\s*entry)\b/i',
                '/\b(courses?|programs?|degrees?|branches?|streams?|btech|mtech|curriculum|syllabus)\b/i'
            ]
        ],

        'DOCUMENT' => [
            'patterns' => [
                '/\b(brochures?|leaflets?|booklets?|prospectus|curriculum booklet|admission brochure|download pdf|download document|download brochure)\b/i',
                '/\b(download|get)\b.*\b(brochure|leaflet|booklet|pdf|document|handbook)\b/i'
            ]
        ],

        'FACILITY' => [
            'patterns' => [
                '/\b(facilities|facility|infrastructure|laborator(y|ies)|labs?|hostels?|canteen|mess|sports|gym|library|wi-fi|campus life)\b/i',
                '/\b(what|which)\b.*\b(facilities|labs?|amenities|hostel)\b/i'
            ]
        ],

        'FEE' => [
            'patterns' => [
                '/\b(how much|what (is|are))\b.*\b(fee|fees|tuition|cost|charges|pay|payment|price|cse|btech|course)\b/i',
                '/\b(how much (is|for|do i have to pay))\b/i',
                '/\b(tell me|show me|give me)\b.*\b(fee|fees|cost|tuition)\b.*\b(structure|details)?\b/i',
                '/\b(fee|fees|tuition|cost|amount|scholarship|concession|waiver)\b/i'
            ]
        ],

        'FACULTY' => [
            'patterns' => [
                '/\b(who|which)\b.*\b(teaches|teach|professors?|teachers?|faculty|staff|hod|cse|ece|civil|mech|ee)\b/i',
                '/\b(who teaches|who is teaching)\b/i',
                '/\b(show|give|list|tell)\b.*\b(faculty|professors?|teachers?|staff)\b/i',
                '/\b(who are the|list of)\b.*\b(teachers?|professors?|faculty)\b/i',
                '/\b(faculty|professor|teacher|hod|dean|director|principal|staff)\b/i'
            ]
        ],

        'ADMISSION' => [
            'patterns' => [
                '/\b(how to|process for|procedure for|can i)\b.*\b(apply|enroll|register|join|admission)\b/i',
                '/\b(admission|admissions|eligibility|criteria|requirements?|qualification|12th percentage|10th percentage|cutoff)\b/i'
            ]
        ],

        'PLACEMENT' => [
            'patterns' => [
                '/\b(placement|placements|job|jobs|career|salary|package|ctc|recruiter|recruiters|companies|company|hiring|placed)\b/i',
                '/\b(which companies|highest package|average package|which students placed)\b/i'
            ]
        ],

        'NOTICE' => [
            'patterns' => [
                '/\b(notices?|announcements?|circulars?|updates?|bulletin)\b/i'
            ]
        ],

        'EVENT' => [
            'patterns' => [
                '/\b(events?|activities|activity|workshops?|fests?|festivals?|seminars?|hackathons?|event pass|event registration)\b/i',
                '/EVT-REG-[A-Z0-9-]+/i'
            ]
        ],

        'CONTACT' => [
            'patterns' => [
                '/\b(contact|phone|mobile|number|helpline|email|reach us|location|address|where is the college|where is campus)\b/i'
            ]
        ]
    ],

    // 5. Context Disambiguation Rules
    'context_disambiguation' => [
        // 'program' in technical context vs college context
        'program' => [
            'tech_triggers' => ['python', 'java', 'c++', 'code', 'write', 'compiler', 'function', 'software', 'loop'],
            'college_triggers' => ['offer', 'study', 'degree', 'branch', 'btech', 'mtech', 'soet', 'college', 'university', 'admission'],
            'default_intent' => 'PROGRAM'
        ],
        // 'cost' in technical/cloud context vs college fee context
        'cost' => [
            'tech_triggers' => ['cloud', 'aws', 'server', 'api', 'docker', 'token'],
            'college_triggers' => ['fee', 'tuition', 'cse', 'btech', 'course', 'semester', 'year'],
            'default_intent' => 'FEE'
        ]
    ]
];
