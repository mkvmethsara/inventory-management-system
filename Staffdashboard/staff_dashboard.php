<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Locations</title>


<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Stock by Location</title>


<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Suppliers</title>


<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Items Inventory</title>


<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Transaction Logs</title>


<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Batch & Expiry</title>

batch-expiry.html
10 KB
Methsara — 14:48
/* --- General Page Styles --- */
body {
    margin: 0;
    font-family: "Segoe UI", system-ui, sans-serif;
    background: #f2f3f7;
}


<?php
session_start();
include 'config/db.php';

// 1. SECURITY: Kick out anyone who isn't logged in
if (!isset($_SESSION['user_id'])) {

message.txt
5 KB
﻿
<?php
session_start();
include 'config/db.php';

// 1. SECURITY: Kick out anyone who isn't logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// 2. GET USER NAME (To say "Hello, Methsara")
$user_id = $_SESSION['user_id'];
$user_sql = mysqli_query($conn, "SELECT username FROM users WHERE user_id = '$user_id'");
$user_row = mysqli_fetch_assoc($user_sql);
$username = $user_row['username'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

    <div class="header">
        <div class="top-bar" style="display:flex; justify-content:space-between; align-items:center;">
            <div class="status" style="background:rgba(255,255,255,0.2); padding:5px 10px; border-radius:15px; font-size:12px;">🟢 ONLINE</div>
            <a href="logout.php" class="logout-btn" style="background:white; color:#4f46e5; padding:5px 15px; border-radius:15px; text-decoration:none; font-weight:bold; font-size:12px;">Logout</a>
        </div>

        <h1>Hello, <?php echo htmlspecialchars($username); ?></h1>
        <div style="opacity:0.8;">What would you like to do today?</div>
    </div>

    <div class="container">

        <a href="receive-stock.php" style="text-decoration:none; color:inherit;">
            <div class="card" style="display:flex; align-items:center; gap:15px; cursor:pointer;">
                <div style="font-size:30px; background:#f3f4f6; width:50px; height:50px; display:flex; align-items:center; justify-content:center; border-radius:12px;">➕</div>
                <div style="flex:1;">
                    <div style="font-weight:bold; font-size:16px;">Receive Stock</div>
                    <div style="color:#6b7280; font-size:13px;">Scan and add new inventory</div>
                </div>
                <div style="color:#9ca3af; font-size:20px;">›</div>
            </div>
        </a>

        <a href="stock-lookup.php" style="text-decoration:none; color:inherit;">
            <div class="card" style="display:flex; align-items:center; gap:15px; cursor:pointer;">
                <div style="font-size:30px; background:#f3f4f6; width:50px; height:50px; display:flex; align-items:center; justify-content:center; border-radius:12px;">📡</div>
                <div style="flex:1;">
                    <div style="font-weight:bold; font-size:16px;">Stock Lookup</div>
                    <div style="color:#6b7280; font-size:13px;">Scan RFID to check details</div>
                </div>
                <div style="color:#9ca3af; font-size:20px;">›</div>
            </div>
        </a>

        <a href="move-stock.php" style="text-decoration:none; color:inherit;">
            <div class="card" style="display:flex; align-items:center; gap:15px; cursor:pointer;">
                <div style="font-size:30px; background:#f3f4f6; width:50px; height:50px; display:flex; align-items:center; justify-content:center; border-radius:12px;">🔄</div>
                <div style="flex:1;">
                    <div style="font-weight:bold; font-size:16px;">Move Stock</div>
                    <div style="color:#6b7280; font-size:13px;">Transfer between locations</div>
                </div>
                <div style="color:#9ca3af; font-size:20px;">›</div>
            </div>
        </a>

        <a href="activity.php" style="text-decoration:none; color:inherit;">
            <div class="card" style="display:flex; align-items:center; gap:15px; cursor:pointer;">
                <div style="font-size:30px; background:#f3f4f6; width:50px; height:50px; display:flex; align-items:center; justify-content:center; border-radius:12px;">🕒</div>
                <div style="flex:1;">
                    <div style="font-weight:bold; font-size:16px;">Recent Activity</div>
                    <div style="color:#6b7280; font-size:13px;">Check your scan history</div>
                </div>
                <div style="color:#9ca3af; font-size:20px;">›</div>
            </div>
        </a>

    </div>

</body>
</html>