<?php
// api/register.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$full_name  = trim($data['full_name']  ?? '');
$email      = trim($data['email']      ?? '');
$student_id = trim($data['student_id'] ?? '');
$password   = $data['password']        ?? '';

// Validate
if (!$full_name || !$email || !$student_id || !$password) {
    echo json_encode(['error' => 'All fields are required.']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['error' => 'Invalid email address.']);
    exit;
}
if (strlen($password) < 6) {
    echo json_encode(['error' => 'Password must be at least 6 characters.']);
    exit;
}

$db = getDB();

// Check if email already exists
$stmt = $db->prepare('SELECT user_id FROM users WHERE email = ?');
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo json_encode(['error' => 'An account with this email already exists.']);
    $stmt->close(); $db->close();
    exit;
}
$stmt->close();

$hash = password_hash($password, PASSWORD_BCRYPT);
$stmt = $db->prepare('INSERT INTO users (full_name, email, student_id, password_hash, role) VALUES (?, ?, ?, ?, "student")');
$stmt->bind_param('ssss', $full_name, $email, $student_id, $hash);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Account created successfully.']);
} else {
    echo json_encode(['error' => 'Registration failed. Please try again.']);
}

$stmt->close();
$db->close();
?>
