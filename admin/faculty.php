<?php
// admin/faculty.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$pageTitle = 'Manage Faculty accounts';
$pageIcon = 'fa-solid fa-chalkboard-user';

$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';

// Add Faculty Account handler
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $department = 'IMSC IT';
    $password = $_POST['password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Name, email, and password are required.';
    }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    }
    else {
        try {
            // Verify unique email
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM faculty WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
                $error = 'Email is already registered by another faculty.';
            }
            else {
                $passHash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO faculty (name, email, password_hash, department) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $email, $passHash, $department]);
                $success = 'Faculty member account created successfully!';
                $action = 'list'; // Redirect back to list
            }
        }
        catch (PDOException $e) {
            $error = 'Failed to create account: ' . $e->getMessage();
        }
    }
}

// Delete Faculty Account
if ($action === 'delete') {
    $id = intval($_GET['id'] ?? 0);
    if ($id > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM faculty WHERE id = ?");
            $stmt->execute([$id]);
            $success = 'Faculty member removed successfully.';
            $action = 'list';
        }
        catch (PDOException $e) {
            $error = 'Failed to remove faculty member: ' . $e->getMessage();
        }
    }
}

// Load Faculty List
$facultyMembers = [];
if ($action === 'list') {
    try {
        $facultyMembers = $pdo->query("SELECT * FROM faculty ORDER BY name ASC")->fetchAll();
    }
    catch (PDOException $e) {
        $error = 'Failed to fetch faculty list: ' . $e->getMessage();
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
    <!-- Add Faculty Form -->
    <div class="glass-card p-4 p-md-5" style="max-width: 600px; margin: 0 auto;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="h5 fw-bold text-white mb-0">Create Faculty Account</h3>
            <a href="faculty.php" class="btn btn-secondary-custom btn-sm text-light"><i class="fa-solid fa-arrow-left me-1"></i> Back to List</a>
        </div>
        
        <form action="faculty.php?action=add" method="POST">
            <div class="mb-3">
                <label class="form-label text-secondary small fw-semibold">Full Name</label>
                <input type="text" class="form-control" name="name" required placeholder="Faculty Name" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>">
            </div>

            <div class="mb-3">
                <label class="form-label text-secondary small fw-semibold">Email Address (Login Username)</label>
                <input type="email" class="form-control" name="email" required placeholder="Email ID" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
            </div>

            <div class="mb-3">
                <label class="form-label text-secondary small fw-semibold">Department</label>
                <input type="text" class="form-control" name="department" value="IMSC IT" readonly style="background-color: rgba(20, 18, 15, 0.5) !important; color: #a49a88 !important; cursor: not-allowed;">
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
                <i class="fa-solid fa-user-plus me-2"></i> Register Account
            </button>
        </form>
    </div>

<?php
else: ?>
    <!-- Faculty List -->
    <div class="glass-card p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="h5 fw-bold text-white mb-0">Registered Faculty</h3>
            <a href="faculty.php?action=add" class="btn btn-primary-custom btn-sm text-light"><i class="fa-solid fa-plus me-1"></i> Add Faculty</a>
        </div>

        <?php if (empty($facultyMembers)): ?>
            <div class="text-center text-secondary py-5">
                <i class="fa-solid fa-chalkboard-user mb-3 fa-2x"></i>
                <p>No faculty registered yet. Add accounts to let lecturers log in.</p>
            </div>
        <?php
    else: ?>
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0" style="--bs-table-bg: transparent; border-color: var(--border-glass);">
                    <thead>
                        <tr class="text-secondary small fw-semibold">
                            <th class="border-0">Name</th>
                            <th class="border-0">Email</th>
                            <th class="border-0">Department</th>
                            <th class="border-0">Date Added</th>
                            <th class="border-0 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($facultyMembers as $faculty): ?>
                            <tr class="border-glass">
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="bg-primary text-white d-flex align-items-center justify-content-center fw-bold rounded-circle" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                            <?php
            $initials = '';
            $parts = explode(' ', $faculty['name']);
            foreach ($parts as $p)
                $initials .= strtoupper(substr($p, 0, 1));
            echo substr($initials, 0, 2);
?>
                                        </div>
                                        <b class="text-white"><?php echo htmlspecialchars($faculty['name']); ?></b>
                                    </div>
                                </td>
                                <td class="text-secondary"><?php echo htmlspecialchars($faculty['email']); ?></td>
                                <td><span class="text-secondary"><?php echo htmlspecialchars($faculty['department'] ?: 'N/A'); ?></span></td>
                                <td class="text-secondary" style="font-size: 0.85rem;"><?php echo date('j M Y', strtotime($faculty['created_at'])); ?></td>
                                <td>
                                    <div class="d-flex gap-1 justify-content-center">
                                        <button class="btn btn-outline-danger btn-sm border-0 delete-btn" data-id="<?php echo $faculty['id']; ?>" data-name="<?php echo htmlspecialchars($faculty['name']); ?>">
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
                    <h5 class="text-white fw-bold mb-2">Remove Faculty Account?</h5>
                    <p class="text-secondary small mb-4">Are you sure you want to remove <b class="text-white" id="delete-name"></b>? They will immediately lose access to their uploaded learning resources.</p>
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
                    deleteLink.setAttribute('href', `faculty.php?action=delete&id=${id}`);
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
