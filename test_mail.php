<?php
$to = "mhabeebulla8@gmail.com";
$subject = "Test Email from PHP/Sendmail";
$message = "This is a test email to verify the sendmail configuration.\n\nTime: " . date('Y-m-d H:i:s');
$headers = "From: mhabeebulla8@gmail.com";

if(mail($to, $subject, $message, $headers)) {
    echo "Email successfully sent to $to...\n";
} else {
    echo "Email sending failed...\n";
    $error = error_get_last();
    if ($error !== null) {
        print_r($error);
    }
}
?>
