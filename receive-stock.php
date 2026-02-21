<?php
session_start();
include 'config/db.php';

// 1. Check Login
if (!isset($_SESSION['user_id'])) {
    header("Location: user-login.php");
    exit();
}

// 2. Initialize Variables
$user_id = $_SESSION['user_id'];
$scanned_item = null;
$message = "";
$message_type = "";

// 3. HANDLE STOCK SUBMISSION
if (isset($_POST['save_stock'])) {
    $item_id = $_POST['item_id'];
    $total_quantity = (int)$_POST['quantity'];
    $expiry_date = $_POST['expiry_date'];
    $received_date = date('Y-m-d');
    
    // Get the Starting Column and Bin entered by the staff
    $current_col = (int)$_POST['col'];
    $current_bin = (int)$_POST['bin'];

    // 🔴 WAREHOUSE SETTINGS (Change this number to whatever capacity you want)
    $BIN_CAPACITY = 500; 

    // Find out if the item is a Drink or Dry
    $item_q = mysqli_query($conn, "SELECT category FROM items WHERE item_id='$item_id'");
    $item_row = mysqli_fetch_assoc($item_q);
    $category = $item_row['category'];
    $prefix = ($category == 'Drinks') ? '0' : '1';

    // STEP A: Create a brand new Batch FIRST
    $insert_batch = "INSERT INTO item_batches (item_id, received_date, expiry_date) VALUES ('$item_id', '$received_date', '$expiry_date')";
    
    if (mysqli_query($conn, $insert_batch)) {
        $new_batch_id = mysqli_insert_id($conn);
        $remaining_qty = $total_quantity;
        $messages_array = []; // To store our success messages

        // --- AUTO-MAGIC SPILL-OVER LOGIC ---
        while ($remaining_qty > 0) {
            
            // Format current location code (e.g. 1-05-12)
            $col_str = str_pad($current_col, 2, '0', STR_PAD_LEFT);
            $bin_str = str_pad($current_bin, 2, '0', STR_PAD_LEFT);
            $loc_code = "$prefix-$col_str-$bin_str";
            
            // 1. Check if this Location already exists, or create it
            $loc_q = mysqli_query($conn, "SELECT location_id FROM locations WHERE location_code='$loc_code'");
            if (mysqli_num_rows($loc_q) > 0) {
                $loc_row = mysqli_fetch_assoc($loc_q);
                $location_id = $loc_row['location_id'];
            } else {
                $zone_name = ($category == 'Drinks') ? 'Drinks Zone' : 'Dry Zone';
                $desc = "$zone_name - Col $current_col, Bin $current_bin";
                mysqli_query($conn, "INSERT INTO locations (location_code, description) VALUES ('$loc_code', '$desc')");
                $location_id = mysqli_insert_id($conn);
            }

            // 2. Check how much space is left in this specific bin
            $cap_q = mysqli_query($conn, "SELECT COALESCE(SUM(quantity), 0) as used_qty FROM stock WHERE location_id='$location_id'");
            $cap_row = mysqli_fetch_assoc($cap_q);
            $used_qty = (int)$cap_row['used_qty'];
            
            $available_space = $BIN_CAPACITY - $used_qty;

            // 3. If bin is completely full, jump to the next bin and try again
            if ($available_space <= 0) {
                $current_bin++;
                if ($current_bin > 99) { // If Bin hits 100, reset to 1 and move to next Column
                    $current_bin = 1;
                    $current_col++;
                }
                continue; // Restart the loop with the new bin
            }

            // 4. Calculate how much we can actually put on this shelf right now
            $qty_to_store = min($remaining_qty, $available_space);

            // 5. Save Stock and Log Transaction
            mysqli_query($conn, "INSERT INTO stock (item_id, batch_id, location_id, quantity) VALUES ('$item_id', '$new_batch_id', '$location_id', '$qty_to_store')");
            mysqli_query($conn, "INSERT INTO stock_transactions (item_id, batch_id, location_id, user_id, transaction_type, quantity, transaction_time, reference) 
                                VALUES ('$item_id', '$new_batch_id', '$location_id', '$user_id', 'IN', '$qty_to_store', NOW(), 'QR-AUTO')");

            // Add instruction to the staff message
            $messages_array[] = "📦 Store <b>$qty_to_store</b> in <b>$loc_code</b>";

            // 6. Subtract the stored amount from our total
            $remaining_qty -= $qty_to_store;

            // 7. If we STILL have more items holding in our hands, jump to next bin for the next loop
            if ($remaining_qty > 0) {
                $current_bin++;
                if ($current_bin > 99) {
                    $current_bin = 1;
                    $current_col++;
                }
            }
        }
        // --- END SPILL-OVER LOGIC ---

        // Display the final instructions
        $message = "✅ <b>Success! Stock split automatically:</b><br><br>" . implode("<br>", $messages_array);
        $message_type = "success";
        
    } else {
        $message = "❌ Database Error: " . mysqli_error($conn);
        $message_type = "error";
    }
}

// 4. HANDLE QR SCAN
if (isset($_POST['qr_code'])) {
    $code = mysqli_real_escape_string($conn, trim($_POST['qr_code']));

    if (filter_var($code, FILTER_VALIDATE_URL)) {
        $message = "⚠️ You scanned a Website Link! Please use a Text-Only QR.";
        $message_type = "error";
    } else {
        $sql = "SELECT * FROM items WHERE item_code = '$code'";
        $result = mysqli_query($conn, $sql);

        if (!$result) {
            die("<div style='padding:20px; color:red; text-align:center;'><b>Database Error:</b> " . mysqli_error($conn) . "</div>");
        }

        if (mysqli_num_rows($result) > 0) {
            $scanned_item = mysqli_fetch_assoc($result);
        } else {
            $message = "❌ No Item found for Code: " . htmlspecialchars($code);
            $message_type = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Receive Stock</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/staff_style.css">

    <style>
        .scanner-container { text-align: center; margin-top: 20px; }
        .scanner-wrapper { width: 100%; max-width: 300px; height: 300px; background: #000; margin: 0 auto 20px auto; border-radius: 12px; overflow: hidden; position: relative; display: none; border: 4px solid #4f46e5; }
        .btn-start-scan { background: #4f46e5; color: white; border: none; padding: 15px 30px; border-radius: 30px; font-size: 18px; font-weight: bold; cursor: pointer; box-shadow: 0 4px 15px rgba(79, 70, 229, 0.4); display: inline-block; }
        .btn-start-scan:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(79, 70, 229, 0.6); }
        .alert-box { padding: 15px; margin-bottom: 20px; border-radius: 12px; font-weight: normal; text-align: left; line-height:1.5; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; text-align:center; font-weight:bold; }
        .result-card { background: white; max-width: 450px; margin: 20px auto; padding: 30px; border-radius: 24px; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1); text-align: center; }
        .modern-select, .modern-input { width: 100%; padding: 14px; margin-bottom: 15px; border: 2px solid #e5e7eb; border-radius: 12px; font-size: 16px; box-sizing: border-box; }
        .btn-confirm { width: 100%; background: #10b981; color: white; padding: 16px; border: none; border-radius: 12px; font-size: 16px; font-weight: bold; cursor: pointer; }
        .badge-success { background: #dcfce7; color: #166534; padding: 5px 10px; border-radius: 15px; font-size: 12px; font-weight: bold; display: inline-block; margin-bottom: 10px; }
    </style>
</head>

<body>

    <div class="header">
        <div class="top-bar">
            <a href="staff_dashboard.php" style="color:white; text-decoration:none;">← Dashboard</a>
            <div class="status">🟢 RECEIVING MODE</div>
        </div>
        <h1>Receive Stock</h1>
        <div class="subtitle">Scan QR Code to add inventory</div>
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

                <button onclick="startScan()" class="btn-start-scan" id="scanBtn">📸 Tap to Scan QR</button>
            </div>
        <?php endif; ?>

        <?php if ($scanned_item): ?>
            <div class="result-card">

                <div class="item-header">
                    <div class="badge-success">✅ Verified Item</div>
                    <h2 style="margin:10px 0; color:#374151;"><?php echo $scanned_item['item_name']; ?></h2>
                    <div style="color:#6b7280; font-family:monospace;"><?php echo $scanned_item['item_code']; ?></div>
                </div>

                <form method="POST" style="margin-top:20px;">
                    <input type="hidden" name="item_id" value="<?php echo $scanned_item['item_id']; ?>">

                    <div style="text-align:left; margin-bottom:5px; font-weight:bold; color:#4b5563;">Expiry Date:</div>
                    <input type="date" name="expiry_date" class="modern-input" required>

                    <div style="display:flex; gap:10px; margin-bottom:15px;">
                        <div style="flex:1;">
                            <div style="text-align:left; margin-bottom:5px; font-weight:bold; color:#4b5563;">Start Column:</div>
                            <input type="number" name="col" class="modern-input" placeholder="e.g. 5" required min="1" max="50">
                        </div>
                        <div style="flex:1;">
                            <div style="text-align:left; margin-bottom:5px; font-weight:bold; color:#4b5563;">Start Bin:</div>
                            <input type="number" name="bin" class="modern-input" placeholder="e.g. 12" required min="1" max="99">
                        </div>
                    </div>

                    <div style="text-align:left; margin-bottom:5px; font-weight:bold; color:#4b5563;">Total Quantity to Store:</div>
                    <input type="number" name="quantity" class="modern-input" placeholder="e.g. 1200" required min="1">

                    <button type="submit" name="save_stock" class="btn-confirm">✅ Confirm & Save</button>
                    <a href="receive-stock.php" style="display:block; margin-top:15px; color:#ef4444; text-decoration:none; font-weight:bold;">Cancel</a>
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

            html5QrCode.start({
                    facingMode: "environment"
                }, {
                    fps: 10,
                    qrbox: {
                        width: 250,
                        height: 250
                    }
                },
                (decodedText) => {
                    html5QrCode.stop();
                    document.getElementById("hiddenCode").value = decodedText;
                    document.getElementById("scanForm").submit();
                },
                (errorMessage) => {}
            ).catch(err => {
                alert("Camera Error: " + err);
                document.getElementById("scannerBox").style.display = "none";
                document.getElementById("scanBtn").style.display = "inline-block";
            });
        }
    </script>

    <style>
        .float-home-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #1e1b4b;
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            text-decoration: none;
            font-size: 24px;
            z-index: 9999;
            transition: transform 0.2s;
        }

        .float-home-btn:hover {
            transform: scale(1.1);
            background: #312e81;
        }
    </style>

    <a href="staff_dashboard.php" class="float-home-btn" title="Back to Dashboard">🏠</a>

</body>

</html>