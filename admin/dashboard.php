<?php
session_start();
include '../config/db.php';

// --- CALCULATE DASHBOARD NUMBERS ---
$q1 = mysqli_query($conn, "SELECT COUNT(*) as count FROM items");
$total_items = ($q1) ? mysqli_fetch_assoc($q1)['count'] : 0;

$low_stock = 0;
if (mysqli_query($conn, "DESCRIBE stock")) {
    $q2 = mysqli_query($conn, "SELECT COUNT(*) as count FROM stock WHERE quantity < 20");
    $low_stock = ($q2) ? mysqli_fetch_assoc($q2)['count'] : 0;
}

$today = date('Y-m-d');
$q3 = mysqli_query($conn, "SELECT COUNT(*) as count FROM stock_transactions WHERE DATE(transaction_time) = '$today'");
$today_scans = ($q3) ? mysqli_fetch_assoc($q3)['count'] : 0;

$trans_query = "SELECT t.*, i.item_name 
                FROM stock_transactions t 
                JOIN items i ON t.item_id = i.item_id 
                ORDER BY t.transaction_time DESC LIMIT 5";
$trans_result = mysqli_query($conn, $trans_query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>TrackFlow – Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="admin-dash-page">

    <div class="header">TrackFlow – Admin Dashboard</div>

    <div class="layout">

        <?php include 'menu.php'; ?>

        <div class="main">

            <div class="cards">
                <div class="card-summary">
                    <h3>Total Items</h3>
                    <div class="big-number"><?php echo number_format($total_items); ?></div>
                </div>

                <div class="card-summary">
                    <h3>Today's Activity</h3>
                    <div class="big-number"><?php echo number_format($today_scans); ?></div>
                </div>

                <div class="card-summary">
                    <h3>Near Expiry</h3>
                    <div class="big-number" style="color:#fbbf24;">0</div>
                </div>

                <div class="card-summary">
                    <h3>Low Stock</h3>
                    <div class="big-number" style="color:#f87171;"><?php echo number_format($low_stock); ?></div>
                </div>
            </div>

            <div class="dash-section">
                <h3>Stock Movements</h3>
                <div style="height:150px; background:#0f1a33; border:1px dashed #334155; border-radius:10px; display:flex; align-items:center; justify-content:center; color:#64748b;">
                    [ Chart UI - Coming Soon ]
                </div>
            </div>

            <div class="dash-section">
                <h3>Recent Transactions</h3>
                <ul class="dash-list">
                    <?php
                    if (mysqli_num_rows($trans_result) > 0) {
                        while ($row = mysqli_fetch_assoc($trans_result)) {
                            $type = strtoupper($row['transaction_type']);
                            $color = ($type == 'IN') ? '#22c55e' : '#ef4444';

                            echo "<li>";
                            echo "<span>" . $row['item_name'] . "</span>";
                            echo "<span style='color:$color; font-weight:bold;'>$type " . $row['quantity'] . "</span>";
                            echo "</li>";
                        }
                    } else {
                        echo "<li style='justify-content:center; opacity:0.5;'>No recent transactions</li>";
                    }
                    ?>
                </ul>
            </div>

        </div>
    </div>

</body>

</html>