<?php
// student/download_action.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Allow downloads validation for students
requireStudent();

$student_id = $_SESSION['student_id'];
$student_sem = $_SESSION['student_semester'];
$material_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($material_id <= 0) {
    die("Invalid material request parameters.");
}

try {
    // Fetch material details
    $stmt = $pdo->prepare("SELECT * FROM materials WHERE id = ? AND semester = ?");
    $stmt->execute([$material_id, $student_sem]);
    $material = $stmt->fetch();
    
    if (!$material) {
        die("Material does not exist in databases.");
    }
    
    // 1. Log in Download History (Track logs)
    $stmt = $pdo->prepare("INSERT INTO download_history (student_id, material_id) VALUES (?, ?)");
    $stmt->execute([$student_id, $material_id]);
    
    // 2. Stream the file or redirect to browser links
    if ($material['category'] === 'video') {
        if (!empty($material['video_url'])) {
            header("Location: " . $material['video_url']);
            exit();
        } else {
            die("Invalid video URL link.");
        }
    } else {
        $filePath = '../' . $material['file_path'];
        if (file_exists($filePath)) {
            // Setup response headers to push binary file
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($material['file_name']) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filePath));
            flush(); // Flush system output buffer
            readfile($filePath);
            exit();
        } else {
            die("Resource file is no longer present on server storage directory. Please contact lecturer.");
        }
    }
} catch (PDOException $e) {
    die("Database access logs error: " . $e->getMessage());
}
