<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "watchtower360";
$port = 3306;

$conn = new mysqli($servername, $username, $password, $dbname, $port);

// Check database connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// Mark all notifications as read for the user based on their role
$sql = "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND user_role = ? AND is_read = 0";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $user_id, $user_role);
$stmt->execute();

$stmt->close();
$conn->close();

echo json_encode(['status' => 'success']);
?>