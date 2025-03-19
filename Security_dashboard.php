<?php
// Ensure session starts at the very beginning
session_start();

// Debugging session
error_log("Session ID: " . session_id());
error_log("User ID: " . ($_SESSION['user_id'] ?? 'Not set'));
error_log("User Role: " . ($_SESSION['user_role'] ?? 'Not set'));

// Redirect if not logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'security') {
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
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch statistics
$totalIncidents = $conn->query("SELECT COUNT(*) AS count FROM incidents")->fetch_assoc()['count'] ?? 0;
$pendingIncidents = $conn->query("SELECT COUNT(*) AS count FROM incidents WHERE status='pending'")->fetch_assoc()['count'] ?? 0;
$resolvedIncidents = $conn->query("SELECT COUNT(*) AS count FROM incidents WHERE status='resolved'")->fetch_assoc()['count'] ?? 0;

// Fetch the number of notifications
$notification_count = 0;
$notification_sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND user_role = 'security' AND is_read = 0";
$notification_stmt = $conn->prepare($notification_sql);
$notification_stmt->bind_param("i", $_SESSION['user_id']);
$notification_stmt->execute();
$notification_result = $notification_stmt->get_result();

if ($notification_result->num_rows > 0) {
    $notification_row = $notification_result->fetch_assoc();
    $notification_count = $notification_row['count'];
}

$notification_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js" defer></script>
    
    <style>
                @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap');

                body {
                    font-family: 'Poppins', sans-serif;
                    background: #f4f4f9;
                    margin: 0;
                    display: flex;
                }

                /* Sidebar Styling */
                .sidebar {
                    width: 260px;
                    background: linear-gradient(135deg, #28a745, #218838);
                    color: white;
                    height: 100vh;
                    padding-top: 20px;
                    position: fixed;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    box-shadow: 2px 0px 10px rgba(0, 0, 0, 0.1);
                }

                /* Sidebar Header */
                .sidebar h2 {
                    font-size: 22px;
                    text-align: center;
                    padding: 15px 0;
                    width: 100%;
                    background: rgba(0, 0, 0, 0.1);
                    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
                    margin: 0;
                }

                /* Sidebar Links */
                .sidebar a {
                    display: flex;
                    align-items: center;
                    width: 100%;
                    padding: 12px 20px;
                    color: white;
                    text-decoration: none;
                    transition: all 0.3s ease-in-out;
                    font-size: 16px;
                }

                /* Sidebar Icons */
                .sidebar a i {
                    margin-right: 12px;
                }

                /* Hover Effect */
                .sidebar a:hover {
                    background: rgba(255, 255, 255, 0.2);
                    padding-left: 25px;
                }

                /* Content */
                .content {
                    margin-left: 260px;
                    padding: 20px;
                    width: 100%;
                    display: flex;
                    flex-direction: column;
                }

                /* Notification Icon */
                .notification-icon {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    cursor: pointer;
                    font-size: 24px;
                    color: #007BFF;
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

                /* Cards Animation */
                .card-container {
                    display: flex;
                    gap: 20px;
                    flex-wrap: wrap;
                }

                .card {
                    background: white;
                    padding: 15px;
                    border-radius: 8px;
                    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
                    flex: 1;
                    min-width: 200px;
                    text-align: center;
                    transform: translateY(20px) scale(0.9);
                    opacity: 0;
                    animation: fadeInBounce 0.8s ease-out forwards;
                }

                .card:nth-child(1) { animation-delay: 0.2s; }
                .card:nth-child(2) { animation-delay: 0.4s; }
                .card:nth-child(3) { animation-delay: 0.6s; }

                .card:hover {
                    transform: scale(1.05);
                    transition: all 0.3s ease-in-out;
                    box-shadow: 0px 8px 15px rgba(0, 0, 0, 0.2);
                }

                @keyframes fadeInBounce {
                    0% { transform: translateY(20px) scale(0.9); opacity: 0; }
                    50% { transform: translateY(-5px) scale(1.02); opacity: 1; }
                    100% { transform: translateY(0) scale(1); opacity: 1; }
                }

                .card h3 {
                    margin: 0;
                    color: #007BFF;
                }

                /* Chart */
                .chart-container {
                    margin: 20px 0;
                    padding: 15px;
                    background: white;
                    border-radius: 10px;
                    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
                    width: 100%;
                    height: 250px;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <h2 class="text-center">Security Panel</h2>
        <a href="#">🏠 Home</a>
        <a href="Reports.php">📜 View Reports</a>
        <a href="hotzones.php">🌍 Hotspot Areas</a>
        <a href="logout.php">🚪 Logout</a>
    </div>

    <!-- Main Content -->
    <div class="content">
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

        <h1>Welcome, Security Officer</h1>

        <!-- Stats Cards -->
        <div class="card-container">
            <div class="card"><h3>Total Incidents</h3><p><?php echo $totalIncidents; ?></p></div>
            <div class="card"><h3>Pending Cases</h3><p><?php echo $pendingIncidents; ?></p></div>
            <div class="card"><h3>Resolved Cases</h3><p><?php echo $resolvedIncidents; ?></p></div>
        </div>

        <!-- Chart -->
        <div class="chart-container">
            <canvas id="incidentChart"></canvas>
        </div>
    </div>

    <script>
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

        // Chart
        new Chart(document.getElementById('incidentChart'), {
            type: 'bar',
            data: {
                labels: ['Total', 'Pending', 'Resolved'],
                datasets: [{
                    label: 'Incidents',
                    data: [<?php echo $totalIncidents; ?>, <?php echo $pendingIncidents; ?>, <?php echo $resolvedIncidents; ?>],
                    backgroundColor: ['#007BFF', '#FFA500', '#28A745']
                }]
            }
        });
    </script>

</body>
</html>

<?php $conn->close(); ?>