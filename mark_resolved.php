<?php
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "watchtower360";
$port = 3306;

$conn = new mysqli($servername, $username, $password, $dbname, (int)$port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$id = $_POST['id'];
$sql = "UPDATE incidents SET status = 'resolved' WHERE id = $id";

if ($conn->query($sql)) {
    echo "success";
} else {
    echo "error";
}
$conn->close();
?>