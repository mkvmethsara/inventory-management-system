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
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" />
                <path d="M3.3 7l8.7 5 8.7-5" />
                <path d="M12 22V12" />
            </svg>
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
        </form>
    </div>

</body>

</html>