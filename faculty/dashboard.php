<?php
// faculty/dashboard.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireFaculty();

$faculty_id = $_SESSION['faculty_id'];
$pageTitle = 'Faculty Dashboard';
$pageIcon = 'fa-solid fa-chart-pie';

// Fetch statistics for this faculty member
$stats = [
    'study_material' => 0,
    'assignment' => 0,
    'question_paper' => 0,
    'practical' => 0,
    'video' => 0,
    'total_submissions' => 0
];

try {
    // Count per category
    $stmt = $pdo->prepare("SELECT category, COUNT(*) as count FROM materials WHERE faculty_id = ? GROUP BY category");
    $stmt->execute([$faculty_id]);
    $results = $stmt->fetchAll();
    foreach ($results as $r) {
        $stats[$r['category']] = $r['count'];
    }
    
    // Count assignment submissions for this faculty's assignments
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM submissions s 
                           JOIN materials m ON s.material_id = m.id 
                           WHERE m.faculty_id = ?");
    $stmt->execute([$faculty_id]);
    $stats['total_submissions'] = $stmt->fetchColumn();

    // Fetch 5 recent uploads by this faculty member
    $stmt = $pdo->prepare("SELECT m.*, s.subject_name FROM materials m 
                           JOIN subjects s ON m.subject_id = s.id 
                           WHERE m.faculty_id = ? 
                           ORDER BY m.uploaded_at DESC LIMIT 5");
    $stmt->execute([$faculty_id]);
    $recentUploads = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Query failed: " . $e->getMessage());
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row g-4 mb-4">
    <!-- Study Material Card -->
    <div class="col-md-2 col-sm-4 col-6">
        <div class="glass-card p-3 text-center">
            <span class="d-inline-flex bg-primary bg-opacity-10 text-primary p-2.5 rounded-3 mb-2">
                <i class="fa-solid fa-book-open"></i>
            </span>
            <h6 class="text-secondary small fw-semibold text-truncate select-none">Notes</h6>
            <div class="h4 fw-bold text-white mb-0"><?php echo $stats['study_material']; ?></div>
        </div>
    </div>
    <!-- Assignments Card -->
    <div class="col-md-2 col-sm-4 col-6">
        <div class="glass-card p-3 text-center">
            <span class="d-inline-flex bg-success bg-opacity-10 text-success p-2.5 rounded-3 mb-2">
                <i class="fa-solid fa-file-pen"></i>
            </span>
            <h6 class="text-secondary small fw-semibold text-truncate select-none">Assignments</h6>
            <div class="h4 fw-bold text-white mb-0"><?php echo $stats['assignment']; ?></div>
        </div>
    </div>
    <!-- Question Papers -->
    <div class="col-md-2 col-sm-4 col-6">
        <div class="glass-card p-3 text-center">
            <span class="d-inline-flex bg-warning bg-opacity-10 text-warning p-2.5 rounded-3 mb-2">
                <i class="fa-solid fa-file-signature"></i>
            </span>
            <h6 class="text-secondary small fw-semibold text-truncate select-none">QP Papers</h6>
            <div class="h4 fw-bold text-white mb-0"><?php echo $stats['question_paper']; ?></div>
        </div>
    </div>
    <!-- Practicals Card -->
    <div class="col-md-2 col-sm-4 col-6">
        <div class="glass-card p-3 text-center">
            <span class="d-inline-flex bg-info bg-opacity-10 text-info p-2.5 rounded-3 mb-2">
                <i class="fa-solid fa-flask"></i>
            </span>
            <h6 class="text-secondary small fw-semibold text-truncate select-none">Practicals</h6>
            <div class="h4 fw-bold text-white mb-0"><?php echo $stats['practical']; ?></div>
        </div>
    </div>
    <!-- Video lectures -->
    <div class="col-md-2 col-sm-4 col-6">
        <div class="glass-card p-3 text-center">
            <span class="d-inline-flex bg-danger bg-opacity-10 text-danger p-2.5 rounded-3 mb-2">
                <i class="fa-solid fa-circle-play"></i>
            </span>
            <h6 class="text-secondary small fw-semibold text-truncate select-none">Videos</h6>
            <div class="h4 fw-bold text-white mb-0"><?php echo $stats['video']; ?></div>
        </div>
    </div>
    <!-- Student submissions -->
    <div class="col-md-2 col-sm-4 col-6">
        <div class="glass-card p-3 text-center border-primary bg-primary bg-opacity-5">
            <span class="d-inline-flex bg-primary bg-opacity-15 text-primary p-2.5 rounded-3 mb-2">
                <i class="fa-solid fa-inbox"></i>
            </span>
            <h6 class="text-secondary small fw-semibold text-truncate select-none">Submissions</h6>
            <div class="h4 fw-bold text-white mb-0"><?php echo $stats['total_submissions']; ?></div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Actions and stats -->
    <div class="col-lg-4">
        <div class="glass-card p-4 h-100 d-flex flex-column justify-content-between">
            <div>
                <h4 class="h5 fw-bold text-white mb-3"><i class="fa-solid fa-bolt text-primary me-2"></i> Faculty Actions</h4>
                <p class="text-secondary small mb-4">Select an item below to quickly add new academic materials or manage student file uploads.</p>
            </div>
            <div class="d-flex flex-column gap-2 mb-4">
                <a href="upload.php" class="btn btn-primary-custom align-items-center justify-content-center d-flex gap-2 py-2.5">
                    <i class="fa-solid fa-cloud-arrow-up"></i> Upload Resources
                </a>
                <a href="manage.php" class="btn btn-secondary-custom btn-sm text-light py-2.5">
                    <i class="fa-solid fa-folder-open me-2"></i> Manage My Uploads
                </a>
                <a href="check_submissions.php" class="btn btn-secondary-custom btn-sm text-light py-2.5">
                    <i class="fa-solid fa-inbox me-2"></i> View Submissions
                </a>
            </div>
            <div class="border-top border-glass pt-3 mt-auto">
                <div class="text-secondary small">
                    Logged in as <b><?php echo htmlspecialchars($_SESSION['faculty_name']); ?></b>. Accounts are managed by the administrator.
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Uploads Table -->
    <div class="col-lg-8">
        <div class="glass-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="h5 fw-bold text-white mb-0"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i> My Recent Uploads</h4>
                <a href="manage.php" class="btn btn-outline-secondary border-glass text-light btn-sm">See All</a>
            </div>
            
            <?php if (empty($recentUploads)): ?>
                <div class="text-center text-secondary py-5">
                    <i class="fa-solid fa-cloud-sun fa-2x mb-3"></i>
                    <p class="small mb-0">You haven't uploaded any study materials yet.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-dark table-hover align-middle mb-0" style="--bs-table-bg: transparent; border-color: var(--border-glass);">
                        <thead>
                            <tr class="text-secondary small fw-semibold">
                                <th class="border-0">Resource Title</th>
                                <th class="border-0">Category</th>
                                <th class="border-0">Subject</th>
                                <th class="border-0 text-center">Semester</th>
                                <th class="border-0 text-end">Uploaded</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentUploads as $upload): ?>
                                <tr class="border-glass">
                                    <td class="text-white fw-medium"><?php echo htmlspecialchars($upload['title']); ?></td>
                                    <td>
                                        <span class="badge-custom <?php 
                                            echo $upload['category'] === 'study_material' ? 'badge-study' : '';
                                            echo $upload['category'] === 'assignment' ? 'badge-assign' : '';
                                            echo $upload['category'] === 'question_paper' ? 'badge-paper' : '';
                                            echo $upload['category'] === 'practical' ? 'badge-prac' : '';
                                            echo $upload['category'] === 'video' ? 'badge-vid' : '';
                                        ?>">
                                            <?php echo str_replace('_', ' ', $upload['category']); ?>
                                        </span>
                                    </td>
                                    <td class="text-secondary small"><?php echo htmlspecialchars($upload['subject_name']); ?></td>
                                    <td class="text-center"><span class="badge bg-secondary">Sem <?php echo $upload['semester']; ?></span></td>
                                    <td class="text-secondary text-end small"><?php echo date('j M y', strtotime($upload['uploaded_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php 
require_once __DIR__ . '/../includes/footer.php';
?>
