<?php
// student/dashboard.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireStudent();

$student_id = $_SESSION['student_id'];
$student_sem = $_SESSION['student_semester'];
$pageTitle = 'Student Learning Hub';
$pageIcon = 'fa-solid fa-graduation-cap';

$error = '';
$success = '';

// Handle filters
$selectedSem = $student_sem;
$selectedSub = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : 0;
$selectedFac = isset($_GET['faculty_id']) ? intval($_GET['faculty_id']) : 0;
$searchQuery = trim($_GET['search'] ?? '');

// Fetch dynamic filter lists
$subjects = [];
$faculties = [];
$bookmarkedIds = [];
try {
    // Subjects matching selected semester
    $stmt = $pdo->prepare("SELECT * FROM subjects WHERE semester = ? ORDER BY subject_name ASC");
    $stmt->execute([$selectedSem]);
    $subjects = $stmt->fetchAll();
    
    // All Faculty members
    $faculties = $pdo->query("SELECT id, name FROM faculty ORDER BY name ASC")->fetchAll();
    
    // Bookmarked materials of active student
    $stmt = $pdo->prepare("SELECT material_id FROM bookmarks WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $bookmarkedIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $error = 'Failed to load filter directories: ' . $e->getMessage();
}

// Build query
$sql = "SELECT m.*, f.name as faculty_name, s.subject_name, s.subject_code,
        (SELECT COUNT(*) FROM submissions sub WHERE sub.material_id = m.id AND sub.student_id = :stud_id) as submitted_count
        FROM materials m 
        JOIN faculty f ON m.faculty_id = f.id 
        JOIN subjects s ON m.subject_id = s.id 
        WHERE m.semester = :semester";
$params = [
    ':semester' => $selectedSem,
    ':stud_id' => $student_id
];

if ($selectedSub > 0) {
    $sql .= " AND m.subject_id = :sub_id";
    $params[':sub_id'] = $selectedSub;
}
if ($selectedFac > 0) {
    $sql .= " AND m.faculty_id = :fac_id";
    $params[':fac_id'] = $selectedFac;
}
if (!empty($searchQuery)) {
    $sql .= " AND m.title LIKE :search";
    $params[':search'] = '%' . $searchQuery . '%';
}

$sql .= " ORDER BY m.uploaded_at DESC";

// Execute search
$materials = [];
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $materials = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Materials load failed: ' . $e->getMessage();
}

require_once __DIR__ . '/../includes/header.php';
?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger mb-4"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<!-- Custom Search and Filter Grid -->
<div class="glass-card p-4 mb-4">
    <form action="dashboard.php" method="GET" class="row g-3">
        <!-- Search Keyword -->
        <div class="col-md-3">
            <label class="form-label text-secondary small fw-semibold">Keywords Search</label>
            <div class="input-group">
                <span class="input-group-text bg-transparent border-glass text-secondary"><i class="fa-solid fa-search"></i></span>
                <input type="text" class="form-control" name="search" placeholder="Search title..." value="<?php echo htmlspecialchars($searchQuery); ?>">
            </div>
        </div>

        <!-- Subject Select -->
        <div class="col-md-3">
            <label class="form-label text-secondary small fw-semibold">Subject Course</label>
            <select class="form-select" name="subject_id">
                <option value="0">All Semester Subjects</option>
                <?php foreach ($subjects as $sub): ?>
                    <option value="<?php echo $sub['id']; ?>" <?php echo $selectedSub == $sub['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($sub['subject_code']) . ' - ' . htmlspecialchars($sub['subject_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Faculty Instructors -->
        <div class="col-md-2">
            <label class="form-label text-secondary small fw-semibold">Professor / Faculty</label>
            <select class="form-select" name="faculty_id">
                <option value="0">All Faculty</option>
                <?php foreach ($faculties as $fac): ?>
                    <option value="<?php echo $fac['id']; ?>" <?php echo $selectedFac == $fac['id'] ? 'selected' : ''; ?>>
                        Prof. <?php echo htmlspecialchars($fac['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Submit & Clear Buttons -->
        <div class="col-md-4 d-flex align-items-end gap-2">
            <button type="submit" class="btn btn-primary-custom w-100 py-2.5"><i class="fa-solid fa-search"></i> Search</button>
            <a href="dashboard.php" class="btn btn-secondary-custom text-light py-2.5">Clear</a>
        </div>
    </form>
</div>

<!-- Main Materials Showcases -->
<div class="row g-4">
    <?php if (empty($materials)): ?>
        <div class="col-12 text-center text-secondary py-5">
            <div class="glass-card p-5">
                <i class="fa-solid fa-folder-open mb-3 fa-3x text-secondary"></i>
                <h5 class="text-white">No materials found for Semester <?php echo $selectedSem; ?></h5>
                <p class="small mb-0">Try clearing filters or choose another semester.</p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($materials as $item): ?>
            <?php 
                $isBookmarked = in_array($item['id'], $bookmarkedIds);
                $isPdf = false;
                if (!empty($item['file_name'])) {
                    $isPdf = strtolower(pathinfo($item['file_name'], PATHINFO_EXTENSION)) === 'pdf';
                }
            ?>
            <div class="col-md-6 col-lg-4">
                <div class="glass-card p-4 h-100 d-flex flex-column justify-content-between position-relative">
                    
                    <!-- Bookmark Icon Button -->
                    <button class="btn border-0 p-1 position-absolute top-0 end-0 mt-3 me-3 bookmark-btn" 
                            data-id="<?php echo $item['id']; ?>" 
                            title="<?php echo $isBookmarked ? 'Remove Bookmark' : 'Save Resource'; ?>">
                        <i class="<?php echo $isBookmarked ? 'fa-solid' : 'fa-regular'; ?> fa-bookmark text-primary fs-5"></i>
                    </button>

                    <div>
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="badge-custom <?php 
                                echo $item['category'] === 'study_material' ? 'badge-study' : '';
                                echo $item['category'] === 'assignment' ? 'badge-assign' : '';
                                echo $item['category'] === 'question_paper' ? 'badge-paper' : '';
                                echo $item['category'] === 'practical' ? 'badge-prac' : '';
                                echo $item['category'] === 'video' ? 'badge-vid' : '';
                            ?>">
                                <?php echo str_replace('_', ' ', $item['category']); ?>
                            </span>
                            <span class="text-secondary small fw-medium" style="font-size: 0.75rem;">
                                <?php echo htmlspecialchars($item['subject_code']); ?>
                            </span>
                        </div>

                        <h5 class="text-white fw-bold mb-2 h6 tracking-tight leading-snug" style="max-height: 40px; overflow: hidden;" title="<?php echo htmlspecialchars($item['title']); ?>">
                            <?php echo htmlspecialchars($item['title']); ?>
                        </h5>
                        

                    </div>

                    <div class="border-top border-glass pt-3 mt-3">
                        <div class="text-secondary small mb-3">
                            <i class="fa-solid fa-graduation-cap text-primary me-1"></i> Prof. <span class="text-light"><?php echo htmlspecialchars($item['faculty_name']); ?></span>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <!-- Left: preview/submit action -->
                            <div class="d-flex gap-1">
                                <?php if ($isPdf): ?>
                                    <button class="btn btn-outline-info btn-xs py-1 px-2.5 small preview-pdf-btn border-glass text-info" 
                                            data-path="../<?php echo htmlspecialchars($item['file_path']); ?>" 
                                            data-title="<?php echo htmlspecialchars($item['title']); ?>"
                                            style="font-size: 0.75rem;">
                                        <i class="fa-solid fa-eye me-1"></i> Preview
                                    </button>
                                <?php endif; ?>
                                
                                <?php if ($item['category'] === 'assignment'): ?>
                                    <a href="submit_assignment.php?material_id=<?php echo $item['id']; ?>" class="btn btn-outline-success btn-xs py-1 px-2.5 small border-glass text-success" style="font-size: 0.75rem;">
                                        <i class="fa-solid fa-cloud-arrow-up me-1"></i> Submit
                                        <?php if ($item['submitted_count'] > 0): ?>
                                            <span class="badge bg-success ms-1"><?php echo $item['submitted_count']; ?></span>
                                        <?php endif; ?>
                                    </a>
                                <?php endif; ?>
                            </div>

                            <!-- Right: Download action -->
                            <div>
                                <?php if ($item['category'] === 'video'): ?>
                                    <a href="download_action.php?id=<?php echo $item['id']; ?>" target="_blank" class="btn btn-primary-custom py-1 px-2.5 small" style="font-size: 0.75rem;">
                                        <i class="fa-brands fa-youtube me-1"></i> Watch
                                    </a>
                                <?php else: ?>
                                    <a href="download_action.php?id=<?php echo $item['id']; ?>" class="btn btn-primary-custom py-1 px-2.5 small" style="font-size: 0.75rem;">
                                        <i class="fa-solid fa-file-arrow-down me-1"></i> Get File
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- PDF Previewer Modal (Standard In-browser Embed / Canvas Preview) -->
<div class="modal fade" id="pdfPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content bg-dark border-glass glass-card">
            <div class="modal-header border-glass px-4 py-3 justify-content-between">
                <h5 class="modal-title fw-bold text-white" id="pdf-preview-title">Document Preview</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" style="height: 70vh;">
                <iframe id="pdf-iframe" src="" class="w-100 h-100 border-0" style="border-radius: 0 0 16px 16px;"></iframe>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Toggle PDF Modal Preview
        const previewModalEl = document.getElementById('pdfPreviewModal');
        const previewModal = new bootstrap.Modal(previewModalEl);
        const pdfIframe = document.getElementById('pdf-iframe');
        const pdfTitle = document.getElementById('pdf-preview-title');
        
        document.querySelectorAll('.preview-pdf-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const path = btn.getAttribute('data-path');
                const title = btn.getAttribute('data-title');
                pdfTitle.textContent = 'Preview: ' + title;
                pdfIframe.setAttribute('src', path);
                previewModal.show();
            });
        });

        previewModalEl.addEventListener('hidden.bs.modal', () => {
            pdfIframe.setAttribute('src', '');
        });

        // AJAX Bookmark Toggler
        document.querySelectorAll('.bookmark-btn').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.preventDefault();
                const mId = btn.getAttribute('data-id');
                try {
                    const response = await fetch(`bookmark_action.php?id=${mId}`);
                    const result = await response.json();
                    
                    if (result.success) {
                        const icon = btn.querySelector('i');
                        if (result.status === 'bookmarked') {
                            icon.className = 'fa-solid fa-bookmark text-primary fs-5';
                            btn.setAttribute('title', 'Remove Bookmark');
                        } else {
                            icon.className = 'fa-regular fa-bookmark text-primary fs-5';
                            btn.setAttribute('title', 'Save Resource');
                        }
                    } else {
                        alert('Operation failed. Please try again.');
                    }
                } catch (err) {
                    console.error('Bookmark toggle fetch error: ', err);
                }
            });
        });
    });
</script>
<?php 
require_once __DIR__ . '/../includes/footer.php';
?>
