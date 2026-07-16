<?php
// student/submit_assignment.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireStudent();

$student_id = $_SESSION['student_id'];
$student_sem = $_SESSION['student_semester'];
$pageTitle = 'Submit Assignment Solution';
$pageIcon = 'fa-solid fa-cloud-arrow-up';

$error = '';
$success = '';
$material_id = isset($_GET['material_id']) ? intval($_GET['material_id']) : 0;

if ($material_id <= 0) {
    header("Location: dashboard.php");
    exit();
}

// Fetch assignment details and verify
$assignment = null;
try {
    $stmt = $pdo->prepare("SELECT m.*, f.name as faculty_name, s.subject_name 
                           FROM materials m 
                           JOIN faculty f ON m.faculty_id = f.id 
                           JOIN subjects s ON m.subject_id = s.id 
                           WHERE m.id = ? AND m.category = 'assignment' AND m.semester = ?");
    $stmt->execute([$material_id, $student_sem]);
    $assignment = $stmt->fetch();
    
    if (!$assignment) {
        header("Location: dashboard.php");
        exit();
    }
} catch (PDOException $e) {
    die("Assignment check failed: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_FILES['submission_file']['name'])) {
        $file_name = $_FILES['submission_file']['name'];
        $file_tmp = $_FILES['submission_file']['tmp_name'];
        $file_size = $_FILES['submission_file']['size'];
        $file_error = $_FILES['submission_file']['error'];
        
        if ($file_error === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed = ['pdf', 'zip', 'docx', 'doc'];
            
            if (in_array($ext, $allowed)) {
                if ($file_size <= 20 * 1024 * 1024) {
                    $upload_dir = '../uploads/submissions/';
                    
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $unique_name = uniqid('stub_', true) . '.' . $ext;
                    $target_path = $upload_dir . $unique_name;
                    
                    if (move_uploaded_file($file_tmp, $target_path)) {
                        try {
                            $stmt = $pdo->prepare("INSERT INTO submissions (student_id, material_id, file_path, file_name) VALUES (?, ?, ?, ?)");
                            $stmt->execute([$student_id, $material_id, 'uploads/submissions/' . $unique_name, $file_name]);
                            $success = 'Your solution was submitted successfully!';
                        } catch (PDOException $e) {
                            $error = 'Database logs insertion failed: ' . $e->getMessage();
                        }
                    } else {
                        $error = 'Failed to copy and write file in submissions folder.';
                    }
                } else {
                    $error = 'Submission file is too large. Limit is 20MB.';
                }
            } else {
                $error = 'Invalid file format. Submissions only permit: PDF, ZIP, DOCX, DOC.';
            }
        } else {
            $error = 'Upload issue experienced with file. Reason: ' . $file_error;
        }
    } else {
        $error = 'Please select a solution file to upload.';
    }
}

// Fetch historical solution submissions by student for ONLY this assignment
$pastSubmissions = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM submissions WHERE student_id = ? AND material_id = ? ORDER BY submitted_at DESC");
    $stmt->execute([$student_id, $material_id]);
    $pastSubmissions = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Failed to load past submissions: ' . $e->getMessage();
}

require_once __DIR__ . '/../includes/header.php';
?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger mb-4"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php if (!empty($success)): ?>
    <div class="alert alert-success mb-4"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<div class="row g-4">
    <!-- Submit form sheet -->
    <div class="col-lg-6">
        <div class="glass-card p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="h5 fw-bold text-white mb-0">Upload Solution</h3>
                <a href="dashboard.php" class="btn btn-secondary-custom btn-sm text-light"><i class="fa-solid fa-arrow-left me-1"></i> Back to Hub</a>
            </div>

            <!-- Task Description Details Card -->
            <div class="p-3 rounded-3 mb-4 bg-primary bg-opacity-10 border-glass">
                <div class="text-secondary small fw-medium text-uppercase mb-1">ASSIGNMENT TASK</div>
                <h5 class="text-white fw-bold mb-2"><?php echo htmlspecialchars($assignment['title']); ?></h5>
                <div class="text-secondary small mb-2">Subject: <span class="text-light"><?php echo htmlspecialchars($assignment['subject_name']); ?></span></div>
                <div class="text-secondary small">Instructor: <span class="text-light">Prof. <?php echo htmlspecialchars($assignment['faculty_name']); ?></span></div>

            </div>

            <form action="submit_assignment.php?material_id=<?php echo $material_id; ?>" method="POST" enctype="multipart/form-data">
                <div class="mb-4">
                    <label class="form-label text-secondary small fw-semibold">Upload Homework File (PDF, ZIP, DOCX)</label>
                    <input type="file" class="form-control" name="submission_file" required>
                    <p class="text-secondary small mt-2 mb-0">Upload your solved assignments. Supporting files up to 20MB.</p>
                </div>

                <button type="submit" class="btn btn-primary-custom w-100 py-3">
                    <i class="fa-solid fa-cloud-arrow-up me-2"></i> Submit Solution
                </button>
            </form>
        </div>
    </div>

    <!-- History list -->
    <div class="col-lg-6">
        <div class="glass-card p-4 h-100">
            <h4 class="h5 fw-bold text-white mb-3"><i class="fa-solid fa-history text-primary me-2"></i> Submission Log Details</h4>
            
            <?php if (empty($pastSubmissions)): ?>
                <div class="text-center text-secondary py-5 h-75 d-flex flex-column justify-content-center align-items-center">
                    <i class="fa-solid fa-folder-open mb-3 fa-2x"></i>
                    <p class="small mb-0">No submissions uploaded for this task yet.</p>
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush bg-transparent">
                    <?php foreach ($pastSubmissions as $row): ?>
                        <div class="list-group-item bg-transparent text-light border-glass px-0 py-3 d-flex justify-content-between align-items-center">
                            <div>
                                <strong class="text-white d-block small"><?php echo htmlspecialchars($row['file_name']); ?></strong>
                                <span class="text-secondary" style="font-size: 0.75rem;">Submitted on: <?php echo date('j M Y, h:i A', strtotime($row['submitted_at'])); ?></span>
                            </div>
                            <span class="badge bg-success-subtle text-success border border-success-subtle">Success</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php 
require_once __DIR__ . '/../includes/footer.php';
?>
