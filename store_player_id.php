<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "watchtower360";
$port = 3306;

$conn = new mysqli($servername, $username, $password, $dbname, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the Player ID from the request
$data = json_decode(file_get_contents('php://input'), true);
$player_id = $data['player_id'];
$user_id = $_SESSION['user_id'];

// Store the Player ID in the database
$sql = "UPDATE users SET onesignal_player_id = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $player_id, $user_id);
$stmt->execute();
$stmt->close();
$conn->close();
?>