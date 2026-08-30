<?php
/**
 * 🤖 CampusAI — Centralized Synonym & Intent Mapping Configuration
 * Easily extendable configuration mapping natural language variations, synonyms,
 * abbreviations, and spelling corrections to standard canonical intents.
 */

return [

    // 1. Spelling Corrections
    'spelling_corrections' => [
        'cmputer'    => 'computer',
        'scnce'      => 'science',
        'faclty'     => 'faculty',
        'admssion'   => 'admission',
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
    ],

    // 3. Synonym Group Mappings (Mapping related terms to canonical concepts)
    'synonym_groups' => [
        'program' => [
            'program', 'course', 'degree', 'academic program', 'curriculum', 'study', 'studies', 'discipline'
        ],
        'branch' => [
            'branch', 'stream', 'specialization', 'department', 'track', 'field'
        ],
        'admission' => [
            'admission', 'enrollment', 'registration', 'apply', 'applying', 'join', 'enroll', 'seat booking'
        ],
        'fee' => [
            'fee', 'fee structure', 'tuition fee', 'course fee', 'charges', 'cost', 'pay', 'payment', 'price', 'expenses'
        ],
        'faculty' => [
            'faculty', 'teacher', 'professor', 'staff', 'lecturer', 'instructor', 'faculty member', 'teaching staff'
        ],
        'seat' => [
            'seat', 'intake', 'capacity', 'available seat', 'vacancy', 'seat count', 'intake capacity'
        ],
        'vacant_seat' => [
            'vacant seat', 'available seat', 'seat left', 'remaining seat', 'vacancy', 'seats open', 'open seat'
        ],
        'filled_seat' => [
            'filled seat', 'occupied seat', 'admitted student', 'confirmed admission', 'taken seat'
        ],
        'eligibility' => [
            'eligibility', 'qualification', 'admission criteria', 'requirement', 'prerequisite', 'marks needed', 'cutoff'
        ],
        'placement' => [
            'placement', 'job placement', 'career', 'recruitment', 'hiring', 'package', 'salary', 'company', 'recruiter'
        ],
        'campus' => [
            'campus', 'college campus', 'institute campus', 'premises'
        ],
        'facility' => [
            'facility', 'infrastructure', 'amenities', 'campus facility', 'hostel', 'canteen', 'lab', 'sports', 'gym', 'bus'
        ],
        'contact' => [
            'contact', 'phone', 'mobile', 'helpline', 'reach us', 'call', 'email', 'number', 'address'
        ],
        'location' => [
            'location', 'address', 'campus location', 'where is the college', 'where is'
        ],
        'experience' => [
            'faculty experience', 'teaching experience', 'work experience', 'experience'
        ],
        'scholarship' => [
            'scholarship', 'financial aid', 'student scholarship', 'fee concession', 'concession'
        ],
        'hostel' => [
            'hostel', 'accommodation', 'residence', 'stay'
        ],
        'library' => [
            'library', 'books section', 'learning resources', 'book'
        ],
        'event' => [
            'event', 'activity', 'programs', 'college event', 'fest', 'workshop', 'seminar'
        ],
        'gallery' => [
            'gallery', 'photo', 'image', 'campus photo', 'picture'
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
                '/\b(seat|seats|intake|capacity|vacant|vacancy|filled|left in|seats available|seat status)\b/i'
            ]
        ],

        'PROGRAM' => [
            'patterns' => [
                '/\b(what|which)\b.*\b(programs?|courses?|degrees?|branches?|streams?)\b.*\b(do you offer|are available|can i study|have)\b/i',
                '/\b(tell me|show me|list)\b.*\b(available|all)?\b.*\b(programs?|courses?|degrees?|branches?|streams?)\b/i',
                '/\b(what|which)\b.*\b(degrees?|courses?)\b.*\b(can i|to)\b.*\b(study|take|choose)\b/i',
                '/\b(courses?|programs?|degrees?|btech|mtech|curriculum|syllabus)\b/i'
            ]
        ],

        'FEE' => [
            'patterns' => [
                '/\b(how much|what (is|are))\b.*\b(fee|fees|tuition|cost|charges|pay|payment|price|cse|btech|course)\b/i',
                '/\b(how much (is|for|do i have to pay))\b/i',
                '/\b(tell me|show me|give me)\b.*\b(fee|fees|cost|tuition)\b.*\b(structure|details)?\b/i',
                '/\b(fee|fees|tuition|cost|amount|scholarship|concession)\b/i'
            ]
        ],

        'FACULTY' => [
            'patterns' => [
                '/\b(who|which)\b.*\b(teaches|teach|professors?|teachers?|faculty|staff|hod|cse|ece|civil|mech)\b/i',
                '/\b(who teaches|who is teaching)\b/i',
                '/\b(show|give|list|tell)\b.*\b(faculty|professors?|teachers?|staff)\b/i',
                '/\b(who are the|list of)\b.*\b(teachers?|professors?|faculty)\b/i',
                '/\b(faculty|professor|teacher|hod|dean|director|staff)\b/i'
            ]
        ],

        'ADMISSION' => [
            'patterns' => [
                '/\b(how to|process for|procedure for|can i)\b.*\b(apply|enroll|register|join|admission)\b/i',
                '/\b(admission|eligibility|criteria|requirements?|qualification|12th percentage|cutoff)\b/i'
            ]
        ],

        'PLACEMENT' => [
            'patterns' => [
                '/\b(placement|placements|job|career|salary|package|ctc|recruiter|company|hiring)\b/i'
            ]
        ],

        'NOTICE' => [
            'patterns' => [
                '/\b(notices?|announcements?|circulars?|updates?|bulletin)\b/i'
            ]
        ],

        'EVENT' => [
            'patterns' => [
                '/\b(events?|activities|activity|workshops?|fests?|festivals?|seminars?|event pass|event registration)\b/i',
                '/EVT-REG-[A-Z0-9-]+/i'
            ]
        ],

        'CONTACT' => [
            'patterns' => [
                '/\b(contact|phone|mobile|number|helpline|email|reach us|location|address|where is)\b/i'
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
