<?php
// Simple email test script
require_once 'config.php';

// Test email
$to = ADMIN_EMAIL;
$subject = 'Test Email - HCC Application System';
$message = "This is a test email from your application system.\n\n";
$message .= "If you're receiving this, your email configuration is working!\n\n";
$message .= "Sent at: " . date('F j, Y \a\t g:i A') . "\n";

$headers = "From: " . FROM_EMAIL . "\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

if (mail($to, $subject, $message, $headers)) {
    echo json_encode([
        'success' => true,
        'message' => 'Test email sent successfully!',
        'to' => $to,
        'from' => FROM_EMAIL
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to send test email',
        'error' => error_get_last()
    ]);
}
?>
