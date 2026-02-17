<?php
session_start();
include 'config/db.php';

// 1. Initialize the variable to stop the Warning
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        if (password_verify($password, $row['password'])) {
            if ($row['role'] == 'Admin') {
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['role'] = 'Admin';
                header("Location: admin/dashboard.php");
                exit();
            } else {
                $error = "🚫 Access Denied! You are Staff, not Admin.";
            }
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
    <title>Admin Login - Warehouse System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="assets/css/style.css?v=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>

<body class="login-page">

    <div class="login-card">
        <div class="logo-box">
            <i class="bi bi-box-seam-fill"></i>
        </div>

        <h3>Admin Portal</h3>
        <p class="subtitle">Inventory Management System</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2 mb-4" style="font-size:14px; background:rgba(220, 38, 38, 0.2); border:1px solid #dc2626; color:#fca5a5;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3 text-start">
                <label>Username</label>
                <input type="text" name="username" placeholder="admin_user" required>
            </div>

            <div class="mb-3 text-start">
                <label>Password</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>

            <button type="submit">Login</button>
        </form>

        <a href="user-login.php" class="staff-link">Are you Staff? Click here</a>
    </div>

</body>

</html>