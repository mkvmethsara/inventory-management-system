<?php
// 1. Start Session & Security
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

// 2. Database Connection
include '../config/db.php';

// --- DATA FETCHING ---
$total_items = 0;
$q1 = mysqli_query($conn, "SELECT COUNT(*) as count FROM items");
if ($q1) $total_items = mysqli_fetch_assoc($q1)['count'];

$low_stock = 0;
if ($conn) {
    $q2 = mysqli_query($conn, "SELECT COUNT(*) as count FROM stock WHERE quantity < 20");
    if ($q2) $low_stock = mysqli_fetch_assoc($q2)['count'];
}

$today = date('Y-m-d');
$today_scans = 0;
$q3 = mysqli_query($conn, "SELECT COUNT(*) as count FROM stock_transactions WHERE DATE(transaction_time) = '$today'");
if ($q3) $today_scans = mysqli_fetch_assoc($q3)['count'];

// --- NEW: Fix 3 - Near Expiry Stat (Live Query) ---
$near_expiry = 0;
$q4 = mysqli_query(
    $conn,
    "SELECT COUNT(*) as count FROM item_batches 
     WHERE expiry_date BETWEEN CURDATE() 
     AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)"
);
if ($q4) $near_expiry = mysqli_fetch_assoc($q4)['count'];

// --- NEW: Fetch Total Stock per Item ---
$stock_summary_query = "SELECT i.item_name, i.item_code, COALESCE(SUM(s.quantity), 0) as total_qty 
                        FROM items i 
                        LEFT JOIN stock s ON i.item_id = s.item_id 
                        GROUP BY i.item_id 
                        ORDER BY total_qty DESC LIMIT 6";
$stock_summary_result = mysqli_query($conn, $stock_summary_query);

// Fetch Recent Transactions (Limit 6)
$trans_query = "SELECT t.*, i.item_name FROM stock_transactions t JOIN items i ON t.item_id = i.item_id ORDER BY t.transaction_time DESC LIMIT 6";
$trans_result = mysqli_query($conn, $trans_query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>TrackFlow Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=3">
</head>

<body class="trackflow-body">

    <aside class="tf-sidebar">
        <div class="tf-logo">
            <i class="bi bi-box-seam-fill"></i> TRACKFLOW
        </div>
        <nav class="tf-nav">
            <a href="dashboard.php" class="active"><i class="bi bi-grid-fill"></i> Dashboard</a>
            <a href="items-inventory.php"><i class="bi bi-box"></i> Items Inventory</a>
            <a href="batch-expiry.php"><i class="bi bi-clock-history"></i> Batch & Expiry</a>
            <a href="stock-location.php"><i class="bi bi-shop"></i> Stock by Location</a>
            <a href="locations.php"><i class="bi bi-geo-alt"></i> Locations</a>
            <a href="suppliers.php"><i class="bi bi-truck"></i> Suppliers</a>
            <a href="staff.php"><i class="bi bi-people"></i> Staff Management</a>
            <a href="transactions.php"><i class="bi bi-file-text"></i> Transaction Logs</a>
            <a href="logout.php" class="tf-logout"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </nav>
    </aside>

    <main class="tf-main">

        <div class="tf-page-header">
            <div class="tf-page-title">
                <h2>System Overview</h2>
                <p>Welcome back, Admin</p>
            </div>
        </div>

        <div class="tf-stats-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 25px;">
            <div class="tf-table-container" style="padding: 24px;">
                <div class="tf-card-top" style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                    <div class="icon-box" style="width: 40px; height: 40px; background: #dbeafe; color: #2563eb; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px;"><i class="bi bi-box-seam"></i></div>
                    <span style="background: #dcfce7; color: #16a34a; padding: 4px 8px; border-radius: 20px; font-size: 11px; font-weight: 700;">ACTIVE</span>
                </div>
                <p style="color: #6b7280; font-size: 14px; margin: 0; font-weight: 500;">Total Catalog Items</p>
                <h3 style="font-size: 28px; margin: 5px 0 0 0; color: #111827;"><?php echo number_format($total_items); ?></h3>
            </div>

            <div class="tf-table-container" style="padding: 24px;">
                <div class="tf-card-top" style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                    <div class="icon-box" style="width: 40px; height: 40px; background: #f3e8ff; color: #9333ea; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px;"><i class="bi bi-qr-code-scan"></i></div>
                    <span style="background: #dcfce7; color: #16a34a; padding: 4px 8px; border-radius: 20px; font-size: 11px; font-weight: 700;">TODAY</span>
                </div>
                <p style="color: #6b7280; font-size: 14px; margin: 0; font-weight: 500;">Today's Scans</p>
                <h3 style="font-size: 28px; margin: 5px 0 0 0; color: #111827;"><?php echo number_format($today_scans); ?></h3>
            </div>

            <div class="tf-table-container" style="padding: 24px;">
                <div class="tf-card-top" style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                    <div class="icon-box" style="width: 40px; height: 40px; background: #ffedd5; color: #ea580c; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px;"><i class="bi bi-exclamation-circle"></i></div>
                    <span style="background: #fee2e2; color: #dc2626; padding: 4px 8px; border-radius: 20px; font-size: 11px; font-weight: 700;">ALERT</span>
                </div>
                <p style="color: #6b7280; font-size: 14px; margin: 0; font-weight: 500;">Near Expiry</p>
                <h3 style="font-size: 28px; margin: 5px 0 0 0; color: #111827;"><?php echo number_format($near_expiry); ?></h3>
            </div>

            <div class="tf-table-container" style="padding: 24px;">
                <div class="tf-card-top" style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                    <div class="icon-box" style="width: 40px; height: 40px; background: #fee2e2; color: #dc2626; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px;"><i class="bi bi-bell"></i></div>
                    <span style="background: #fee2e2; color: #dc2626; padding: 4px 8px; border-radius: 20px; font-size: 11px; font-weight: 700;">LOW</span>
                </div>
                <p style="color: #6b7280; font-size: 14px; margin: 0; font-weight: 500;">Low Stock Shelves</p>
                <h3 style="font-size: 28px; margin: 5px 0 0 0; color: #111827;"><?php echo number_format($low_stock); ?></h3>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">

            <div class="tf-table-container">
                <div class="tf-page-header" style="padding: 20px; margin: 0; border-bottom: 1px solid #e5e7eb;">
                    <h4 style="margin:0; font-size:16px;">Current Stock Levels</h4>
                </div>
                <table class="tf-table">
                    <thead>
                        <tr>
                            <th style="padding-left: 20px;">Item</th>
                            <th style="text-align: right; padding-right: 20px;">Total Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($item = mysqli_fetch_assoc($stock_summary_result)): ?>
                            <tr>
                                <td style="padding: 15px 20px;">
                                    <span style="font-weight: 700; display: block; color: #1f2937;"><?php echo htmlspecialchars($item['item_name']); ?></span>
                                    <span style="color: #6b7280; font-size: 12px; font-family: monospace;"><?php echo htmlspecialchars($item['item_code']); ?></span>
                                </td>
                                <td style="text-align: right; padding: 15px 20px;">
                                    <span style="font-weight: 700; color: #4f46e5; font-size: 15px; background: #e0e7ff; padding: 6px 12px; border-radius: 8px;">
                                        <?php echo number_format($item['total_qty']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        <?php if (mysqli_num_rows($stock_summary_result) == 0): ?>
                            <tr>
                                <td colspan="2" style="text-align:center; padding:30px; color:#9ca3af;">No stock available</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="tf-table-container">
                <div class="tf-page-header" style="padding: 20px; margin: 0; border-bottom: 1px solid #e5e7eb;">
                    <h4 style="margin:0; font-size:16px;">Recent Transactions</h4>
                </div>
                <table class="tf-table">
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($trans_result)):
                            $color = ($row['transaction_type'] == 'IN') ? '#16a34a' : (($row['transaction_type'] == 'OUT') ? '#dc2626' : '#d97706');
                            $bg    = ($row['transaction_type'] == 'IN') ? '#dcfce7' : (($row['transaction_type'] == 'OUT') ? '#fee2e2' : '#fef3c7');
                            $icon  = ($row['transaction_type'] == 'IN') ? 'bi-arrow-down-left' : (($row['transaction_type'] == 'OUT') ? 'bi-arrow-up-right' : 'bi-arrow-left-right');
                        ?>
                            <tr>
                                <td width="50" style="padding-left: 20px;">
                                    <div style="width: 36px; height: 36px; background: <?php echo $bg; ?>; color: <?php echo $color; ?>; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                        <i class="bi <?php echo $icon; ?>"></i>
                                    </div>
                                </td>
                                <td>
                                    <span style="font-weight: 700; display: block; color: #1f2937;"><?php echo htmlspecialchars($row['item_name']); ?></span>
                                    <span style="color: #6b7280; font-size: 12px;">Type: <?php echo $row['transaction_type']; ?></span>
                                </td>
                                <td style="font-weight: 700; text-align: right;">
                                    <?php echo ($row['transaction_type'] == 'OUT' ? '-' : '+') . $row['quantity']; ?>
                                </td>
                                <td style="text-align: right; font-weight: 600; color: #9ca3af; padding-right: 20px; font-size: 13px;">
                                    <?php echo substr($row['transaction_time'], 11, 5); ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        <?php if (mysqli_num_rows($trans_result) == 0): ?>
                            <tr>
                                <td colspan="4" style="text-align:center; padding:30px; color:#9ca3af;">No recent transactions</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>

    </main>
</body>

</html>