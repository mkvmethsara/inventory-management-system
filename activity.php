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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            background: #f8fafc;
        }

        .history-card {
            background: white;
            border-radius: 16px;
            padding: 16px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
            border: 1px solid #f1f5f9;
            transition: transform 0.2s;
        }

        .history-card:active {
            transform: scale(0.98);
        }

        .history-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .icon-in {
            background: #dcfce7;
            color: #16a34a;
        }

        .icon-out {
            background: #fee2e2;
            color: #dc2626;
        }

        .icon-move {
            background: #e0e7ff;
            color: #4f46e5;
        }

        .history-details {
            flex: 1;
        }

        .history-title {
            font-weight: 700;
            color: #1e293b;
            font-size: 15px;
            margin-bottom: 4px;
        }

        .history-meta {
            font-size: 13px;
            color: #64748b;
        }

        .history-right {
            text-align: right;
        }

        .qty-in {
            color: #16a34a;
            font-weight: 800;
            font-size: 17px;
        }

        .qty-out {
            color: #dc2626;
            font-weight: 800;
            font-size: 17px;
        }

        .qty-move {
            color: #4f46e5;
            font-weight: 800;
            font-size: 17px;
        }

        .history-time {
            font-size: 12px;
            color: #94a3b8;
            margin-top: 4px;
            font-weight: 600;
        }

        .float-home-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #1e1b4b;
            color: white;
            width: 55px;
            height: 55px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(30, 27, 75, 0.4);
            text-decoration: none;
            font-size: 24px;
            z-index: 9999;
            transition: transform 0.2s;
        }

        .float-home-btn:hover {
            transform: scale(1.1);
            background: #312e81;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #94a3b8;
        }

        .empty-icon {
            font-size: 48px;
            color: #cbd5e1;
            margin-bottom: 15px;
        }
    </style>
</head>

<body>

    <div class="header">
        <div class="top-bar">
            <a href="staff_dashboard.php" style="color:white; text-decoration:none;"><i class="bi bi-arrow-left"></i> Dashboard</a>
            <div class="status"><i class="bi bi-clock-history"></i> HISTORY</div>
        </div>
        <h1>Recent Activity</h1>
        <div class="subtitle">Your last 50 transactions</div>
    </div>

    <div class="container" style="padding-top: 10px;">

        <div class="activity-list">
            <?php if (mysqli_num_rows($result) > 0): ?>

                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <?php
                    // Determine Color, Icon, & Text based on Type
                    if ($row['transaction_type'] == 'IN') {
                        $actionText = "Received Stock";
                        $qtyClass = "qty-in";
                        $qtySign = "+";
                        $iconBox = "icon-in";
                        $iconClass = "bi-box-arrow-in-down-left";
                    } elseif ($row['transaction_type'] == 'MOVE') {
                        $actionText = "Moved Stock";
                        $qtyClass = "qty-move";
                        $qtySign = "";
                        $iconBox = "icon-move";
                        $iconClass = "bi-arrow-left-right";
                    } else {
                        $actionText = "Dispatched Stock";
                        $qtyClass = "qty-out";
                        $qtySign = "-";
                        $iconBox = "icon-out";
                        $iconClass = "bi-box-arrow-up-right";
                    }

                    // Format Time (e.g., "Feb 23, 10:30 AM")
                    $time = date("M d, h:i A", strtotime($row['transaction_time']));
                    ?>

                    <div class="history-card">
                        <div class="history-icon <?php echo $iconBox; ?>">
                            <i class="bi <?php echo $iconClass; ?>"></i>
                        </div>

                        <div class="history-details">
                            <div class="history-title"><?php echo $actionText; ?></div>
                            <div class="history-meta">
                                <b><?php echo htmlspecialchars($row['item_name']); ?></b><br>
                                <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($row['location_code']); ?>
                            </div>
                        </div>

                        <div class="history-right">
                            <div class="<?php echo $qtyClass; ?>">
                                <?php echo $qtySign . $row['quantity']; ?>
                            </div>
                            <div class="history-time"><?php echo $time; ?></div>
                        </div>
                    </div>

                <?php endwhile; ?>

            <?php else: ?>

                <div class="empty-state">
                    <div class="empty-icon"><i class="bi bi-inbox"></i></div>
                    <div style="font-weight: bold; color: #475569; font-size: 16px;">No activity found yet.</div>
                    <div style="font-size:13px; margin-top: 5px;">Start scanning items to see logs here.</div>
                </div>

            <?php endif; ?>
        </div>

    </div>

    <a href="staff_dashboard.php" class="float-home-btn" title="Back to Dashboard">
        <i class="bi bi-house-door-fill" style="font-size: 22px;"></i>
    </a>

</body>

</html>