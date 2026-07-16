<?php
// admin/subjects.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$pageTitle = 'Subjects Configuration';
$pageIcon = 'fa-solid fa-book';

$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';

// Add Subject handler
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_name = trim($_POST['subject_name'] ?? '');
    $subject_code = trim($_POST['subject_code'] ?? '');
    $semester = intval($_POST['semester'] ?? 1);
    
    if (empty($subject_name) || empty($subject_code)) {
        $error = 'Both Subject Name and Subject Code are required.';
    } else {
        try {
            // Verify unique subject code
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM subjects WHERE subject_code = ?");
            $stmt->execute([$subject_code]);
            if ($stmt->fetchColumn() > 0) {
                $error = 'A subject with this Subject Code has already been configured.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO subjects (subject_name, subject_code, semester) VALUES (?, ?, ?)");
                $stmt->execute([$subject_name, $subject_code, $semester]);
                $success = 'Subject configured successfully!';
                $action = 'list';
            }
        } catch (PDOException $e) {
            $error = 'Failed to create subject: ' . $e->getMessage();
        }
    }
}

// Delete Subject
if ($action === 'delete') {
    $id = intval($_GET['id'] ?? 0);
    if ($id > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM subjects WHERE id = ?");
            $stmt->execute([$id]);
            $success = 'Subject configured details cleared successfully.';
            $action = 'list';
        } catch (PDOException $e) {
            $error = 'Failed to remove subject: ' . $e->getMessage();
        }
    }
}

// Filter subjects by semester parameter
$filterSem = isset($_GET['filter_sec']) ? intval($_GET['filter_sec']) : 0;

// Load Subjects List
$subjects = [];
try {
    if ($filterSem > 0) {
        $stmt = $pdo->prepare("SELECT * FROM subjects WHERE semester = ? ORDER BY subject_name ASC");
        $stmt->execute([$filterSem]);
        $subjects = $stmt->fetchAll();
    } else {
        $subjects = $pdo->query("SELECT * FROM subjects ORDER BY semester ASC, subject_name ASC")->fetchAll();
    }
} catch (PDOException $e) {
    $error = 'Failed to fetch subjects list: ' . $e->getMessage();
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

<?php if ($action === 'add'): ?>
    <!-- Add Subject Form -->
    <div class="glass-card p-4 p-md-5" style="max-width: 600px; margin: 0 auto;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="h5 fw-bold text-white mb-0">Configure New Subject</h3>
            <a href="subjects.php" class="btn btn-secondary-custom btn-sm text-light"><i class="fa-solid fa-arrow-left me-1"></i> Back to List</a>
        </div>
        
        <form action="subjects.php?action=add" method="POST">
            <div class="mb-3">
                <label class="form-label text-secondary small fw-semibold">Subject Title / Display Name</label>
                <input type="text" class="form-control" name="subject_name" required placeholder="Structure and Algorithm Design" value="<?php echo isset($subject_name) ? htmlspecialchars($subject_name) : ''; ?>">
            </div>

            <div class="mb-3">
                <label class="form-label text-secondary small fw-semibold">Subject Identifier Code</label>
                <input type="text" class="form-control" name="subject_code" required placeholder="e.g. CS-301" value="<?php echo isset($subject_code) ? htmlspecialchars($subject_code) : ''; ?>">
            </div>

            <div class="mb-4">
                <label class="form-label text-secondary small fw-semibold">Course Semester</label>
                <select class="form-select" name="semester" required>
                    <?php for($i=1; $i<=10; $i++): ?>
                        <option value="<?php echo $i; ?>">Semester <?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-primary-custom w-100 py-3">
                <i class="fa-solid fa-folder-plus me-2"></i> Configure Subject
            </button>
        </form>
    </div>

<?php else: ?>
    <!-- Subject Filter & List Grid -->
    
    <div class="glass-card p-3 mb-4">
        <form action="subjects.php" method="GET" class="row g-2 align-items-center">
            <div class="col-sm-auto">
                <label class="text-secondary small fw-bold">Filter Semester:</label>
            </div>
            <div class="col-sm-4">
                <select class="form-select form-select-sm" name="filter_sec" onchange="this.form.submit()">
                    <option value="0">Show All Semesters</option>
                    <?php for($i=1; $i<=10; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo $filterSem == $i ? 'selected' : ''; ?>>Semester <?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
        </form>
    </div>

    <div class="glass-card p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="h5 fw-bold text-white mb-0">Configured Subjects</h3>
            <a href="subjects.php?action=add" class="btn btn-primary-custom btn-sm text-light"><i class="fa-solid fa-plus me-1"></i> Configure Subject</a>
        </div>

        <?php if (empty($subjects)): ?>
            <div class="text-center text-secondary py-5">
                <i class="fa-solid fa-book mb-3 fa-2x"></i>
                <p>No subjects found for selection. Add one above.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0" style="--bs-table-bg: transparent; border-color: var(--border-glass);">
                    <thead>
                        <tr class="text-secondary small fw-semibold">
                            <th class="border-0">Subject Code</th>
                            <th class="border-0">Name / Title</th>
                            <th class="border-0 text-center">Semester</th>
                            <th class="border-0 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($subjects as $sub): ?>
                            <tr class="border-glass">
                                <td class="fw-bold text-primary"><?php echo htmlspecialchars($sub['subject_code']); ?></td>
                                <td class="text-light"><?php echo htmlspecialchars($sub['subject_name']); ?></td>
                                <td class="text-center"><span class="badge bg-secondary">Semester <?php echo $sub['semester']; ?></span></td>
                                <td>
                                    <div class="d-flex gap-1 justify-content-center">
                                        <button class="btn btn-outline-danger btn-sm border-0 delete-btn" data-id="<?php echo $sub['id']; ?>" data-name="<?php echo htmlspecialchars($sub['subject_name']); ?>">
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

    <!-- Confirm Modal -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark border-glass glass-card">
                <div class="modal-body text-center p-4">
                    <span class="d-inline-flex bg-danger bg-opacity-10 text-danger p-3 rounded-circle mb-3">
                        <i class="fa-solid fa-trash fa-xl"></i>
                    </span>
                    <h5 class="text-white fw-bold mb-2">Delete Subject?</h5>
                    <p class="text-secondary small mb-4">Are you sure you want to delete <b class="text-white" id="delete-name"></b>? This will also remove any study resources mapped to this subject!</p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-secondary-custom text-light btn-sm px-4" data-bs-dismiss="modal">Cancel</button>
                        <a href="" id="delete-link" class="btn btn-danger btn-sm px-4">Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const deleteButtons = document.querySelectorAll('.delete-btn');
            const confirmModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
            const deleteName = document.getElementById('delete-name');
            const deleteLink = document.getElementById('delete-link');

            deleteButtons.forEach(btn => {
                btn.addEventListener('click', () => {
                    const id = btn.getAttribute('data-id');
                    const name = btn.getAttribute('data-name');
                    deleteName.textContent = name;
                    deleteLink.setAttribute('href', `subjects.php?action=delete&id=${id}`);
                    confirmModal.show();
                });
            });
        });
    </script>
<?php endif; ?>

<?php 
require_once __DIR__ . '/../includes/footer.php';
?>
