<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// SECURITY GATE 🔒
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

include '../config/db.php';

// --- 1. HANDLE MOVE STOCK ---
if (isset($_POST['move_stock_btn'])) {
    $item_id   = $_POST['item_id'];
    $batch_id  = $_POST['batch_id'];
    $old_loc   = $_POST['old_location_id'];
    $new_loc   = $_POST['new_location_id'];

    // Move logic: Update the location_id for this specific Item+Batch combination
    $sql = "UPDATE stock SET location_id = '$new_loc' 
            WHERE item_id = '$item_id' 
            AND batch_id = '$batch_id' 
            AND location_id = '$old_loc'";
    
    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('✅ Stock Moved Successfully!'); window.location.href='stock-location.php';</script>";
    } else {
        echo "<script>alert('❌ Database Error: " . mysqli_error($conn) . "');</script>";
    }
}

// --- FILTER LOGIC ---
$filter_loc = isset($_GET['loc']) ? $_GET['loc'] : 'all';
$where_clause = "";

if ($filter_loc !== 'all') {
    $safe_loc = mysqli_real_escape_string($conn, $filter_loc);
    $where_clause = "WHERE l.location_code = '$safe_loc'";
}

// --- FETCH STOCK DATA ---
// FIXED: Removed 's.stock_id' which was causing the crash.
// We now select item_id, batch_id, and location_id explicitly.
$sql = "SELECT 
            s.item_id, 
            s.batch_id, 
            s.location_id,
            s.quantity, 
            l.location_code, 
            i.item_name
        FROM stock s
        JOIN locations l ON s.location_id = l.location_id
        JOIN items i ON s.item_id = i.item_id
        $where_clause
        ORDER BY l.location_code ASC";

$result = mysqli_query($conn, $sql);

// Check if query failed
if (!$result) {
    die("<div style='padding:20px; color:red;'><b>CRITICAL SQL ERROR:</b> " . mysqli_error($conn) . "</div>");
}

// --- FETCH LOCATIONS FOR DROPDOWN ---
$loc_sql = "SELECT * FROM locations ORDER BY location_code ASC";
$loc_res = mysqli_query($conn, $loc_sql);
$locations_list = [];
while ($row = mysqli_fetch_assoc($loc_res)) {
    $locations_list[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>TrackFlow – Stock by Location</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=42">
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
            <a href="stock-location.php" class="active"><i class="bi bi-shop"></i> Stock by Location</a>
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
                <h2>Stock Management</h2>
                <p>View and move stock between locations</p>
            </div>

            <div class="tf-loc-filter-group">
                <a href="stock-location.php?loc=all" class="tf-loc-btn <?php echo ($filter_loc == 'all') ? 'active' : ''; ?>">All</a>
                <?php foreach ($locations_list as $l): ?>
                    <a href="stock-location.php?loc=<?php echo $l['location_code']; ?>"
                        class="tf-loc-btn <?php echo ($filter_loc == $l['location_code']) ? 'active' : ''; ?>">
                        <?php echo $l['location_code']; ?>
                    </a>
                <?php endforeach; ?>
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
                        <th style="text-align:right; padding-right:30px;">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            $batch_display = $row['batch_id'] ? "B" . $row['batch_id'] : "N/A";
                            $qty = number_format($row['quantity']);
                            
                            // Prepare data for the JS function
                            $item_id = $row['item_id'];
                            $batch_id = $row['batch_id'];
                            $old_loc = $row['location_id'];
                            $item_name = addslashes($row['item_name']);
                            $loc_code = $row['location_code'];

                            echo "<tr>";
                            // Location
                            echo "<td style='padding-left:30px;'>
                                    <span class='loc-badge'>
                                        <i class='bi bi-geo-alt-fill'></i> " . $loc_code . "
                                    </span>
                                  </td>";

                            // Item Name
                            echo "<td style='font-weight:600; color:#1f2937;'>" . $row['item_name'] . "</td>";

                            // Batch
                            echo "<td style='color:#6b7280; font-size:13px; font-weight:500;'>" . $batch_display . "</td>";

                            // Quantity
                            echo "<td style='font-weight:800; font-size:16px; color:#111827;'>$qty</td>";

                            // ACTION BUTTON (Move)
                            echo "<td style='text-align:right; padding-right:30px;'>
                                    <button class='tf-btn-secondary' style='font-size:12px; padding:6px 12px;' 
                                        onclick='openMoveModal(\"$item_id\", \"$batch_id\", \"$old_loc\", \"$item_name\", \"$loc_code\")'>
                                        <i class='bi bi-arrow-left-right'></i> Move
                                    </button>
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

    <div id="MOVE_MODAL" class="modal-overlay">
        <div class="modal-box">
            <h3 style="margin-top:0; margin-bottom:5px;">Move Stock</h3>
            <p id="move_item_label" style="margin-top:0; color:#6b7280; font-size:14px; margin-bottom:20px;">...</p>
            
            <form method="POST">
                <input type="hidden" name="item_id" id="input_item_id">
                <input type="hidden" name="batch_id" id="input_batch_id">
                <input type="hidden" name="old_location_id" id="input_old_loc">

                <label style="font-size:13px; font-weight:600; color:#6b7280;">Current Location</label>
                <input type="text" id="current_loc_display" disabled style="background:#f3f4f6; color:#9ca3af; cursor:not-allowed;">

                <label style="font-size:13px; font-weight:600; color:#6b7280; margin-top:10px; display:block;">Select New Location</label>
                <select name="new_location_id" required>
                    <?php foreach ($locations_list as $l): ?>
                        <option value="<?php echo $l['location_id']; ?>">
                            <?php echo $l['location_code'] . " (" . $l['location_name'] . ")"; ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                    <button type="button" onclick="closeModal()" class="btn-cancel" style="background:transparent; border:1px solid #e5e7eb; color:#374151;">Cancel</button>
                    <button type="submit" name="move_stock_btn" class="tf-btn-primary">Confirm Move</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById("MOVE_MODAL");
        const inputItem = document.getElementById("input_item_id");
        const inputBatch = document.getElementById("input_batch_id");
        const inputOldLoc = document.getElementById("input_old_loc");
        
        const labelItem = document.getElementById("move_item_label");
        const displayLoc = document.getElementById("current_loc_display");

        function openMoveModal(itemId, batchId, oldLocId, itemName, locCode) {
            // Fill hidden inputs so PHP knows what to move
            inputItem.value = itemId;
            inputBatch.value = batchId;
            inputOldLoc.value = oldLocId;
            
            // Update UI text
            labelItem.innerText = "Moving: " + itemName + " (Batch " + batchId + ")";
            displayLoc.value = locCode;
            
            modal.style.display = "flex";
        }

        function closeModal() {
            modal.style.display = "none";
        }

        window.onclick = function(e) {
            if(e.target == modal) closeModal();
        }
    </script>
</body>
</html>