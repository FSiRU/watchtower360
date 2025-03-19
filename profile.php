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

// Fetch user details
$user_id = $_SESSION['user_id'];

$sql = "SELECT full_name, email, phone, address, user_role, profile_picture FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// ✅ Check if user exists
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    die("User not found!");
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        /* Smooth Fade-in on Page Load */
        body {
            opacity: 0;
            transition: opacity 1s ease-in-out;
            background-color: #e2e8f0;
        }

        .main-body {
            padding: 15px;
        }

        /* Profile Section Animations */
        .card {
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1), 0 1px 2px rgba(0, 0, 0, 0.06);
            transform: translateY(50px);
            opacity: 0;
            transition: transform 0.8s ease-out, opacity 0.8s ease-in;
        }

        /* Profile Picture Hover Effect */
        .profile-pic {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 15px;
            transition: transform 0.3s ease-in-out;
        }

        .profile-pic:hover {
            transform: scale(1.1);
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
        }

        /* Button Hover Effect */
        .btn {
            transition: transform 0.2s ease-in-out;
        }

        .btn:hover {
            transform: scale(1.05);
        }

        /* Dark Mode */
        .dark-mode {
            background-color: #222;
            color: white;
        }

        .dark-mode .card {
            background-color: #333;
            border-color: #555;
            box-shadow: 2px 2px 15px rgba(255, 255, 255, 0.1);
        }

        .dark-mode .btn {
            background-color: white;
            color: black;
        }

        /* Dark Mode Toggle Button */
        .dark-mode-toggle {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: black;
            color: white;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
            transition: transform 0.3s ease-in-out;
        }

        .dark-mode-toggle:hover {
            transform: scale(1.1);
        }
    </style>
</head>
<body>

<div class="container">
    <div class="main-body">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="main-breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="resident_dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">User Profile</li>
            </ol>
        </nav>

        <div class="row gutters-sm">
            <div class="col-md-4 mb-3">
                <div class="card">
                    <div class="card-body text-center">
                        <img src="<?php echo !empty($user['profile_picture']) ? 'uploads/' . htmlspecialchars($user['profile_picture']) : 'uploads/default.png'; ?>" 
                             alt="Profile Picture" class="profile-pic">
                        <div class="mt-3">
                            <h4><?php echo htmlspecialchars($user['full_name']); ?></h4>
                            <p class="text-secondary mb-1"><?php echo ucfirst($user['user_role']); ?></p>
                            <p class="text-muted font-size-sm"><?php echo htmlspecialchars($user['address']); ?></p>
                            <a href="edit_profile.php" class="btn btn-primary">Edit Profile</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-sm-3"><h6 class="mb-0">Full Name</h6></div>
                            <div class="col-sm-9 text-secondary"><?php echo htmlspecialchars($user['full_name']); ?></div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-sm-3"><h6 class="mb-0">Email</h6></div>
                            <div class="col-sm-9 text-secondary"><?php echo htmlspecialchars($user['email']); ?></div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-sm-3"><h6 class="mb-0">Phone</h6></div>
                            <div class="col-sm-9 text-secondary"><?php echo htmlspecialchars($user['phone']); ?></div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-sm-3"><h6 class="mb-0">Address</h6></div>
                            <div class="col-sm-9 text-secondary"><?php echo htmlspecialchars($user['address']); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Profile Completion Box (Now Same Size as Other Sections) -->
                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="d-flex align-items-center mb-3">Profile Completion</h6>
                        <small>Account Setup</small>
                        <div class="progress mb-3">
                            <div class="progress-bar bg-primary" role="progressbar" style="width: 85%;" aria-valuenow="85" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <small>Security</small>
                        <div class="progress mb-3">
                            <div class="progress-bar bg-primary" role="progressbar" style="width: 70%;" aria-valuenow="70" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <small>Incident Reports</small>
                        <div class="progress mb-3">
                            <div class="progress-bar bg-primary" role="progressbar" style="width: 75%;" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Dark Mode Toggle Button -->
<button class="dark-mode-toggle" onclick="toggleDarkMode()">🌙 Dark Mode</button>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        document.body.style.opacity = "1";
        let cards = document.querySelectorAll('.card');
        cards.forEach((card, index) => {
            setTimeout(() => {
                card.style.transform = "translateY(0)";
                card.style.opacity = "1";
            }, index * 150);
        });
    });

    function toggleDarkMode() {
        document.body.classList.toggle("dark-mode");
        localStorage.setItem("darkMode", document.body.classList.contains("dark-mode") ? "enabled" : "disabled");
    }
</script>

</body>
</html>
