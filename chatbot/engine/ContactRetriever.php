<?php
/**
 * CampusAI — Dynamic Contact & Campus Location Information Engine
 *
 * Intercepts institutional contact and geographic location inquiries,
 * querying live database phone numbers, emails, addresses, and office hours
 * from the site_settings table.
 *
 * @package SOET_College_Project
 * @subpackage Chatbot_Engine
 */

require_once __DIR__ . '/../../core/bootstrap.php';

class ContactRetriever
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Determines whether a user query is asking for contact or campus location details.
     *
     * @param string $query User query text.
     * @return bool True if contact-related.
     */
    public function isContactQuery(string $query): bool
    {
        return (bool)preg_match('/\b(contact|phone|telephone|mobile|helpline|call|email|reach us|address|location|where is|how to reach|office|whatsapp|inquiry|enquiry)\b/i', $query);
    }

    public function getContactInfo(): array
    {
        $defaults = [
            'phone'         => '0240-6674999 / +91 9371714253',
            'email'         => 'soet@mgmu.ac.in / admissions@mgmu.ac.in',
            'address'       => 'MGM University Campus, N-6, CIDCO, Chhatrapati Sambhajinagar (Aurangabad) - 431003, Maharashtra, India',
            'working_hours' => 'Monday to Saturday: 9:00 AM – 5:30 PM (Closed on Sundays & Public Holidays)',
            'whatsapp'      => '+91 9371714253',
        ];

        try {
            $rows = $this->db->fetchAll("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('contact_phone', 'contact_email', 'contact_address', 'working_hours', 'whatsapp_number', 'phone', 'email', 'address')");
            foreach ($rows as $r) {
                $k = $r['setting_key'];
                $v = trim($r['setting_value']);
                if (empty($v)) continue;

                if (in_array($k, ['contact_phone', 'phone'])) $defaults['phone'] = $v;
                if (in_array($k, ['contact_email', 'email'])) $defaults['email'] = $v;
                if (in_array($k, ['contact_address', 'address'])) $defaults['address'] = $v;
                if ($k === 'working_hours') $defaults['working_hours'] = $v;
                if ($k === 'whatsapp_number') $defaults['whatsapp'] = $v;
            }
        } catch (Exception $e) {}

        return $defaults;
    }

    public function buildContactResponse(): string
    {
        $c = $this->getContactInfo();

        return "### 📍 SOET MGM University — Official Contact & Campus Details\n\n"
             . "| Detail | Information |\n"
             . "|---|---|\n"
             . "| 📞 **Phone / Helpline** | {$c['phone']} |\n"
             . "| 📧 **Official Email** | {$c['email']} |\n"
             . "| 🏢 **Campus Address** | {$c['address']} |\n"
             . "| ⏰ **Office Timings** | {$c['working_hours']} |\n"
             . "| 💬 **WhatsApp Desk** | {$c['whatsapp']} |\n\n"
             . "🔗 [Open Google Maps Location](/contact.php) | [Submit Online Admission Enquiry](/contact.php#enquiry)";
    }
}
