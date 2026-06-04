<?php
// api/submit.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data        = json_decode(file_get_contents('php://input'), true);
$category_id = intval($data['category_id'] ?? 0);
$subject     = trim($data['subject']       ?? '');
$message     = trim($data['message']       ?? '');
$user_id     = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;

if (!$category_id || !$subject || !$message) {
    echo json_encode(['error' => 'Category, subject, and message are required.']);
    exit;
}
if (strlen($message) < 20) {
    echo json_encode(['error' => 'Message must be at least 20 characters.']);
    exit;
}

// Generate unique reference number: FBK-YYYYMMDD-XXXX
$ref_no = 'FBK-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

$db   = getDB();
$stmt = $db->prepare('INSERT INTO feedback (ref_no, category_id, subject, message, user_id) VALUES (?, ?, ?, ?, ?)');
$stmt->bind_param('siisi', $ref_no, $category_id, $subject, $message, $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'ref_no' => $ref_no]);
} else {
    echo json_encode(['error' => 'Submission failed. Please try again.']);
}

$stmt->close();
$db->close();
?>
