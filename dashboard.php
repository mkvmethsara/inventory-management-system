<?php
include 'config/db.php';
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

        <?php include 'menu.php'; ?>

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