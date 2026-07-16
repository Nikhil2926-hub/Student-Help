<?php
// faculty/check_submissions.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireFaculty();

$faculty_id = $_SESSION['faculty_id'];
$pageTitle = 'Student Submissions';
$pageIcon = 'fa-solid fa-file-invoice';

$error = '';

// Load all submission files for assignments uploaded by this faculty member
$submissions = [];
try {
    $stmt = $pdo->prepare("SELECT s.*, stud.name as student_name, stud.enrollment_no, m.title as assignment_title 
                           FROM submissions s 
                           JOIN students stud ON s.student_id = stud.id
                           JOIN materials m ON s.material_id = m.id
                           WHERE m.faculty_id = ?
                           ORDER BY s.submitted_at DESC");
    $stmt->execute([$faculty_id]);
    $submissions = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Failed to load assignment submissions: ' . $e->getMessage();
}

require_once __DIR__ . '/../includes/header.php';
?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" role="alert">
        <i class="fa-solid fa-circle-exclamation"></i>
        <div><?php echo htmlspecialchars($error); ?></div>
    </div>
<?php endif; ?>

<div class="glass-card p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="h5 fw-bold text-white mb-0">Student Homework Submissions</h3>
    </div>

    <?php if (empty($submissions)): ?>
        <div class="text-center text-secondary py-5">
            <i class="fa-solid fa-inbox mb-3 fa-2x"></i>
            <p>No student submissions received for your assignments yet.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0" style="--bs-table-bg: transparent; border-color: var(--border-glass);">
                <thead>
                    <tr class="text-secondary small fw-semibold">
                        <th class="border-0">Student Details</th>
                        <th class="border-0">Assignment Task Name</th>
                        <th class="border-0">Uploaded File</th>
                        <th class="border-0 text-end">Submission Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($submissions as $sub): ?>
                        <tr class="border-glass">
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="bg-primary text-white d-flex align-items-center justify-content-center fw-bold rounded-circle" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                        <?php 
                                            echo strtoupper(substr($sub['student_name'], 0, 2));
                                        ?>
                                    </div>
                                    <div>
                                        <b class="text-white d-block"><?php echo htmlspecialchars($sub['student_name']); ?></b>
                                        <span class="text-secondary" style="font-size: 0.75rem;">Enroll: <?php echo htmlspecialchars($sub['enrollment_no']); ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="text-light fw-medium"><?php echo htmlspecialchars($sub['assignment_title']); ?></span>
                            </td>
                            <td>
                                <a href="../<?php echo htmlspecialchars($sub['file_path']); ?>" target="_blank" class="text-primary text-decoration-none small">
                                    <i class="fa-solid fa-file-arrow-down me-1"></i> <?php echo htmlspecialchars($sub['file_name']); ?>
                                </a>
                            </td>
                            <td class="text-secondary text-end small">
                                <?php echo date('j M Y, h:i A', strtotime($sub['submitted_at'])); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php 
require_once __DIR__ . '/../includes/footer.php';
?>
