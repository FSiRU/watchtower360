<?php
session_start();

// Ensure only admin can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
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

// Fetch statistics
$totalUsers = $conn->query("SELECT COUNT(*) AS count FROM users")->fetch_assoc()['count'];
$totalIncidents = $conn->query("SELECT COUNT(*) AS count FROM incidents")->fetch_assoc()['count'];
$pendingIncidents = $conn->query("SELECT COUNT(*) AS count FROM incidents WHERE status='pending'")->fetch_assoc()['count'];
$resolvedIncidents = $conn->query("SELECT COUNT(*) AS count FROM incidents WHERE status='resolved'")->fetch_assoc()['count'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap');

        body {
            font-family: 'Poppins', sans-serif;
            background: #f4f4f9;
            margin: 0;
            display: flex;
        }
        .sidebar {
            text-align: center;
            width: 250px;
            background: #007BFF;
            color: white;
            height: 100vh;
            padding-top: 20px;
            position: fixed;
        }
        .sidebar a {
            display: block;
            padding: 15px;
            color: white;
            text-decoration: none;
            transition: 0.3s;
        }
        .sidebar a:hover { background: #0056b3; }
        .content {
            margin-left: 270px;
            padding: 20px;
            width: 100%;
            position: relative;
        }
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            color: #0056b3;
        }
        .top-bar h1 {
            margin: 0;
        }
        .toggle-dark {
            padding: 10px 15px;
            background: #333;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: 0.3s;
        }
        .toggle-dark:hover { background: #555; }
        .card-container {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        .card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            flex: 1;
            min-width: 180px;
            text-align: center;
            transition: transform 0.2s ease-in-out;
        }
        .card:hover {
            transform: scale(1.05);
        }
        .card h3 { 
            margin: 5px 0; 
            color: #007BFF; 
            font-size: 16px; 
        }
        .card p { 
            font-size: 22px; 
            font-weight: bold; 
        }
        .table-container { 
            margin-top: 20px;
            color: #0056b3;
         }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            background: white; 
        }
        th, td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }
        th { 
            background: #007BFF; 
            color: white; 
            cursor: pointer; 
        }
        th:hover { 
            background: #0056b3; 
        }
        .chart-container {
            margin: 20px 0;
            padding: 15px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2 class="text-center">Admin Panel</h2>
        <a href="#"><i class="fas fa-chart-line"></i> Dashboard</a>
        <a href="manage_users.php"><i class="fas fa-users"></i> Manage Users</a>
        <a href="manage_incidents.php"><i class="fas fa-exclamation-triangle"></i> Manage Incidents</a>
        <a href="hotzones.php">🌍 Hotspot Areas</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="content">
        <div class="top-bar">
            <h1>Welcome, Admin 👋</h1>
        </div>

        <div class="card-container">
            <div class="card"><h3>Total Users</h3><p><?php echo $totalUsers; ?></p></div>
            <div class="card"><h3>Total Incidents</h3><p><?php echo $totalIncidents; ?></p></div>
            <div class="card"><h3>Pending Cases</h3><p><?php echo $pendingIncidents; ?></p></div>
            <div class="card"><h3>Resolved Cases</h3><p><?php echo $resolvedIncidents; ?></p></div>
        </div>

        <div class="chart-container">
            <canvas id="incidentChart"></canvas>
        </div>

        <div class="table-container">
            <h2>Recent Incidents</h2>
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Location</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $incidents = $conn->query("SELECT * FROM incidents ORDER BY date_time DESC LIMIT 5");
                    if ($incidents->num_rows > 0) {
                        while ($row = $incidents->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['type']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['location']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['date_time']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['status']) . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' style='text-align: center;'>No incidents found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>

        new Chart(document.getElementById('incidentChart'), {
            type: 'bar',
            data: {
                labels: ['Total Incidents', 'Pending Cases', 'Resolved'],
                datasets: [{
                    label: 'Incident Reports',
                    data: [<?php echo $totalIncidents; ?>, <?php echo $pendingIncidents; ?>, <?php echo $resolvedIncidents; ?>],
                    backgroundColor: ['#007BFF', '#FFA500', '#28A745']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    </script>
</body>
</html>

<?php $conn->close(); ?>
