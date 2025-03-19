<?php
session_start();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "watchtower360";
$port = 3306;

$conn = new mysqli($servername, $username, $password, $dbname, (int) $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $_SESSION['error'] = "All fields are required!";
        header("Location: login.php");
        exit();
    }

    $stmt = $conn->prepare("SELECT id, full_name, password, user_role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $full_name, $hashedPassword, $user_role);
        $stmt->fetch();

        if (password_verify($password, $hashedPassword)) {
            $_SESSION['user_id'] = $id;
            $_SESSION['full_name'] = $full_name;  // Store full_name properly
            $_SESSION['user_role'] = $user_role;

            if ($user_role === "admin") {
                header("Location: Admin_dashboard.php");
            } elseif ($user_role === "security") {
                header("Location: Security_dashboard.php");
            } else {
                header("Location: Resident_dashboard.php");
            }
            exit();
        } else {
            $_SESSION['error'] = "Incorrect password!";
        }
    } else {
        $_SESSION['error'] = "Email not found!";
    }
    header("Location: login.php");
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Watchtower360 - Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
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
        .login-container {
            display: flex;
            max-width: 900px;
            width: 100%;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.15);
            transition: transform 0.3s ease-in-out;
        }
        .login-container:hover {
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
        .not-member {
            position: absolute;
            bottom: 20px;
            font-size: 14px;
        }
        .not-member a {
            text-decoration: none;
            color: #ccc;
            transition: color 0.3s ease-in-out;
        }
        .not-member a:hover {
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
        .input-group {
            position: relative;
            margin-bottom: 20px;
        }
        .input-group input {
            width: 100%;
            padding: 12px;
            padding-left: 40px;
            background: #f5f5f5;
            border: 1px solid #ccc;
            border-radius: 8px;
            outline: none;
            transition: all 0.3s ease;
        }
        .input-group input:focus {
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
        .login-btn {
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
        .login-btn:hover {
            background: #444;
            transform: scale(1.05);
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
                max-width: 95%;
            }
            .left-panel {
                width: 100%;
                padding: 20px;
            }
            .right-panel {
                width: 100%;
                padding: 30px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="left-panel">
            <h1>WatchTower360 🙂</h1>
            <div class="not-member">
                Not a member? <a href="Registration.php">Register</a>
            </div>
        </div>

        <div class="right-panel">
            <h2>Login</h2>
            <?php if (isset($_GET['success'])): ?>
                <p style="color: green; text-align: center;">✔ Registration successful! Please log in.</p>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <p style="color: red; text-align: center;">⚠️ <?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" placeholder="Email" required>
                </div>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <button type="submit" name="login" class="login-btn">Login</button>
            </form>
        </div>
    </div>
</body>
</html>