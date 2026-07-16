<?php
// faculty/upload.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireFaculty();

$faculty_id = $_SESSION['faculty_id'];
$pageTitle = 'Upload learning Resource';
$pageIcon = 'fa-solid fa-cloud-arrow-up';

$error = '';
$success = '';

// Load subjects from Database
$subjects = [];
try {
    $subjects = $pdo->query("SELECT * FROM subjects ORDER BY semester ASC, subject_name ASC")->fetchAll();
}
catch (PDOException $e) {
    $error = 'Failed to load system subjects: ' . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = $_POST['category'] ?? '';
    $semester = intval($_POST['semester'] ?? 1);
    $subject_id = intval($_POST['subject_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $description = '';
    $video_url = trim($_POST['video_url'] ?? '');

    if (empty($category) || empty($title) || $subject_id <= 0) {
        $error = 'Category, dynamic subject selection, and resource title are required.';
    }
    else {
        // Enforce physical file / video url depending on type
        if ($category === 'video') {
            if (empty($video_url)) {
                $error = 'Video Lecture URL is required for video category.';
            }
            else {
                try {
                    $stmt = $pdo->prepare("INSERT INTO materials (faculty_id, subject_id, category, semester, title, description, video_url) 
                                           VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$faculty_id, $subject_id, $category, $semester, $title, $description, $video_url]);
                    $success = 'Video lecture link uploaded successfully!';
                }
                catch (PDOException $e) {
                    $error = 'Upload failed: ' . $e->getMessage();
                }
            }
        }
        else {
            // File Upload Flow (supports single or bulk uploaded resources)
            if (!empty($_FILES['files']['name'][0])) {
                $total_files = count($_FILES['files']['name']);
                $uploaded_count = 0;
                $upload_dir = '../uploads/';

                // Ensure directory exists
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $allowed_exs = ['pdf', 'ppt', 'pptx', 'doc', 'docx', 'zip', 'png', 'jpg', 'jpeg'];

                for ($i = 0; $i < $total_files; $i++) {
                    $file_name = $_FILES['files']['name'][$i];
                    $file_tmp = $_FILES['files']['tmp_name'][$i];
                    $file_size = $_FILES['files']['size'][$i];
                    $file_error = $_FILES['files']['error'][$i];

                    if ($file_error === UPLOAD_ERR_OK) {
                        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                        if (in_array($ext, $allowed_exs)) {
                            // Enforce file limit (100MB)
                            if ($file_size <= 100 * 1024 * 1024) {
                                $unique_name = uniqid('file_', true) . '.' . $ext;
                                $target_path = $upload_dir . $unique_name;

                                if (move_uploaded_file($file_tmp, $target_path)) {
                                    try {
                                        // Insert file model
                                        $stmt = $pdo->prepare("INSERT INTO materials (faculty_id, subject_id, category, semester, title, description, file_path, file_name, file_size) 
                                                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                                        // If bulk upload, append index to title if more than 1 file
                                        $file_title = $title . ($total_files > 1 ? (" - Part " . ($i + 1)) : "");

                                        $stmt->execute([
                                            $faculty_id,
                                            $subject_id,
                                            $category,
                                            $semester,
                                            $file_title,
                                            $description,
                                            'uploads/' . $unique_name,
                                            $file_name,
                                            $file_size
                                        ]);
                                        $uploaded_count++;
                                    }
                                    catch (PDOException $e) {
                                        $error = 'Database error: ' . $e->getMessage();
                                        break;
                                    }
                                }
                                else {
                                    $error = "Failed to copy '$file_name' to server storage directory.";
                                    break;
                                }
                            }
                            else {
                                $error = "File '$file_name' exceeds size limit of 100MB.";
                                break;
                            }
                        }
                        else {
                            $error = "File '$file_name' format extension is not permitted.";
                            break;
                        }
                    }
                    else {
                        $error = "Upload error encountered on '$file_name'. Reason code: " . $file_error;
                        break;
                    }
                }

                if ($uploaded_count > 0 && empty($error)) {
                    $success = "$uploaded_count file(s) uploaded and saved successfully!";
                }
            }
            else {
                $error = 'Please select at least one file resource to upload.';
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" role="alert">
        <i class="fa-solid fa-circle-exclamation"></i>
        <div><?php echo htmlspecialchars($error); ?></div>
    </div>
<?php
endif; ?>

<?php if (!empty($success)): ?>
    <div class="alert alert-success d-flex align-items-center gap-2 mb-4" role="alert">
        <i class="fa-solid fa-circle-check"></i>
        <div><?php echo htmlspecialchars($success); ?></div>
    </div>
<?php
endif; ?>

<div class="glass-card p-4 p-md-5" style="max-width: 800px; margin: 0 auto;">
    <form action="upload.php" method="POST" enctype="multipart/form-data">
        <div class="row g-3 mb-3">
            <!-- Category Selector -->
            <div class="col-md-6">
                <label class="form-label text-secondary small fw-semibold">Resource Category</label>
                <select class="form-select" name="category" id="resource-category" required>
                    <option value="study_material" selected>Study Material (Notes/Slides)</option>
                    <option value="assignment">Student Assignment Task</option>
                    <option value="question_paper">Previous Year Question Paper</option>
                    <option value="practical">Practical / Lab File</option>
                    <option value="video">Lecture Video (URL Link)</option>
                </select>
            </div>
            
            <!-- Semester -->
            <div class="col-md-6">
                <label class="form-label text-secondary small fw-semibold">Target Semester</label>
                <select class="form-select" name="semester" id="semester-select" required>
                    <?php for ($i = 1; $i <= 10; $i++): ?>
                        <option value="<?php echo $i; ?>">Semester <?php echo $i; ?></option>
                    <?php
endfor; ?>
                </select>
            </div>
        </div>

        <!-- Subject Select (filtered page script helps focus) -->
        <div class="mb-3">
            <label class="form-label text-secondary small fw-semibold">MAPPED SUBJECT</label>
            <select class="form-select" name="subject_id" id="subject-select" required>
                <option value="">-- Select Subject (Choose Semester First) --</option>
                <!-- Filled dynamically by JavaScript -->
            </select>
        </div>

        <!-- Title -->
        <div class="mb-3">
            <label class="form-label text-secondary small fw-semibold">Resource Title / Caption</label>
            <input type="text" class="form-control" name="title" required placeholder="e.g. Unit 2 - Binary Search Trees Analysis" value="<?php echo htmlspecialchars($title ?? ''); ?>">
        </div>

        <!-- Video Lectures input block -->
        <div class="mb-4 d-none" id="video-input-group">
            <label class="form-label text-secondary small fw-semibold">YouTube / Video URL Link</label>
            <div class="input-group">
                <span class="input-group-text bg-transparent border-glass text-secondary">
                    <i class="fa-solid fa-link"></i>
                </span>
                <input type="url" class="form-control" name="video_url" placeholder="https://www.youtube.com/watch?v=..." value="<?php echo htmlspecialchars($video_url ?? ''); ?>">
            </div>
        </div>

        <!-- File upload attachments block -->
        <div class="mb-4" id="file-input-group">
            <label class="form-label text-secondary small fw-semibold">File Attachments (Multiple permitted for Bulk uploads)</label>
            <div class="border border-dashed border-glass rounded-4 p-4 text-center cursor-pointer position-relative" style="background-color: rgba(255, 255, 255, 0.02);" id="dropzone">
                <input type="file" name="files[]" id="file-input" class="opacity-0 position-absolute w-100 h-100 top-0 start-0 cursor-pointer" multiple>
                <i class="fa-solid fa-cloud-arrow-up fa-2x text-primary mb-3"></i>
                <h5 class="text-white fs-6 mb-1">Drag and drop file here, or click to browse</h5>
                <p class="text-secondary small mb-0">Supported formats: PDF, PPT, DOCX, ZIP, Images. Max size: 100MB.</p>
                <div id="file-list-preview" class="mt-3 text-start small text-primary fw-medium"></div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary-custom w-100 py-3">
            <i class="fa-solid fa-cloud-arrow-up me-2"></i> Upload Resource
        </button>
    </form>
</div>

<script>
    // Selections filter and dropdown visibility toggle
    const allSubjects = <?php echo json_encode($subjects); ?>;
    
    const categorySelect = document.getElementById('resource-category');
    const semesterSelect = document.getElementById('semester-select');
    const subjectSelect = document.getElementById('subject-select');
    const fileGroup = document.getElementById('file-input-group');
    const videoGroup = document.getElementById('video-input-group');
    const fileInput = document.getElementById('file-input');
    const fileListPreview = document.getElementById('file-list-preview');

    // Dynamic subject load based on selected semester
    function filterSubjects() {
        const semester = parseInt(semesterSelect.value);
        subjectSelect.innerHTML = '<option value="">-- Choose Subject --</option>';
        
        const filtered = allSubjects.filter(sub => sub.semester === semester);
        filtered.forEach(sub => {
            const opt = document.createElement('option');
            opt.value = sub.id;
            opt.textContent = `${sub.subject_code} - ${sub.subject_name}`;
            subjectSelect.appendChild(opt);
        });
    }

    // Toggle input types: file upload vs video urls
    function toggleUploadInput() {
        if (categorySelect.value === 'video') {
            fileGroup.classList.add('d-none');
            videoGroup.classList.remove('d-none');
            fileInput.removeAttribute('required');
        } else {
            fileGroup.classList.remove('d-none');
            videoGroup.classList.add('d-none');
        }
    }

    // Handle files list preview
    fileInput.addEventListener('change', () => {
        fileListPreview.innerHTML = '';
        if (fileInput.files.length > 0) {
            const list = document.createElement('ol');
            list.className = 'mb-0';
            for (let i = 0; i < fileInput.files.length; i++) {
                const li = document.createElement('li');
                const kb = (fileInput.files[i].size / 1024).toFixed(1);
                li.innerHTML = `<b>${fileInput.files[i].name}</b> (${kb} KB)`;
                list.appendChild(li);
            }
            fileListPreview.appendChild(list);
        }
    });

    // Event listeners
    semesterSelect.addEventListener('change', filterSubjects);
    categorySelect.addEventListener('change', toggleUploadInput);

    // Initial page configs
    filterSubjects();
    toggleUploadInput();
</script>
