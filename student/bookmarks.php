<?php
// student/bookmarks.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireStudent();

$student_id = $_SESSION['student_id'];
$student_sem = $_SESSION['student_semester'];
$pageTitle = 'Saved Bookmarks';
$pageIcon = 'fa-solid fa-bookmark';

$error = '';
$success = '';

// Load bookmarked materials
$bookmarks = [];
try {
    $stmt = $pdo->prepare("SELECT m.*, f.name as faculty_name, s.subject_name, s.subject_code 
                           FROM bookmarks b 
                           JOIN materials m ON b.material_id = m.id 
                           JOIN faculty f ON m.faculty_id = f.id 
                           JOIN subjects s ON m.subject_id = s.id 
                           WHERE b.student_id = ? AND m.semester = ?
                           ORDER BY b.bookmarked_at DESC");
    $stmt->execute([$student_id, $student_sem]);
    $bookmarks = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Failed to load bookmarks: ' . $e->getMessage();
}

require_once __DIR__ . '/../includes/header.php';
?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger mb-4"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="row g-4" id="bookmark-grid">
    <?php if (empty($bookmarks)): ?>
        <div class="col-12 text-center text-secondary py-5">
            <div class="glass-card p-5">
                <i class="fa-regular fa-bookmark mb-3 fa-3x text-secondary"></i>
                <h5 class="text-white">Your Bookmarks Library is Empty</h5>
                <p class="small mb-0">Bookmark important books, slide notes, reference files, or videos to review them here.</p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($bookmarks as $item): ?>
            <?php 
                $isPdf = false;
                if (!empty($item['file_name'])) {
                    $isPdf = strtolower(pathinfo($item['file_name'], PATHINFO_EXTENSION)) === 'pdf';
                }
            ?>
            <div class="col-md-6 col-lg-4 bookmark-card-container" id="bookmark-item-<?php echo $item['id']; ?>">
                <div class="glass-card p-4 h-100 d-flex flex-column justify-content-between position-relative">
                    
                    <!-- Remove Bookmark button -->
                    <button class="btn border-0 p-1 position-absolute top-0 end-0 mt-3 me-3 remove-bookmark-btn" 
                            data-id="<?php echo $item['id']; ?>" 
                            title="Remove Bookmark">
                        <i class="fa-solid fa-bookmark text-primary fs-5"></i>
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

                        <h5 class="text-white fw-bold mb-2 h6 tracking-tight leading-snug" style="max-height: 40px; overflow: hidden;">
                            <?php echo htmlspecialchars($item['title']); ?>
                        </h5>
                        

                    </div>

                    <div class="border-top border-glass pt-3 mt-3">
                        <div class="text-secondary small mb-3">
                            <i class="fa-solid fa-graduation-cap text-primary me-1"></i> Prof. <span class="text-light"><?php echo htmlspecialchars($item['faculty_name']); ?></span>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <!-- Left Actions -->
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
                                    </a>
                                <?php endif; ?>
                            </div>

                            <!-- Right Action -->
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

<!-- Modal PDF Previewer -->
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
        // PDF Preview
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

        // AJAX Bookmark Remover (on this bookmarks page, toggle off removes the element)
        document.querySelectorAll('.remove-bookmark-btn').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.preventDefault();
                const mId = btn.getAttribute('data-id');
                try {
                    const response = await fetch(`bookmark_action.php?id=${mId}`);
                    const result = await response.json();
                    
                    if (result.success && result.status === 'unbookmarked') {
                        const cardContainer = document.getElementById(`bookmark-item-${mId}`);
                        if (cardContainer) {
                            cardContainer.remove();
                        }
                        
                        // Check if no bookmarks remain
                        const remaining = document.querySelectorAll('.bookmark-card-container');
                        if (remaining.length === 0) {
                            document.getElementById('bookmark-grid').innerHTML = `
                                <div class="col-12 text-center text-secondary py-5">
                                    <div class="glass-card p-5">
                                        <i class="fa-regular fa-bookmark mb-3 fa-3x text-secondary"></i>
                                        <h5 class="text-white">Your Bookmarks Library is Empty</h5>
                                        <p class="small mb-0">Bookmark important books, slide notes, reference files, or videos to review them here.</p>
                                    </div>
                                </div>`;
                        }
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
