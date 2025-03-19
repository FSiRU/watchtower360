<?php
session_start();

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure only admin can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
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

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch incidents securely with error handling
$sql = "SELECT id, title, type, location, date_time, urgency, status, assigned_to 
        FROM incidents 
        ORDER BY date_time DESC";

$result = $conn->query($sql);

if (!$result) {
    die("Query failed: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Incidents</title>

    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        /* General Page Styling */
        body {
            font-family: 'Poppins', sans-serif;
            background: #f4f4f9;
            margin: 0;
            padding: 0;
            opacity: 0;
            animation: fadeIn 1s forwards;
        }

        /* Container */
        .container {
            width: 95%;
            max-width: 1100px;
            margin: 20px auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        /* Back Button */
        .back-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            background: #007BFF;
            color: white;
            padding: 10px 20px;
            border-radius: 30px;
            text-decoration: none;
            font-size: 14px;
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .back-btn:hover {
            background: #0056b3;
        }

        .back-btn i {
            transition: transform 0.3s ease;
        }

        .back-btn:hover i {
            transform: translateX(-5px);
        }

        /* Page Heading */
        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
            animation: fadeInUp 0.8s ease-in-out;
        }

        /* Table Styling */
        .table-container {
            overflow-x: auto;
            border-radius: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        th, td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #007BFF;
            color: white;
            position: sticky;
            top: 0;
            z-index: 2;
        }

        /* Row Hover Effect */
        tbody tr:hover {
            background-color: #f1f1f1;
            transition: 0.3s;
        }

        /* Status Badges */
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: bold;
            display: inline-block;
            text-align: center;
        }

        .status-pending {
            background: red;
            color: white;
        }

        .status-reviewed {
            background: orange;
            color: white;
        }

        .status-resolved {
            background: green;
            color: white;
        }

        /* Delete Button */
        .delete-btn {
            background: #ff4d4d;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
            transition: 0.3s;
        }

        .delete-btn i {
            margin-right: 5px;
        }

        .delete-btn:hover {
            background: #cc0000;
            box-shadow: 0px 0px 10px rgba(255, 77, 77, 0.7);
        }

        /* Floating Action Button */
        .add-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #28A745;
            color: white;
            padding: 15px;
            border-radius: 50%;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);
            font-size: 20px;
            cursor: pointer;
            transition: 0.3s;
        }

        .add-btn:hover {
            background: #1e7e34;
            transform: scale(1.1);
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>

<body>

    <div class="container">

        <!-- Back Button -->
        <a href="admin_dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Go Back
        </a>

        <h2>Manage Incidents</h2>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Location</th>
                        <th>Date & Time</th>
                        <th>Urgency</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $count = 1; while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $count++ ?></td>
                        <td><?= htmlspecialchars($row['title']) ?></td>
                        <td><?= htmlspecialchars($row['type']) ?></td>
                        <td><?= htmlspecialchars($row['location']) ?></td>
                        <td><?= htmlspecialchars($row['date_time']) ?></td>
                        <td><?= htmlspecialchars($row['urgency']) ?></td>
                        <td><span class="status-badge status-<?= strtolower($row['status']) ?>">
                            <?= htmlspecialchars($row['status']) ?>
                        </span></td>
                        <td>
                            <button class="delete-btn" onclick="deleteIncident(<?= $row['id'] ?>)">
                                <i class="fas fa-trash-alt"></i> Delete
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <button class="add-btn">+</button>

    </div>

    <script>
    function deleteIncident(id) {
        if (confirm("Are you sure you want to delete this incident?")) {
            fetch("delete_incident.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: "id=" + id
            })
            .then(response => response.text())
            .then(data => {
                if (data.trim() === "success") {
                    alert("Incident deleted successfully!");
                    location.reload(); // Reload page to update incident list
                } else {
                    alert("Failed to delete incident. Please try again.");
                }
            })
            .catch(error => console.error("Error:", error));
        }
    }
</script>


</body>
</html>

<?php $conn->close(); ?>
