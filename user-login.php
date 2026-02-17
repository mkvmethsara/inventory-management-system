<?php
session_start();
include 'config/db.php';

$error = "";

// 1. LISTEN FOR LOGIN CLICK
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    // 2. FIND USER IN DATABASE
    $sql = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);

        // 3. CHECK PASSWORD (Encrypted or Plain Text)
        // If you used the Admin Panel to create the user, the password is encrypted.
        if (password_verify($password, $row['password'])) {
            // SUCCESS! Start Session
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['role'] = $row['role'];

            // Redirect to Staff Dashboard
            header("Location: staff_dashboard.php");
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
    <title>Staff Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            margin: 0;
            font-family: "Segoe UI", sans-serif;
            background: #f2f3f7;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }

        .login-card {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        h1 {
            color: #3c34c9;
            margin-bottom: 10px;
        }

        p {
            color: #6b7280;
            margin-bottom: 30px;
            font-size: 14px;
        }

        input {
            width: 100%;
            padding: 15px;
            margin-bottom: 15px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 16px;
            box-sizing: border-box;
            outline: none;
            transition: 0.3s;
        }

        input:focus {
            border-color: #5a48f5;
            background: #f9fafb;
        }

        button {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #5a48f5, #3c34c9);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }

        button:hover {
            opacity: 0.9;
            transform: scale(0.98);
        }

        .error-msg {
            background: #fee2e2;
            color: #ef4444;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: bold;
        }

        .back-link {
            display: block;
            margin-top: 20px;
            color: #6b7280;
            text-decoration: none;
            font-size: 13px;
        }

        .back-link:hover {
            color: #3c34c9;
        }
    </style>
</head>

<body>

    <div class="login-card">
        <h1>Staff Portal</h1>
        <p>Please enter your credentials to continue</p>

        <?php if ($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="text" name="username" placeholder="Username" required autofocus>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>

        <a href="index.php" class="back-link">← Go to Admin Login</a>
    </div>

</body>

</html>