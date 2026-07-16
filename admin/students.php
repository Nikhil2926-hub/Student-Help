<?php
// admin/students.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$pageTitle = 'Manage Student accounts';
$pageIcon = 'fa-solid fa-user-graduate';

$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';

// Add Student handler
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $enrollment_no = trim($_POST['enrollment_no'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $semester = intval($_POST['semester'] ?? 1);
    $password = $_POST['password'] ?? '';

    if (empty($enrollment_no) || empty($name) || empty($email) || empty($password)) {
        $error = 'All fields are required.';
    }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    }
    else {
        try {
            // Verify unique enrollment
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE enrollment_no = ?");
            $stmt->execute([$enrollment_no]);
            if ($stmt->fetchColumn() > 0) {
                $error = 'Enrollment number is already registered.';
            }
            else {
                // Verify unique email
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetchColumn() > 0) {
                    $error = 'Email is already registered by another student.';
                }
                else {
                    $passHash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO students (name, email, enrollment_no, semester, password_hash) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$name, $email, $enrollment_no, $semester, $passHash]);
                    $success = 'Student account created successfully!';
                    $action = 'list';
                }
            }
        }
        catch (PDOException $e) {
            $error = 'Failed to create student: ' . $e->getMessage();
        }
    }
}

// Delete Student
if ($action === 'delete') {
    $id = intval($_GET['id'] ?? 0);
    if ($id > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
            $stmt->execute([$id]);
            $success = 'Student account removed successfully.';
            $action = 'list';
        }
        catch (PDOException $e) {
            $error = 'Failed to remove student: ' . $e->getMessage();
        }
    }
}

// Load List
$students = [];
if ($action === 'list') {
    try {
        $students = $pdo->query("SELECT * FROM students ORDER BY semester ASC, name ASC")->fetchAll();
    }
    catch (PDOException $e) {
        $error = 'Failed to fetch student database: ' . $e->getMessage();
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

<?php if ($action === 'add'): ?>
    <!-- Add Student Form -->
    <div class="glass-card p-4 p-md-5" style="max-width: 600px; margin: 0 auto;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="h5 fw-bold text-white mb-0">Register Student</h3>
            <a href="students.php" class="btn btn-secondary-custom btn-sm text-light"><i class="fa-solid fa-arrow-left me-1"></i> Back to List</a>
        </div>
        
        <form action="students.php?action=add" method="POST">
            <div class="mb-3">
                <label class="form-label text-secondary small fw-semibold">Enrollment Number</label>
                <input type="text" class="form-control" name="enrollment_no" required placeholder="e.g. 220761305000" value="<?php echo isset($enrollment_no) ? htmlspecialchars($enrollment_no) : ''; ?>">
            </div>

            <div class="mb-3">
                <label class="form-label text-secondary small fw-semibold">Full Name</label>
                <input type="text" class="form-control" name="name" required placeholder="Student Name" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>">
            </div>

            <div class="mb-3">
                <label class="form-label text-secondary small fw-semibold">Email Address</label>
                <input type="email" class="form-control" name="email" required placeholder="Email ID" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
            </div>

            <div class="mb-3">
                <label class="form-label text-secondary small fw-semibold">Current Semester</label>
                <select class="form-select" name="semester" required>
                    <?php for ($i = 1; $i <= 10; $i++): ?>
                        <option value="<?php echo $i; ?>">Semester <?php echo $i; ?></option>
                    <?php
    endfor; ?>
                </select>
            </div>

            <div class="mb-4">
                <label class="form-label text-secondary small fw-semibold">Password</label>
                <div class="input-group password-group">
                    <input type="password" class="form-control" name="password" required placeholder="••••••••">
                    <button class="btn btn-outline-secondary border-glass text-secondary toggle-password" type="button">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary-custom w-100 py-3">
                <i class="fa-solid fa-user-plus me-2"></i> Register Student Account
            </button>
        </form>
    </div>

<?php
else: ?>
    <!-- Student List -->
    <div class="glass-card p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="h5 fw-bold text-white mb-0">Registered Students</h3>
            <a href="students.php?action=add" class="btn btn-primary-custom btn-sm text-light"><i class="fa-solid fa-plus me-1"></i> Register Student</a>
        </div>

        <?php if (empty($students)): ?>
            <div class="text-center text-secondary py-5">
                <i class="fa-solid fa-user-graduate mb-3 fa-2x"></i>
                <p>No students registered. Students can also sign up from frontend registration.</p>
            </div>
        <?php
    else: ?>
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0" style="--bs-table-bg: transparent; border-color: var(--border-glass);">
                    <thead>
                        <tr class="text-secondary small fw-semibold">
                            <th class="border-0">Enrollment No</th>
                            <th class="border-0">Name</th>
                            <th class="border-0">Email</th>
                            <th class="border-0 text-center">Semester</th>
                            <th class="border-0 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $stud): ?>
                            <tr class="border-glass">
                                <td class="fw-bold text-light"><?php echo htmlspecialchars($stud['enrollment_no']); ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="bg-success text-white d-flex align-items-center justify-content-center fw-bold rounded-circle" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                            <?php
            $initials = '';
            $parts = explode(' ', $stud['name']);
            foreach ($parts as $p)
                $initials .= strtoupper(substr($p, 0, 1));
            echo substr($initials, 0, 2);
?>
                                        </div>
                                        <span class="text-white"><?php echo htmlspecialchars($stud['name']); ?></span>
                                    </div>
                                </td>
                                <td class="text-secondary"><?php echo htmlspecialchars($stud['email']); ?></td>
                                <td class="text-center"><span class="badge bg-secondary">Semester <?php echo $stud['semester']; ?></span></td>
                                <td>
                                    <div class="d-flex gap-1 justify-content-center">
                                        <button class="btn btn-outline-danger btn-sm border-0 delete-btn" data-id="<?php echo $stud['id']; ?>" data-name="<?php echo htmlspecialchars($stud['name']); ?>">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php
        endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php
    endif; ?>
    </div>

    <!-- Confirm Modal -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark border-glass glass-card">
                <div class="modal-body text-center p-4">
                    <span class="d-inline-flex bg-danger bg-opacity-10 text-danger p-3 rounded-circle mb-3">
                        <i class="fa-solid fa-trash fa-xl"></i>
                    </span>
                    <h5 class="text-white fw-bold mb-2">Delete Student Account?</h5>
                    <p class="text-secondary small mb-4">Are you sure you want to remove <b class="text-white" id="delete-name"></b>? This will purge their dynamic bookmark library and submission lists.</p>
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
                    deleteLink.setAttribute('href', `students.php?action=delete&id=${id}`);
                    confirmModal.show();
                });
            });
        });
    </script>
<?php
endif; ?>

<?php

require_once __DIR__ . '/../includes/footer.php';
?>
