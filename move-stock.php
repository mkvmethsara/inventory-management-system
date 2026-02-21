<?php
session_start();
include 'config/db.php';

// 1. Check Login
if (!isset($_SESSION['user_id'])) {
    header("Location: user-login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";
$message_type = "";
$scanned_item = null;
$available_stock = [];

// --- 2. HANDLE STOCK TRANSFER OR DISPATCH ---
if (isset($_POST['move_stock'])) {
    $item_id   = $_POST['item_id'];
    $source_signature = $_POST['source_stock_signature']; // BatchID|LocationID
    $target_loc_id = $_POST['target_location_id']; // Can be an ID or 'OUT'
    $move_qty  = (int)$_POST['quantity'];

    // Split the signature back into IDs
    list($batch_id, $old_loc_id) = explode('|', $source_signature);

    // Get current quantity of the source
    $source_sql = mysqli_query($conn, "SELECT * FROM stock WHERE item_id='$item_id' AND batch_id='$batch_id' AND location_id='$old_loc_id'");
    $source_row = mysqli_fetch_assoc($source_sql);

    if ($source_row && $source_row['quantity'] >= $move_qty) {

        // A. DECREASE from Old Location (Happens for both MOVE and OUT)
        $new_src_qty = $source_row['quantity'] - $move_qty;
        if ($new_src_qty == 0) {
            mysqli_query($conn, "DELETE FROM stock WHERE item_id='$item_id' AND batch_id='$batch_id' AND location_id='$old_loc_id'");
        } else {
            mysqli_query($conn, "UPDATE stock SET quantity = $new_src_qty WHERE item_id='$item_id' AND batch_id='$batch_id' AND location_id='$old_loc_id'");
        }

        // B. CHECK IF WE ARE MOVING OR DISPATCHING
        if ($target_loc_id === 'OUT') {
            // --- STOCK OUT (DISPATCH) LOGIC ---
            mysqli_query($conn, "INSERT INTO stock_transactions (item_id, batch_id, location_id, user_id, transaction_type, quantity, transaction_time, reference) 
                                VALUES ('$item_id', '$batch_id', '$old_loc_id', '$user_id', 'OUT', '$move_qty', NOW(), 'DISPATCHED OUT')");

            $message = "📦 Success! $move_qty items have been Dispatched (Stock Out).";
            $message_type = "success";
        } else {
            // --- MOVE STOCK LOGIC ---
            $check_target = mysqli_query($conn, "SELECT * FROM stock WHERE item_id='$item_id' AND batch_id='$batch_id' AND location_id='$target_loc_id'");

            if (mysqli_num_rows($check_target) > 0) {
                mysqli_query($conn, "UPDATE stock SET quantity = quantity + $move_qty WHERE item_id='$item_id' AND batch_id='$batch_id' AND location_id='$target_loc_id'");
            } else {
                mysqli_query($conn, "INSERT INTO stock (item_id, batch_id, location_id, quantity) VALUES ('$item_id', '$batch_id', '$target_loc_id', '$move_qty')");
            }

            mysqli_query($conn, "INSERT INTO stock_transactions (item_id, batch_id, location_id, user_id, transaction_type, quantity, transaction_time, reference) 
                                VALUES ('$item_id', '$batch_id', '$target_loc_id', '$user_id', 'MOVE', '$move_qty', NOW(), 'FROM LOC $old_loc_id')");

            $message = "✅ Success! Moved $move_qty items to new location.";
            $message_type = "success";
        }
    } else {
        $message = "❌ Error: Not enough stock in the source location.";
        $message_type = "error";
    }
}

// --- 3. HANDLE QR SCAN ---
if (isset($_POST['qr_code'])) {
    $code = mysqli_real_escape_string($conn, trim($_POST['qr_code']));

    if (filter_var($code, FILTER_VALIDATE_URL)) {
        $message = "⚠️ You scanned a Website Link! Please use a Text-Only QR.";
        $message_type = "error";
    } else {
        // FIXED: Removed RFID tag search to prevent Fatal Error
        $sql = "SELECT * FROM items WHERE item_code = '$code'";
        $result = mysqli_query($conn, $sql);

        // Safety check to prevent fatal crashes
        if (!$result) {
            die("<div style='padding:20px; color:red; text-align:center;'><b>Database Error:</b> " . mysqli_error($conn) . "</div>");
        }

        if (mysqli_num_rows($result) > 0) {
            $scanned_item = mysqli_fetch_assoc($result);

            // Find WHERE this item is currently stored
            $stock_sql = "SELECT s.quantity, s.batch_id, s.location_id, l.location_code, l.description, b.expiry_date 
                          FROM stock s 
                          JOIN locations l ON s.location_id = l.location_id 
                          LEFT JOIN item_batches b ON s.batch_id = b.batch_id
                          WHERE s.item_id = '" . $scanned_item['item_id'] . "' AND s.quantity > 0";

            $stock_result = mysqli_query($conn, $stock_sql);

            while ($row = mysqli_fetch_assoc($stock_result)) {
                $available_stock[] = $row;
            }

            if (empty($available_stock)) {
                $message = "⚠️ Item found, but the warehouse has 0 Stock. Nothing to move or dispatch.";
                $message_type = "error";
                $scanned_item = null;
            }
        } else {
            $message = "❌ Item not found: " . htmlspecialchars($code);
            $message_type = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Move & Dispatch Stock</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/staff_style.css">

    <style>
        .result-card { background: white; max-width: 500px; margin: 20px auto; padding: 25px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); text-align: center; }
        .item-name { font-size: 22px; font-weight: 800; color: #1f2937; margin: 5px 0; }
        .item-code { background: #f3f4f6; color: #6b7280; padding: 4px 10px; border-radius: 6px; font-size: 14px; font-family: monospace; display: inline-block; }
        .modern-select, .modern-input { width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 15px; background: white; margin-bottom: 10px; box-sizing: border-box; }
        .btn-confirm { width: 100%; background: #4f46e5; color: white; padding: 15px; border: none; border-radius: 12px; font-size: 16px; font-weight: bold; cursor: pointer; }
        .scanner-container { text-align: center; margin-top: 20px; }
        .scanner-wrapper { width: 100%; max-width: 300px; height: 300px; background: #000; margin: 0 auto 20px auto; border-radius: 12px; display: none; overflow: hidden; border: 4px solid #4f46e5; }
        .btn-scan { background: #4f46e5; color: white; border: none; padding: 15px 30px; border-radius: 30px; font-size: 16px; font-weight: bold; cursor: pointer; }
        .alert-box { padding: 15px; margin-bottom: 20px; border-radius: 12px; font-weight: bold; text-align: center; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .dispatch-option { font-weight: bold; color: #b91c1c; background: #fee2e2; }
    </style>
</head>

<body>

    <div class="header">
        <div class="top-bar">
            <a href="staff_dashboard.php" style="color:white; text-decoration:none;">← Dashboard</a>
            <div class="status">🚚 TRANSFER & DISPATCH</div>
        </div>
        <h1>Move & Dispatch Stock</h1>
        <div class="subtitle">Transfer items between shelves or remove from warehouse</div>
    </div>

    <div class="container">

        <?php if ($message): ?>
            <div class="alert-box <?php echo ($message_type == 'success') ? 'alert-success' : 'alert-error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if (!$scanned_item): ?>
            <div class="scanner-container">
                <form method="POST" id="scanForm">
                    <input type="hidden" name="qr_code" id="hiddenCode">
                </form>
                <div class="scanner-wrapper" id="scannerBox">
                    <div id="reader" style="width:100%;height:100%;"></div>
                </div>
                <button onclick="startScan()" class="btn-scan" id="scanBtn">🔄 Tap to Scan Item</button>
            </div>
        <?php endif; ?>

        <?php if ($scanned_item): ?>
            <div class="result-card">

                <div style="border-bottom:1px dashed #e5e7eb; padding-bottom:15px; margin-bottom:20px;">
                    <div style="background:#e0e7ff; color:#4338ca; font-size:11px; font-weight:800; padding:4px 8px; border-radius:4px; display:inline-block; margin-bottom:5px;">ITEM IDENTIFIED</div>
                    <div class="item-name"><?php echo $scanned_item['item_name']; ?></div>
                    <div class="item-code"><?php echo $scanned_item['item_code']; ?></div>
                </div>

                <form method="POST">
                    <input type="hidden" name="item_id" value="<?php echo $scanned_item['item_id']; ?>">

                    <div style="text-align:left; font-weight:bold; margin-bottom:5px; color:#4b5563;">1. Take From (Source Location)</div>
                    <select name="source_stock_signature" class="modern-select" required>
                        <?php foreach ($available_stock as $stk): ?>
                            <option value="<?php echo $stk['batch_id'] . '|' . $stk['location_id']; ?>">
                                📍 <?php echo $stk['location_code']; ?> (Qty: <?php echo $stk['quantity']; ?>)
                                - Exp: <?php echo $stk['expiry_date'] ? $stk['expiry_date'] : 'N/A'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <div style="text-align:center; margin:10px 0; color:#9ca3af; font-size:20px;">⬇️</div>

                    <div style="text-align:left; font-weight:bold; margin-bottom:5px; color:#4b5563;">2. Move To (Destination)</div>
                    <select name="target_location_id" class="modern-select" required>
                        <option value="OUT" class="dispatch-option">🚪 DISPATCH (Remove from Warehouse)</option>
                        
                        <optgroup label="Or move to another shelf:">
                        <?php
                        $locs = mysqli_query($conn, "SELECT * FROM locations");
                        while ($l = mysqli_fetch_assoc($locs)) {
                            echo "<option value='" . $l['location_id'] . "'>📍 " . $l['location_code'] . " - " . $l['description'] . "</option>";
                        }
                        ?>
                        </optgroup>
                    </select>

                    <div style="text-align:left; font-weight:bold; margin-bottom:5px; color:#4b5563;">3. Quantity to Process</div>
                    <input type="number" name="quantity" class="modern-input" placeholder="Enter amount" required min="1">

                    <button type="submit" name="move_stock" class="btn-confirm">
                        ✅ Confirm Action
                    </button>

                    <a href="move-stock.php" style="display:block; margin-top:15px; color:#ef4444; text-decoration:none; font-weight:bold;">Cancel</a>
                </form>
            </div>
        <?php endif; ?>

    </div>

    <script src="https://unpkg.com/html5-qrcode"></script>
    <script>
        function startScan() {
            document.getElementById("scannerBox").style.display = "block";
            document.getElementById("scanBtn").style.display = "none";
            const html5QrCode = new Html5Qrcode("reader");
            html5QrCode.start({ facingMode: "environment" }, { fps: 10, qrbox: { width: 250, height: 250 } },
                (decodedText) => { html5QrCode.stop(); document.getElementById("hiddenCode").value = decodedText; document.getElementById("scanForm").submit(); },
                (errorMessage) => {}
            ).catch(err => { alert("Camera Error: " + err); document.getElementById("scannerBox").style.display = "none"; document.getElementById("scanBtn").style.display = "block"; });
        }
    </script>
    <style>
        .float-home-btn { position: fixed; bottom: 20px; right: 20px; background: #1e1b4b; color: white; width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3); text-decoration: none; font-size: 24px; z-index: 9999; transition: transform 0.2s; }
        .float-home-btn:hover { transform: scale(1.1); background: #312e81; }
    </style>
    <a href="staff_dashboard.php" class="float-home-btn" title="Back to Dashboard">🏠</a>
</body>
</html>