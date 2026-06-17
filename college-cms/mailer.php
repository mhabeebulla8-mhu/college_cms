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
        $mail->setFrom(EMAIL_USER, 'College CMS Support');
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
?>
