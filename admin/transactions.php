<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// SECURITY GATE 🔒
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

include '../config/db.php';

// --- 1. HANDLE ADD TRANSACTION ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $t_date  = $_POST['t_date'];
    $t_type  = $_POST['t_type'];
    $qty     = (int)$_POST['quantity'];
    $user_id = $_POST['user_id'];
    $loc_id  = $_POST['location_id'];

    // Split "batch_id,item_id"
    $batch_data = explode(',', $_POST['batch_data']);
    $batch_id = $batch_data[0];
    $item_id  = $batch_data[1];

    // A. Handle Batch Logic for SQL (Check if batch is 0 or empty)
    // If batch_id is 0 or empty, we check for (batch_id IS NULL OR batch_id = '0')
    $batch_sql_check = (empty($batch_id) || $batch_id == '0') ? "(batch_id IS NULL OR batch_id = '0')" : "batch_id = '$batch_id'";

    // 1. Log the Transaction
    $sql_insert = "INSERT INTO stock_transactions 
                   (transaction_time, item_id, batch_id, transaction_type, quantity, user_id, location_id) 
                   VALUES ('$t_date', '$item_id', '$batch_id', '$t_type', '$qty', '$user_id', '$loc_id')";

    if (mysqli_query($conn, $sql_insert)) {
        
        // --- 2. UPDATE REAL STOCK LEVEL (Fixed Logic - No stock_id) ---
        
        // Check if a record exists for this Item + Batch + Location
        // Removed 'stock_id' from SELECT
        $check_sql = "SELECT quantity FROM stock WHERE item_id='$item_id' AND $batch_sql_check AND location_id='$loc_id'";
        $check_res = mysqli_query($conn, $check_sql);
        
        // Error handling if query fails
        if (!$check_res) {
             die("Database Error: " . mysqli_error($conn));
        }

        $stock_row = mysqli_fetch_assoc($check_res);

        if ($t_type == 'IN') {
            if ($stock_row) {
                // Record exists -> Add to it
                $new_qty = $stock_row['quantity'] + $qty;
                // Update using composite key (Item + Batch + Location)
                mysqli_query($conn, "UPDATE stock SET quantity='$new_qty' WHERE item_id='$item_id' AND $batch_sql_check AND location_id='$loc_id'");
            } else {
                // New record -> Insert it
                $b_val = (empty($batch_id)) ? "0" : "'$batch_id'";
                mysqli_query($conn, "INSERT INTO stock (item_id, batch_id, location_id, quantity) VALUES ('$item_id', $b_val, '$loc_id', '$qty')");
            }

        } elseif ($t_type == 'OUT') {
            if ($stock_row) {
                $current_qty = $stock_row['quantity'];
                $new_qty = $current_qty - $qty;

                if ($new_qty <= 0) {
                    // Option A: Delete row if 0 (Keeps table clean)
                    // mysqli_query($conn, "DELETE FROM stock WHERE item_id='$item_id' AND $batch_sql_check AND location_id='$loc_id'");
                    
                    // Option B: Keep row at 0 or negative (Better for tracking errors)
                    mysqli_query($conn, "UPDATE stock SET quantity='$new_qty' WHERE item_id='$item_id' AND $batch_sql_check AND location_id='$loc_id'");
                } else {
                    mysqli_query($conn, "UPDATE stock SET quantity='$new_qty' WHERE item_id='$item_id' AND $batch_sql_check AND location_id='$loc_id'");
                }
            } else {
                // Creating a negative record if stock didn't exist (Rare case)
                 $b_val = (empty($batch_id)) ? "0" : "'$batch_id'";
                 mysqli_query($conn, "INSERT INTO stock (item_id, batch_id, location_id, quantity) VALUES ('$item_id', $b_val, '$loc_id', '-$qty')");
            }
        }

        echo "<script>alert('✅ Transaction Logged & Stock Updated!'); window.location.href='transactions.php';</script>";
    } else {
        echo "<script>alert('❌ Error: " . mysqli_error($conn) . "');</script>";
    }
}

// --- 2. CALCULATE SUMMARY STATS ---
$count_res = mysqli_query($conn, "SELECT COUNT(*) as total FROM stock_transactions");
$total_txns = mysqli_fetch_assoc($count_res)['total'];

$in_res = mysqli_query($conn, "SELECT SUM(quantity) as total_in FROM stock_transactions WHERE transaction_type='IN'");
$total_in = mysqli_fetch_assoc($in_res)['total_in'] ?? 0;

$out_res = mysqli_query($conn, "SELECT SUM(quantity) as total_out FROM stock_transactions WHERE transaction_type='OUT'");
$total_out = mysqli_fetch_assoc($out_res)['total_out'] ?? 0;


// --- 3. FETCH TABLE DATA ---
$sql = "SELECT t.*, i.item_name, u.username, b.batch_id, l.location_code
        FROM stock_transactions t
        LEFT JOIN items i ON t.item_id = i.item_id
        LEFT JOIN users u ON t.user_id = u.user_id
        LEFT JOIN item_batches b ON t.batch_id = b.batch_id
        LEFT JOIN locations l ON t.location_id = l.location_id
        ORDER BY t.transaction_time DESC";
$result = mysqli_query($conn, $sql);

// --- 4. DROPDOWN DATA ---
$batches = mysqli_query($conn, "SELECT b.batch_id, b.item_id, i.item_name FROM item_batches b JOIN items i ON b.item_id = i.item_id ORDER BY i.item_name ASC");
$users_dd = mysqli_query($conn, "SELECT user_id, username FROM users ORDER BY username ASC");
$locs_dd  = mysqli_query($conn, "SELECT * FROM locations ORDER BY location_code ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>TrackFlow – Transaction Logs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=28">
</head>

<body class="trackflow-body">

    <aside class="tf-sidebar">
        <div class="tf-logo"><i class="bi bi-box-seam-fill"></i> TRACKFLOW</div>
        <nav class="tf-nav">
            <a href="dashboard.php"><i class="bi bi-grid-fill"></i> Dashboard</a>
            <a href="items-inventory.php"><i class="bi bi-box"></i> Items Inventory</a>
            <a href="batch-expiry.php"><i class="bi bi-clock-history"></i> Batch & Expiry</a>
            <a href="stock-location.php"><i class="bi bi-shop"></i> Stock by Location</a>
            <a href="locations.php"><i class="bi bi-geo-alt"></i> Locations</a>
            <a href="suppliers.php"><i class="bi bi-truck"></i> Suppliers</a>
            <a href="staff.php"><i class="bi bi-people"></i> Staff Management</a>
            <div class="nav-label">ADMINISTRATION</div>
            <a href="transactions.php"><i class="bi bi-file-text"></i> Transaction Logs</a>
            <a href="logout.php" class="tf-logout"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </nav>
    </aside>

    <main class="tf-main">
        <div class="tf-page-header">
            <div class="tf-page-title">
                <h2>Transactions</h2>
                <p>View all stock transactions and movements</p>
            </div>
            <button onclick="openTransModal()" class="tf-btn-primary" style="background:#111827;">
                <i class="bi bi-plus-lg"></i> Add Transaction
            </button>
        </div>

        <div class="summary-row">
            <div class="summary-card">
                <p class="sum-title">Total Transactions</p>
                <h3 class="sum-value"><?php echo number_format($total_txns); ?></h3>
                <span class="sum-sub">All time records</span>
            </div>
            <div class="summary-card">
                <p class="sum-title">Total IN</p>
                <h3 class="sum-value text-green"><?php echo number_format($total_in); ?></h3>
                <span class="sum-sub">Units received</span>
            </div>
            <div class="summary-card">
                <p class="sum-title">Total OUT</p>
                <h3 class="sum-value text-red"><?php echo number_format($total_out); ?></h3>
                <span class="sum-sub">Units dispatched</span>
            </div>
        </div>

        <div class="tf-table-container">
            <div style="padding: 20px; border-bottom: 1px solid #f3f4f6; display:flex; gap:15px; align-items:center;">
                <div style="position:relative;">
                    <i class="bi bi-search" style="position:absolute; left:12px; top:12px; color:#9ca3af;"></i>
                    <input type="text" id="searchInput" placeholder="Search by ID, Item..."
                        style="padding: 10px 10px 10px 35px; width: 280px; border: 1px solid #e5e7eb; border-radius: 8px; background:#f9fafb; outline:none;">
                </div>
            </div>

            <table class="tf-table" id="transTable">
                <thead>
                    <tr>
                        <th style="padding-left:30px;">ID</th>
                        <th>Item</th>
                        <th>Batch</th>
                        <th>Type</th>
                        <th>Location</th>
                        <th>Qty</th>
                        <th>Time</th>
                        <th>User</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            $txn_id = "TXN-" . str_pad($row['transaction_id'], 4, '0', STR_PAD_LEFT);
                            $batch_val = $row['batch_id'];
                            $batch_display = ($batch_val && $batch_val != '0') ? "B" . $batch_val : "-";
                            
                            $loc_display = $row['location_code'] ? $row['location_code'] : "Unknown";
                            $type = strtoupper($row['transaction_type']);
                            $qty = $row['quantity'];
                            $user = $row['username'] ?? 'System';
                            $date = date("M d, H:i A", strtotime($row['transaction_time']));

                            $type_badge = ($type === 'IN') ? 'badge-in' : 'badge-out';
                            $qty_color = ($type === 'IN') ? 'text-green' : 'text-red';
                            $qty_sign = ($type === 'IN') ? '+' : '-';

                            echo "<tr>";
                            echo "<td style='padding-left:30px; font-family:monospace; color:#6b7280; font-size:13px;'>$txn_id</td>";
                            echo "<td style='font-weight:700; color:#1f2937;'>" . $row['item_name'] . "</td>";
                            echo "<td><span class='batch-pill'>$batch_display</span></td>";
                            echo "<td><span class='$type_badge'>$type</span></td>";
                            echo "<td><span class='loc-link'>$loc_display</span></td>";
                            echo "<td style='font-weight:800;' class='$qty_color'>$qty_sign$qty</td>";
                            echo "<td style='color:#6b7280; font-size:13px;'>$date</td>";
                            echo "<td style='color:#6b7280; font-size:13px;'>$user</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='8' style='text-align:center; padding:50px; color:#9ca3af;'>No transactions found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </main>

    <div id="TRANS_LOG_MODAL_OVERLAY" class="modal-overlay">
        <div class="modal-box">
            <h3 style="margin-top:0; margin-bottom:20px;">Add Manual Transaction</h3>

            <form method="POST">
                <label style="font-size:13px; font-weight:600; color:#6b7280;">Transaction Date</label>
                <input type="date" name="t_date" class="modal-input" required value="<?php echo date('Y-m-d'); ?>">

                <label style="font-size:13px; font-weight:600; color:#6b7280;">Select Item Batch</label>
                <select name="batch_data" class="modal-input" required>
                    <option value="">-- Choose Batch --</option>
                    <?php
                    foreach ($batches as $b) {
                        $val = $b['batch_id'] . "," . $b['item_id'];
                        echo "<option value='$val'>" . $b['item_name'] . " (B-" . $b['batch_id'] . ")</option>";
                    }
                    ?>
                </select>

                <label style="font-size:13px; font-weight:600; color:#6b7280; display:block; margin-top:10px;">Location</label>
                <select name="location_id" class="modal-input" required>
                    <option value="">-- Select Where Stock is Moving --</option>
                    <?php
                    if(mysqli_num_rows($locs_dd) > 0) {
                        mysqli_data_seek($locs_dd, 0); // Reset pointer
                        while ($l = mysqli_fetch_assoc($locs_dd)) {
                            // Using location_code only (since description is optional/unknown)
                            echo "<option value='" . $l['location_id'] . "'>" . $l['location_code'] . "</option>";
                        }
                    }
                    ?>
                </select>

                <div style="display:flex; gap:15px; margin-top:10px;">
                    <div style="flex:1;">
                        <label style="font-size:13px; font-weight:600; color:#6b7280;">Type</label>
                        <select name="t_type" class="modal-input" required>
                            <option value="IN">Stock IN (+)</option>
                            <option value="OUT">Stock OUT (-)</option>
                        </select>
                    </div>
                    <div style="flex:1;">
                        <label style="font-size:13px; font-weight:600; color:#6b7280;">Quantity</label>
                        <input type="number" name="quantity" class="modal-input" placeholder="0" min="1" required>
                    </div>
                </div>

                <label style="font-size:13px; font-weight:600; color:#6b7280; margin-top:10px;">Staff Member</label>
                <select name="user_id" class="modal-input" required>
                    <option value="">-- Choose Staff --</option>
                    <?php
                    foreach ($users_dd as $u) {
                        echo "<option value='" . $u['user_id'] . "'>" . $u['username'] . "</option>";
                    }
                    ?>
                </select>

                <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:15px;">
                    <button type="button" class="btn-cancel" onclick="closeTransModal()" style="background:transparent; border:1px solid #e5e7eb; color:#374151;">Cancel</button>
                    <button type="submit" class="tf-btn-primary">Save Log & Update</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openTransModal() { document.getElementById("TRANS_LOG_MODAL_OVERLAY").style.display = "flex"; }
        function closeTransModal() { document.getElementById("TRANS_LOG_MODAL_OVERLAY").style.display = "none"; }
        document.getElementById("searchInput").addEventListener("keyup", function() {
            let val = this.value.toLowerCase();
            document.querySelectorAll("#transTable tbody tr").forEach(row => {
                row.style.display = row.innerText.toLowerCase().includes(val) ? "" : "none";
            });
        });
    </script>
</body>
</html>