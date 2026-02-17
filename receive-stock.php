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
    $batch_id = $_POST['batch_id'];
    $location_id = $_POST['location_id'];
    $quantity = (int)$_POST['quantity'];

    // Update Stock Logic
    $check_stock = mysqli_query($conn, "SELECT * FROM stock WHERE item_id='$item_id' AND batch_id='$batch_id' AND location_id='$location_id'");

    if (mysqli_num_rows($check_stock) > 0) {
        $update = "UPDATE stock SET quantity = quantity + $quantity WHERE item_id='$item_id' AND batch_id='$batch_id' AND location_id='$location_id'";
        mysqli_query($conn, $update);
    } else {
        $insert = "INSERT INTO stock (item_id, batch_id, location_id, quantity) VALUES ('$item_id', '$batch_id', '$location_id', '$quantity')";
        mysqli_query($conn, $insert);
    }

    // Log Transaction
    mysqli_query($conn, "INSERT INTO stock_transactions (item_id, batch_id, location_id, user_id, transaction_type, quantity, transaction_time, reference) 
                        VALUES ('$item_id', '$batch_id', '$location_id', '$user_id', 'IN', '$quantity', NOW(), 'RFID-AUTO')");

    $message = "✅ Success! Stock Added.";
    $message_type = "success";
}

// 4. HANDLE QR SCAN
if (isset($_POST['qr_code'])) {
    $code = mysqli_real_escape_string($conn, trim($_POST['qr_code']));

    // Check for "Website URL" mistake
    if (filter_var($code, FILTER_VALIDATE_URL)) {
        $message = "⚠️ You scanned a Website Link! Please use a Text-Only QR.";
        $message_type = "error";
    } else {
        // Search for Item
        $sql = "SELECT * FROM items WHERE item_code = '$code' OR rfid_tag_id = '$code'";
        $result = mysqli_query($conn, $sql);

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
        /* 1. The Scanner Box (Hidden by default until button clicked) */
        .scanner-container {
            text-align: center;
            margin-top: 20px;
        }

        .scanner-wrapper {
            width: 100%;
            max-width: 300px;
            height: 300px;
            background: #000;
            margin: 0 auto 20px auto;
            border-radius: 12px;
            overflow: hidden;
            position: relative;
            display: none;
            /* Hidden initially */
            border: 4px solid #4f46e5;
        }

        /* 2. The Big Scan Button */
        .btn-start-scan {
            background: #4f46e5;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 30px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.4);
            display: inline-block;
            /* Ensure it is visible */
        }

        .btn-start-scan:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.6);
        }

        /* 3. Alerts */
        .alert-box {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 12px;
            font-weight: bold;
            text-align: center;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        /* 4. Modern Card Styles */
        .result-card {
            background: white;
            max-width: 450px;
            margin: 20px auto;
            padding: 30px;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .modern-select,
        .modern-input {
            width: 100%;
            padding: 14px;
            margin-bottom: 15px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 16px;
            box-sizing: border-box;
        }

        .btn-confirm {
            width: 100%;
            background: #10b981;
            color: white;
            padding: 16px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        .badge-success {
            background: #dcfce7;
            color: #166534;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 10px;
        }
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

                    <div style="text-align:left; margin-bottom:5px; font-weight:bold; color:#4b5563;">Select Batch:</div>
                    <select name="batch_id" class="modern-select" required>
                        <?php
                        $b_sql = mysqli_query($conn, "SELECT * FROM item_batches WHERE item_id = '" . $scanned_item['item_id'] . "'");
                        if (mysqli_num_rows($b_sql) > 0) {
                            while ($b = mysqli_fetch_assoc($b_sql)) {
                                echo "<option value='" . $b['batch_id'] . "'>#" . $b['batch_id'] . " (Exp: " . $b['expiry_date'] . ")</option>";
                            }
                        } else {
                            echo "<option value='' disabled selected>⚠️ No Batches! (Add in Admin)</option>";
                        }
                        ?>
                    </select>

                    <div style="text-align:left; margin-bottom:5px; font-weight:bold; color:#4b5563;">Location:</div>
                    <select name="location_id" class="modern-select" required>
                        <?php
                        $l_sql = mysqli_query($conn, "SELECT * FROM locations");
                        while ($l = mysqli_fetch_assoc($l_sql)) {
                            echo "<option value='" . $l['location_id'] . "'>📍 " . $l['location_code'] . "</option>";
                        }
                        ?>
                    </select>

                    <div style="text-align:left; margin-bottom:5px; font-weight:bold; color:#4b5563;">Quantity:</div>
                    <input type="number" name="quantity" class="modern-input" placeholder="Enter Qty" required min="1">

                    <button type="submit" name="save_stock" class="btn-confirm">✅ Confirm & Save</button>
                    <a href="receive-stock.php" style="display:block; margin-top:15px; color:#ef4444; text-decoration:none; font-weight:bold;">Cancel</a>
                </form>
            </div>
        <?php endif; ?>

    </div>

    <script src="https://unpkg.com/html5-qrcode"></script>
    <script>
        function startScan() {
            // Show the camera box
            document.getElementById("scannerBox").style.display = "block";
            // Hide the button
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
                    // Success!
                    html5QrCode.stop();
                    document.getElementById("hiddenCode").value = decodedText;
                    document.getElementById("scanForm").submit();
                },
                (errorMessage) => {
                    // Scanning... ignore errors
                }
            ).catch(err => {
                alert("Camera Error: " + err);
                // If error, show button again
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
            /* Matches your dashboard header color */
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