<?php
// student/bookmark_action.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (!isStudentLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$student_id = $_SESSION['student_id'];
$material_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($material_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid Resource ID']);
    exit();
}

try {
    // Check if bookmark exists
    $stmt = $pdo->prepare("SELECT id FROM bookmarks WHERE student_id = ? AND material_id = ?");
    $stmt->execute([$student_id, $material_id]);
    $bookmark = $stmt->fetch();
    
    if ($bookmark) {
        // Toggle off (remove bookmark)
        $stmt = $pdo->prepare("DELETE FROM bookmarks WHERE student_id = ? AND material_id = ?");
        $stmt->execute([$student_id, $material_id]);
        echo json_encode(['success' => true, 'status' => 'unbookmarked']);
    } else {
        // Toggle on (add bookmark)
        $stmt = $pdo->prepare("INSERT INTO bookmarks (student_id, material_id) VALUES (?, ?)");
        $stmt->execute([$student_id, $material_id]);
        echo json_encode(['success' => true, 'status' => 'bookmarked']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
