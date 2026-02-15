<?php
session_start(); // Start session to remember the logged-in user
include 'config/db.php';

$error = "";

if (isset($_POST['login_btn'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    // 1. Get the user record based on Username ONLY
    $query = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $stored_pass = $row['password'];

        // 2. HYBRID CHECK: Supports both Hashed (New) and Plain Text (Old) passwords
        $login_success = false;

        // Check A: Is it a secure hash? (For Methsara)
        if (password_verify($password, $stored_pass)) {
            $login_success = true;
        }
        // Check B: Is it plain text? (For 'user' / 1234)
        else if ($password === $stored_pass) {
            $login_success = true;
        }

        if ($login_success) {
            // 3. Set Session Variables
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];

            // 4. Redirect based on Role
            if ($row['role'] == 'admin') {
                header("Location: admin/dashboard.php");
            } else {
                // If you haven't built staff/dashboard.php yet, send them to admin for now
                // or create a simple 'staff/dashboard.php' later.
                header("Location: admin/dashboard.php");
            }
            exit();
        } else {
            $error = "❌ Incorrect Password!";
        }
    } else {
        $error = "❌ User not found!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>TrackFlow Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <div class="login-card">
        <div class="logo">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </svg>
        </div>

        <h1>TrackFlow</h1>
        <div class="subtitle">Inventory Management System</div>

        <form action="index.php" method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" placeholder="Enter username" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Enter password" required>
            </div>

            <button type="submit" name="login_btn">Sign In</button>

            <?php if ($error != "") { ?>
                <p style="color: #ef4444; margin-top: 15px; font-weight: 600; font-size: 14px;"><?php echo $error; ?></p>
            <?php } ?>
        </form>

        <div class="footer-text">
            &copy; 2026 NSBM Green University Project
        </div>
    </div>
</body>

</html>