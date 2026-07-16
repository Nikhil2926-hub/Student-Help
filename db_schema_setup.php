<?php
// db_schema_setup.php
// Setup MySQL database and tables for the Study Material Management System.

// Default connection settings (empty password is standard for local XAMPP/WampServer)
$host = '127.0.0.1';
$user = 'root';
$pass = '';

try {
    // Create PDO connection to MySQL server directly
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create DB
    $pdo->exec("CREATE DATABASE IF NOT EXISTS study_material_db;");
    $pdo->exec("USE study_material_db;");
    
    echo "Database 'study_material_db' created/selected successfully.<br>";

    // 1. Admins Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");
    echo "Table 'admins' verified.<br>";

    // 2. Faculty Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS faculty (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        department VARCHAR(100) DEFAULT '',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");
    echo "Table 'faculty' verified.<br>";

    // 3. Students Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS students (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        enrollment_no VARCHAR(50) UNIQUE NOT NULL,
        semester INT NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");
    echo "Table 'students' verified.<br>";

    // 4. Subjects Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS subjects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        subject_name VARCHAR(150) NOT NULL,
        subject_code VARCHAR(50) UNIQUE NOT NULL,
        semester INT NOT NULL
    ) ENGINE=InnoDB;");
    echo "Table 'subjects' verified.<br>";

    // 5. Materials Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS materials (
        id INT AUTO_INCREMENT PRIMARY KEY,
        faculty_id INT NOT NULL,
        subject_id INT NOT NULL,
        category VARCHAR(50) NOT NULL,
        semester INT NOT NULL,
        title VARCHAR(200) NOT NULL,
        description TEXT,
        file_path VARCHAR(255),
        file_name VARCHAR(255),
        file_size INT,
        video_url VARCHAR(255),
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (faculty_id) REFERENCES faculty(id) ON DELETE CASCADE,
        FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;");
    echo "Table 'materials' verified.<br>";

    // 6. Bookmarks Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS bookmarks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        material_id INT NOT NULL,
        bookmarked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE CASCADE,
        UNIQUE KEY student_bookmark (student_id, material_id)
    ) ENGINE=InnoDB;");
    echo "Table 'bookmarks' verified.<br>";

    // 7. Submissions Table (Students submitting assignments)
    $pdo->exec("CREATE TABLE IF NOT EXISTS submissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        material_id INT NOT NULL,
        file_path VARCHAR(255) NOT NULL,
        file_name VARCHAR(255) NOT NULL,
        submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;");
    echo "Table 'submissions' verified.<br>";

    // 8. Download History Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS download_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        material_id INT NOT NULL,
        downloaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;");
    echo "Table 'download_history' verified.<br>";

    // Seed default Admin
    $adminEmail = 'balar.n.0480@gmail.com';
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE email = ?");
    $stmt->execute([$adminEmail]);
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("DELETE FROM admins WHERE email = 'admin@college.edu'");
        $adminPass = password_hash('CFvgbhnj12#', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO admins (name, email, password_hash) VALUES (?, ?, ?)");
        $stmt->execute(["System Administrator", $adminEmail, $adminPass]);
        echo "Default Administrator dynamic credentials seeded (balar.n.0480@gmail.com / CFvgbhnj12#).<br>";
    }

    // Seed default Faculty
    $facEmail = 'faculty@college.edu';
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM faculty WHERE email = ?");
    $stmt->execute([$facEmail]);
    if ($stmt->fetchColumn() == 0) {
        $facPass = password_hash('faculty123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO faculty (name, email, password_hash, department) VALUES (?, ?, ?, ?)");
        $stmt->execute(["Professor Watson", $facEmail, $facPass, "IMSC IT"]);
        echo "Default Faculty credentials seeded (faculty@college.edu / faculty123).<br>";
    }
    
    // Seed some test subjects (needed for dynamic material upload)
    $stmt = $pdo->query("SELECT COUNT(*) FROM subjects");
    if ($stmt->fetchColumn() == 0) {
        $subjects = [
            ["Data Structures & Algorithms", "CS-301", 3],
            ["Object Oriented Programming", "CS-302", 3],
            ["Discrete Mathematics", "CS-303", 3],
            ["Database Management Systems", "CS-501", 5],
            ["Computer Networks", "CS-502", 5],
            ["Operating Systems", "CS-503", 5],
            ["Software Engineering", "CS-601", 6]
        ];
        
        $insStmt = $pdo->prepare("INSERT INTO subjects (subject_name, subject_code, semester) VALUES (?, ?, ?)");
        foreach ($subjects as $s) {
            $insStmt->execute($s);
        }
        echo "Default subjects seeded (Data Structures, DBMS, Operating Systems, etc.).<br>";
    }

    echo "<h3>Setup completed successfully!</h3>";
} catch (PDOException $e) {
    die("Database setup failed: " . $e->getMessage());
}
