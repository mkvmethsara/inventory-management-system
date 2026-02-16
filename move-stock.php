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
$scanned_item = null;
$available_stock = []; // To hold current locations of the item

// --- 2. HANDLE STOCK TRANSFER ---
if (isset($_POST['move_stock'])) {
    $item_id   = $_POST['item_id'];
    $source_id = $_POST['source_location_id']; // ID from stock table (unique row)
    $target_loc_id = $_POST['target_location_id'];
    $move_qty  = (int)$_POST['quantity'];

    // Get details of the source stock (Batch ID, Current Qty)
    $source_sql = mysqli_query($conn, "SELECT * FROM stock WHERE id = '$source_id'");
    $source_row = mysqli_fetch_assoc($source_sql);

    if ($source_row && $source_row['quantity'] >= $move_qty) {
        $batch_id = $source_row['batch_id'];
        $old_loc_id = $source_row['location_id'];

        // A. DECREASE from Old Location
        mysqli_query($conn, "UPDATE stock SET quantity = quantity - $move_qty WHERE id = '$source_id'");

        // B. INCREASE to New Location (Check if row exists first)
        $check_target = mysqli_query($conn, "SELECT * FROM stock WHERE item_id='$item_id' AND batch_id='$batch_id' AND location_id='$target_loc_id'");

        if (mysqli_num_rows($check_target) > 0) {
            mysqli_query($conn, "UPDATE stock SET quantity = quantity + $move_qty WHERE item_id='$item_id' AND batch_id='$batch_id' AND location_id='$target_loc_id'");
        } else {
            mysqli_query($conn, "INSERT INTO stock (item_id, batch_id, location_id, quantity) VALUES ('$item_id', '$batch_id', '$target_loc_id', '$move_qty')");
        }

        // C. LOG TRANSACTION (MOVE)
        mysqli_query($conn, "INSERT INTO stock_transactions (item_id, batch_id, location_id, user_id, transaction_type, quantity, transaction_time, reference) 
                            VALUES ('$item_id', '$batch_id', '$target_loc_id', '$user_id', 'MOVE', '$move_qty', NOW(), 'FROM LOC $old_loc_id')");

        $message = "✅ Success! Moved $move_qty items.";
    } else {
        $message = "❌ Error: Not enough stock in source location.";
    }
}

// --- 3. HANDLE QR SCAN ---
if (isset($_POST['qr_code'])) {
    $code = $_POST['qr_code'];

    // Find Item
    $sql = "SELECT * FROM items WHERE item_code = '$code' OR rfid_tag_id = '$code'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        $scanned_item = mysqli_fetch_assoc($result);

        // Find WHERE this item is currently stored
        $stock_sql = "SELECT s.id, s.quantity, s.batch_id, l.location_code 
                      FROM stock s 
                      JOIN locations l ON s.location_id = l.location_id 
                      WHERE s.item_id = '" . $scanned_item['item_id'] . "' AND s.quantity > 0";
        $stock_result = mysqli_query($conn, $stock_sql);

        while ($row = mysqli_fetch_assoc($stock_result)) {
            $available_stock[] = $row;
        }

        if (empty($available_stock)) {
            $message = "⚠️ Item found, but stock is 0.";
            $scanned_item = null; // Don't show form if no stock
        }
    } else {
        $message = "❌ Item not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Move Stock</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/staff_style.css">
</head>

<body>

    <div class="header">
        <div class="top-bar">
            <a href="staff_dashboard.php" style="color:white; text-decoration:none;">← Dashboard</a>
            <div class="status">🟢 TRANSFER MODE</div>
        </div>
        <h1>Move Stock</h1>
        <div class="subtitle">Transfer items between locations</div>
    </div>

    <div class="container">

        <?php if ($message): ?>
            <div class="alert-box <?php echo strpos($message, 'Success') !== false ? 'alert-success' : 'alert-error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if (!$scanned_item): ?>
            <div class="scanner-container">
                <form method="POST" id="scanForm">
                    <input type="hidden" name="qr_code" id="hiddenCode">
                </form>
                <div class="scanner-wrapper" id="scannerBox">
                    <div id="reader"></div>
                    <div class="scan-line"></div>
                </div>
                <button onclick="startScan()" class="btn-start-scan" id="scanBtn">🔄 Tap to Scan Item</button>
            </div>
        <?php endif; ?>

        <?php if ($scanned_item): ?>
            <div class="result-card">

                <div class="item-preview-box">
                    <div class="item-preview-title">MOVING ITEM</div>
                    <div class="item-preview-name"><?php echo $scanned_item['item_name']; ?></div>
                </div>

                <form method="POST">
                    <input type="hidden" name="item_id" value="<?php echo $scanned_item['item_id']; ?>">

                    <div class="transfer-grid">

                        <div class="source-box">
                            <label>FROM Location</label>
                            <select name="source_location_id" required>
                                <?php foreach ($available_stock as $stk): ?>
                                    <option value="<?php echo $stk['id']; ?>">
                                        <?php echo $stk['location_code']; ?> (Qty: <?php echo $stk['quantity']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="transfer-arrow">➜</div>

                        <div class="target-box">
                            <label>TO Location</label>
                            <select name="target_location_id" required>
                                <?php
                                $locs = mysqli_query($conn, "SELECT * FROM locations");
                                while ($l = mysqli_fetch_assoc($locs)) {
                                    echo "<option value='" . $l['location_id'] . "'>" . $l['location_code'] . "</option>";
                                }
                                ?>
                            </select>
                        </div>

                    </div>

                    <div class="form-group">
                        <label>Quantity to Move</label>
                        <input type="number" name="quantity" placeholder="Enter Qty" required min="1">
                    </div>

                    <button type="submit" name="move_stock" class="btn-action btn-scan" style="background:#4f46e5;">
                        🔄 Confirm Transfer
                    </button>
                    <a href="move-stock.php" class="btn-action btn-cancel" style="margin-top:10px;">❌ Cancel</a>
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
                        width: 200,
                        height: 200
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
            });
        }
    </script>

</body>

</html>