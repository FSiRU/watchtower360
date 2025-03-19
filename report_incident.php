<?php
session_start();

// Autoload Swift Mailer classes
require 'vendor/autoload.php';

use Swift_SmtpTransport as SmtpTransport;
use Swift_Mailer as Mailer;
use Swift_Message as Message;
use Swift_Attachment as Attachment;

// Database connection
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "watchtower360";
$port = 3306;

$conn = new mysqli($servername, $username, $password, $dbname, (int) $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure user is logged in before proceeding
if (!isset($_SESSION['user_id'])) {
    die("<script>alert('Error: User not logged in!'); window.location.href='login.php';</script>");
}
$user_id = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $type = trim($_POST['type']);
    $location = trim($_POST['location']);
    $date_time = $_POST['date_time'];
    $description = trim($_POST['description']);
    $urgency = trim($_POST['urgency']);
    $status = "pending";

    // Extract latitude & longitude from location input
    $latitude = $longitude = NULL;
    if (preg_match('/Lat:\s*([\d.-]+),\s*Lng:\s*([\d.-]+)/', $location, $matches)) {
        $latitude = (float)$matches[1];
        $longitude = (float)$matches[2];
    }

    // Handle file uploads
    $media_paths = [];
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'mp4', 'mov', 'mp3', 'wav'];

    if (!empty($_FILES['media']['name'][0])) {
        $upload_dir = "uploads/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        foreach ($_FILES['media']['name'] as $key => $filename) {
            $file_tmp = $_FILES['media']['tmp_name'][$key];
            $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (in_array($file_ext, $allowed_extensions)) {
                $new_filename = uniqid("media_") . "." . $file_ext;
                $file_path = $upload_dir . $new_filename;

                if (move_uploaded_file($file_tmp, $file_path)) {
                    $media_paths[] = $file_path;
                } else {
                    echo "<script>alert('Error uploading file: $filename');</script>";
                }
            } else {
                echo "<script>alert('Invalid file type: $filename');</script>";
            }
        }
    }

    $media = !empty($media_paths) ? implode(",", $media_paths) : NULL;

    // Insert incident into the database
    $stmt = $conn->prepare("INSERT INTO incidents 
        (user_id, title, type, location, latitude, longitude, date_time, description, media, urgency, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssddsssss", $user_id, $title, $type, $location, $latitude, $longitude, $date_time, $description, $media, $urgency, $status);

    // After the incident is successfully inserted into the database
    if ($stmt->execute()) {
        // Insert a notification for the resident
        $notification_message = "A new incident has been reported: $title";
        $notification_sql = "INSERT INTO notifications (user_id, user_role, title, message, is_read) VALUES (?, ?, ?, ?, 0)";
        $notification_stmt = $conn->prepare($notification_sql);

        // Store the role in a variable
        $user_role = 'resident'; // Variable for the role
        $notification_stmt->bind_param("isss", $user_id, $user_role, $title, $notification_message);
        $notification_stmt->execute();
        $notification_stmt->close();

        // Insert a notification for all security personnel
        $security_sql = "SELECT id FROM users WHERE role = 'security'";
        $security_result = $conn->query($security_sql);

        if ($security_result->num_rows > 0) {
            while ($row = $security_result->fetch_assoc()) {
                $security_user_id = $row['id'];
                $notification_stmt = $conn->prepare($notification_sql);

                // Store the role in a variable
                $user_role = 'security'; // Variable for the role
                $notification_stmt->bind_param("isss", $security_user_id, $user_role, $title, $notification_message);
                $notification_stmt->execute();
                $notification_stmt->close();
            }
        }

        // Send push notification using OneSignal
        sendPushNotification($title, $notification_message);

        // Send email alerts to all users (existing code)
        // ...
    }

    if ($stmt->execute()) {
        // Send email alerts to all users
        $result = $conn->query("SELECT email FROM users");
        if ($result->num_rows > 0) {
            $subject = "New Incident Reported: $title";
            $body = "
                <h2>New Incident Reported in Your Neighborhood</h2>
                <p><strong>Type:</strong> $type</p>
                <p><strong>Location:</strong> $location</p>
                <p><strong>Urgency:</strong> $urgency</p>
                <p><strong>Description:</strong> $description</p>
                <p><strong>Reported At:</strong> $date_time</p>
                <p>Please stay alert and take necessary precautions.</p>
            ";
    
            while ($row = $result->fetch_assoc()) {
                sendEmailAlert($row['email'], $subject, $body, $media_paths);
            }
        }
        echo "<script>alert('Incident reported successfully!'); window.location.href='Resident_dashboard.php';</script>";
    } else {
        echo "<script>alert('Error reporting incident. Please try again.');</script>";
    }

    $stmt->close();
    $conn->close();
}

// Email Alert Function using Swift Mailer
function sendEmailAlert($recipientEmail, $subject, $body, $media_paths) {
    // Create the SMTP transport
    $transport = (new SmtpTransport('smtp.gmail.com', 587, 'tls'))
        ->setUsername('sirufanuel@gmail.com') // Your email
        ->setPassword('ddrs wwfi lhzr xbsg'); // Your app password

    // Create the Mailer using the created Transport
    $mailer = new Mailer($transport);

    // Create the message
    $message = (new Message($subject))
        ->setFrom(['sirufanuel@gmail.com' => 'WatchTower360'])
        ->setTo([$recipientEmail])
        ->setBody($body, 'text/html'); // Set email body as HTML

    // Attach media files
    if (!empty($media_paths)) {
        foreach ($media_paths as $media_path) {
            $file_ext = strtolower(pathinfo($media_path, PATHINFO_EXTENSION));

            // If the file is an image, embed it in the email body
            if (in_array($file_ext, ['jpg', 'jpeg', 'png'])) {
                $cid = basename($media_path); // Unique content ID
                $message->embed(Attachment::fromPath($media_path)->setDisposition('inline'), $cid);
                $body .= "<br><img src='cid:$cid' alt='Incident Image'>";
            } else {
                // Attach other file types (e.g., videos, audio) as regular attachments
                $message->attach(Attachment::fromPath($media_path));
            }
        }
    }

    // Send the message
    try {
        $mailer->send($message);
    } catch (Exception $e) {
        error_log("Email could not be sent. Error: " . $e->getMessage());
    }
}

// Push Notification Function using OneSignal
function sendPushNotification($title, $message) {
    $appId = "a60a9794-fcce-404e-a238-eeb93b9e1c09"; // Replace with your OneSignal App ID
    $apiKey = "YOUR_REST_API_KEY"; // Replace with your OneSignal REST API Key

    $content = [
        "en" => $message, // English message
    ];

    $fields = [
        'app_id' => $appId,
        'included_segments' => ['All'], // Send to all subscribers
        'contents' => $content,
        'headings' => ['en' => $title], // Notification title
    ];

    $fields = json_encode($fields);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json; charset=utf-8',
        'Authorization: Basic ' . $apiKey,
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report an Incident</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/leaflet.css"/>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap');

        body {
            background: url("https://raw.githubusercontent.com/FSiRU/MKU/main/incident%20form.jpg") no-repeat center center fixed;
            background-size: cover;
            margin: 0;
            font-family: 'Poppins', sans-serif;
            font-size: 16px;
        }

        body::before {
            content: "";
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(10px);
            z-index: -1;
        }

        .container {
            max-width: 500px;
            margin: 50px auto;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            padding: 30px;
            color: white;
        }

        .back-buttons {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .back-buttons a {
            display: inline-block;
            padding: 10px 15px;
            background: #007BFF;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
        }

        .back-buttons a:hover { background: #0056b3; }

        input, select, textarea {
            display: block;
            width: 100%;
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #007BFF, #0056b3);
            border: none;
            color: white;
            font-size: 16px;
            cursor: pointer;
            border-radius: 5px;
        }

        #map {
            height: 300px;
            margin-bottom: 15px;
            border-radius: 5px;
        }

        select {
            background-color: #ffffff;
            color: #000000;
            border: 1px solid #ccc;
            padding: 5px;
            font-size: 16px;
        }

        select option {
            background-color: #ffffff;
            color: #000000;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="back-buttons">
            <a href="Resident_dashboard.php"><i class="fas fa-home"></i> Resident Dashboard</a>
            <a href="view_reports.php"><i class="fas fa-file-alt"></i> View Reports</a>
        </div>

        <h2>Report an Incident</h2>
        <form action="" method="POST" enctype="multipart/form-data">
            <label for="title">Incident Title:</label>
            <input type="text" id="title" name="title" required>

            <label for="type">Incident Type:</label>
            <select id="type" name="type">
                <option value="Theft">Theft</option>
                <option value="Vandalism">Vandalism</option>
                <option value="Suspicious Activity">Suspicious Activity</option>
                <option value="Assault">Assault</option>
                <option value="Fire">Fire</option>
                <option value="Other">Other</option>
            </select>

            <label for="location">Location:</label>
            <input type="text" id="location" name="location" required>
            <div id="map"></div>

            <label for="date_time">Date & Time of Incident:</label>
            <input type="datetime-local" id="date_time" name="date_time" required>

            <label for="description">Incident Description:</label>
            <textarea id="description" name="description" rows="4" required></textarea>

            <label for="media">Upload Evidence:</label>
            <input type="file" id="media" name="media[]" multiple accept="image/*,video/*,audio/*">

            <label for="urgency">Urgency Level:</label>
            <select id="urgency" name="urgency">
                <option value="High">High</option>
                <option value="Medium">Medium</option>
                <option value="Low">Low</option>
            </select>

            <button type="submit">Submit Report</button>
        </form>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/leaflet.js"></script>
    <script>
        // Initialize Map
        var map = L.map('map').setView([0, 0], 2); // Default view before location is found

        // Load OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        var marker;

        // Function to update the marker and input field
        function updateLocation(lat, lng) {
            if (marker) {
                map.removeLayer(marker);
            }
            marker = L.marker([lat, lng]).addTo(map);
            document.getElementById("location").value = `Lat: ${lat}, Lng: ${lng}`;
        }

        // Try to get user's current location with a timeout
        function getLocationWithTimeout() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function (position) {
                        var lat = position.coords.latitude;
                        var lng = position.coords.longitude;

                        map.setView([lat, lng], 15); // Center map on user
                        updateLocation(lat, lng);
                    },
                    function (error) {
                        console.warn("Geolocation error: " + error.message);
                    },
                    { timeout: 5000 } // Timeout after 5 seconds
                );
            } else {
                alert("Geolocation is not supported by your browser.");
            }
        }

        // Run geolocation after short delay to avoid blank map
        setTimeout(getLocationWithTimeout, 1000);

        // Allow user to click on the map to update location
        map.on('click', function (e) {
            updateLocation(e.latlng.lat, e.latlng.lng);
        });
    </script>
</body>
</html>