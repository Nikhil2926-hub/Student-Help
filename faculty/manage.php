<?php
// faculty/manage.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireFaculty();

$faculty_id = $_SESSION['faculty_id'];
$pageTitle = 'Manage My Uploads';
$pageIcon = 'fa-solid fa-folder-open';

$error = '';
$success = '';

if (isset($_GET['deleted'])) {
    $success = 'Resource deleted successfully!';
}
if (isset($_GET['updated'])) {
    $success = 'Resource updated successfully!';
}

// Extract filter values
$categoryFilter = $_GET['category'] ?? '';
$semesterFilter = isset($_GET['semester']) ? intval($_GET['semester']) : 0;
$subjectFilter = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : 0;
$searchQuery = trim($_GET['search'] ?? '');

// Load faculty subjects for filters
$subjects = [];
try {
    $subjects = $pdo->query("SELECT * FROM subjects ORDER BY semester ASC, subject_name ASC")->fetchAll();
} catch (PDOException $e) {
    $error = 'Failed to load filter subjects: ' . $e->getMessage();
}

// Build query
$sql = "SELECT m.*, s.subject_name FROM materials m 
        JOIN subjects s ON m.subject_id = s.id 
        WHERE m.faculty_id = :fac_id";
$params = [':fac_id' => $faculty_id];

if (!empty($categoryFilter)) {
    $sql .= " AND m.category = :category";
    $params[':category'] = $categoryFilter;
}
if ($semesterFilter > 0) {
    $sql .= " AND m.semester = :semester";
    $params[':semester'] = $semesterFilter;
}
if ($subjectFilter > 0) {
    $sql .= " AND m.subject_id = :sub_id";
    $params[':sub_id'] = $subjectFilter;
}
if (!empty($searchQuery)) {
    $sql .= " AND m.title LIKE :search";
    $params[':search'] = '%' . $searchQuery . '%';
}

$sql .= " ORDER BY m.uploaded_at DESC";

// Get uploads
$uploads = [];
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $uploads = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Failed to query materials: ' . $e->getMessage();
}

require_once __DIR__ . '/../includes/header.php';
?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" role="alert">
        <i class="fa-solid fa-circle-exclamation"></i>
        <div><?php echo htmlspecialchars($error); ?></div>
    </div>
<?php endif; ?>

<?php if (!empty($success)): ?>
    <div class="alert alert-success d-flex align-items-center gap-2 mb-4" role="alert">
        <i class="fa-solid fa-circle-check"></i>
        <div><?php echo htmlspecialchars($success); ?></div>
    </div>
<?php endif; ?>

<!-- Filters Form -->
<div class="glass-card p-4 mb-4">
    <form action="manage.php" method="GET" class="row g-3">
        <!-- Search -->
        <div class="col-md-3">
            <label class="form-label text-secondary small fw-semibold">Search Title</label>
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-transparent border-glass text-secondary"><i class="fa-solid fa-search"></i></span>
                <input type="text" class="form-control" name="search" placeholder="Type keywords..." value="<?php echo htmlspecialchars($searchQuery); ?>">
            </div>
        </div>
        
        <!-- Category -->
        <div class="col-md-3">
            <label class="form-label text-secondary small fw-semibold">Category</label>
            <select class="form-select form-select-sm" name="category">
                <option value="">All Categories</option>
                <option value="study_material" <?php echo $categoryFilter === 'study_material' ? 'selected' : ''; ?>>Study Material</option>
                <option value="assignment" <?php echo $categoryFilter === 'assignment' ? 'selected' : ''; ?>>Assignment</option>
                <option value="question_paper" <?php echo $categoryFilter === 'question_paper' ? 'selected' : ''; ?>>Question Paper</option>
                <option value="practical" <?php echo $categoryFilter === 'practical' ? 'selected' : ''; ?>>Practical File</option>
                <option value="video" <?php echo $categoryFilter === 'video' ? 'selected' : ''; ?>>Video URL</option>
            </select>
        </div>

        <!-- Semester -->
        <div class="col-md-2">
            <label class="form-label text-secondary small fw-semibold">Semester</label>
            <select class="form-select form-select-sm" name="semester">
                <option value="0">All Semesters</option>
                <?php for($i=1; $i<=10; $i++): ?>
                    <option value="<?php echo $i; ?>" <?php echo $semesterFilter == $i ? 'selected' : ''; ?>>Semester <?php echo $i; ?></option>
                <?php endfor; ?>
            </select>
        </div>

        <!-- Subject -->
        <div class="col-md-2">
            <label class="form-label text-secondary small fw-semibold">Subject</label>
            <select class="form-select form-select-sm" name="subject_id">
                <option value="0">All Subjects</option>
                <?php foreach ($subjects as $sub): ?>
                    <option value="<?php echo $sub['id']; ?>" <?php echo $subjectFilter == $sub['id'] ? 'selected' : ''; ?>>
                        Sem <?php echo $sub['semester']; ?>: <?php echo htmlspecialchars($sub['subject_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Buttons -->
        <div class="col-md-2 d-flex align-items-end gap-2">
            <button type="submit" class="btn btn-primary-custom btn-sm w-100 py-2"><i class="fa-solid fa-filter me-1"></i> Filter</button>
            <a href="manage.php" class="btn btn-secondary-custom btn-sm w-100 text-light py-2">Clear</a>
        </div>
    </form>
</div>

<!-- Uploaded list -->
<div class="glass-card p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="h5 fw-bold text-white mb-0">My Uploaded Resources</h3>
        <a href="upload.php" class="btn btn-primary-custom btn-sm text-light"><i class="fa-solid fa-plus me-1"></i> New Upload</a>
    </div>

    <?php if (empty($uploads)): ?>
        <div class="text-center text-secondary py-5">
            <i class="fa-solid fa-folder-open mb-3 fa-2x"></i>
            <p>No matching learning resources found.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0" style="--bs-table-bg: transparent; border-color: var(--border-glass);">
                <thead>
                    <tr class="text-secondary small fw-semibold">
                        <th class="border-0">Title</th>
                        <th class="border-0">Category</th>
                        <th class="border-0">Subject</th>
                        <th class="border-0 text-center">Semester</th>
                        <th class="border-0">Attachment / Path</th>
                        <th class="border-0 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($uploads as $row): ?>
                        <tr class="border-glass">
                            <td>
                                <strong class="text-white"><?php echo htmlspecialchars($row['title']); ?></strong>

                            </td>
                            <td>
                                <span class="badge-custom <?php 
                                    echo $row['category'] === 'study_material' ? 'badge-study' : '';
                                    echo $row['category'] === 'assignment' ? 'badge-assign' : '';
                                    echo $row['category'] === 'question_paper' ? 'badge-paper' : '';
                                    echo $row['category'] === 'practical' ? 'badge-prac' : '';
                                    echo $row['category'] === 'video' ? 'badge-vid' : '';
                                ?>">
                                    <?php echo str_replace('_', ' ', $row['category']); ?>
                                </span>
                            </td>
                            <td class="text-secondary small"><?php echo htmlspecialchars($row['subject_name']); ?></td>
                            <td class="text-center"><span class="badge bg-secondary">Sem <?php echo $row['semester']; ?></span></td>
                            <td class="small">
                                <?php if ($row['category'] === 'video'): ?>
                                    <a href="<?php echo htmlspecialchars($row['video_url']); ?>" target="_blank" class="text-danger text-decoration-none">
                                        <i class="fa-brands fa-youtube me-1"></i> Open Video Link
                                    </a>
                                <?php else: ?>
                                    <a href="../<?php echo htmlspecialchars($row['file_path']); ?>" target="_blank" class="text-primary text-decoration-none">
                                        <i class="fa-solid fa-file-arrow-down me-1"></i> <?php echo htmlspecialchars($row['file_name']); ?>
                                    </a>
                                    <div class="text-secondary" style="font-size: 0.75rem;">
                                        (<?php echo number_format($row['file_size'] / (1024 * 1024), 2); ?> MB)
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex justify-content-center gap-2">
                                    <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn btn-outline-primary btn-sm border-0"><i class="fa-solid fa-pencil"></i></a>
                                    <button class="btn btn-outline-danger btn-sm border-0 delete-btn" data-id="<?php echo $row['id']; ?>" data-title="<?php echo htmlspecialchars($row['title']); ?>">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Deletion Confirmation Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark border-glass glass-card">
            <div class="modal-body text-center p-4">
                <span class="d-inline-flex bg-danger bg-opacity-10 text-danger p-3 rounded-circle mb-3">
                    <i class="fa-solid fa-triangle-exclamation fa-xl"></i>
                </span>
                <h5 class="text-white fw-bold mb-2">Remove Academic Resource?</h5>
                <p class="text-secondary small mb-4 font-normal">Are you sure you want to permanently delete <b class="text-white" id="delete-title"></b>? Students will lose immediate access to this resource.</p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-secondary-custom text-light btn-sm px-4" data-bs-dismiss="modal">Cancel</button>
                    <a href="" id="delete-link" class="btn btn-danger btn-sm px-4">Delete Permanently</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const deleteButtons = document.querySelectorAll('.delete-btn');
        const confirmModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
        const deleteTitle = document.getElementById('delete-title');
        const deleteLink = document.getElementById('delete-link');

        deleteButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.getAttribute('data-id');
                const title = btn.getAttribute('data-title');
                deleteTitle.textContent = title;
                deleteLink.setAttribute('href', `delete.php?id=${id}`);
                confirmModal.show();
            });
        });
    });
</script>
<?php 
require_once __DIR__ . '/../includes/footer.php';
?>
