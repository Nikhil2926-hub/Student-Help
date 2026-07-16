<?php
// register.php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

redirectIfLoggedIn();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enrollment_no = trim($_POST['enrollment_no'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $semester = intval($_POST['semester'] ?? 1);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($enrollment_no) || empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        try {
            // Check if Enrollment Number already registered
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE enrollment_no = ?");
            $stmt->execute([$enrollment_no]);
            if ($stmt->fetchColumn() > 0) {
                $error = 'Enrollment number is already registered.';
            } else {
                // Check if email already registered
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetchColumn() > 0) {
                    $error = 'Email address is already registered.';
                } else {
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO students (name, email, enrollment_no, semester, password_hash) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$name, $email, $enrollment_no, $semester, $password_hash]);
                    
                    header("Location: login.php?registered=1");
                    exit();
                }
            }
        } catch (PDOException $e) {
            $error = 'Registration failed: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration - Study Material Management System</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="d-flex align-items-center justify-content-center py-5">

<div class="container" style="max-width: 520px;">
    <div class="glass-card p-4 p-md-5">
        <div class="text-center mb-4">
            <span class="d-inline-flex bg-primary bg-opacity-10 text-primary p-3 rounded-4 mb-3">
                <i class="fa-solid fa-user-plus fa-2x"></i>
            </span>
            <h2 class="fw-bold mb-1">Student Registration</h2>
            <p class="text-secondary small">Sign up to access and bookmark student learning materials</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" role="alert">
                <i class="fa-solid fa-circle-exclamation"></i>
                <div><?php echo htmlspecialchars($error); ?></div>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <!-- Enrollment No -->
            <div class="mb-3">
                <label class="form-label text-secondary small fw-semibold">Enrollment Number</label>
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-glass text-secondary">
                        <i class="fa-solid fa-id-card"></i>
                    </span>
                    <input type="text" class="form-control" name="enrollment_no" required placeholder="e.g. EN19CS301025" value="<?php echo isset($enrollment_no) ? htmlspecialchars($enrollment_no) : ''; ?>">
                </div>
            </div>

            <!-- Full Name -->
            <div class="mb-3">
                <label class="form-label text-secondary small fw-semibold">Full Name</label>
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-glass text-secondary">
                        <i class="fa-solid fa-user"></i>
                    </span>
                    <input type="text" class="form-control" name="name" required placeholder="Professor/Student John Doe" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>">
                </div>
            </div>

            <!-- Email Address -->
            <div class="mb-3">
                <label class="form-label text-secondary small fw-semibold">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-glass text-secondary">
                        <i class="fa-solid fa-envelope"></i>
                    </span>
                    <input type="email" class="form-control" name="email" required placeholder="name@college.edu" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                </div>
            </div>

            <!-- Semester Selection -->
            <div class="mb-3">
                <label class="form-label text-secondary small fw-semibold">Current Semester</label>
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-glass text-secondary">
                        <i class="fa-solid fa-layer-group"></i>
                    </span>
                    <select class="form-select" name="semester" required>
                        <?php for($i=1; $i<=10; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo (isset($semester) && $semester == $i) ? 'selected' : ''; ?>>Semester <?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>

            <!-- Password -->
            <div class="row g-2 mb-4">
                <div class="col-md-6">
                    <label class="form-label text-secondary small fw-semibold">Password</label>
                    <div class="input-group password-group">
                        <input type="password" class="form-control" name="password" required placeholder="Min 6 chars">
                        <button class="btn btn-outline-secondary border-glass text-secondary toggle-password" type="button">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-secondary small fw-semibold">Confirm Password</label>
                    <div class="input-group password-group">
                        <input type="password" class="form-control" name="confirm_password" required placeholder="Re-enter password">
                        <button class="btn btn-outline-secondary border-glass text-secondary toggle-password" type="button">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary-custom w-100 py-3">
                <i class="fa-solid fa-user-plus me-2"></i> Register Account
            </button>
        </form>

        <div class="text-center mt-4 pt-1">
            <span class="text-secondary small">Already have an account?</span> 
            <a href="login.php" class="text-primary small fw-semibold text-decoration-none ms-1">Log in here</a>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
