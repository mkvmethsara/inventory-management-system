<?php
session_start();
include 'config/db.php';

// 1. Check Login
if (!isset($_SESSION['user_id'])) {
    header("Location: user-login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 2. FETCH HISTORY
// We join with 'items' and 'locations' to get real names instead of just IDs
$sql = "SELECT t.*, i.item_name, l.location_code 
        FROM stock_transactions t 
        JOIN items i ON t.item_id = i.item_id 
        JOIN locations l ON t.location_id = l.location_id 
        WHERE t.user_id = '$user_id' 
        ORDER BY t.transaction_time DESC 
        LIMIT 50";

$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Recent Activity</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/staff_style.css">
</head>

<body>

    <div class="header">
        <div class="top-bar">
            <a href="staff_dashboard.php" style="color:white; text-decoration:none;">← Dashboard</a>
            <div class="status">🕒 HISTORY</div>
        </div>
        <h1>Recent Activity</h1>
        <div class="subtitle">Your last 50 transactions</div>
    </div>

    <div class="container">

        <div class="activity-list">
            <?php if (mysqli_num_rows($result) > 0): ?>

                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <?php
                    // Determine Color & Text based on Type
                    $typeClass = "type-" . $row['transaction_type'];
                    $actionText = "";
                    $qtyClass = "";

                    if ($row['transaction_type'] == 'IN') {
                        $actionText = "Received Stock";
                        $qtyClass = "bg-green";
                    } elseif ($row['transaction_type'] == 'MOVE') {
                        $actionText = "Moved Stock";
                        $qtyClass = "bg-blue";
                    } else {
                        $actionText = "Removed Stock";
                        $qtyClass = "bg-red";
                    }

                    // Format Time (e.g., "10:30 AM")
                    $time = date("M d, h:i A", strtotime($row['transaction_time']));
                    ?>

                    <div class="activity-card <?php echo $typeClass; ?>">
                        <div class="act-content">
                            <div class="act-title">
                                <?php echo $actionText; ?>
                                <span class="qty-badge <?php echo $qtyClass; ?>">+<?php echo $row['quantity']; ?></span>
                            </div>
                            <div class="act-desc">
                                <b><?php echo $row['item_name']; ?></b> <br>
                                Location: <?php echo $row['location_code']; ?>
                            </div>
                        </div>
                        <div class="act-time"><?php echo $time; ?></div>
                    </div>

                <?php endwhile; ?>

            <?php else: ?>

                <div style="text-align:center; padding:40px; color:#9ca3af;">
                    <div style="font-size:40px; margin-bottom:10px;">💤</div>
                    <div>No activity found yet.</div>
                    <div style="font-size:12px;">Start scanning items to see logs here.</div>
                </div>

            <?php endif; ?>
        </div>

    </div>

    <style>
        .float-home-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #1e1b4b;
            /* Matches your dashboard header color */
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            text-decoration: none;
            font-size: 24px;
            z-index: 9999;
            transition: transform 0.2s;
        }

        .float-home-btn:hover {
            transform: scale(1.1);
            background: #312e81;
        }
    </style>

    <a href="staff_dashboard.php" class="float-home-btn" title="Back to Dashboard">🏠</a>

</body>

</html>