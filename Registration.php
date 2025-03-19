<?php
// Enable error reporting for debugging
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Database connection
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "watchtower360";
$port = 3306; // Updated for consistency

$conn = new mysqli($servername, $username, $password, $dbname, (int) $port);

// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize message
$message = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST["full_name"]);
    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone"]);
    $password = $_POST["password"];
    $address = trim($_POST["address"]);
    $user_role = trim($_POST["user_role"]);

    // Basic validation
    if (empty($full_name) || empty($email) || empty($phone) || empty($password) || empty($address) || empty($user_role)) {
        $message = "All fields are required!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format!";
    } elseif (!preg_match("/^(\+254|0)[1-9][0-9]{8}$/", $phone)) {
        $message = "Enter a valid Kenyan phone number!";
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters long!";
    } else {
        // Check if email already exists
        $checkEmail = $conn->prepare("SELECT email FROM users WHERE email = ?");
        $checkEmail->bind_param("s", $email);
        $checkEmail->execute();
        $checkEmail->store_result();

        if ($checkEmail->num_rows > 0) {
            $message = "Email already exists!";
        } else {
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Insert user into database
            $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone, password, address, user_role) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $full_name, $email, $phone, $hashedPassword, $address, $user_role);

            if ($stmt->execute()) {
                // Redirect with success message
                header("Location: login.php?success=1");
                exit();
            } else {
                $message = "Error: " . $stmt->error;
            }
            $stmt->close();
        }
        $checkEmail->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Watchtower360 - Register</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        /* Global Styles */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: #f5f5f5;
            color: #000;
            animation: fadeIn 0.6s ease-in-out;
        }

        .register-container {
            display: flex;
            max-width: 900px;
            width: 100%;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.15);
            transition: transform 0.3s ease-in-out;
        }

        .register-container:hover {
            transform: scale(1.01);
        }

        .left-panel {
            width: 40%;
            background: linear-gradient(135deg, #000, #444);
            color: #fff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 30px;
            position: relative;
        }

        .left-panel h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .already-member {
            position: absolute;
            bottom: 20px;
            font-size: 14px;
        }

        .already-member a {
            text-decoration: none;
            color: #ccc;
            transition: color 0.3s ease-in-out;
        }

        .already-member a:hover {
            color: #fff;
        }

        .right-panel {
            width: 60%;
            padding: 40px;
        }

        .right-panel h2 {
            text-align: center;
            color: #000;
            margin-bottom: 20px;
        }

        .input-group, .select-group {
            position: relative;
            margin-bottom: 20px;
        }

        .input-group input, .select-group select {
            width: 100%;
            padding: 12px;
            padding-left: 40px;
            background: #f5f5f5;
            border: 1px solid #ccc;
            border-radius: 8px;
            outline: none;
            color: #000;
            transition: all 0.3s ease;
        }

        .input-group input:focus, .select-group select:focus {
            border-color: #000;
            box-shadow: 0 0 8px rgba(0, 0, 0, 0.2);
        }

        .input-group i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }

        .register-btn {
            width: 100%;
            padding: 12px;
            background: #000;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            color: #fff;
            transition: transform 0.3s ease, background 0.3s ease;
        }

        .register-btn:hover {
            background: #444;
            transform: scale(1.05);
        }

        .error-message {
            text-align: center;
            color: red;
            margin-bottom: 10px;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 768px) {
            .register-container {
                flex-direction: column;
                max-width: 95%;
            }
            .left-panel, .right-panel {
                width: 100%;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="left-panel">
            <h1>WatchTower360 🙂</h1>
            <div class="already-member">
                Already a Member? <a href="login.php">Log in</a>
            </div>
        </div>

        <div class="right-panel">
            <h2>Register</h2>
            <?php if (!empty($message)) { echo "<p class='error-message'>$message</p>"; } ?>
            <form action="" method="POST">
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" name="full_name" placeholder="Full Name" required>
                </div>
                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" placeholder="Email" required>
                </div>
                <div class="input-group">
                    <i class="fas fa-phone"></i>
                    <input type="text" name="phone" placeholder="Phone" required>
                </div>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <div class="input-group">
                    <i class="fas fa-home"></i>
                    <input type="text" name="address" placeholder="Address" required>
                </div>
                <div class="select-group">
                    <select name="user_role" required>
                        <option value="">Select Role</option>
                        <option value="Resident">Resident</option>
                        <option value="Security">Security</option>
                        <option value="Admin">Admin</option>
                    </select>
                </div>
                <button type="submit" class="register-btn">Register Now</button>
            </form>
        </div>
    </div>
</body>
</html>
