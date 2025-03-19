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

// Fetch users securely using prepared statements
$stmt = $conn->prepare("SELECT id, full_name, email, user_role FROM users ORDER BY user_role ASC");
$stmt->execute();
$users = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f4f4f9;
            margin: 0;
            opacity: 0;
            animation: fadeIn 1s forwards;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .container {
            width: 90%;
            max-width: 1000px;
            margin: 20px auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .back-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            background: #007BFF;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            transition: 0.3s;
        }

        .back-btn:hover {
            background: #0056b3;
        }

        h2 {
            text-align: center;
            color: #333;
        }

        .search-box {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            transition: 0.3s;
        }

        .search-box:focus {
            border-color: #007BFF;
            box-shadow: 0px 0px 8px rgba(0, 123, 255, 0.6);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        th {
            background: #007BFF;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        tr:nth-child(odd) {
            background-color: #ffffff;
        }

        tr:hover {
            background-color: #e0e0e0;
        }

        .delete-btn {
            background: red;
            color: white;
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: 0.3s;
        }

        .delete-btn:hover {
            background: darkred;
            transform: scale(1.1);
        }

        .role-badge {
            padding: 5px 10px;
            border-radius: 15px;
            color: white;
            font-size: 12px;
        }

        .resident { background: green; }
        .security { background: blue; }
        .admin { background: red; }
    </style>
</head>
<body>
    <div class="container">
        <a href="admin_dashboard.php" class="back-btn">Go Back to Dashboard</a>
        <h2>Manage Users</h2>
        <input type="text" id="search" class="search-box" placeholder="Search users..." onkeyup="filterUsers()">
        <table>
            <thead>
                <tr>
                    <th onclick="sortTable(0)">Name</th>
                    <th onclick="sortTable(1)">Email</th>
                    <th onclick="sortTable(2)">Role</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="user-list">
                <?php while ($row = $users->fetch_assoc()): ?>
                    <tr>
                        <td contenteditable="true" onblur="editUser(<?= $row['id'] ?>, 'full_name', this.innerText)"><?= htmlspecialchars($row['full_name']) ?></td>
                        <td contenteditable="true" onblur="editUser(<?= $row['id'] ?>, 'email', this.innerText)"><?= htmlspecialchars($row['email']) ?></td>
                        <td>
                            <span class="role-badge <?= $row['user_role'] ?>"> <?= ucfirst($row['user_role']) ?> </span>
                        </td>
                        <td>
                            <button class="delete-btn" onclick="deleteUser(this, <?= $row['id'] ?>)"><i class="fas fa-trash-alt"></i> Delete</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <script>
function deleteUser(button, userId) {
    if (confirm("Are you sure you want to delete this user?")) {
        fetch("delete_user.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: "id=" + userId,
        })
        .then(response => response.text())
        .then(data => {
            if (data.trim() === "success") {
                button.closest("tr").remove(); // Remove the deleted row
                alert("User deleted successfully.");
            } else {
                alert("Error deleting user.");
            }
        })
        .catch(error => console.error("Error:", error));
    }
}
</script>

</body>
</html>

<?php $conn->close(); ?>
