<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
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

// ✅ Check database connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// ✅ Fetch security personnel from the database
$sql = "SELECT full_name, email, phone FROM users WHERE user_role = 'security'";
$result = $conn->query($sql);

$security_users = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $security_users[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Contacts</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa, #c3cfe2);
            font-family: 'Arial', sans-serif;
        }

        .container {
            max-width: 800px;
            margin: auto;
            padding: 20px;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            animation: fadeIn 1s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .back-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            background: #007bff;
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: 0.3s;
        }

        .back-btn:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }

        h2 {
            color: #333;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .table {
            margin-top: 20px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .table thead {
            background: #007bff;
            color: white;
        }

        .table th, .table td {
            padding: 12px;
            text-align: left;
        }

        .table tbody tr {
            transition: background 0.3s ease;
        }

        .table tbody tr:hover {
            background: rgba(0, 123, 255, 0.1);
        }

        .no-data {
            text-align: center;
            color: #666;
            font-style: italic;
            margin-top: 20px;
        }

        .contact-icon {
            margin-right: 8px;
            color: #007bff;
        }

        .btn-call, .btn-email {
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 14px;
            transition: 0.3s;
        }

        .btn-call {
            background: #28a745;
            color: white;
            border: none;
        }

        .btn-call:hover {
            background: #218838;
        }

        .btn-email {
            background: #17a2b8;
            color: white;
            border: none;
        }

        .btn-email:hover {
            background: #138496;
        }
    </style>
</head>
<body class="container mt-5">

    <!-- Back to Dashboard Button -->
    <a href="resident_dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>

    <h2 class="text-center">Security Personnel Contacts</h2>

    <?php if (!empty($security_users)): ?>
        <table class="table">
            <thead>
                <tr>
                    <th><i class="fas fa-user contact-icon"></i> Name</th>
                    <th><i class="fas fa-envelope contact-icon"></i> Email</th>
                    <th><i class="fas fa-phone contact-icon"></i> Phone</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($security_users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['phone']); ?></td>
                        <td>
                            <a href="tel:<?php echo htmlspecialchars($user['phone']); ?>" class="btn btn-call">
                                <i class="fas fa-phone"></i> Call
                            </a>
                            <a href="mailto:<?php echo htmlspecialchars($user['email']); ?>" class="btn btn-email">
                                <i class="fas fa-envelope"></i> Email
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="no-data">No security personnel available.</p>
    <?php endif; ?>

</body>
</html>