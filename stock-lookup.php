<?php
session_start();
include 'config/db.php';

// 1. Check Login
if (!isset($_SESSION['user_id'])) {
    header("Location: user-login.php");
    exit();
}

$scanned_item = null;
$total_qty = 0;
$stock_list = [];
$error_msg = "";

// 2. HANDLE QR SCAN
if (isset($_POST['qr_code'])) {
    $code = mysqli_real_escape_string($conn, trim($_POST['qr_code']));

    if (filter_var($code, FILTER_VALIDATE_URL)) {
        $error_msg = "⚠️ You scanned a Website Link! Please use a Text-Only QR.";
    } else {
        // A. Find the Item
        $sql_item = "SELECT * FROM items WHERE item_code = '$code' OR rfid_tag_id = '$code'";
        $result_item = mysqli_query($conn, $sql_item);

        if (mysqli_num_rows($result_item) > 0) {
            $scanned_item = mysqli_fetch_assoc($result_item);
            $item_id = $scanned_item['item_id'];

            // B. Find Stock
            $sql_stock = "SELECT s.*, l.location_code, l.description, b.expiry_date 
                          FROM stock s 
                          JOIN locations l ON s.location_id = l.location_id 
                          LEFT JOIN item_batches b ON s.batch_id = b.batch_id
                          WHERE s.item_id = '$item_id' AND s.quantity > 0";

            $result_stock = mysqli_query($conn, $sql_stock);

            // C. Calculate Total
            while ($row = mysqli_fetch_assoc($result_stock)) {
                $stock_list[] = $row;
                $total_qty += $row['quantity'];
            }
        } else {
            $error_msg = "❌ Item not found: " . htmlspecialchars($code);
        }
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

    <style>
        /* Modern Card Styles */
        .result-card {
            background: white;
            max-width: 500px;
            margin: 20px auto;
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            text-align: center;
        }

        .item-name {
            font-size: 22px;
            font-weight: 800;
            color: #1f2937;
            margin: 5px 0;
        }

        .item-code {
            background: #f3f4f6;
            color: #6b7280;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 14px;
            font-family: monospace;
            display: inline-block;
        }

        /* Single Big Stat Box */
        .stat-box {
            background: #f0fdf4;
            padding: 20px;
            border-radius: 16px;
            border: 1px solid #bbf7d0;
            margin: 20px 0;
        }

        .stat-val {
            font-size: 40px;
            font-weight: 800;
            color: #166534;
        }

        .stat-lbl {
            font-size: 13px;
            text-transform: uppercase;
            color: #15803d;
            font-weight: 700;
            letter-spacing: 1px;
        }

        /* Table */
        .stock-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .stock-table th {
            text-align: left;
            color: #9ca3af;
            font-size: 11px;
            text-transform: uppercase;
            border-bottom: 2px solid #f3f4f6;
            padding-bottom: 8px;
        }

        .stock-table td {
            padding: 12px 0;
            border-bottom: 1px solid #f9fafb;
            font-size: 14px;
            color: #374151;
        }

        .loc-badge {
            background: #e0e7ff;
            color: #4338ca;
            padding: 4px 8px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 12px;
        }

        /* Scanner */
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
            display: none;
            overflow: hidden;
        }

        .btn-scan {
            background: #4f46e5;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 30px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        .btn-rescan {
            display: block;
            width: 100%;
            background: #f3f4f6;
            color: #374151;
            padding: 15px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: bold;
            margin-top: 20px;
        }
    </style>
</head>

<body>

    <div class="header">
        <div class="top-bar">
            <a href="staff_dashboard.php" style="color:white; text-decoration:none;">← Dashboard</a>
            <div class="status">🔍 LOOKUP MODE</div>
        </div>
        <h1>Stock Lookup</h1>
        <div class="subtitle">Check availability and location</div>
    </div>

    <div class="container">

        <?php if ($error_msg): ?>
            <div style="background:#fee2e2; color:#991b1b; padding:15px; border-radius:12px; text-align:center; font-weight:bold; margin-bottom:20px;">
                <?php echo $error_msg; ?>
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
                <button onclick="startScan()" class="btn-scan" id="scanBtn">🔍 Tap to Scan Item</button>
            </div>
        <?php endif; ?>

        <?php if ($scanned_item): ?>
            <div class="result-card">

                <div style="border-bottom:2px dashed #f3f4f6; padding-bottom:20px;">
                    <div class="item-name"><?php echo $scanned_item['item_name']; ?></div>
                    <div class="item-code"><?php echo $scanned_item['item_code']; ?></div>
                </div>

                <div class="stat-box">
                    <div class="stat-val"><?php echo $total_qty; ?></div>
                    <div class="stat-lbl">Total Available Stock</div>
                </div>

                <div style="text-align:left;">
                    <div style="font-size:12px; font-weight:700; color:#9ca3af; margin-bottom:10px; text-transform:uppercase;">📍 Stock Locations</div>

                    <?php if (count($stock_list) > 0): ?>
                        <table class="stock-table">
                            <thead>
                                <tr>
                                    <th>Location</th>
                                    <th>Expiry</th>
                                    <th style="text-align:right;">Qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stock_list as $stock): ?>
                                    <tr>
                                        <td>
                                            <span class="loc-badge"><?php echo $stock['location_code']; ?></span>
                                            <div style="font-size:11px; color:#6b7280; margin-top:2px;"><?php echo $stock['description']; ?></div>
                                        </td>
                                        <td style="color:#ef4444; font-weight:500;">
                                            <?php echo ($stock['expiry_date']) ? $stock['expiry_date'] : '-'; ?>
                                        </td>
                                        <td style="text-align:right; font-weight:bold; font-size:16px;">
                                            <?php echo $stock['quantity']; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div style="text-align:center; padding:20px; color:#ef4444; font-weight:bold; background:#fef2f2; border-radius:10px;">
                            ⚠️ Out of Stock (0 Quantity)
                        </div>
                    <?php endif; ?>
                </div>

                <a href="stock-lookup.php" class="btn-rescan">Scan Another Item</a>

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
                document.getElementById("scanBtn").style.display = "block";
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