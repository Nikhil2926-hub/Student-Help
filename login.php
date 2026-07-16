<?php
// login.php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

redirectIfLoggedIn();

$error = '';
$success = '';

if (isset($_GET['registered'])) {
    $success = 'Student registration successful! You can now log in.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? 'student';
    $email_or_enroll = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email_or_enroll) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        try {
            if ($role === 'admin') {
                $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
                $stmt->execute([$email_or_enroll]);
                $user = $stmt->fetch();
                if ($user && password_verify($password, $user['password_hash'])) {
                    $_SESSION['role'] = 'admin';
                    $_SESSION['admin_id'] = $user['id'];
                    $_SESSION['admin_name'] = $user['name'];
                    header("Location: admin/dashboard.php");
                    exit();
                }
            } elseif ($role === 'faculty') {
                $stmt = $pdo->prepare("SELECT * FROM faculty WHERE email = ?");
                $stmt->execute([$email_or_enroll]);
                $user = $stmt->fetch();
                if ($user && password_verify($password, $user['password_hash'])) {
                    $_SESSION['role'] = 'faculty';
                    $_SESSION['faculty_id'] = $user['id'];
                    $_SESSION['faculty_name'] = $user['name'];
                    header("Location: faculty/dashboard.php");
                    exit();
                }
            } elseif ($role === 'student') {
                // Students can log in with Enrollment No OR Email Address!
                $stmt = $pdo->prepare("SELECT * FROM students WHERE email = ? OR enrollment_no = ?");
                $stmt->execute([$email_or_enroll, $email_or_enroll]);
                $user = $stmt->fetch();
                if ($user && password_verify($password, $user['password_hash'])) {
                    $_SESSION['role'] = 'student';
                    $_SESSION['student_id'] = $user['id'];
                    $_SESSION['student_name'] = $user['name'];
                    $_SESSION['student_semester'] = $user['semester'];
                    header("Location: student/dashboard.php");
                    exit();
                }
            }
            $error = 'Invalid credentials. Please verify your entries.';
        } catch (PDOException $e) {
            $error = 'A database error occurred: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Study Material Management System</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="d-flex align-items-center justify-content-center py-5">

<div class="container" style="max-width: 480px;">
    <div class="glass-card p-4 p-md-5">
        <div class="text-center mb-4">
            <span class="d-inline-flex bg-primary bg-opacity-10 text-primary p-3 rounded-4 mb-3">
                <i class="fa-solid fa-graduation-cap fa-2x"></i>
            </span>
            <h2 class="fw-bold mb-1">Welcome Back</h2>
            <p class="text-secondary small">Access the Study Material Management System</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" role="alert">
                <i class="fa-solid fa-circle-exclamation"></i>
                <div><?php echo htmlspecialchars($error); ?></div>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success d-flex align-items-center gap-2 mb-4" role="alert">
                <i class="fa-solid fa-circle-check"></i>
                <div><?php echo htmlspecialchars($success); ?></div>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <!-- Role Selector -->
            <div class="mb-3">
                <label class="form-label text-secondary small fw-semibold">Login Role</label>
                <div class="row g-2">
                    <div class="col-4">
                        <input type="radio" class="btn-check" name="role" id="role-student" value="student" checked>
                        <label class="btn btn-outline-secondary w-100 py-2 border-glass text-light" style="font-size: 0.85rem;" for="role-student">
                            <i class="fa-solid fa-user-graduate d-block mb-1"></i> Student
                        </label>
                    </div>
                    <div class="col-4">
                        <input type="radio" class="btn-check" name="role" id="role-faculty" value="faculty">
                        <label class="btn btn-outline-secondary w-100 py-2 border-glass text-light" style="font-size: 0.85rem;" for="role-faculty">
                            <i class="fa-solid fa-chalkboard-user d-block mb-1"></i> Faculty
                        </label>
                    </div>
                    <div class="col-4">
                        <input type="radio" class="btn-check" name="role" id="role-admin" value="admin">
                        <label class="btn btn-outline-secondary w-100 py-2 border-glass text-light" style="font-size: 0.85rem;" for="role-admin">
                            <i class="fa-solid fa-user-shield d-block mb-1"></i> Admin
                        </label>
                    </div>
                </div>
            </div>

            <!-- Email / Enrollment Number -->
            <div class="mb-3">
                <label class="form-label text-secondary small fw-semibold" id="username-label">Enrollment No. / Email</label>
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-glass text-secondary">
                        <i class="fa-solid fa-user"></i>
                    </span>
                    <input type="text" class="form-control" name="username" id="username-input" required placeholder="Enter Enrollment No or Email">
                </div>
            </div>

            <!-- Password -->
            <div class="mb-4">
                <label class="form-label text-secondary small fw-semibold">Password</label>
                <div class="input-group password-group">
                    <span class="input-group-text bg-transparent border-glass text-secondary">
                        <i class="fa-solid fa-lock"></i>
                    </span>
                    <input type="password" class="form-control" name="password" required placeholder="••••••••">
                    <button class="btn btn-outline-secondary border-glass text-secondary toggle-password" type="button">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary-custom w-100 py-3">
                <i class="fa-solid fa-right-to-bracket me-2"></i> Log In
            </button>
        </form>

        <div class="text-center mt-4 pt-1" id="signup-redirect">
            <span class="text-secondary small">Are you a Student?</span> 
            <a href="register.php" class="text-primary small fw-semibold text-decoration-none ms-1">Register here</a>
        </div>
    </div>
</div>

<script>
    // Dynamically adjust username label and registration redirect based on role
    const studentRadio = document.getElementById('role-student');
    const facultyRadio = document.getElementById('role-faculty');
    const adminRadio = document.getElementById('role-admin');
    const usernameLabel = document.getElementById('username-label');
    const usernameInput = document.getElementById('username-input');
    const signupRedirect = document.getElementById('signup-redirect');

    function updateFormDetails() {
        if (studentRadio.checked) {
            usernameLabel.textContent = "Enrollment Number / Email";
            usernameInput.placeholder = "Enter Enrollment No or Email";
            signupRedirect.style.display = 'block';
        } else if (facultyRadio.checked) {
            usernameLabel.textContent = "Email Address";
            usernameInput.placeholder = "Enter Email ID";
            signupRedirect.style.display = 'none'; // Faculty register is disabled!
        } else if (adminRadio.checked) {
            usernameLabel.textContent = "Admin Email Address";
            usernameInput.placeholder = "Enter Email ID";
            signupRedirect.style.display = 'none';
        }
    }

    [studentRadio, facultyRadio, adminRadio].forEach(radio => {
        radio.addEventListener('change', updateFormDetails);
    });

    // Run once on load to sync placeholder on browser refilling
    updateFormDetails();
</script>
<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
