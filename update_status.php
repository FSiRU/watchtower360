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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id'])) {
    $incident_id = intval($_POST['id']);
    $sql = "UPDATE incidents SET status = 'reviewed' WHERE id = $incident_id AND status = 'pending'";
    if ($conn->query($sql) === TRUE) {
        echo "success";
    } else {
        echo "error";
    }
}
$conn->close();
?>
