<?php
// index.php
require_once __DIR__ . '/includes/auth.php';

redirectIfLoggedIn();

header("Location: login.php");
exit();
