<?php
// faculty/delete.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireFaculty();

$faculty_id = $_SESSION['faculty_id'];
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    try {
        // Fetch resource details and make sure it belongs to current faculty
        $stmt = $pdo->prepare("SELECT * FROM materials WHERE id = ? AND faculty_id = ?");
        $stmt->execute([$id, $faculty_id]);
        $row = $stmt->fetch();
        
        if ($row) {
            // Delete physical attachment if exists
            if ($row['category'] !== 'video' && !empty($row['file_path']) && file_exists('../' . $row['file_path'])) {
                unlink('../' . $row['file_path']);
            }
            
            // Delete db entity
            $stmt = $pdo->prepare("DELETE FROM materials WHERE id = ?");
            $stmt->execute([$id]);
            
            header("Location: manage.php?deleted=1");
            exit();
        }
    } catch (PDOException $e) {
        die("Deletion failed: " . $e->getMessage());
    }
}

header("Location: manage.php");
exit();
