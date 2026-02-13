<?php
include 'config/db.php';

if (isset($_POST['user_login_btn'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $query = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        header("Location: staff_dashboard.php");
        exit();
    } else {
        $error = "Invalid Staff Credentials!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Warehouse RFID – User Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body style="background:linear-gradient(180deg,#0b1326,#050b18); color:white; display:flex; align-items:center; justify-content:center; min-height:100vh; margin:0;">

    <div class="container" style="width:100%; max-width:420px; text-align:center;">
        <h1>Warehouse RFID</h1>
        <div class="subtitle" style="color:#9fb0d0; margin-bottom:30px;">Inventory Management System</div>

        <form action="user-login.php" method="POST">
            <div class="form-group" style="text-align:left; margin-bottom:20px;">
                <label style="display:block; margin-bottom:8px;">Username</label>
                <input type="text" name="username" placeholder="staff_01" required style="width:100%; padding:15px; border-radius:10px; border:1px solid #22304f; background:#18253d; color:white;">
            </div>

            <div class="form-group" style="text-align:left; margin-bottom:20px;">
                <label style="display:block; margin-bottom:8px;">Password</label>
                <input type="password" name="password" placeholder="••••••••" required style="width:100%; padding:15px; border-radius:10px; border:1px solid #22304f; background:#18253d; color:white;">
            </div>

            <button type="submit" name="user_login_btn" style="width:100%; padding:15px; border-radius:10px; border:none; background:#4f46e5; color:white; font-weight:bold; cursor:pointer;">Login</button>

            <?php if (isset($error)) echo "<p style='color:#ff4d4d; margin-top:15px;'>$error</p>"; ?>
        </form>
    </div>

</body>

</html>