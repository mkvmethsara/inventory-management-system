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

// --- 1. HANDLE MOVE STOCK (Updated for your DB) ---
if (isset($_POST['move_stock_btn'])) {
    $item_id   = $_POST['item_id'];
    $batch_id  = $_POST['batch_id'];
    $old_loc   = $_POST['old_location_id'];
    $new_loc   = $_POST['new_location_id'];
    $move_qty  = (int)$_POST['move_quantity'];

    // Validation
    if ($old_loc == $new_loc) {
        echo "<script>alert('⚠️ Source and Destination are the same!'); window.location.href='stock-location.php';</script>";
        exit();
    }
    if ($move_qty <= 0) {
        echo "<script>alert('⚠️ Quantity must be greater than 0!'); window.location.href='stock-location.php';</script>";
        exit();
    }

    // A. Handle Batch Logic (Your DB allows NULL or '0' for no batch)
    // We build a SQL snippet to check batch_id safely
    $batch_sql_check = (empty($batch_id) || $batch_id == '0') ? "(batch_id IS NULL OR batch_id = '0')" : "batch_id = '$batch_id'";

    // B. Check Source Quantity
    $check_sql = "SELECT quantity FROM stock WHERE item_id='$item_id' AND $batch_sql_check AND location_id='$old_loc'";
    $check_source = mysqli_query($conn, $check_sql);
    $source_row = mysqli_fetch_assoc($check_source);

    if (!$source_row || $source_row['quantity'] < $move_qty) {
        echo "<script>alert('❌ Error: Not enough stock available to move!'); window.location.href='stock-location.php';</script>";
        exit();
    }

    // --- C. EXECUTE MOVE ---

    // 1. Decrease Source (Old Location)
    $new_source_qty = $source_row['quantity'] - $move_qty;
    if ($new_source_qty == 0) {
        // If empty, delete the row
        mysqli_query($conn, "DELETE FROM stock WHERE item_id='$item_id' AND $batch_sql_check AND location_id='$old_loc'");
    } else {
        // Else, just update quantity
        mysqli_query($conn, "UPDATE stock SET quantity='$new_source_qty' WHERE item_id='$item_id' AND $batch_sql_check AND location_id='$old_loc'");
    }

    // 2. Increase Destination (New Location)
    // Check if stock already exists in the new location
    $dest_sql = "SELECT quantity FROM stock WHERE item_id='$item_id' AND $batch_sql_check AND location_id='$new_loc'";
    $check_dest = mysqli_query($conn, $dest_sql);
    $dest_row = mysqli_fetch_assoc($check_dest);

    if ($dest_row) {
        // Update existing record
        $new_dest_qty = $dest_row['quantity'] + $move_qty;
        mysqli_query($conn, "UPDATE stock SET quantity='$new_dest_qty' WHERE item_id='$item_id' AND $batch_sql_check AND location_id='$new_loc'");
    } else {
        // Insert new record
        // Use '0' or NULL for batch_id depending on how you store empty batches. Assuming '0' or ID from POST.
        $b_val = (empty($batch_id)) ? "0" : "'$batch_id'";
        mysqli_query($conn, "INSERT INTO stock (item_id, batch_id, location_id, quantity) VALUES ('$item_id', $b_val, '$new_loc', '$move_qty')");
    }

    echo "<script>alert('✅ Successfully moved $move_qty items!'); window.location.href='stock-location.php';</script>";
}

// --- FILTER LOGIC ---
$filter_loc = isset($_GET['loc']) ? $_GET['loc'] : 'all';
$where_clause = "";

if ($filter_loc !== 'all') {
    $safe_loc = mysqli_real_escape_string($conn, $filter_loc);
    $where_clause = "WHERE l.location_code = '$safe_loc'";
}

// --- FETCH STOCK DATA ---
// We select columns explicitly. No stock_id needed.
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
if (!$result) {
    die("Database Error: " . mysqli_error($conn));
}

// --- FETCH LOCATIONS ---
$loc_res = mysqli_query($conn, "SELECT * FROM locations ORDER BY location_code ASC");
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
    <link rel="stylesheet" href="../assets/css/style.css?v=55">
</head>

<body class="trackflow-body">

    <aside class="tf-sidebar">
        <div class="tf-logo"><i class="bi bi-box-seam-fill"></i> TRACKFLOW</div>
        <nav class="tf-nav">
            <a href="dashboard.php"><i class="bi bi-grid-fill"></i> Dashboard</a>
            <a href="items-inventory.php"><i class="bi bi-box"></i> Items Inventory</a>
            <a href="batch-expiry.php"><i class="bi bi-clock-history"></i> Batch & Expiry</a>
            <a href="stock-location.php" class="active"><i class="bi bi-shop"></i> Stock by Location</a>
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
                            // Check if batch is 0 or empty
                            $batch_val = $row['batch_id'];
                            $batch_display = ($batch_val && $batch_val != '0') ? "B" . $batch_val : "No Batch";

                            $qty = number_format($row['quantity']);

                            // Prepare JS data
                            $item_id = $row['item_id'];
                            $batch_id = $row['batch_id'];
                            $old_loc = $row['location_id'];
                            $item_name = addslashes($row['item_name']);
                            $loc_code = $row['location_code'];
                            $max_qty = $row['quantity'];

                            echo "<tr>";
                            // Location Badge
                            echo "<td style='padding-left:30px;'>
                                    <span class='loc-badge'><i class='bi bi-geo-alt-fill'></i> $loc_code</span>
                                  </td>";

                            echo "<td style='font-weight:600; color:#1f2937;'>" . $row['item_name'] . "</td>";
                            echo "<td style='color:#6b7280; font-size:13px;'>" . $batch_display . "</td>";
                            echo "<td style='font-weight:800; font-size:16px; color:#111827;'>$qty</td>";

                            // Action Button
                            echo "<td style='text-align:right; padding-right:30px;'>
                                    <button class='tf-btn-secondary' style='font-size:12px; padding:6px 12px;' 
                                        onclick='openMoveModal(\"$item_id\", \"$batch_id\", \"$old_loc\", \"$item_name\", \"$loc_code\", \"$max_qty\")'>
                                        <i class='bi bi-arrow-left-right'></i> Move
                                    </button>
                                  </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' style='text-align:center; padding:50px; color:#9ca3af;'>No stock found.</td></tr>";
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

                <label style="font-size:13px; font-weight:600; color:#6b7280; margin-top:10px; display:block;">Quantity to Move</label>
                <div style="display:flex; align-items:center; gap:10px;">
                    <input type="number" name="move_quantity" id="input_move_qty" min="1" required style="font-weight:bold;">
                    <span id="max_qty_label" style="font-size:12px; color:#6b7280;">(Max: 0)</span>
                </div>

                <label style="font-size:13px; font-weight:600; color:#6b7280; margin-top:10px; display:block;">Select New Location</label>
                <select name="new_location_id" required>
                    <?php foreach ($locations_list as $l): ?>
                        <option value="<?php echo $l['location_id']; ?>">
                            <?php echo $l['location_code'] . " - " . $l['description']; ?>
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
        const inputQty = document.getElementById("input_move_qty");
        const labelItem = document.getElementById("move_item_label");
        const displayLoc = document.getElementById("current_loc_display");
        const labelMax = document.getElementById("max_qty_label");

        function openMoveModal(itemId, batchId, oldLocId, itemName, locCode, maxQty) {
            inputItem.value = itemId;
            inputBatch.value = batchId;
            inputOldLoc.value = oldLocId;

            let batchText = (batchId && batchId != '0') ? " (Batch " + batchId + ")" : "";
            labelItem.innerText = "Moving: " + itemName + batchText;

            displayLoc.value = locCode;

            inputQty.value = "";
            inputQty.max = maxQty;
            inputQty.placeholder = "Max: " + maxQty;
            labelMax.innerText = "(Available: " + maxQty + ")";

            modal.style.display = "flex";
        }

        function closeModal() {
            modal.style.display = "none";
        }
        window.onclick = function(e) {
            if (e.target == modal) closeModal();
        }
    </script>
</body>

</html>