<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Get the JSON data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON data']);
    exit();
}

// Email configuration from config file
$adminRecipients = array_values(array_filter([
    defined('ADMIN_EMAIL1') ? ADMIN_EMAIL1 : null,
    defined('ADMIN_EMAIL2') ? ADMIN_EMAIL2 : null,
    defined('ADMIN_EMAIL3') ? ADMIN_EMAIL3 : null,
    defined('ADMIN_EMAIL4') ? ADMIN_EMAIL4 : null,
]));

if (empty($adminRecipients)) {
    http_response_code(500);
    echo json_encode(['error' => 'No admin recipient configured in config.php']);
    exit();
}

$studentFirstName = $data['student']['firstName'] ?? '';
$studentLastName = $data['student']['lastName'] ?? '';
$subject = 'New Application Submission - ' . trim($studentFirstName . ' ' . $studentLastName);

// Create email content
$emailBody = "New Application Submission\n";
$emailBody .= "========================\n\n";

// Student Information
$emailBody .= "STUDENT INFORMATION:\n";
$emailBody .= "-------------------\n";
$emailBody .= "Name: " . $data['student']['firstName'] . " " . $data['student']['lastName'] . "\n";
$emailBody .= "Date of Birth: " . $data['student']['dateOfBirth'] . "\n";
$emailBody .= "Gender: " . ucfirst($data['student']['gender']) . "\n";
$emailBody .= "Home Address: " . $data['student']['homeAddress'] . "\n";
$emailBody .= "City: " . $data['student']['city'] . "\n";
$emailBody .= "Zip/Postal Code: " . $data['student']['zipCode'] . "\n\n";

// Primary Parent/Guardian Information
$emailBody .= "PRIMARY PARENT/GUARDIAN:\n";
$emailBody .= "------------------------\n";
$emailBody .= "Name: " . $data['parent']['parentFirstName'] . " " . $data['parent']['parentLastName'] . "\n";
$emailBody .= "Email: " . $data['parent']['parentEmail'] . "\n";
$emailBody .= "Phone: " . $data['parent']['parentPhone'] . "\n";
$emailBody .= "Relationship: " . $data['parent']['relationship'] . "\n";
$emailBody .= "Work Address: " . (!empty($data['parent']['workAddress']) ? $data['parent']['workAddress'] : 'Not provided') . "\n";
$emailBody .= "Work Phone: " . (!empty($data['parent']['workPhone']) ? $data['parent']['workPhone'] : 'Not provided') . "\n\n";

// Second Parent/Guardian (Optional)
if (!empty($data['parent2']['parentFirstName']) || !empty($data['parent2']['parentLastName'])) {
    $emailBody .= "SECOND PARENT/GUARDIAN:\n";
    $emailBody .= "-----------------------\n";
    $emailBody .= "Name: " . $data['parent2']['parentFirstName'] . " " . $data['parent2']['parentLastName'] . "\n";
    $emailBody .= "Email: " . (!empty($data['parent2']['parentEmail']) ? $data['parent2']['parentEmail'] : 'Not provided') . "\n";
    $emailBody .= "Phone: " . (!empty($data['parent2']['parentPhone']) ? $data['parent2']['parentPhone'] : 'Not provided') . "\n";
    $emailBody .= "Relationship: " . (!empty($data['parent2']['relationship']) ? $data['parent2']['relationship'] : 'Not provided') . "\n";
    $emailBody .= "Work Address: " . (!empty($data['parent2']['workAddress']) ? $data['parent2']['workAddress'] : 'Not provided') . "\n";
    $emailBody .= "Work Phone: " . (!empty($data['parent2']['workPhone']) ? $data['parent2']['workPhone'] : 'Not provided') . "\n\n";
}

// Classroom Request
$emailBody .= "CLASSROOM REQUEST:\n";
$emailBody .= "------------------\n";
$emailBody .= "Preferred Classroom: " . $data['classroomRequest']['classroom'] . "\n";
$emailBody .= "Requested Days: " . $data['classroomRequest']['requestedDays'] . "\n";
$emailBody .= "Requested Start Date: " . (!empty($data['classroomRequest']['startDate']) ? $data['classroomRequest']['startDate'] : 'Not specified') . "\n";
$emailBody .= "Space Preference: " . $data['classroomRequest']['spacePreference'] . "\n\n";

// Final Questions
$emailBody .= "ADDITIONAL INFORMATION:\n";
$emailBody .= "-----------------------\n";
$emailBody .= "How did you hear about us: " . $data['finalQuestions']['howDidYouHear'] . "\n";
$emailBody .= "Acknowledged Terms: " . ($data['finalQuestions']['acknowledgment'] ? 'Yes' : 'No') . "\n\n";

// Submission Metadata
$emailBody .= "SUBMISSION DETAILS:\n";
$emailBody .= "-------------------\n";
$emailBody .= "Submitted: " . date('F j, Y \a\t g:i A') . "\n";
if (defined('INCLUDE_IP_ADDRESS') && INCLUDE_IP_ADDRESS) {
    $emailBody .= "IP Address: " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . "\n";
}
$emailBody .= "\n";

// Add raw JSON data for testing/debugging
// $emailBody .= "========================\n";
// $emailBody .= "RAW JSON DATA (for testing):\n";
// $emailBody .= "========================\n";
// $emailBody .= json_encode($data, JSON_PRETTY_PRINT) . "\n";

// Email headers
$headers = "From: " . FROM_EMAIL . "\r\n";
$replyTo = !empty($data['parent']['parentEmail']) ? $data['parent']['parentEmail'] : (defined('REPLY_TO_EMAIL') ? REPLY_TO_EMAIL : FROM_EMAIL);
$headers .= "Reply-To: " . $replyTo . "\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

// Send email to all configured admin recipients
$success = true;
foreach ($adminRecipients as $recipient) {
    if (!mail($recipient, $subject, $emailBody, $headers)) {
        $success = false;
    }
}

if ($success) {
    // Also send confirmation email to parent if enabled
    if (SEND_CONFIRMATION && !empty($data['parent']['parentEmail'])) {
        $confirmationSubject = "Application Received - " . ORG_NAME;
        $confirmationBody = "Dear " . $data['parent']['parentFirstName'] . ",\n\n";
        $confirmationBody .= "Thank you for submitting an application for " . $data['student']['firstName'] . " " . $data['student']['lastName'] . ".\n\n";
        $confirmationBody .= "We have received your application and will review it within 2-3 business days.\n\n";
        $confirmationBody .= "APPLICATION SUMMARY:\n";
        $confirmationBody .= "-------------------\n";
        $confirmationBody .= "Student Name: " . $data['student']['firstName'] . " " . $data['student']['lastName'] . "\n";
        $confirmationBody .= "Requested Classroom: " . $data['classroomRequest']['classroom'] . "\n";
        $confirmationBody .= "Requested Days: " . $data['classroomRequest']['requestedDays'] . "\n";
        $confirmationBody .= "Submitted: " . date('F j, Y \a\t g:i A') . "\n\n";
        $confirmationBody .= "IMPORTANT REMINDER:\n";
        $confirmationBody .= "Once a space is secured, you will need to pay a non-refundable application fee of $150.00 and a non-refundable 'Space Deposit' equivalent to half a month's fees. The Space Deposit will be applied toward your child's first month's fees.\n\n";
        $confirmationBody .= "If you have any questions in the meantime, you can reply to this email or call us at " . ORG_PHONE . " during our hours.\n\n";
        $confirmationBody .= "Best regards,\n";
        $confirmationBody .= ORG_NAME . "\n";
        $confirmationBody .= ORG_PHONE;
        
        $confirmationHeaders = "From: " . FROM_EMAIL . "\r\n";
        $confirmationHeaders .= "Reply-To: " . REPLY_TO_EMAIL . "\r\n";
        $confirmationHeaders .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        @mail($data['parent']['parentEmail'], $confirmationSubject, $confirmationBody, $confirmationHeaders);
    }
    
    echo json_encode(['success' => true, 'message' => 'Application submitted successfully']);
} else {
    if (DEBUG_MODE) {
        echo json_encode(['error' => 'Failed to send email. Check server mail configuration.']);
    } else {
        echo json_encode(['error' => 'Failed to send email']);
    }
    http_response_code(500);
}
?>
