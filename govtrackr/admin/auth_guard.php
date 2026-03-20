<?php
// admin/auth_guard.php — include at top of every admin page
session_start();
require_once '../config.php';
$conn = getDatabaseConnection();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php"); exit();
}
