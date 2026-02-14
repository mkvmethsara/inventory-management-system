<?php
include 'config/db.php'; // Your database bridge
?>
<!DOCTYPE html>
<html lang="en" class="admin-dash-page">

<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard – TrackFlow</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

    <div class="header">
        TrackFlow Admin Dashboard
    </div>

    <div class="layout">

        <div class="sidebar">
            <a href="dashboard.php">Dashboard</a>
            <a href="#">Items</a>
            <a href="#">Locations</a>
            <a href="#">Suppliers</a>
            <a href="#">Users</a>
            <a href="#">Reports</a>
            <a href="index.php">Logout</a>
        </div>

        <div class="main">
            <div class="card">
                <h2>Welcome, Admin</h2>
                <p>This is your admin dashboard.</p>
                <p>Database Status:
                    <span style="color: #4f46e5;">
                        <?php echo ($conn) ? "Connected 🟢" : "Disconnected 🔴"; ?>
                    </span>
                </p>
            </div>

            <div class="card">
                <h2>System Status</h2>
                <p>XAMPP / MySQL : Active</p>
            </div>
        </div>

    </div>

</body>

</html>