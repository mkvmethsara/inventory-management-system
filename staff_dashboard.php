<?php
session_start();
include 'config/db.php';

// 1. SECURITY: LOGIN CHECK 🔒
// If the user is NOT logged in (neither Admin nor Staff), kick them out.
if (!isset($_SESSION['user_id'])) {
    header("Location: user-login.php");
    exit();
}

// 2. GET USER NAME
$user_id = $_SESSION['user_id'];
$username = "Staff"; // Default name

if ($conn) {
    $user_sql = mysqli_query($conn, "SELECT username FROM users WHERE user_id = '$user_id'");
    if ($user_sql && mysqli_num_rows($user_sql) > 0) {
        $user_row = mysqli_fetch_assoc($user_sql);
        $username = $user_row['username'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Staff Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
            background: #f3f4f6;
            color: #1f2937;
        }

        .header {
            background: #1e1b4b;
            color: white;
            padding: 25px 20px;
            border-bottom-left-radius: 30px;
            border-bottom-right-radius: 30px;
        }

        .user-name {
            font-size: 28px;
            font-weight: 800;
            margin-top: 5px;
        }

        .menu-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            padding: 25px;
            margin-top: -10px;
        }

        .menu-card {
            background: white;
            padding: 20px;
            border-radius: 20px;
            text-decoration: none;
            color: #1f2937;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 140px;
        }

        .icon {
            font-size: 40px;
            margin-bottom: 10px;
        }

        .label {
            font-size: 16px;
            font-weight: 700;
        }

        .card-green {
            border-bottom: 5px solid #10b981;
        }

        .card-blue {
            border-bottom: 5px solid #4f46e5;
        }

        .card-purple {
            border-bottom: 5px solid #8b5cf6;
        }

        .card-orange {
            border-bottom: 5px solid #f97316;
        }

        .logout-btn {
            display: block;
            margin: 30px 25px;
            background: #fee2e2;
            color: #991b1b;
            text-align: center;
            padding: 15px;
            border-radius: 15px;
            text-decoration: none;
            font-weight: bold;
        }
    </style>
</head>

<body>

    <div class="header">
        <div style="font-size:14px; opacity:0.8; font-weight:600;">WELCOME BACK,</div>
        <div class="user-name"><?php echo htmlspecialchars($username); ?> 👋</div>
    </div>

    <div class="menu-grid">
        <a href="receive-stock.php" class="menu-card card-green">
            <div class="icon">➕</div>
            <div class="label">Receive Stock</div>
        </a>
        <a href="move-stock.php" class="menu-card card-blue">
            <div class="icon">🔄</div>
            <div class="label">Move Stock</div>
        </a>
        <a href="stock-lookup.php" class="menu-card card-purple">
            <div class="icon">📡</div>
            <div class="label">Stock Lookup</div>
        </a>
        <a href="activity.php" class="menu-card card-orange">
            <div class="icon">🕒</div>
            <div class="label">History</div>
        </a>
    </div>

    <a href="staff_logout.php" class="logout-btn">🔒 Logout</a>

</body>

</html>