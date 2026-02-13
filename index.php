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

        <form>
            <div class="form-group">
                <label>Username</label>
                <input type="text" placeholder="admin_user">
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" placeholder="********">
            </div>

            <button type="submit">Sign In to Dashboard</button>
        </form>

        <div class="footer-text">
            Authorized personnel only.
        </div>
    </div>

</body>

</html>