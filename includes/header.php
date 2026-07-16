<?php
// includes/header.php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

// Dynamically determine the active page name
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = basename(dirname($_SERVER['PHP_SELF']));

$role = $_SESSION['role'] ?? '';
$userName = '';
if ($role === 'admin') {
    $userName = $_SESSION['admin_name'] ?? 'Admin';
    $backPath = '../';
} elseif ($role === 'faculty') {
    $userName = $_SESSION['faculty_name'] ?? 'Faculty';
    $backPath = '../';
} elseif ($role === 'student') {
    $userName = $_SESSION['student_name'] ?? 'Student';
    $backPath = '../';
} else {
    $backPath = '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Study Material System'; ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom Style Sheet -->
    <link rel="stylesheet" href="<?php echo $backPath; ?>css/style.css">
</head>
<body>

<div class="grid-container">
    
    <!-- Sidebar Navigation -->
    <aside class="custom-sidebar" id="sidebar">
        <div class="d-flex align-items-center gap-2 mb-4 px-2">
            <span class="text-primary bg-primary bg-opacity-10 p-2 rounded-3">
                <i class="fa-solid fa-graduation-cap fs-4"></i>
            </span>
            <span class="fw-bold fs-5 tracking-tight text-white">EduHub</span>
        </div>

        <nav class="flex-column" style="flex-grow: 1;">
            <?php if ($role === 'admin'): ?>
                <a href="<?php echo $backPath; ?>admin/dashboard.php" class="nav-link-custom <?php echo $currentPage === 'dashboard.php' && $currentDir === 'admin' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-chart-line" style="width: 20px;"></i>
                    <span>Dashboard</span>
                </a>
                <a href="<?php echo $backPath; ?>admin/faculty.php" class="nav-link-custom <?php echo $currentPage === 'faculty.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-chalkboard-user" style="width: 20px;"></i>
                    <span>Faculty Members</span>
                </a>
                <a href="<?php echo $backPath; ?>admin/students.php" class="nav-link-custom <?php echo $currentPage === 'students.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-user-graduate" style="width: 20px;"></i>
                    <span>Students</span>
                </a>
                <a href="<?php echo $backPath; ?>admin/subjects.php" class="nav-link-custom <?php echo $currentPage === 'subjects.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-book" style="width: 20px;"></i>
                    <span>Subjects</span>
                </a>
            <?php elseif ($role === 'faculty'): ?>
                <a href="<?php echo $backPath; ?>faculty/dashboard.php" class="nav-link-custom <?php echo $currentPage === 'dashboard.php' && $currentDir === 'faculty' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-chart-pie" style="width: 20px;"></i>
                    <span>Dashboard</span>
                </a>
                <a href="<?php echo $backPath; ?>faculty/upload.php" class="nav-link-custom <?php echo $currentPage === 'upload.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-cloud-arrow-up" style="width: 20px;"></i>
                    <span>Upload Resource</span>
                </a>
                <a href="<?php echo $backPath; ?>faculty/manage.php" class="nav-link-custom <?php echo $currentPage === 'manage.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-folder-open" style="width: 20px;"></i>
                    <span>My Uploads</span>
                </a>
                <a href="<?php echo $backPath; ?>faculty/check_submissions.php" class="nav-link-custom <?php echo $currentPage === 'check_submissions.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-file-invoice" style="width: 20px;"></i>
                    <span>Student Submissions</span>
                </a>
            <?php elseif ($role === 'student'): ?>
                <a href="<?php echo $backPath; ?>student/dashboard.php" class="nav-link-custom <?php echo $currentPage === 'dashboard.php' && $currentDir === 'student' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-search" style="width: 20px;"></i>
                    <span>Find Materials</span>
                </a>
                <a href="<?php echo $backPath; ?>student/bookmarks.php" class="nav-link-custom <?php echo $currentPage === 'bookmarks.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-bookmark" style="width: 20px;"></i>
                    <span>Bookmarks</span>
                </a>
                <a href="<?php echo $backPath; ?>student/stats.php" class="nav-link-custom <?php echo $currentPage === 'stats.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-clock-rotate-left" style="width: 20px;"></i>
                    <span>My Downloads</span>
                </a>
            <?php endif; ?>
        </nav>

        <div class="user-profile-widget">
            <div class="bg-primary text-white d-flex align-items-center justify-content-center fw-bold rounded-circle" style="width: 38px; height: 38px; font-size: 0.9rem;">
                <?php 
                    $initials = '';
                    $parts = explode(' ', $userName);
                    foreach ($parts as $p) $initials .= strtoupper(substr($p, 0, 1));
                    echo substr($initials, 0, 2);
                ?>
            </div>
            <div style="overflow: hidden; flex-grow: 1;">
                <div class="text-white fw-semibold small text-truncate" title="<?php echo htmlspecialchars($userName); ?>">
                    <?php echo htmlspecialchars($userName); ?>
                </div>
                <div class="text-secondary" style="font-size: 0.75rem; text-transform: uppercase;">
                    <?php echo htmlspecialchars($role); ?>
                </div>
            </div>
            <a href="<?php echo $backPath; ?>logout.php" class="btn btn-outline-danger border-0 p-1 btn-sm" title="Log Out">
                <i class="fa-solid fa-power-off"></i>
            </a>
        </div>
    </aside>

    <!-- Main Workspace Container -->
    <main class="workspace">
        <!-- Top Toolbar -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 fw-bold mb-0 text-white">
                    <?php if (isset($pageIcon)): ?>
                        <i class="<?php echo $pageIcon; ?> me-2 text-primary"></i>
                    <?php endif; ?>
                    <?php echo $pageTitle ?? 'Study Materials'; ?>
                </h1>
            </div>
            
            <button class="btn btn-outline-secondary border-glass d-lg-none text-light" id="sidebar-hamburger">
                <i class="fa-solid fa-bars"></i>
            </button>
        </div>
        
        <!-- Page contents -->
