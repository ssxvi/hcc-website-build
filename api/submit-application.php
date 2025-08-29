<?php
require_once 'config.php';

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
$to = ADMIN_EMAIL;
$subject = 'New Application Submission - ' . $data['student']['firstName'] . ' ' . $data['student']['lastName'];

// Create email content
$emailBody = "New Application Submission\n";
$emailBody .= "========================\n\n";

// Student Information
$emailBody .= "STUDENT INFORMATION:\n";
$emailBody .= "Name: " . $data['student']['firstName'] . " " . $data['student']['lastName'] . "\n";
$emailBody .= "Date of Birth: " . $data['student']['dateOfBirth'] . "\n";
$emailBody .= "Gender: " . $data['student']['gender'] . "\n";
$emailBody .= "Address: " . $data['student']['homeAddress'] . "\n";
$emailBody .= "City: " . $data['student']['city'] . "\n";
$emailBody .= "Zip Code: " . $data['student']['zipCode'] . "\n\n";

// Parent Information
$emailBody .= "PARENT/GUARDIAN INFORMATION:\n";
$emailBody .= "Name: " . $data['parent']['parentFirstName'] . " " . $data['parent']['parentLastName'] . "\n";
$emailBody .= "Email: " . $data['parent']['parentEmail'] . "\n";
$emailBody .= "Phone: " . $data['parent']['parentPhone'] . "\n";
$emailBody .= "Work Address: " . $data['parent']['workAddress'] . "\n";
$emailBody .= "Work Phone: " . $data['parent']['workPhone'] . "\n";
$emailBody .= "Relationship: " . $data['parent']['relationship'] . "\n\n";

// Classroom Request
$emailBody .= "CLASSROOM REQUEST:\n";
$emailBody .= "Preferred Classroom: " . $data['classroomRequest']['classroom'] . "\n";
$emailBody .= "Requested Days: " . $data['classroomRequest']['requestedDays'] . "\n";
$emailBody .= "Start Date: " . $data['classroomRequest']['startDate'] . "\n";
$emailBody .= "Space Preference: " . $data['classroomRequest']['spacePreference'] . "\n\n";

// Emergency Contact 1
$emailBody .= "PRIMARY EMERGENCY CONTACT:\n";
$emailBody .= "Name: " . $data['emergencyContact1']['name'] . "\n";
$emailBody .= "Phone: " . $data['emergencyContact1']['phone'] . "\n";
$emailBody .= "Secondary Phone: " . $data['emergencyContact1']['secondaryPhone'] . "\n";
$emailBody .= "Email: " . $data['emergencyContact1']['email'] . "\n";
$emailBody .= "Relationship: " . $data['emergencyContact1']['relationship'] . "\n";
$emailBody .= "Occupation: " . $data['emergencyContact1']['occupation'] . "\n";
$emailBody .= "Employer: " . $data['emergencyContact1']['employer'] . "\n\n";

// Emergency Contact 2
if (!empty($data['emergencyContact2']['name'])) {
    $emailBody .= "ADDITIONAL EMERGENCY CONTACT:\n";
    $emailBody .= "Name: " . $data['emergencyContact2']['name'] . "\n";
    $emailBody .= "Phone: " . $data['emergencyContact2']['phone'] . "\n";
    $emailBody .= "Secondary Phone: " . $data['emergencyContact2']['secondaryPhone'] . "\n";
    $emailBody .= "Email: " . $data['emergencyContact2']['email'] . "\n";
    $emailBody .= "Relationship: " . $data['emergencyContact2']['relationship'] . "\n";
    $emailBody .= "Occupation: " . $data['emergencyContact2']['occupation'] . "\n";
    $emailBody .= "Employer: " . $data['emergencyContact2']['employer'] . "\n\n";
}

// Medical Information
$emailBody .= "MEDICAL INFORMATION:\n";
$emailBody .= "Physician: " . $data['medical']['physician'] . "\n";
$emailBody .= "Physician Phone: " . $data['medical']['physicianPhone'] . "\n";
$emailBody .= "Allergies: " . $data['medical']['allergies'] . "\n";
$emailBody .= "Medications: " . $data['medical']['medications'] . "\n";
$emailBody .= "Medical Conditions: " . $data['medical']['medicalConditions'] . "\n";
$emailBody .= "Immunizations: " . $data['medical']['immunizations'] . "\n\n";

// Agreements
$emailBody .= "AGREEMENTS & CONSENT:\n";
$emailBody .= "Parent Contract: " . ($data['agreements']['parentContract'] ? 'Yes' : 'No') . "\n";
$emailBody .= "Web Consent: " . ($data['agreements']['webConsent'] ? 'Yes' : 'No') . "\n";
$emailBody .= "Photo Consent: " . ($data['agreements']['photoConsent'] ? 'Yes' : 'No') . "\n\n";

$emailBody .= "Submitted on: " . date('Y-m-d H:i:s') . "\n";
$emailBody .= "IP Address: " . $_SERVER['REMOTE_ADDR'] . "\n";

// Email headers
$headers = "From: " . FROM_EMAIL . "\r\n";
$headers .= "Reply-To: " . $data['parent']['parentEmail'] . "\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

// Send email
$success = mail($to, $subject, $emailBody, $headers);

if ($success) {
    // Also send confirmation email to parent if enabled
    if (SEND_CONFIRMATION) {
        $confirmationSubject = "Application Received - " . ORG_NAME;
        $confirmationBody = "Dear " . $data['parent']['parentFirstName'] . ",\n\n";
        $confirmationBody .= "Thank you for submitting your application for " . $data['student']['firstName'] . " " . $data['student']['lastName'] . ".\n\n";
        $confirmationBody .= "We have received your application and will review it shortly. A member of our team will contact you within 2-3 business days.\n\n";
        $confirmationBody .= "If you have any questions, please don't hesitate to contact us at " . ORG_PHONE . ".\n\n";
        $confirmationBody .= "Best regards,\n";
        $confirmationBody .= ORG_NAME . "\n";
        $confirmationBody .= REPLY_TO_EMAIL;
        
        $confirmationHeaders = "From: " . FROM_EMAIL . "\r\n";
        $confirmationHeaders .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        mail($data['parent']['parentEmail'], $confirmationSubject, $confirmationBody, $confirmationHeaders);
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
