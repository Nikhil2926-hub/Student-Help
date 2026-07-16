<?php
// faculty/edit.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireFaculty();

$faculty_id = $_SESSION['faculty_id'];
$pageTitle = 'Edit Resource';
$pageIcon = 'fa-solid fa-pencil';

$error = '';
$success = '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    header("Location: manage.php");
    exit();
}

// Fetch resource details and verify ownership
$material = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM materials WHERE id = ? AND faculty_id = ?");
    $stmt->execute([$id, $faculty_id]);
    $material = $stmt->fetch();
    
    if (!$material) {
        header("Location: manage.php");
        exit();
    }
    
    // Fetch subjects list for choice dropdown
    $subjects = $pdo->query("SELECT * FROM subjects ORDER BY semester ASC, subject_name ASC")->fetchAll();
} catch (PDOException $e) {
    die("Query failed: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $semester = intval($_POST['semester'] ?? 1);
    $subject_id = intval($_POST['subject_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $description = '';
    $video_url = trim($_POST['video_url'] ?? '');
    
    if (empty($title) || $subject_id <= 0) {
        $error = 'Title and Dynamic Subject fields are required.';
    } else {
        try {
            if ($material['category'] === 'video') {
                if (empty($video_url)) {
                    $error = 'Video Lecture URL is required.';
                } else {
                    $stmt = $pdo->prepare("UPDATE materials SET semester = ?, subject_id = ?, title = ?, description = ?, video_url = ? 
                                           WHERE id = ? AND faculty_id = ?");
                    $stmt->execute([$semester, $subject_id, $title, $description, $video_url, $id, $faculty_id]);
                    header("Location: manage.php?updated=1");
                    exit();
                }
            } else {
                // File check (optional replacement)
                $file_uploaded = !empty($_FILES['file']['name']);
                if ($file_uploaded) {
                    $file_name = $_FILES['file']['name'];
                    $file_tmp = $_FILES['file']['tmp_name'];
                    $file_size = $_FILES['file']['size'];
                    $file_error = $_FILES['file']['error'];
                    
                    if ($file_error === UPLOAD_ERR_OK) {
                        $allowed_exs = ['pdf', 'ppt', 'pptx', 'doc', 'docx', 'zip', 'png', 'jpg', 'jpeg'];
                        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                        
                        if (in_array($ext, $allowed_exs)) {
                            if ($file_size <= 100 * 1024 * 1024) {
                                $upload_dir = '../uploads/';
                                $unique_name = uniqid('file_', true) . '.' . $ext;
                                $target_path = $upload_dir . $unique_name;
                                
                                if (move_uploaded_file($file_tmp, $target_path)) {
                                    // Remove old file from disk
                                    if (!empty($material['file_path']) && file_exists('../' . $material['file_path'])) {
                                        unlink('../' . $material['file_path']);
                                    }
                                    
                                    $stmt = $pdo->prepare("UPDATE materials SET semester = ?, subject_id = ?, title = ?, description = ?, file_path = ?, file_name = ?, file_size = ? 
                                                           WHERE id = ? AND faculty_id = ?");
                                    $stmt->execute([$semester, $subject_id, $title, $description, 'uploads/' . $unique_name, $file_name, $file_size, $id, $faculty_id]);
                                    header("Location: manage.php?updated=1");
                                    exit();
                                } else {
                                    $error = 'Failed to copy replacement file to webserver storage.';
                                }
                            } else {
                                $error = 'File exceeds upload limit (Max: 100MB).';
                            }
                        } else {
                            $error = 'File format type is not permitted.';
                        }
                    } else {
                        $error = 'Upload issue experienced. Error code: ' . $file_error;
                    }
                } else {
                    // Update metadata parameters only
                    $stmt = $pdo->prepare("UPDATE materials SET semester = ?, subject_id = ?, title = ?, description = ? 
                                           WHERE id = ? AND faculty_id = ?");
                    $stmt->execute([$semester, $subject_id, $title, $description, $id, $faculty_id]);
                    header("Location: manage.php?updated=1");
                    exit();
                }
            }
        } catch (PDOException $e) {
            $error = 'Update failed: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger mb-4"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="glass-card p-4 p-md-5" style="max-width: 800px; margin: 0 auto;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="h5 fw-bold text-white mb-0">Update Metadata</h3>
        <a href="manage.php" class="btn btn-secondary-custom btn-sm text-light"><i class="fa-solid fa-arrow-left me-1"></i> Back to List</a>
    </div>

    <form action="edit.php?id=<?php echo $id; ?>" method="POST" enctype="multipart/form-data">
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label text-secondary small fw-semibold">Course Semester</label>
                <select class="form-select" name="semester" id="semester-select" required>
                    <?php for($i=1; $i<=10; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo $material['semester'] == $i ? 'selected' : ''; ?>>Semester <?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <div class="col-md-6">
                <label class="form-label text-secondary small fw-semibold">Subject</label>
                <select class="form-select" name="subject_id" id="subject-select" required>
                    <!-- JavaScript will load matching semester subjects -->
                </select>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label text-secondary small fw-semibold">Resource Title / Caption</label>
            <input type="text" class="form-control" name="title" required value="<?php echo htmlspecialchars($material['title']); ?>">
        </div>



        <?php if ($material['category'] === 'video'): ?>
            <div class="mb-4" id="video-url-block">
                <label class="form-label text-secondary small fw-semibold">Video URL Link</label>
                <input type="url" class="form-control" name="video_url" required value="<?php echo htmlspecialchars($material['video_url']); ?>">
            </div>
        <?php else: ?>
            <div class="mb-4">
                <label class="form-label text-secondary small fw-semibold">Replace Current Attachment (Optional)</label>
                <input type="file" class="form-control" name="file">
                <div class="mt-2 text-secondary" style="font-size: 0.8rem;">
                    Active Attachment: <span class="text-primary"><?php echo htmlspecialchars($material['file_name']); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <button type="submit" class="btn btn-primary-custom w-100 py-3">
            <i class="fa-solid fa-save me-2"></i> Save Changes
        </button>
    </form>
</div>

<script>
    const allSubjects = <?php echo json_encode($subjects); ?>;
    const currentSubjectId = <?php echo intval($material['subject_id']); ?>;
    const semesterSelect = document.getElementById('semester-select');
    const subjectSelect = document.getElementById('subject-select');

    function filterSubjects() {
        const semester = parseInt(semesterSelect.value);
        subjectSelect.innerHTML = '';
        
        const filtered = allSubjects.filter(sub => sub.semester === semester);
        filtered.forEach(sub => {
            const opt = document.createElement('option');
            opt.value = sub.id;
            opt.textContent = `${sub.subject_code} - ${sub.subject_name}`;
            if (sub.id === currentSubjectId) {
                opt.selected = true;
            }
            subjectSelect.appendChild(opt);
        });
    }

    semesterSelect.addEventListener('change', filterSubjects);
    // Init dynamically
    filterSubjects();
</script>
