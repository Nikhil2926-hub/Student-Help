<?php
// admin/dashboard.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$pageTitle = 'Admin Dashboard';
$pageIcon = 'fa-solid fa-chart-line';

// Fetch metrics
$counts = [
    'faculty' => 0,
    'students' => 0,
    'subjects' => 0,
    'materials' => 0
];

try {
    $counts['faculty'] = $pdo->query("SELECT COUNT(*) FROM faculty")->fetchColumn();
    $counts['students'] = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
    $counts['subjects'] = $pdo->query("SELECT COUNT(*) FROM subjects")->fetchColumn();
    $counts['materials'] = $pdo->query("SELECT COUNT(*) FROM materials")->fetchColumn();
    
    // Fetch 5 recent materials
    $stmt = $pdo->query("SELECT m.*, f.name as faculty_name, s.subject_name FROM materials m 
                         JOIN faculty f ON m.faculty_id = f.id 
                         JOIN subjects s ON m.subject_id = s.id 
                         ORDER BY m.uploaded_at DESC LIMIT 5");
    $recentUploads = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database query error: " . $e->getMessage());
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row g-4 mb-4">
    <!-- Stat Card: Faculty -->
    <div class="col-md-3 col-sm-6">
        <a href="faculty.php" class="text-decoration-none d-block h-100">
            <div class="glass-card p-4 text-center h-100">
                <span class="d-inline-flex bg-primary bg-opacity-10 text-primary p-3 rounded-3 mb-2">
                    <i class="fa-solid fa-chalkboard-user fa-lg"></i>
                </span>
                <h4 class="text-secondary small fw-semibold text-uppercase tracking-wider">Faculty Members</h4>
                <div class="display-6 fw-bold text-white"><?php echo $counts['faculty']; ?></div>
            </div>
        </a>
    </div>
    <!-- Stat Card: Students -->
    <div class="col-md-3 col-sm-6">
        <a href="students.php" class="text-decoration-none d-block h-100">
            <div class="glass-card p-4 text-center h-100">
                <span class="d-inline-flex bg-success bg-opacity-10 text-success p-3 rounded-3 mb-2">
                    <i class="fa-solid fa-user-graduate fa-lg"></i>
                </span>
                <h4 class="text-secondary small fw-semibold text-uppercase tracking-wider">Students</h4>
                <div class="display-6 fw-bold text-white"><?php echo $counts['students']; ?></div>
            </div>
        </a>
    </div>
    <!-- Stat Card: Subjects -->
    <div class="col-md-3 col-sm-6">
        <a href="subjects.php" class="text-decoration-none d-block h-100">
            <div class="glass-card p-4 text-center h-100">
                <span class="d-inline-flex bg-warning bg-opacity-10 text-warning p-3 rounded-3 mb-2">
                    <i class="fa-solid fa-book fa-lg"></i>
                </span>
                <h4 class="text-secondary small fw-semibold text-uppercase tracking-wider">Subjects</h4>
                <div class="display-6 fw-bold text-white"><?php echo $counts['subjects']; ?></div>
            </div>
        </a>
    </div>
    <!-- Stat Card: Materials -->
    <div class="col-md-3 col-sm-6">
        <a href="#materials-list" class="text-decoration-none d-block h-100">
            <div class="glass-card p-4 text-center h-100">
                <span class="d-inline-flex bg-info bg-opacity-10 text-info p-3 rounded-3 mb-2">
                    <i class="fa-solid fa-file-invoice fa-lg"></i>
                </span>
                <h4 class="text-secondary small fw-semibold text-uppercase tracking-wider">Uploaded Materials</h4>
                <div class="display-6 fw-bold text-white"><?php echo $counts['materials']; ?></div>
            </div>
        </a>
    </div>
</div>

<div class="row g-4" id="materials-list">
    <!-- Quick Panel Links -->
    <div class="col-lg-5">
        <div class="glass-card p-4 mb-4">
            <h3 class="h5 fw-bold text-white mb-3"><i class="fa-solid fa-wrench me-2 text-primary"></i> Administrative Actions</h3>
            <div class="d-flex flex-column gap-2">
                <a href="faculty.php?action=add" class="btn btn-primary-custom text-start align-items-center d-flex gap-3 py-3">
                    <i class="fa-solid fa-user-plus"></i> Add New Faculty Account
                </a>
                <a href="students.php?action=add" class="btn btn-secondary-custom text-start align-items-center d-flex gap-3 py-3 text-light">
                    <i class="fa-solid fa-user-check"></i> Register New Student
                </a>
                <a href="subjects.php?action=add" class="btn btn-secondary-custom text-start align-items-center d-flex gap-3 py-3 text-light">
                    <i class="fa-solid fa-folder-plus"></i> Configure New Subject
                </a>
            </div>
        </div>
    </div>

    <!-- Recent Shared Resources -->
    <div class="col-lg-7">
        <div class="glass-card p-4">
            <h3 class="h5 fw-bold text-white mb-3"><i class="fa-solid fa-history me-2 text-primary"></i> Recent Uploads Across Platform</h3>
            
            <?php if (empty($recentUploads)): ?>
                <div class="text-center text-secondary py-5">
                    <i class="fa-solid fa-folder-open mb-3 fa-2x"></i>
                    <p class="small">No resources has been shared yet.</p>
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush bg-transparent">
                    <?php foreach ($recentUploads as $upload): ?>
                        <div class="list-group-item bg-transparent text-light border-glass d-flex justify-content-between align-items-center px-0 py-3">
                            <div class="d-flex align-items-center gap-3">
                                <span class="badge-custom <?php 
                                    echo $upload['category'] === 'study_material' ? 'badge-study' : '';
                                    echo $upload['category'] === 'assignment' ? 'badge-assign' : '';
                                    echo $upload['category'] === 'question_paper' ? 'badge-paper' : '';
                                    echo $upload['category'] === 'practical' ? 'badge-prac' : '';
                                    echo $upload['category'] === 'video' ? 'badge-vid' : '';
                                ?>">
                                    <?php echo str_replace('_', ' ', $upload['category']); ?>
                                </span>
                                <div>
                                    <div class="fw-semibold text-white small" style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        <?php echo htmlspecialchars($upload['title']); ?>
                                    </div>
                                    <div class="text-secondary" style="font-size: 0.75rem;">
                                        By Prof. <?php echo htmlspecialchars($upload['faculty_name']); ?> • Subject: <?php echo htmlspecialchars($upload['subject_name']); ?>
                                    </div>
                                </div>
                            </div>
                            <span class="text-secondary small" style="font-size: 0.75rem;"><?php echo date('j M Y', strtotime($upload['uploaded_at'])); ?></span>
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
