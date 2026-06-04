<?php
// api/my_feedback.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized. Please log in.']);
    exit;
}

$user_id = intval($_SESSION['user_id']);
$db      = getDB();

$stmt = $db->prepare("
    SELECT f.feedback_id, f.ref_no, c.name AS category, f.subject, f.message,
           f.status, f.submitted_at,
           r.message AS response, r.responded_at
    FROM feedback f
    JOIN categories c ON f.category_id = c.category_id
    LEFT JOIN responses r ON f.feedback_id = r.feedback_id
    WHERE f.user_id = ?
    ORDER BY f.submitted_at DESC
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}

echo json_encode(['success' => true, 'data' => $rows]);
$stmt->close();
$db->close();
?>
