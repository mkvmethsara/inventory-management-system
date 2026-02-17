<?php
// 1. Start Session (Safe Mode)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. SECURITY GATE: LOGIN CHECK 🔒
// If user_id is missing OR the role is NOT Admin, kick them out!
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php"); // Send back to Admin Login
    exit();
}

// 3. DATABASE CONNECTION
include '../config/db.php';

// --- CALCULATE DASHBOARD NUMBERS ---
$total_items = 0;
$q1 = mysqli_query($conn, "SELECT COUNT(*) as count FROM items");
if ($q1) {
    $total_items = mysqli_fetch_assoc($q1)['count'];
}

$low_stock = 0;
if ($conn) {
    $q2 = mysqli_query($conn, "SELECT COUNT(*) as count FROM stock WHERE quantity < 20");
    if ($q2) {
        $low_stock = mysqli_fetch_assoc($q2)['count'];
    }
}

$today = date('Y-m-d');
$today_scans = 0;
$q3 = mysqli_query($conn, "SELECT COUNT(*) as count FROM stock_transactions WHERE DATE(transaction_time) = '$today'");
if ($q3) {
    $today_scans = mysqli_fetch_assoc($q3)['count'];
}

// Recent Transactions
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
                <h3>Recent Transactions</h3>
                <ul class="dash-list">
                    <?php
                    if ($trans_result && mysqli_num_rows($trans_result) > 0) {
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