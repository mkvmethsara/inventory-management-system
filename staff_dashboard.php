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
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <div style="font-size:13px; opacity:0.8;">🟢 ONLINE</div>
            <a href="user-login.php" style="background:white; color:#4f46e5; padding:6px 14px; border-radius:20px; font-size:13px; font-weight:600; text-decoration:none;">Logout</a>
        </div>

        <h1>Hello, Staff</h1>
        <div class="subtitle">What would you like to do today?</div>
    </div>

    <div class="container" style="padding:20px 18px; margin-top:-40px;">

        <div class="card">
            <div style="display:flex; align-items:center; gap:14px;">
                <div style="width:48px; height:48px; border-radius:14px; background:#f1f2f7; display:flex; align-items:center; justify-content:center; font-size:22px;">➕</div>
                <div>
                    <div style="font-weight:600; color:#111827;">Receive Stock</div>
                    <div style="font-size:13px; color:#6b7280;">Scan and add new inventory</div>
                </div>
            </div>
            <div style="font-size:18px; color:#9ca3af;">›</div>
        </div>

        <div class="card">
            <div style="display:flex; align-items:center; gap:14px;">
                <div style="width:48px; height:48px; border-radius:14px; background:#f1f2f7; display:flex; align-items:center; justify-content:center; font-size:22px;">📡</div>
                <div>
                    <div style="font-weight:600; color:#111827;">Stock Lookup</div>
                    <div style="font-size:13px; color:#6b7280;">Scan RFID to check details</div>
                </div>
            </div>
            <div style="font-size:18px; color:#9ca3af;">›</div>
        </div>

    </div>

</body>

</html>