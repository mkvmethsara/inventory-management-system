<?php
session_start();
include 'config/db.php';

// 1. Check Login
if (!isset($_SESSION['user_id'])) {
    header("Location: user-login.php");
    exit();
}

$item = null;
$total_qty = 0;
$stock_list = [];
$error_msg = "";

// 2. HANDLE QR SCAN
if (isset($_POST['qr_code'])) {
    $code = mysqli_real_escape_string($conn, $_POST['qr_code']);

    // A. Find the Item
    $sql_item = "SELECT * FROM items WHERE item_code = '$code' OR rfid_tag_id = '$code'";
    $result_item = mysqli_query($conn, $sql_item);

    if (mysqli_num_rows($result_item) > 0) {
        $item = mysqli_fetch_assoc($result_item);
        $item_id = $item['item_id'];

        // B. Find Stock for this Item (Join with Locations table to get names)
        $sql_stock = "SELECT s.*, l.location_code, l.description 
                      FROM stock s 
                      JOIN locations l ON s.location_id = l.location_id 
                      WHERE s.item_id = '$item_id' AND s.quantity > 0";

        $result_stock = mysqli_query($conn, $sql_stock);

        // C. Calculate Total & Store Rows
        while ($row = mysqli_fetch_assoc($result_stock)) {
            $stock_list[] = $row;
            $total_qty += $row['quantity'];
        }
    } else {
        $error_msg = "❌ Item not found for code: " . htmlspecialchars($code);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Stock Lookup</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/staff_style.css">
</head>

<body>

    <div class="header">
        <div class="top-bar">
            <a href="staff_dashboard.php" style="color:white; text-decoration:none;">← Dashboard</a>
            <div class="status">🟢 LOOKUP MODE</div>
        </div>
        <h1>Stock Lookup</h1>
        <div class="subtitle">Scan to check price and availability</div>
    </div>

    <div class="container">

        <?php if ($error_msg): ?>
            <div class="alert-box alert-error"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <?php if (!$item): ?>
            <div class="scanner-container">
                <form method="POST" id="scanForm">
                    <input type="hidden" name="qr_code" id="hiddenCode">
                </form>

                <div class="scanner-wrapper" id="scannerBox">
                    <div id="reader"></div>
                    <div class="scan-line"></div>
                </div>

                <button onclick="startScan()" class="btn-start-scan" id="scanBtn">🔍 Tap to Scan</button>
            </div>
        <?php endif; ?>

        <?php if ($item): ?>
            <div class="result-card">

                <div class="item-preview-box">
                    <div class="item-preview-title">ITEM DETAILS</div>
                    <div class="item-preview-name"><?php echo $item['item_name']; ?></div>
                    <div class="item-preview-code">
                        <?php echo $item['item_code']; ?> | <?php echo $item['category_id']; ?>
                    </div>
                </div>

                <div class="stock-info-grid">
                    <div class="stock-stat">
                        <div class="stat-value"><?php echo $total_qty; ?></div>
                        <div class="stat-label">Total Stock</div>
                    </div>
                    <div class="stock-stat">
                        <div class="stat-value" style="color:#16a34a;">Rs. <?php echo number_format($item['price'], 2); ?></div>
                        <div class="stat-label">Unit Price</div>
                    </div>
                </div>

                <div class="stock-table-container">
                    <div class="section-title">📍 Stock Locations</div>

                    <?php if (count($stock_list) > 0): ?>
                        <table class="location-table">
                            <thead>
                                <tr>
                                    <th>Location</th>
                                    <th>Batch</th>
                                    <th style="text-align:right;">Qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stock_list as $stock): ?>
                                    <tr>
                                        <td>
                                            <span class="location-badge"><?php echo $stock['location_code']; ?></span>
                                            <div style="font-size:11px; color:#9ca3af; margin-top:2px;"><?php echo $stock['description']; ?></div>
                                        </td>
                                        <td>#<?php echo $stock['batch_id']; ?></td>
                                        <td style="text-align:right; font-weight:bold;"><?php echo $stock['quantity']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p style="text-align:center; color:#ef4444; font-weight:bold; margin-top:20px;">
                            ⚠️ No Stock Available (Out of Stock)
                        </p>
                    <?php endif; ?>
                </div>

                <a href="stock-lookup.php" class="btn-action btn-scan" style="text-align:center; text-decoration:none; display:block;">
                    Scan Another Item
                </a>

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
                (decodedText, decodedResult) => {
                    html5QrCode.stop();
                    // Submit form automatically
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