<?php
session_start();

// Ensure only admin can perform this action
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo "unauthorized";
    exit();
}

$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "watchtower360";
$port = 3306;

$conn = new mysqli($servername, $username, $password, $dbname, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user ID is provided
if (!isset($_POST['id']) || empty($_POST['id'])) {
    echo "error";
    exit();
}

$user_id = intval($_POST['id']);

// Use prepared statement to delete the user
$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    echo "success";
} else {
    echo "error";
}

// Close connections
$stmt->close();
$conn->close();
?>
