<?php
// api/admin_feedback.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

$db = getDB();

$where  = [];
$params = [];
$types  = '';

$status   = trim($_GET['status']   ?? '');
$category = trim($_GET['category'] ?? '');
$search   = trim($_GET['search']   ?? '');

if ($status)   { $where[] = 'f.status = ?';          $params[] = $status;       $types .= 's'; }
if ($category) { $where[] = 'c.name = ?';            $params[] = $category;     $types .= 's'; }
if ($search)   { $where[] = '(f.subject LIKE ? OR f.ref_no LIKE ?)';
                 $like = "%$search%";
                 $params[] = $like; $params[] = $like; $types .= 'ss'; }

$sql = "
    SELECT f.feedback_id, f.ref_no, c.name AS category, f.subject, f.message,
           f.status, f.submitted_at,
           u.full_name, u.student_id, u.email,
           r.message AS response, r.responded_at
    FROM feedback f
    JOIN categories c ON f.category_id = c.category_id
    LEFT JOIN users u ON f.user_id = u.user_id
    LEFT JOIN responses r ON f.feedback_id = r.feedback_id
";
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' ORDER BY f.submitted_at DESC';

$stmt = $db->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}

// Also get summary counts
$counts = [];
$countRes = $db->query("SELECT status, COUNT(*) as cnt FROM feedback GROUP BY status");
while ($c = $countRes->fetch_assoc()) {
    $counts[$c['status']] = $c['cnt'];
}

echo json_encode(['success' => true, 'data' => $rows, 'counts' => $counts]);
$stmt->close();
$db->close();
?>
