<?php
// api/respond.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data        = json_decode(file_get_contents('php://input'), true);
$feedback_id = intval($data['feedback_id'] ?? 0);
$message     = trim($data['message']       ?? '');
$status      = trim($data['status']        ?? '');
$admin_id    = intval($_SESSION['user_id']);

$allowed_statuses = ['Submitted', 'Under Review', 'Resolved', 'Closed'];
if (!$feedback_id) {
    echo json_encode(['error' => 'Invalid feedback ID.']); exit;
}
if ($status && !in_array($status, $allowed_statuses)) {
    echo json_encode(['error' => 'Invalid status.']); exit;
}

$db = getDB();

// Update status if provided
if ($status) {
    $stmt = $db->prepare('UPDATE feedback SET status = ? WHERE feedback_id = ?');
    $stmt->bind_param('si', $status, $feedback_id);
    $stmt->execute();
    $stmt->close();
}

// Insert response if message provided
if ($message) {
    // Delete old response (one response per feedback for simplicity)
    $del = $db->prepare('DELETE FROM responses WHERE feedback_id = ?');
    $del->bind_param('i', $feedback_id);
    $del->execute();
    $del->close();

    $stmt = $db->prepare('INSERT INTO responses (feedback_id, admin_id, message) VALUES (?, ?, ?)');
    $stmt->bind_param('iis', $feedback_id, $admin_id, $message);
    $stmt->execute();
    $stmt->close();
}

echo json_encode(['success' => true, 'message' => 'Updated successfully.']);
$db->close();
?>
