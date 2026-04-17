<?php

function isValidEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function sendEmail(string $to, string $subject, string $message): bool {
    if (!isValidEmail($to)) {
        return false;
    }

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "From: College CMS <noreply@college.edu>\r\n";
    $headers .= "Reply-To: support@college.edu\r\n";

    return mail($to, $subject, $message, $headers);
}

function sendComplaintNotification(string $email, string $name, int $complaintId, string $category, string $description): bool {
    $subject = "Complaint Received (#$complaintId)";
    $body = "Hello $name,\n\n";
    $body .= "We received your complaint (#$complaintId) under the category: $category.\n\n";
    $body .= "Complaint details:\n$description\n\n";
    $body .= "Our team will review your case and notify you by email when the status changes.\n\n";
    $body .= "Thank you for reaching out to College CMS.\n";
    $body .= "- College CMS Support Team\n";

    return sendEmail($email, $subject, $body);
}

function sendComplaintStatusUpdate(string $email, string $name, int $complaintId, string $status): bool {
    $subject = "Complaint Update (#$complaintId)";
    $body = "Hello $name,\n\n";
    $body .= "The status of your complaint (#$complaintId) has been updated to: $status.\n\n";
    $body .= "If you have additional details or need further assistance, please reply to this email.\n\n";
    $body .= "Thank you,\n";
    $body .= "- College CMS Support Team\n";

    return sendEmail($email, $subject, $body);
}
