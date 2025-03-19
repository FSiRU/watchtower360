<?php
session_start();

// Debugging: Print session data (Remove or comment out in production)
// echo '<pre>';
// print_r($_SESSION);
// echo '</pre>';

// Redirect if not logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'resident') {
    header("Location: login.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "watchtower360";
$port = 3306;

$conn = new mysqli($servername, $username, $password, $dbname, (int)$port);

// Check database connection
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("Database connection failed. Please try again later.");
}

// Fetch the latest full name from the database if not already set or changed
$user_id = $_SESSION['user_id'];
$sql = "SELECT full_name FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    if (!isset($_SESSION['full_name']) || $_SESSION['full_name'] !== $row['full_name']) {
        $_SESSION['full_name'] = $row['full_name'];
    }
}

// Fetch the number of notifications
$notification_count = 0;
$notification_sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND user_role = 'resident' AND is_read = 0";
$notification_stmt = $conn->prepare($notification_sql);
$notification_stmt->bind_param("i", $user_id);
$notification_stmt->execute();
$notification_result = $notification_stmt->get_result();

if ($notification_result->num_rows > 0) {
    $notification_row = $notification_result->fetch_assoc();
    $notification_count = $notification_row['count'];
}

$stmt->close();
$notification_stmt->close();
$conn->close();

// Set the full name for display
$full_name = isset($_SESSION['full_name']) ? htmlspecialchars($_SESSION['full_name']) : 'Guest';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resident Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js" defer></script>

    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            height: 100vh;
            background: #343a40;
            color: white;
            position: fixed;
            left: -250px;
            top: 0;
            transition: left 0.3s ease-in-out;
            padding-top: 20px;
            z-index: 1000; /* Ensures sidebar is on top */
        }

        .sidebar a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 12px 20px;
            border-bottom: 1px solid #495057;
        }

        .sidebar a:hover {
            background: #495057;
        }

        .menu-btn {
            position: absolute;
            left: 280px;
            top: 15px;
            cursor: pointer;
            font-size: 22px;
            color: white;
            padding: 5px 15px;
            background: #007bff;
            border-radius: 5px;
        }

        /* Content Shift */
        .content {
            margin-left: 0;
            transition: margin-left 0.3s ease-in-out;
            padding: 20px;
        }

        /* When sidebar is open */
        .sidebar.open {
            left: 0;
        }

        .content.shift {
            margin-left: 250px;
        }

        /* Card Styling */
        .container {
            display: flex;
            justify-content: center;
            gap: 30px;
            flex-wrap: wrap;
            margin-top: 40px;
        }

        .card {
            width: 300px;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            color: white;
            font-weight: bold;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            transition: transform 0.2s;
        }

        .card:hover {
            transform: scale(1.05);
        }

        /* Card Colors */
        .blue { background-color: red; }
        .green { background-color: #28a745; }
        .yellow { background-color: #ffc107; color: black; }

        /* Buttons */
        button {
            background-color: white;
            color: black;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
        }

        button:hover {
            background-color: #ddd;
        }

        /* Notification Badge */
        .notification-icon {
            position: fixed;
            top: 20px;
            right: 20px;
            cursor: pointer;
            font-size: 24px;
            color: #007bff;
        }

        .notification-badge {
            position: absolute;
            top: -10px;
            right: -10px;
            background: red;
            color: white;
            padding: 5px 10px;
            border-radius: 50%;
            font-size: 14px;
        }

        .notification-item.unread {
            background-color: #f8f9fa;
            border-left: 4px solid #007bff;
            padding-left: 10px;
        }

        .notification-item.read {
            background-color: #e9ecef;
            border-left: 4px solid #6c757d;
            padding-left: 10px;
            opacity: 0.7;
        }
        </style>
</head>
<body>
    <!-- Sidebar and Content -->
    <div class="sidebar" id="sidebar">
        <span class="menu-btn" onclick="toggleSidebar()">☰</span>
        <h3 class="text-center">Resident Panel</h3>
        <a href="#">🏠 Home</a>
        <a href="report_incident.php">🚨 Report Incident</a>
        <a href="view_reports.php">📜 View Reports</a>
        <a href="hotzones.php">🌍 Hotspot Areas</a>
        <a href="profile.php">👤 Profile</a>
        <a href="logout.php">🚪 Logout</a>
    </div>

    <div class="content" id="content">
        <!-- Notification Icon -->
        <div class="notification-icon" onclick="fetchNotifications()">
            🔔
            <span class="notification-badge" id="notificationBadge"><?php echo $notification_count; ?></span>
        </div>

        <!-- Notification Modal -->
        <div class="modal fade" id="notificationModal" tabindex="-1" aria-labelledby="notificationModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="notificationModalLabel">Notifications</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="notificationList">
                        <!-- Notifications will be loaded here -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="container">
            <h2 class="mb-4">Welcome, <?php echo $full_name; ?> 👋</h2>
            <div class="row">
                <div class="col-md-4">
                    <div class="card blue">
                        <h5>Report an Incident</h5>
                        <p>Quickly report any security issue</p>
                        <a href="report_incident.php" class="btn btn-light mt-2">Report Now</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card green">
                        <h5>View Reports</h5>
                        <p>Check the status of your reports</p>
                        <a href="view_reports.php" class="btn btn-light mt-2">View Reports</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card yellow">
                        <h5>Emergency Contacts</h5>
                        <p>Quick access to security</p>
                        <a href="contacts.php" class="btn btn-light mt-2">View Contacts</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        // Initialize OneSignal
        window.OneSignalDeferred = window.OneSignalDeferred || [];
        OneSignalDeferred.push(async function(OneSignal) {
            await OneSignal.init({
                appId: "a60a9794-fcce-404e-a238-eeb93b9e1c09", // Replace with your OneSignal App ID
            });

            // Optional: Show the notification bell icon
            OneSignal.Notifications.addEventListener('permissionChange', (permission) => {
                if (permission === 'granted') {
                    OneSignal.Notifications.requestPermission();
                }
            });

            // Optional: Handle user subscription
            OneSignal.User.pushSubscription.addEventListener('change', (subscription) => {
                if (subscription) {
                    const playerId = subscription.id;
                    console.log("User subscribed with Player ID:", playerId);
                    // Send the Player ID to your server for future use
                    fetch('store_player_id.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ player_id: playerId }),
                    });
                }
            });
        });

        function toggleSidebar() {
            let sidebar = document.getElementById("sidebar");
            let content = document.getElementById("content");

            if (sidebar.classList.contains("open")) {
                sidebar.classList.remove("open");
                content.classList.remove("shift");
            } else {
                sidebar.classList.add("open");
                content.classList.add("shift");
            }
        }

        // Fetch notifications and display them in the modal
        function fetchNotifications() {
            fetch('fetch_notifications.php')
                .then(response => response.json())
                .then(data => {
                    const notificationList = document.getElementById('notificationList');
                    notificationList.innerHTML = '';

                    if (data.length > 0) {
                        data.forEach(notification => {
                            const notificationItem = document.createElement('div');
                            notificationItem.className = `notification-item mb-2 p-2 ${notification.is_read ? 'read' : 'unread'}`;
                            notificationItem.innerHTML = `
                                <strong>${notification.title}</strong><br>
                                <small>${notification.message}</small>
                                <hr>
                            `;
                            notificationList.appendChild(notificationItem);
                        });

                        // Mark notifications as read
                        fetch('mark_notifications_read.php', { method: 'POST' })
                            .then(response => response.json())
                            .then(result => {
                                if (result.status === 'success') {
                                    document.getElementById('notificationBadge').textContent = 0;
                                }
                            });
                    } else {
                        notificationList.innerHTML = '<p>No new notifications.</p>';
                    }

                    // Show the modal
                    const notificationModal = new bootstrap.Modal(document.getElementById('notificationModal'));
                    notificationModal.show();
                })
                .catch(error => console.error('Error fetching notifications:', error));
        }
    </script>

</body>
</html>