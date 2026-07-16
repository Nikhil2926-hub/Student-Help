<?php
// includes/auth.php
// Session configuration and multi-role authentication gates.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ----------------------------------------------------
// Admin Auth Helpers
// ----------------------------------------------------
function isAdminLoggedIn() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin' && isset($_SESSION['admin_id']);
}

function requireAdmin() {
    if (!isAdminLoggedIn()) {
        header("Location: ../login.php");
        exit();
    }
}

// ----------------------------------------------------
// Faculty Auth Helpers
// ----------------------------------------------------
function isFacultyLoggedIn() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'faculty' && isset($_SESSION['faculty_id']);
}

function requireFaculty() {
    if (!isFacultyLoggedIn()) {
        header("Location: ../login.php");
        exit();
    }
}

// ----------------------------------------------------
// Student Auth Helpers
// ----------------------------------------------------
function isStudentLoggedIn() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'student' && isset($_SESSION['student_id']);
}

function requireStudent() {
    if (!isStudentLoggedIn()) {
        header("Location: ../login.php");
        exit();
    }
}

// ----------------------------------------------------
// General Auth helpers
// ----------------------------------------------------
function isLoggedIn() {
    return isset($_SESSION['role']) && isset($_SESSION['user_id']);
}

function redirectIfLoggedIn() {
    if (isset($_SESSION['role'])) {
        if ($_SESSION['role'] === 'admin') {
            header("Location: admin/dashboard.php");
            exit();
        } elseif ($_SESSION['role'] === 'faculty') {
            header("Location: faculty/dashboard.php");
            exit();
        } elseif ($_SESSION['role'] === 'student') {
            header("Location: student/dashboard.php");
            exit();
        }
    }
}
