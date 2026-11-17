<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

function sanitizeText($value) {
    return trim((string) $value);
}

function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

$name = sanitizeText($_POST['name'] ?? '');
$email = sanitizeText($_POST['email'] ?? '');
$phone = sanitizeText($_POST['phone'] ?? '');
$comments = sanitizeText($_POST['comments'] ?? '');
$honeypot = sanitizeText($_POST['website'] ?? '');
$origin = sanitizeText($_POST['origin'] ?? '');
$destination = sanitizeText($_POST['destination'] ?? '');
$freightType = sanitizeText($_POST['freight_type'] ?? '');
$deliveryRequirements = sanitizeText($_POST['delivery_requirements'] ?? $_POST['incoterms'] ?? '');
$distance = sanitizeText($_POST['distance'] ?? '');
$fragile = isset($_POST['fragile']) ? 'Yes' : 'No';
$timeSensitive = isset($_POST['express_delivery']) ? 'Yes' : 'No';
$packaging = isset($_POST['packaging']) ? 'Yes' : 'No';
$dimensions = [
    'Width' => sanitizeText($_POST['width'] ?? ''),
    'Height' => sanitizeText($_POST['height'] ?? ''),
    'Weight' => sanitizeText($_POST['weight'] ?? ''),
    'Length' => sanitizeText($_POST['length'] ?? ''),
];

if ($honeypot !== '') {
    http_response_code(403);
    exit('Forbidden.');
}

if ($name === '') {
    http_response_code(400);
    echo '<div class="alert alert-error">You must enter your name.</div>';
    exit();
}

if ($email === '') {
    http_response_code(400);
    echo '<div class="alert alert-error">You must enter your email address.</div>';
    exit();
}

if (!isValidEmail($email)) {
    http_response_code(400);
    echo '<div class="alert alert-error">You must enter a valid email address.</div>';
    exit();
}

if ($comments === '') {
    http_response_code(400);
    echo '<div class="alert alert-error">You must enter your message.</div>';
    exit();
}

$recipient = getenv('GERAD_CARGO_CONTACT_EMAIL');
if ($recipient === false || !isValidEmail($recipient)) {
    http_response_code(503);
    exit('Quote enquiries are temporarily unavailable because the contact recipient is not configured.');
}
$subject = 'New inquiry from ' . $name;

$body = "You have been contacted by {$name}.\r\n";
$body .= "Email: {$email}\r\n";
$body .= "Phone: " . ($phone !== '' ? $phone : 'Not provided') . "\r\n\r\n";
$body .= "Origin: " . ($origin !== '' ? $origin : 'Not provided') . "\r\n";
$body .= "Destination: " . ($destination !== '' ? $destination : 'Not provided') . "\r\n\r\n";
$body .= "Freight type: " . ($freightType !== '' ? $freightType : 'Not provided') . "\r\n";
$body .= "Delivery requirements: " . ($deliveryRequirements !== '' ? $deliveryRequirements : 'Not provided') . "\r\n";
$body .= "Estimated route distance: " . ($distance !== '' ? $distance . " km" : 'Not provided') . "\r\n";
$body .= "Fragile cargo: {$fragile}\r\n";
$body .= "Time-sensitive delivery: {$timeSensitive}\r\n";
$body .= "Packaging assistance: {$packaging}\r\n";
$body .= "Dimensions: " . implode(', ', array_map(
    static fn($label, $value) => $label . ': ' . ($value !== '' ? $value : 'Not provided'),
    array_keys($dimensions),
    $dimensions
)) . "\r\n\r\n";
$body .= "Message:\r\n{$comments}\r\n";

$headers = [
    'From: ' . $email,
    'Reply-To: ' . $email,
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
    'X-Mailer: PHP/' . phpversion(),
];

if (!mail($recipient, $subject, $body, implode("\r\n", $headers))) {
    http_response_code(500);
    echo '<div class="alert alert-error">Unable to send your message at this time. Please try again later.</div>';
    exit();
}

echo "<div class='alert alert-success'>";
echo "<h3>Email Sent Successfully.</h3>";
echo "<p>Thank you <strong>" . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "</strong>, your message has been submitted to us.</p>";
echo "</div>";
