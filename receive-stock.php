<?php
session_start();
include 'config/db.php';

// Check Login
if (!isset($_SESSION['user_id'])) {
    header("Location: user-login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";
$scanned_item = null;

// --- 2. HANDLE SAVE STOCK (Adding Quantity) ---
if (isset($_POST['save_stock'])) {
    $item_id  = $_POST['item_id'];
    $batch_id = $_POST['batch_id'];
    $loc_id   = $_POST['location_id'];
    $qty      = $_POST['quantity'];

    if ($qty > 0) {
        // Update Stock
        $check = mysqli_query($conn, "SELECT * FROM stock WHERE item_id='$item_id' AND batch_id='$batch_id' AND location_id='$loc_id'");
        if (mysqli_num_rows($check) > 0) {
            mysqli_query($conn, "UPDATE stock SET quantity = quantity + $qty WHERE item_id='$item_id' AND batch_id='$batch_id' AND location_id='$loc_id'");
        } else {
            mysqli_query($conn, "INSERT INTO stock (item_id, batch_id, location_id, quantity) VALUES ('$item_id', '$batch_id', '$loc_id', '$qty')");
        }

        // Log Transaction
        mysqli_query($conn, "INSERT INTO stock_transactions (item_id, batch_id, location_id, user_id, transaction_type, quantity, transaction_time, reference) VALUES ('$item_id', '$batch_id', '$loc_id', '$user_id', 'IN', '$qty', NOW(), 'QR-SCAN')");

        $message = "✅ Stock Added Successfully!";
    }
}

// --- 3. HANDLE QR CODE RESULT ---
if (isset($_POST['qr_code'])) {
    $code = $_POST['qr_code'];
    // Find Item
    $sql = "SELECT * FROM items WHERE item_code = '$code' OR rfid_tag_id = '$code'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        $scanned_item = mysqli_fetch_assoc($result);
    } else {
        $message = "❌ No Item found for Code: " . htmlspecialchars($code);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Receive Stock (QR)</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/staff_style.css">
</head>

<body>

    <div class="header">
        <div class="top-bar">
            <a href="staff_dashboard.php" style="color:white; text-decoration:none;">← Dashboard</a>
            <div class="status">🟢 CAMERA MODE</div>
        </div>
        <h1>Receive Stock</h1>
        <div class="subtitle">Scan QR Code to add inventory</div>
    </div>

    <div class="container">

        <?php if ($message): ?>
            <div style="background:#dbeafe; color:#1e40af; padding:15px; border-radius:12px; margin-bottom:20px; text-align:center; font-weight:bold;">
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

                <button onclick="startScan()" class="btn-start-scan" id="scanBtn">📸 Tap to Scan QR</button>

                <p style="color:#6b7280; font-size:13px; margin-top:15px;">
                    Allow camera permission when asked.
                </p>
            </div>
        <?php endif; ?>


        <?php if ($scanned_item): ?>
            <div class="result-card">

                <div class="item-preview-box">
                    <div class="item-preview-title">ITEM FOUND</div>
                    <div class="item-preview-name"><?php echo $scanned_item['item_name']; ?></div>
                    <div class="item-preview-code"><?php echo $scanned_item['item_code']; ?></div>
                </div>

                <form method="POST">
                    <input type="hidden" name="item_id" value="<?php echo $scanned_item['item_id']; ?>">

                    <div class="form-group">
                        <label>Batch</label>
                        <select name="batch_id" required>
                            <?php
                            $b_sql = mysqli_query($conn, "SELECT * FROM item_batches WHERE item_id = '" . $scanned_item['item_id'] . "'");
                            if (mysqli_num_rows($b_sql) > 0) {
                                while ($b = mysqli_fetch_assoc($b_sql)) {
                                    echo "<option value='" . $b['batch_id'] . "'>#" . $b['batch_id'] . " (Exp: " . $b['expiry_date'] . ")</option>";
                                }
                            } else {
                                echo "<option value=''>No Batches</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Location</label>
                        <select name="location_id" required>
                            <?php
                            $l_sql = mysqli_query($conn, "SELECT * FROM locations");
                            while ($l = mysqli_fetch_assoc($l_sql)) {
                                echo "<option value='" . $l['location_id'] . "'>" . $l['location_code'] . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Quantity</label>
                        <input type="number" name="quantity" placeholder="Qty" required min="1">
                    </div>

                    <button type="submit" name="save_stock" class="btn-action btn-save">✅ Save Stock</button>
                    <a href="receive-stock.php" class="btn-action btn-cancel" style="margin-top:10px;">❌ Cancel</a>
                </form>
            </div>
        <?php endif; ?>

    </div>

    <script src="https://unpkg.com/html5-qrcode"></script>

    <script>
        function startScan() {
            // Show the camera box
            document.getElementById("scannerBox").style.display = "block";
            document.getElementById("scanBtn").style.display = "none";

            const html5QrCode = new Html5Qrcode("reader");

            html5QrCode.start({
                    facingMode: "environment"
                }, // Use back camera
                {
                    fps: 10,
                    qrbox: {
                        width: 200,
                        height: 200
                    }
                },
                (decodedText, decodedResult) => {
                    // SUCCESS! Code found.
                    // 1. Play a beep sound (Optional)
                    // 2. Stop camera
                    html5QrCode.stop();

                    // 3. Put code in hidden form and submit
                    document.getElementById("hiddenCode").value = decodedText;
                    document.getElementById("scanForm").submit();
                },
                (errorMessage) => {
                    // Scanning... ignore errors
                }
            ).catch(err => {
                alert("Camera Error: " + err);
            });
        }
    </script>

</body>

</html>