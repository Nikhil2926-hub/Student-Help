<?php
// student/stats.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireStudent();

$student_id = $_SESSION['student_id'];
$pageTitle = 'My Learning Statistics';
$pageIcon = 'fa-solid fa-chart-line';

$error = '';

// Load download logs history
$downloads = [];
try {
    $stmt = $pdo->prepare("SELECT dh.*, m.title as material_title, m.category, s.subject_name 
                           FROM download_history dh 
                           JOIN materials m ON dh.material_id = m.id 
                           JOIN subjects s ON m.subject_id = s.id 
                           WHERE dh.student_id = ? 
                           ORDER BY dh.downloaded_at DESC 
                           LIMIT 20");
    $stmt->execute([$student_id]);
    $downloads = $stmt->fetchAll();
    
    // Count stats breakdown for this student
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM download_history WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $totalDownloads = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookmarks WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $totalBookmarks = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM submissions WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $totalSubmissions = $stmt->fetchColumn();
} catch (PDOException $e) {
    $error = 'Failed to fetch logs: ' . $e->getMessage();
}

require_once __DIR__ . '/../includes/header.php';
?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger mb-4"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<!-- Stats Metrics Row -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="glass-card p-4 text-center">
            <span class="d-inline-flex bg-primary bg-opacity-10 text-primary p-3 rounded-circle mb-3">
                <i class="fa-solid fa-file-arrow-down fa-xl"></i>
            </span>
            <h5 class="text-secondary small fw-semibold">Resources Downloaded</h5>
            <div class="h2 fw-bold text-white mb-0"><?php echo $totalDownloads; ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="glass-card p-4 text-center">
            <span class="d-inline-flex bg-warning bg-opacity-10 text-warning p-3 rounded-circle mb-3">
                <i class="fa-solid fa-bookmark fa-xl"></i>
            </span>
            <h5 class="text-secondary small fw-semibold">Current Bookmarks</h5>
            <div class="h2 fw-bold text-white mb-0"><?php echo $totalBookmarks; ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="glass-card p-4 text-center">
            <span class="d-inline-flex bg-success bg-opacity-10 text-success p-3 rounded-circle mb-3">
                <i class="fa-solid fa-cloud-arrow-up fa-xl"></i>
            </span>
            <h5 class="text-secondary small fw-semibold">Solutions Submissions</h5>
            <div class="h2 fw-bold text-white mb-0"><?php echo $totalSubmissions; ?></div>
        </div>
    </div>
</div>

<!-- History Log table -->
<div class="glass-card p-4">
    <h4 class="h5 fw-bold text-white mb-4"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i> Recent Download History (Last 20 downloads)</h4>
    
    <?php if (empty($downloads)): ?>
        <div class="text-center text-secondary py-5">
            <i class="fa-solid fa-history mb-3 fa-2x"></i>
            <p class="small mb-0">No download logs registered yet. Download some study notes from the panel.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0" style="--bs-table-bg: transparent; border-color: var(--border-glass);">
                <thead>
                    <tr class="text-secondary small fw-semibold">
                        <th class="border-0">Resource Title</th>
                        <th class="border-0">Category</th>
                        <th class="border-0">Subject Course</th>
                        <th class="border-0 text-end">Downloaded At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($downloads as $row): ?>
                        <tr class="border-glass">
                            <td class="text-white fw-medium"><?php echo htmlspecialchars($row['material_title']); ?></td>
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
                            <td class="text-secondary text-end small"><?php echo date('j M Y, h:i A', strtotime($row['downloaded_at'])); ?></td>
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
