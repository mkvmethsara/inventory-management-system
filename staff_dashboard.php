<?php
session_start();
include 'config/db.php';

// 1. SECURITY: Kick out anyone who isn't logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: user-login.php"); // Fixed: Redirect to User Login if kicked out
    exit();
}

// 2. GET USER NAME
$user_id = $_SESSION['user_id'];
$username = "Staff"; // Default fallback

// Check connection
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

    <style>
        /* --- INTERNAL CSS --- */
        * {
            box-sizing: border-box;
            font-family: "Segoe UI", system-ui, sans-serif;
        }

        body {
            margin: 0;
            background-color: #f2f3f7 !important;
            color: #111827;
        }

        /* Top Header */
        .header {
            background: linear-gradient(135deg, #5a48f5, #3c34c9);
            color: white;
            padding: 22px 20px 80px;
            border-bottom-left-radius: 30px;
            border-bottom-right-radius: 30px;
            position: relative;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .status {
            font-size: 13px;
            background: rgba(255, 255, 255, 0.2);
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: 600;
        }

        .logout-btn {
            background: white;
            color: #4f46e5;
            padding: 8px 18px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            transition: 0.2s;
        }

        .logout-btn:hover {
            background: #e0e7ff;
        }

        h1 {
            margin: 10px 0 5px;
            font-size: 28px;
            font-weight: 700;
        }

        .subtitle {
            opacity: 0.9;
            font-size: 15px;
        }

        /* Cards Container */
        .container {
            padding: 0 20px;
            margin-top: -50px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Card Style */
        .card {
            background: white;
            border-radius: 18px;
            padding: 20px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
            cursor: pointer;
            transition: transform 0.2s ease;
            text-decoration: none;
            color: inherit;
        }

        .card:hover {
            transform: translateY(-3px);
        }

        .card-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .icon-box {
            width: 50px;
            height: 50px;
            border-radius: 14px;
            background: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .title {
            font-weight: 700;
            font-size: 16px;
            color: #111827;
            margin-bottom: 2px;
        }

        .desc {
            font-size: 13px;
            color: #6b7280;
        }

        .arrow {
            font-size: 20px;
            color: #9ca3af;
            font-weight: bold;
        }

        /* Footer */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background: white;
            padding: 15px 20px;
            font-size: 13px;
            display: flex;
            justify-content: space-between;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
        }

        .synced {
            color: #4f46e5;
            font-weight: 600;
        }
    </style>
</head>

<body>

    <div class="header">
        <div class="top-bar">
            <div class="status">🟢 ONLINE</div>
            <a href="staff_logout.php" class="logout-btn">Logout</a>
        </div>

        <h1>Hello, <?php echo htmlspecialchars($username); ?></h1>
        <div class="subtitle">What would you like to do today?</div>
    </div>

    <div class="container">
        <a href="receive-stock.php" class="card">
            <div class="card-left">
                <div class="icon-box">➕</div>
                <div>
                    <div class="title">Receive Stock</div>
                    <div class="desc">Scan and add new inventory</div>
                </div>
            </div>
            <div class="arrow">›</div>
        </a>

        <a href="stock-lookup.php" class="card">
            <div class="card-left">
                <div class="icon-box">📡</div>
                <div>
                    <div class="title">Stock Lookup</div>
                    <div class="desc">Scan RFID to check details</div>
                </div>
            </div>
            <div class="arrow">›</div>
        </a>

        <a href="move-stock.php" class="card">
            <div class="card-left">
                <div class="icon-box">🔄</div>
                <div>
                    <div class="title">Move Stock</div>
                    <div class="desc">Transfer between locations</div>
                </div>
            </div>
            <div class="arrow">›</div>
        </a>

        <a href="activity.php" class="card">
            <div class="card-left">
                <div class="icon-box">🕒</div>
                <div>
                    <div class="title">Recent Activity</div>
                    <div class="desc">Check your scan history</div>
                </div>
            </div>
            <div class="arrow">›</div>
        </a>
    </div>

    <div class="footer">
        <div>System Status</div>
        <div class="synced">All items synced</div>
    </div>

</body>

</html>