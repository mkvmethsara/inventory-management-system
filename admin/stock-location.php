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

// --- 1. HANDLE MOVE OR DISPATCH STOCK ---
if (isset($_POST['move_stock_btn']) || isset($_POST['dispatch_btn'])) {
    $item_id   = $_POST['item_id'];
    $batch_id  = $_POST['batch_id'];
    $old_loc   = $_POST['old_location_id'];
    $move_qty  = (int)$_POST['move_quantity'];

    // Validation
    if ($move_qty <= 0) {
        echo "<script>alert('⚠️ Quantity must be greater than 0!'); window.location.href='stock-location.php';</script>";
        exit();
    }

    // A. Handle Batch Logic
    $batch_sql_check = (empty($batch_id) || $batch_id == '0') ? "(batch_id IS NULL OR batch_id = '0')" : "batch_id = '$batch_id'";

    // B. Check Source Quantity
    $check_sql = "SELECT quantity FROM stock WHERE item_id='$item_id' AND $batch_sql_check AND location_id='$old_loc'";
    $check_source = mysqli_query($conn, $check_sql);
    $source_row = mysqli_fetch_assoc($check_source);

    if (!$source_row || $source_row['quantity'] < $move_qty) {
        echo "<script>alert('❌ Error: Not enough stock available!'); window.location.href='stock-location.php';</script>";
        exit();
    }

    // --- C. 🚪 DISPATCH (DELETE) LOGIC ---
    if (isset($_POST['dispatch_btn'])) {
        $new_source_qty = $source_row['quantity'] - $move_qty;

        if ($new_source_qty == 0) {
            mysqli_query($conn, "DELETE FROM stock WHERE item_id='$item_id' AND $batch_sql_check AND location_id='$old_loc'");
        } else {
            mysqli_query($conn, "UPDATE stock SET quantity='$new_source_qty' WHERE item_id='$item_id' AND $batch_sql_check AND location_id='$old_loc'");
        }

        mysqli_query($conn, "INSERT INTO stock_transactions (item_id, batch_id, location_id, user_id, transaction_type, quantity, transaction_time, reference) 
                             VALUES ('$item_id', '$batch_id', '$old_loc', '" . $_SESSION['user_id'] . "', 'OUT', '$move_qty', NOW(), 'ADMIN DISPATCH')");

        echo "<script>alert('✅ Successfully Dispatched $move_qty items from the warehouse!'); window.location.href='stock-location.php';</script>";
        exit();
    }

    // --- D. 🚚 NORMAL MOVE LOGIC ---
    elseif (isset($_POST['move_stock_btn'])) {

        $new_loc_code = mysqli_real_escape_string($conn, trim($_POST['new_location_code']));
        if (empty($new_loc_code)) {
            echo "<script>alert('⚠️ Please type a destination location!'); window.location.href='stock-location.php';</script>";
            exit();
        }

        // 1. Check if typed location exists, if not, AUTO-CREATE IT!
        $loc_query = mysqli_query($conn, "SELECT location_id FROM locations WHERE location_code='$new_loc_code'");
        if (mysqli_num_rows($loc_query) > 0) {
            $loc_row = mysqli_fetch_assoc($loc_query);
            $new_loc = $loc_row['location_id'];
        } else {
            mysqli_query($conn, "INSERT INTO locations (location_code, description) VALUES ('$new_loc_code', 'Auto-created')");
            $new_loc = mysqli_insert_id($conn);
        }

        if ($old_loc == $new_loc) {
            echo "<script>alert('⚠️ Source and Destination are the same!'); window.location.href='stock-location.php';</script>";
            exit();
        }

        // 2. Capacity Check (Max 500)
        $capacity_check_sql = mysqli_query($conn, "SELECT SUM(quantity) as total_bin_qty FROM stock WHERE location_id='$new_loc'");
        $capacity_row = mysqli_fetch_assoc($capacity_check_sql);
        $current_bin_qty = $capacity_row['total_bin_qty'] ? (int)$capacity_row['total_bin_qty'] : 0;

        $max_capacity = 500;
        $available_space = $max_capacity - $current_bin_qty;

        if ($move_qty > $available_space) {
            echo "<script>alert('❌ CAPACITY ERROR!\\n\\nBin [$new_loc_code] can only hold $max_capacity items.\\nCurrently inside: $current_bin_qty\\nAvailable space: $available_space\\n\\nYou cannot move $move_qty items here.'); window.location.href='stock-location.php';</script>";
            exit();
        }

        // 3. Decrease Source
        $new_source_qty = $source_row['quantity'] - $move_qty;
        if ($new_source_qty == 0) {
            mysqli_query($conn, "DELETE FROM stock WHERE item_id='$item_id' AND $batch_sql_check AND location_id='$old_loc'");
        } else {
            mysqli_query($conn, "UPDATE stock SET quantity='$new_source_qty' WHERE item_id='$item_id' AND $batch_sql_check AND location_id='$old_loc'");
        }

        // 4. Increase Destination
        $dest_sql = "SELECT quantity FROM stock WHERE item_id='$item_id' AND $batch_sql_check AND location_id='$new_loc'";
        $check_dest = mysqli_query($conn, $dest_sql);
        $dest_row = mysqli_fetch_assoc($check_dest);

        if ($dest_row) {
            $new_dest_qty = $dest_row['quantity'] + $move_qty;
            mysqli_query($conn, "UPDATE stock SET quantity='$new_dest_qty' WHERE item_id='$item_id' AND $batch_sql_check AND location_id='$new_loc'");
        } else {
            $b_val = (empty($batch_id)) ? "0" : "'$batch_id'";
            mysqli_query($conn, "INSERT INTO stock (item_id, batch_id, location_id, quantity) VALUES ('$item_id', $b_val, '$new_loc', '$move_qty')");
        }

        // 5. Log as MOVE
        mysqli_query($conn, "INSERT INTO stock_transactions (item_id, batch_id, location_id, user_id, transaction_type, quantity, transaction_time, reference) 
                             VALUES ('$item_id', '$batch_id', '$new_loc', '" . $_SESSION['user_id'] . "', 'MOVE', '$move_qty', NOW(), 'FROM LOC $old_loc')");

        echo "<script>alert('✅ Successfully moved $move_qty items to $new_loc_code!'); window.location.href='stock-location.php';</script>";
        exit();
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

// --- FETCH LOCATIONS FOR DATALIST ---
$loc_res = mysqli_query($conn, "SELECT location_code FROM locations ORDER BY location_code ASC");
$locations_list = [];
while ($row = mysqli_fetch_assoc($loc_res)) {
    $locations_list[] = $row['location_code'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>TrackFlow – Stock by Location</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=57">
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
                <p>View, move, or dispatch stock from specific locations</p>
            </div>

            <div class="tf-loc-filter-group" style="max-width: 600px; overflow-x: auto; white-space: nowrap; display: flex; gap: 8px; padding-bottom: 5px;">
                <a href="stock-location.php?loc=all" class="tf-loc-btn <?php echo ($filter_loc == 'all') ? 'active' : ''; ?>">All</a>
                <?php foreach ($locations_list as $l_code): ?>
                    <a href="stock-location.php?loc=<?php echo $l_code; ?>"
                        class="tf-loc-btn <?php echo ($filter_loc == $l_code) ? 'active' : ''; ?>">
                        <?php echo $l_code; ?>
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
                            $batch_val = $row['batch_id'];
                            $batch_display = ($batch_val && $batch_val != '0') ? "B" . $batch_val : "No Batch";
                            $qty = number_format($row['quantity']);

                            $item_id = $row['item_id'];
                            $batch_id = $row['batch_id'];
                            $old_loc = $row['location_id'];
                            $item_name = addslashes($row['item_name']);
                            $loc_code = $row['location_code'];
                            $max_qty = $row['quantity'];

                            echo "<tr>";
                            echo "<td style='padding-left:30px;'><span class='loc-badge'><i class='bi bi-geo-alt-fill'></i> $loc_code</span></td>";
                            echo "<td style='font-weight:600; color:#1f2937;'>" . $row['item_name'] . "</td>";
                            echo "<td style='color:#6b7280; font-size:13px;'>" . $batch_display . "</td>";
                            echo "<td style='font-weight:800; font-size:16px; color:#111827;'>$qty</td>";

                            echo "<td style='text-align:right; padding-right:30px;'>
                                    <button class='tf-btn-secondary' style='font-size:12px; padding:6px 12px;' 
                                        onclick='openMoveModal(\"$item_id\", \"$batch_id\", \"$old_loc\", \"$item_name\", \"$loc_code\", \"$max_qty\")'>
                                        <i class='bi bi-pencil-square'></i> Manage
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
        <div class="modal-box" style="max-width: 450px;">
            <h3 style="margin-top:0; margin-bottom:5px;">Manage Stock</h3>
            <p id="move_item_label" style="margin-top:0; color:#4f46e5; font-size:15px; font-weight:bold; margin-bottom:20px;">...</p>

            <form method="POST">
                <input type="hidden" name="item_id" id="input_item_id">
                <input type="hidden" name="batch_id" id="input_batch_id">
                <input type="hidden" name="old_location_id" id="input_old_loc">

                <label style="font-size:13px; font-weight:600; color:#6b7280;">Current Location</label>
                <input type="text" id="current_loc_display" disabled style="background:#f3f4f6; color:#9ca3af; cursor:not-allowed; border: 1px solid #e5e7eb; width: 100%; padding: 10px; border-radius: 8px; margin-bottom: 10px;">

                <label style="font-size:13px; font-weight:600; color:#6b7280;">Quantity to Process</label>
                <div style="display:flex; align-items:center; gap:10px; margin-bottom: 15px;">
                    <input type="number" name="move_quantity" id="input_move_qty" min="1" required style="font-weight:bold; width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px;">
                    <span id="max_qty_label" style="font-size:12px; color:#6b7280; min-width: 90px;">(Max: 0)</span>
                </div>

                <div style="background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px dashed #cbd5e1; margin-bottom: 20px;">
                    <label style="font-size:13px; font-weight:600; color:#334155; display:block; margin-bottom:5px;">🚚 Move to New Location</label>
                    <p style="font-size: 11px; color: #64748b; margin-top: 0; margin-bottom: 8px;">Type a code (e.g., 1-05-12). If it doesn't exist, we will create it.</p>

                    <input list="location_suggestions" name="new_location_code" id="input_new_loc" placeholder="Type or select a location..." style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px; font-weight: bold; color: #1e293b;" required>
                    <datalist id="location_suggestions">
                        <?php foreach ($locations_list as $l_code): ?>
                            <option value="<?php echo $l_code; ?>">
                            <?php endforeach; ?>
                    </datalist>
                </div>

                <div style="display:flex; justify-content:space-between; gap:10px; border-top: 1px solid #e5e7eb; padding-top: 15px;">
                    <button type="submit" name="dispatch_btn" formnovalidate style="background:#dc2626; color:white; border:none; padding: 10px 15px; border-radius: 8px; cursor: pointer; font-weight: bold; flex: 1;">
                        <i class="bi bi-trash3"></i> Delete / Dispatch
                    </button>

                    <button type="button" onclick="closeModal()" class="btn-cancel" style="background:transparent; border:1px solid #e5e7eb; color:#374151; padding: 10px 15px; border-radius: 8px; cursor: pointer;">Cancel</button>

                    <button type="submit" name="move_stock_btn" class="tf-btn-primary" style="padding: 10px 15px; flex: 1;">
                        Confirm Move
                    </button>
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
        const inputNewLoc = document.getElementById("input_new_loc");
        const labelItem = document.getElementById("move_item_label");
        const displayLoc = document.getElementById("current_loc_display");
        const labelMax = document.getElementById("max_qty_label");

        function openMoveModal(itemId, batchId, oldLocId, itemName, locCode, maxQty) {
            inputItem.value = itemId;
            inputBatch.value = batchId;
            inputOldLoc.value = oldLocId;

            let batchText = (batchId && batchId != '0') ? " (B" + batchId + ")" : "";
            labelItem.innerText = itemName + batchText;

            displayLoc.value = locCode;

            // Clear the text box for the new location
            inputNewLoc.value = "";

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