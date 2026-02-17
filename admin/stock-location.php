<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// SECURITY GATE 🔒
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

include '../config/db.php';

// --- FILTER LOGIC (PHP) ---
$filter_loc = isset($_GET['loc']) ? $_GET['loc'] : 'all';
$where_clause = "";

if ($filter_loc !== 'all') {
    $safe_loc = mysqli_real_escape_string($conn, $filter_loc);
    $where_clause = "WHERE l.location_code = '$safe_loc'";
}

// --- FETCH STOCK DATA ---
$sql = "SELECT 
            s.quantity, 
            l.location_code, 
            i.item_name, 
            b.batch_id
        FROM stock s
        JOIN locations l ON s.location_id = l.location_id
        JOIN items i ON s.item_id = i.item_id
        LEFT JOIN item_batches b ON s.batch_id = b.batch_id
        $where_clause
        ORDER BY l.location_code ASC";

$result = mysqli_query($conn, $sql);

// --- FETCH LOCATIONS FOR BUTTONS ---
$loc_sql = "SELECT DISTINCT location_code FROM locations ORDER BY location_code ASC";
$loc_res = mysqli_query($conn, $loc_sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>TrackFlow – Stock by Location</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=12">
</head>

<body class="trackflow-body">

    <aside class="tf-sidebar">
        <div class="tf-logo">
            <i class="bi bi-box-seam-fill"></i> TRACKFLOW
        </div>
        <nav class="tf-nav">
            <a href="dashboard.php"><i class="bi bi-grid-fill"></i> Dashboard</a>
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
                <h2>Stock</h2>
                <h2 style="font-size:24px; margin-top:5px;">Real-time Stock by Location</h2>
            </div>

            <div class="tf-loc-filter-group">
                <a href="stock-location.php?loc=all" class="tf-loc-btn <?php echo ($filter_loc == 'all') ? 'active' : ''; ?>">All</a>

                <?php while ($l = mysqli_fetch_assoc($loc_res)): ?>
                    <a href="stock-location.php?loc=<?php echo $l['location_code']; ?>"
                        class="tf-loc-btn <?php echo ($filter_loc == $l['location_code']) ? 'active' : ''; ?>">
                        <?php echo $l['location_code']; ?>
                    </a>
                <?php endwhile; ?>
            </div>
        </div>

        <div class="tf-table-container">
            <table class="tf-table">
                <thead>
                    <tr>
                        <th style="padding-left:30px;">LOCATION</th>
                        <th>ITEM</th>
                        <th>BATCH</th>
                        <th>IN-STOCK QTY</th>
                        <th>CONDITION</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            // Format Batch Display
                            $batch_display = $row['batch_id'] ? "B" . $row['batch_id'] : "N/A";

                            echo "<tr>";
                            // Location Column
                            echo "<td style='padding-left:30px;'>
                                    <span class='loc-badge'>
                                        <i class='bi bi-geo-alt-fill'></i> " . $row['location_code'] . "
                                    </span>
                                  </td>";

                            // Item Name
                            echo "<td style='font-weight:600; color:#1f2937;'>" . $row['item_name'] . "</td>";

                            // Batch ID
                            echo "<td style='color:#9ca3af; font-size:13px; font-weight:500;'>" . $batch_display . "</td>";

                            // Quantity
                            echo "<td style='font-weight:800; font-size:16px; color:#111827;'>" . number_format($row['quantity']) . "</td>";

                            // Condition
                            echo "<td>
                                    <span class='condition-badge'>
                                        <span class='dot green'></span> Optimal Storage
                                    </span>
                                  </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' style='text-align:center; padding:50px; color:#9ca3af;'>No stock found for this location.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

    </main>
</body>

</html>