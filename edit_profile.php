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
$sql = "SELECT full_name, email, phone, address, user_role, profile_picture, password FROM users WHERE id = ?";
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

// ✅ Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $old_password = trim($_POST['old_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);
    $profile_picture = $user['profile_picture'];

    // Validate required fields
    if (empty($full_name) || empty($email) || empty($phone) || empty($address)) {
        $_SESSION['error'] = "All fields are required!";
        header("Location: edit_profile.php");
        exit();
    }

    // ✅ Handle profile picture upload
    if (!empty($_FILES['profile_picture']['name'])) {
        $upload_dir = "uploads/";
        $profile_picture = basename($_FILES["profile_picture"]["name"]);
        $target_file = $upload_dir . $profile_picture;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Allow only JPG, PNG files
        if (!in_array($imageFileType, ['jpg', 'jpeg', 'png'])) {
            $_SESSION['error'] = "Only JPG, JPEG, and PNG files are allowed!";
            header("Location: edit_profile.php");
            exit();
        }

        // Move uploaded file
        move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file);
    }

    // ✅ Handle password change
    if (!empty($old_password) && !empty($new_password) && !empty($confirm_password)) {
        if (!password_verify($old_password, $user['password'])) {
            $_SESSION['error'] = "Incorrect old password!";
            header("Location: edit_profile.php");
            exit();
        }

        if ($new_password !== $confirm_password) {
            $_SESSION['error'] = "New passwords do not match!";
            header("Location: edit_profile.php");
            exit();
        }

        // Hash new password
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

        // Update password in database
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $user_id);
        $stmt->execute();
        $stmt->close();
    }

    // ✅ Email verification if changed
    if ($email !== $user['email']) {
        $_SESSION['verify_email'] = $email;
        $_SESSION['old_email'] = $user['email'];
        header("Location: verify_email.php");  // Redirect to verification process
        exit();
    }

    // ✅ Update user details in database
    $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ?, profile_picture = ? WHERE id = ?");
    $stmt->bind_param("sssssi", $full_name, $email, $phone, $address, $profile_picture, $user_id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Profile updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating profile!";
    }

    $stmt->close();
    $conn->close();

    header("Location: profile.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa, #c3cfe2);
            font-family: 'Arial', sans-serif;
        }

        .container {
            max-width: 600px;
            margin: auto;
            padding: 20px;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .profile-pic {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 15px;
            border: 3px solid #007bff;
            transition: transform 0.3s ease;
        }

        .profile-pic:hover {
            transform: scale(1.1);
        }

        .alert {
            text-align: center;
            animation: slideDown 0.5s ease-in-out;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
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

        .form-control {
            border-radius: 10px;
            padding: 10px;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 8px rgba(0, 123, 255, 0.3);
        }

        .btn-primary {
            background: #007bff;
            border: none;
            border-radius: 10px;
            padding: 10px;
            font-weight: bold;
            transition: 0.3s;
        }

        .btn-primary:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }

        .file-upload {
            position: relative;
            display: inline-block;
            cursor: pointer;
        }

        .file-upload input[type="file"] {
            display: none;
        }

        .file-upload-label {
            background: #007bff;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: 0.3s;
        }

        .file-upload-label:hover {
            background: #0056b3;
        }
    </style>
</head>
<body class="container mt-5">

    <!-- Back to Dashboard Button -->
    <a href="resident_dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>

    <h2 class="text-center mb-4">Edit Profile</h2>

    <!-- Success & Error Messages -->
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="text-center">
            <img src="uploads/<?php echo htmlspecialchars($user['profile_picture'] ?? 'default.png'); ?>" class="profile-pic">
            <div class="file-upload">
                <label for="profile-picture" class="file-upload-label">
                    <i class="fas fa-camera"></i> Change Photo
                </label>
                <input type="file" name="profile_picture" id="profile-picture" accept="image/*">
            </div>
        </div>

        <div class="mb-3">
            <label>Full Name</label>
            <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
        </div>

        <div class="mb-3">
            <label>Email</label>
            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
        </div>

        <div class="mb-3">
            <label>Phone</label>
            <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
        </div>

        <div class="mb-3">
            <label>Address</label>
            <input type="text" name="address" class="form-control" value="<?php echo htmlspecialchars($user['address']); ?>" required>
        </div>

        <div class="mb-3">
            <label>Old Password</label>
            <input type="password" name="old_password" class="form-control">
        </div>

        <div class="mb-3">
            <label>New Password</label>
            <input type="password" name="new_password" class="form-control">
        </div>

        <div class="mb-3">
            <label>Confirm Password</label>
            <input type="password" name="confirm_password" class="form-control">
        </div>

        <button type="submit" class="btn btn-primary w-100">Save Changes</button>
    </form>

    <script>
        // Add interactivity to the file upload button
        document.getElementById('profile-picture').addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.querySelector('.profile-pic').src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>