<?php
include 'config/db.php';

if (isset($_POST['login_btn'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];


    $query = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {

        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid Username or Password!";
    }
}
?>

<head>
    <meta charset="UTF-8">
    <title>TrackFlow Admin</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <div class="login-card">
        <div class="logo">
        </div>

        <h1>TrackFlow Admin</h1>
        <div class="subtitle">Inventory Management Portal</div>

        <form action="index.php" method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" placeholder="admin_user" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="********" required>
            </div>

            <button type="submit" name="login_btn">Sign In to Dashboard</button>

            <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
        </form>

        <div class="footer-text">
            Authorized personnel only.
        </div>
    </div>

</body>

</html>