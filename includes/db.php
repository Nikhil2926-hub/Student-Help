<?php
// includes/db.php
// Setup MySQL database connection parameters

$host = '127.0.0.1';
$db   = 'study_material_db';
$user = 'root';
$pass = ''; // Leave blank for standard dev stack (XAMPP/WampServer)
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     // If database is not created yet, try connecting directly to server to check
     try {
         $tempPdo = new PDO("mysql:host=$host", $user, $pass);
         $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `$db`;");
         // Retry connection
         $pdo = new PDO($dsn, $user, $pass, $options);
     } catch (\PDOException $err) {
         die("MySQL Database Connection Failed: " . $err->getMessage());
     }
}
