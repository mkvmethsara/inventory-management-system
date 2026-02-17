<?php
session_start();
include 'config/db.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    // 1. CHECK DATABASE FOR USER
    $sql = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);

        // 2. CHECK PASSWORD
        if (password_verify($password, $row['password'])) {

            // 3. SECURITY CHECK: IS THIS USER AN ADMIN? 🛡️
            if ($row['role'] == 'Admin') {
                // Yes, they are Admin. Let them in.
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['role'] = 'Admin';
                header("Location: admin/dashboard.php"); // Go to Admin Dashboard
                exit();
            } else {
                // No, they are Staff. KICK THEM OUT! ❌
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
    <title>Admin Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: #1e1b4b;
        }

        .login-card {
            background: white;
            padding: 40px;
            border-radius: 10px;
            text-align: center;
            width: 350px;
        }

        input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            padding: 10px;
            background: #4f46e5;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }

        .error {
            color: red;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .staff-link {
            display: block;
            margin-top: 15px;
            font-size: 12px;
            color: #666;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="login-card">
        <h2>Admin Portal 🔒</h2>

        <?php if ($error) {
            echo "<div class='error'>$error</div>";
        } ?>

        <form method="POST">
            <input type="text" name="username" placeholder="Admin Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>

        <a href="user-login.php" class="staff-link">Are you Staff? Click here</a>
    </div>
</body>

</html>