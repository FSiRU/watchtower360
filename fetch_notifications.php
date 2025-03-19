<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) { // Fixed syntax error here
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

// Fetch notifications for the user based on their role
$sql = "SELECT id, title, message, is_read, created_at FROM notifications WHERE user_id = ? AND user_role = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $user_id, $user_role);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

$stmt->close();
$conn->close();

echo json_encode($notifications);
?>