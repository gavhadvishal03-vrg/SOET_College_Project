<?php
/**
 * Simple Email Notification Service
 */

class Mailer
{
    /**
     * Send review notification email for News, Blogs, or Contact Submissions
     */
    public static function sendReviewNotification(string $toEmail, string $recipientName, string $itemType, string $itemTitle, string $decision, string $remarks = ''): bool
    {
        if (empty($toEmail)) {
            return false;
        }

        $itemTypeFormatted = ucfirst($itemType);
        $decisionFormatted = ucfirst($decision);
        
        $statusColor = '#198754'; // Green for published
        if (in_array(strtolower($decision), ['rejected', 'declined'])) {
            $statusColor = '#dc3545'; // Red
        } elseif (in_array(strtolower($decision), ['returned', 'under_review', 'pending'])) {
            $statusColor = '#fd7e14'; // Orange
        }

        $subject = "Update on your {$itemTypeFormatted} submission: \"{$itemTitle}\" - SOET MGM University";

        $htmlBody = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f8f9fa; margin: 0; padding: 20px; color: #333; }
                .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
                .header { background-color: #0d233a; padding: 25px; text-align: center; color: #ffffff; }
                .header h2 { margin: 0; font-size: 22px; font-weight: 700; color: #ffc107; }
                .header p { margin: 5px 0 0 0; opacity: 0.8; font-size: 13px; }
                .content { padding: 30px; }
                .status-badge { display: inline-block; padding: 8px 16px; background-color: {$statusColor}; color: #ffffff; font-weight: bold; border-radius: 20px; font-size: 14px; text-transform: uppercase; margin-bottom: 20px; }
                .details-box { background-color: #f8f9fa; border-left: 4px solid {$statusColor}; padding: 15px; border-radius: 4px; margin: 20px 0; }
                .remarks-box { background-color: #fff3cd; border: 1px solid #ffe69c; padding: 15px; border-radius: 6px; margin: 15px 0; color: #664d03; }
                .footer { background-color: #f1f5f9; padding: 20px; text-align: center; font-size: 12px; color: #64748b; border-top: 1px solid #e2e8f0; }
                .footer a { color: #0d6efd; text-decoration: none; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>School of Engineering & Technology</h2>
                    <p>MGM University, Chhatrapati Sambhajinagar</p>
                </div>
                <div class='content'>
                    <p>Dear <strong>" . htmlspecialchars($recipientName) . "</strong>,</p>
                    <p>This is an official notification regarding your recent submission to the <strong>SOET MGM University Portal</strong>.</p>
                    
                    <div style='text-align: center; margin: 25px 0;'>
                        <span class='status-badge'>Action Taken: " . htmlspecialchars($decisionFormatted) . "</span>
                    </div>

                    <div class='details-box'>
                        <p style='margin: 0 0 8px 0;'><strong>Submission Type:</strong> " . htmlspecialchars($itemTypeFormatted) . "</p>
                        <p style='margin: 0 0 8px 0;'><strong>Title:</strong> " . htmlspecialchars($itemTitle) . "</p>
                        <p style='margin: 0;'><strong>Review Date:</strong> " . date('F j, Y, g:i a') . "</p>
                    </div>";

        if (!empty($remarks)) {
            $htmlBody .= "
                    <div class='remarks-box'>
                        <strong>Editorial Remarks / Remarks from Administrator:</strong><br>
                        " . nl2br(htmlspecialchars($remarks)) . "
                    </div>";
        }

        $htmlBody .= "
                    <p style='margin-top: 25px;'>If you have any questions or require further assistance, please contact the SOET Administrative Desk at <a href='mailto:soet@mgmu.ac.in'>soet@mgmu.ac.in</a>.</p>
                    <p>Best regards,<br><strong>Editorial & Verification Board</strong><br>SOET, MGM University</p>
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " School of Engineering & Technology, MGM University. All rights reserved.</p>
                    <p>MGM Campus, N-6, CIDCO, Chhatrapati Sambhajinagar (Aurangabad) - 431003, Maharashtra, India</p>
                </div>
            </div>
        </body>
        </html>";

        // Dispatch via PHP native mail()
        return self::dispatchEmail($toEmail, $subject, $htmlBody);
    }

    /**
     * Native Mail Dispatcher
     */
    public static function dispatchEmail(string $toEmail, string $subject, string $htmlBody): bool
    {
        $fromEmail = 'soet@mgmu.ac.in';
        $fromName = 'SOET MGM University';

        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: {$fromName} <{$fromEmail}>" . "\r\n";
        $headers .= "Reply-To: {$fromEmail}" . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        return @mail($toEmail, $subject, $htmlBody, $headers);
    }
}
