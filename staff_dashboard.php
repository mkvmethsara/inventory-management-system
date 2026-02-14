<?php
include 'config/db.php';
?>
<!DOCTYPE html>
<html lang="en" class="staff-dash-page">

<head>
    <meta charset="UTF-8">
    <title>User Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

    <div class="header">
        <div class="top-bar">
            <div class="status">🟢 ONLINE</div>
            <a href="user-login.php" class="logout-btn">Logout</a>
        </div>

        <h1>Hello, Staff</h1>
        <div class="subtitle">What would you like to do today?</div>
    </div>

    <div class="container">

        <a href="receive-stock.php" class="card">
            <div class="card-left">
                <div class="icon">➕</div>
                <div>
                    <div class="title">Receive Stock</div>
                    <div class="desc">Scan and add new inventory</div>
                </div>
            </div>
            <div class="arrow">›</div>
        </a>

        <a href="stock-lookup.php" class="card">
            <div class="card-left">
                <div class="icon">📡</div>
                <div>
                    <div class="title">Stock Lookup</div>
                    <div class="desc">Scan RFID to check details</div>
                </div>
            </div>
            <div class="arrow">›</div>
        </a>

        <a href="move-stock.php" class="card">
            <div class="card-left">
                <div class="icon">🔄</div>
                <div>
                    <div class="title">Move Stock</div>
                    <div class="desc">Transfer between locations</div>
                </div>
            </div>
            <div class="arrow">›</div>
        </a>

        <a href="activity.php" class="card">
            <div class="card-left">
                <div class="icon">🕒</div>
                <div>
                    <div class="title">Recent Activity</div>
                    <div class="desc">Check your scan history</div>
                </div>
            </div>
            <div class="arrow">›</div>
        </a>

    </div>

    <div class="footer">
        <div>Sync Status</div>
        <div class="synced">All items synced</div>
    </div>

</body>

</html>