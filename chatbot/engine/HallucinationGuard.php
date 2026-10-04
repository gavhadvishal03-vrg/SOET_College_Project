<?php
/**
 * CampusAI — Anti-Hallucination & Response Validation Guard
 *
 * Scans generated answers to guarantee zero false or fabricated information:
 * 1. Financial Verification: Intercepts unverified rupee numbers generated for SOET tuition.
 * 2. Seat Count Integrity: Prevents hallucination of intake and vacancy figures.
 * 3. Faculty Authority Check: Ensures non-canonical names are not claimed as Dean/HOD.
 * 4. Safe Redirection: Replaces high-risk responses with canonical links to official pages.
 *
 * @package SOET_College_Project
 * @subpackage Chatbot_Engine
 */

class HallucinationGuard
{
    /**
     * Validates a candidate bot response against domain safety and factual hallucination risks.
     *
     * @param string $response Candidate answer string.
     * @param string $source Origin of the response (e.g. 'DATABASE', 'GENERAL_AI', 'openai').
     * @param string $intent Classified intent of user query.
     * @return array Associative array with 'is_safe', 'risk_level', and 'reason'.
     */
    public function validate(string $response, string $source, string $intent): array
    {
        $clean = trim($response);

        // If source is already verified database or CMS knowledge, risk is none
        if (in_array($source, ['DATABASE', 'database', 'seats_database', 'fee_database', 'WEBSITE', 'TRAINING_KNOWLEDGE'])) {
            return ['is_safe' => true, 'risk_level' => 'none', 'reason' => 'Verified Institutional Source'];
        }

        // Check if response is from GENERAL_AI / fallback
        if (in_array($source, ['GENERAL_AI', 'openai', 'system', 'general_knowledge'])) {
            // Check for fabricated SOET-specific rupee amounts (e.g. ₹1,20,000 or Rs. 150000)
            if (preg_match('/(₹|rs\.?|inr)\s*[\d,]{4,}/i', $clean) && preg_match('/\b(soet|mgm|college|tuition|b\.?tech|course fee)\b/i', $clean)) {
                return [
                    'is_safe'    => false,
                    'risk_level' => 'high',
                    'reason'     => 'Unverified fee figures detected from non-database AI source.'
                ];
            }

            // Check for fabricated seat numbers
            if (preg_match('/\b\d{1,3}\s+(seats?|vacant seats?|intake)\b/i', $clean) && preg_match('/\b(soet|mgm|engineering|admission)\b/i', $clean)) {
                return [
                    'is_safe'    => false,
                    'risk_level' => 'high',
                    'reason'     => 'Unverified seat numbers detected from non-database AI source.'
                ];
            }

            // Check for fabricated faculty names claiming to be HOD/Dean
            if (preg_match('/\b(dr\.|prof\.)\s+[a-z]+\s+[a-z]+\s+(is the (hod|head|dean|director))\b/i', $clean) && !preg_match('/(parminder|dhingra)/i', $clean)) {
                return [
                    'is_safe'    => false,
                    'risk_level' => 'high',
                    'reason'     => 'Unverified faculty leadership claim detected.'
                ];
            }
        }

        return ['is_safe' => true, 'risk_level' => 'none', 'reason' => 'Safe'];
    }

    /**
     * Return safe, canonical fallback response by intent
     */
    public function getSafeResponse(string $intent): string
    {
        switch ($intent) {
            case 'FEE':
                return "I couldn't find verified fee details for that specific inquiry in our database. Please check the official fee structure on our <a href=\"departments.php\" class=\"fw-bold text-decoration-underline\">Departments &amp; Programs page</a> or contact the admissions office at 0240-6674999.";
            
            case 'SEAT_AVAILABILITY':
                return "I couldn't retrieve real-time seat availability right now. Please visit the <a href=\"admissions.php\" class=\"fw-bold text-decoration-underline\">Admissions Portal</a> or contact the admission desk for current vacancy numbers.";
            
            case 'FACULTY':
                return "I couldn't find verified faculty information for that specific query. Please browse our official <a href=\"faculty.php\" class=\"fw-bold text-decoration-underline\">Faculty Directory</a>.";
            
            case 'ADMISSION':
                return "For official admission criteria, cutoffs, and counseling timelines, please visit our <a href=\"admissions.php\" class=\"fw-bold text-decoration-underline\">Admissions page</a> or reach out to our counseling helpline at 0240-6674999.";
            
            case 'PLACEMENT':
                return "For verified placement packages and list of visiting MNC recruiters, please explore our <a href=\"placements.php\" class=\"fw-bold text-decoration-underline\">Training &amp; Placements page</a>.";
            
            default:
                return "I couldn't find a reliable answer to this question in the SOET website or database. Please contact the SOET office for verified details: Phone: <strong>0240-6674999</strong> | Email: <strong>soet@mgmu.ac.in</strong>";
        }
    }
}
