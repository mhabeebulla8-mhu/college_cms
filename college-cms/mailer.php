<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
require_once 'db.php';

function analyzePriority($description, $category) {
    $apiKey = GEMINI_API_KEY;
    if (empty($apiKey) || $apiKey === 'your-gemini-api-key') {
        return 'Medium';
    }

    $prompt = "Analyze this college complaint. Description: \"$description\". Category: \"$category\". 
    Based on the severity, return EXACTLY one word: 'High', 'Medium', or 'Low'. 
    'High' is for safety threats, harassment, ragging, or emergencies. 
    'Medium' is for academic or facility issues. 
    'Low' is for general inquiries or minor suggestions.";
    
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $apiKey;

    $data = [
        "contents" => [["parts" => [["text" => $prompt]]]]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if ($ch) curl_close($ch);

    if ($httpCode === 200) {
        $result = json_decode($response, true);
        $text = trim($result['candidates'][0]['content']['parts'][0]['text'] ?? 'Medium');
        if (stripos($text, 'High') !== false) return 'High';
        if (stripos($text, 'Low') !== false) return 'Low';
    }
    
    return 'Medium';
}

function generateAISmartReply($description, $category, $subcategory) {
    $apiKey = GEMINI_API_KEY;
    if (empty($apiKey) || $apiKey === 'your-gemini-api-key') {
        return "Thank you for your patience. We have reviewed your complaint and are taking the necessary steps to resolve it.";
    }

    $prompt = "You are a college administrator. A student submitted a complaint.
    Category: $category
    Subcategory: $subcategory
    Description: \"$description\"
    
    Write a professional, empathetic, and action-oriented resolution message. 
    State that the issue has been looked into and provide a reassuring closing.
    Keep it concise (2-3 sentences).";
    
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $apiKey;

    $data = [
        "contents" => [["parts" => [["text" => $prompt]]]]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if ($ch) curl_close($ch);

    if ($httpCode === 200) {
        $result = json_decode($response, true);
        return trim($result['candidates'][0]['content']['parts'][0]['text'] ?? "");
    }
    
    return "The administration has reviewed your grievance and the necessary actions have been initiated.";
}

function generateGeminiEmail($name, $category) {
    $apiKey = GEMINI_API_KEY;
    if (empty($apiKey) || $apiKey === 'your-gemini-api-key') {
        return null;
    }

    $prompt = "Write a professional and reassuring email to a student named $name who just submitted a complaint in the category of \"$category\". The email should acknowledge the receipt, mention it will be reviewed soon, and emphasize the college's commitment to safety and fairness. Return ONLY the HTML body of the email.";
    
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $apiKey;

    $data = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $prompt]
                ]
            ]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if ($ch) curl_close($ch);

    if ($httpCode === 200) {
        $result = json_decode($response, true);
        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            return $result['candidates'][0]['content']['parts'][0]['text'];
        }
    }
    
    return null;
}

function sendMail($to, $subject, $body) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = EMAIL_USER;
        $mail->Password   = EMAIL_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom(EMAIL_USER, 'MSc/BCA College Support');
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

function generateComplaintRegisteredEmailHtml($name, $category, $description, $dateStr) {
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
    </head>
    <body style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif; background-color: #ffffff; margin: 0; padding: 20px;">
        <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #ffffff; padding: 20px 0;">
            <tr>
                <td align="center">
                    <table width="100%" max-width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #f1f5f9;">
                        <!-- Header -->
                        <tr>
                            <td style="background-color: #5468ff; text-align: center; padding: 35px 20px;">
                                <h1 style="margin: 0; font-size: 24px; font-weight: 600; color: #ffffff;">📋 Complaint Registered</h1>
                                <p style="margin: 10px 0 0; font-size: 14px; color: #e0e7ff; font-weight: 400;">College Complaint Management System</p>
                            </td>
                        </tr>
                        <!-- Content -->
                        <tr>
                            <td style="padding: 30px;">
                                <p style="margin: 0 0 15px 0; font-size: 15px; color: #334155;">Dear <strong>' . htmlspecialchars($name) . '</strong>,</p>
                                <p style="margin: 0 0 25px 0; font-size: 15px; color: #475569; line-height: 1.6;">Your complaint has been successfully registered. Our grievance committee will review it and take appropriate action at the earliest.</p>
                                
                                <!-- Details Box -->
                                <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-bottom: 25px;">
                                    <tr>
                                        <td>
                                            <div style="font-size: 12px; font-weight: 700; color: #1e293b; letter-spacing: 0.5px; margin-bottom: 12px;">COMPLAINT DETAILS</div>
                                            <div style="height: 1px; background-color: #5468ff; width: 100%; margin-bottom: 16px;"></div>
                                            <table width="100%" cellpadding="0" cellspacing="0">
                                                <tr>
                                                    <td width="130" style="padding: 8px 0; color: #64748b; font-size: 14px; vertical-align: top;">Category</td>
                                                    <td style="padding: 8px 0; color: #0f172a; font-size: 14px; font-weight: 500; vertical-align: top;">' . htmlspecialchars($category) . '</td>
                                                </tr>
                                                <tr>
                                                    <td width="130" style="padding: 8px 0; color: #64748b; font-size: 14px; vertical-align: top;">Submitted On</td>
                                                    <td style="padding: 8px 0; color: #0f172a; font-size: 14px; font-weight: 500; vertical-align: top;">' . htmlspecialchars($dateStr) . '</td>
                                                </tr>
                                                <tr>
                                                    <td width="130" style="padding: 8px 0; color: #64748b; font-size: 14px; vertical-align: top;">Status</td>
                                                    <td style="padding: 8px 0; vertical-align: top;">
                                                        <span style="background-color: #fef3c7; color: #92400e; padding: 4px 10px; border-radius: 9999px; font-size: 12px; font-weight: 600; display: inline-block;">⏳ Pending</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td width="130" style="padding: 8px 0; color: #64748b; font-size: 14px; vertical-align: top;">Description</td>
                                                    <td style="padding: 8px 0; color: #0f172a; font-size: 14px; font-weight: 500; vertical-align: top;">' . nl2br(htmlspecialchars($description)) . '</td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>

                                <!-- Next Steps Box -->
                                <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #eff6ff; border-radius: 8px; padding: 20px; margin-bottom: 25px;">
                                    <tr>
                                        <td>
                                            <h3 style="margin: 0 0 12px 0; color: #1e40af; font-size: 15px; font-weight: 600;">💡 What happens next?</h3>
                                            <ul style="margin: 0; padding-left: 20px; color: #1e3a8a; font-size: 14px; line-height: 1.6;">
                                                <li style="margin-bottom: 4px;">Your complaint will be reviewed by the relevant committee</li>
                                                <li style="margin-bottom: 4px;">You\'ll receive email updates when the status changes</li>
                                                <li style="margin-bottom: 0;">You can track progress anytime on your dashboard</li>
                                            </ul>
                                        </td>
                                    </tr>
                                </table>

                                <p style="margin: 0; font-size: 13px; color: #94a3b8;">If you have any questions, please don\'t hesitate to reach out to the administration.</p>
                            </td>
                        </tr>
                        <!-- Footer -->
                        <tr>
                            <td style="background-color: #f8fafc; padding: 20px; text-align: center; border-top: 1px solid #f1f5f9;">
                                <p style="margin: 0 0 6px 0; color: #94a3b8; font-size: 12px;">Al Ameen Institute of Information Sciences</p>
                                <p style="margin: 0; color: #cbd5e1; font-size: 11px;">This is an automated notification. Please do not reply to this email.</p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
    </html>
    ';
}

function generateStatusInquiryEmailHtml($name, $category, $dateStr) {
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
    </head>
    <body style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif; background-color: #ffffff; margin: 0; padding: 20px;">
        <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #ffffff; padding: 20px 0;">
            <tr>
                <td align="center">
                    <table width="100%" max-width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #f1f5f9;">
                        <!-- Header -->
                        <tr>
                            <td style="background-color: #5468ff; text-align: center; padding: 35px 20px;">
                                <h1 style="margin: 0; font-size: 24px; font-weight: 600; color: #ffffff;">Complaint Status Inquiry</h1>
                                <p style="margin: 10px 0 0; font-size: 14px; color: #e0e7ff; font-weight: 400;">College Complaint Management System</p>
                            </td>
                        </tr>
                        <!-- Content -->
                        <tr>
                            <td style="padding: 30px;">
                                <p style="margin: 0 0 15px 0; font-size: 15px; color: #334155;">Dear Grievance Committee,</p>
                                <p style="margin: 0 0 25px 0; font-size: 15px; color: #475569; line-height: 1.6;">I am writing to respectfully request an update regarding the status of the following complaint, submitted by <strong>' . htmlspecialchars($name) . '</strong>.</p>
                                
                                <!-- Details Box -->
                                <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-bottom: 25px;">
                                    <tr>
                                        <td>
                                            <div style="font-size: 12px; font-weight: 700; color: #1e293b; letter-spacing: 0.5px; margin-bottom: 12px;">COMPLAINT DETAILS</div>
                                            <div style="height: 1px; background-color: #5468ff; width: 100%; margin-bottom: 16px;"></div>
                                            <ul style="margin: 0; padding-left: 20px; color: #0f172a; font-size: 14px; line-height: 1.6;">
                                                <li style="margin-bottom: 8px;"><span style="color: #64748b;">Category:</span> ' . htmlspecialchars($category) . '</li>
                                                <li style="margin-bottom: 8px;"><span style="color: #64748b;">Submitted On:</span> ' . htmlspecialchars($dateStr) . '</li>
                                                <li><span style="color: #64748b;">Status:</span> <span style="background-color: #fef3c7; color: #92400e; padding: 4px 10px; border-radius: 9999px; font-size: 12px; font-weight: 600; display: inline-block;">⏳ Pending</span></li>
                                            </ul>
                                        </td>
                                    </tr>
                                </table>

                                <!-- Next Steps Box -->
                                <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #eff6ff; border-radius: 8px; padding: 20px; margin-bottom: 25px;">
                                    <tr>
                                        <td>
                                            <h3 style="margin: 0 0 12px 0; color: #1e40af; font-size: 15px; font-weight: 600;">What happens next?</h3>
                                            <ul style="margin: 0; padding-left: 20px; color: #1e3a8a; font-size: 14px; line-height: 1.6;">
                                                <li style="margin-bottom: 4px;">Please provide an update on the current stage of the review process.</li>
                                                <li style="margin-bottom: 0;">Kindly inform us if any further information is required from the student to proceed.</li>
                                            </ul>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <!-- Footer -->
                        <tr>
                            <td style="background-color: #f8fafc; padding: 20px; text-align: center; border-top: 1px solid #f1f5f9;">
                                <p style="margin: 0 0 6px 0; color: #94a3b8; font-size: 12px;">Al Ameen Institute of Information Sciences</p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
    </html>
    ';
}

?>
