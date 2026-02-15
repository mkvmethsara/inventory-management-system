<?php
include '../config/db.php';

// --- 1. HANDLE FORM SUBMISSION ---
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $item_id = $_POST['item_id'];
    $qty = $_POST['quantity'];
    $received = $_POST['received_date'];
    $expiry = $_POST['expiry_date'];

    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

    $sql_batch = "INSERT INTO item_batches (item_id, received_date, expiry_date) 
                  VALUES ('$item_id', '$received', '$expiry')";

    if (mysqli_query($conn, $sql_batch)) {
        $new_batch_id = mysqli_insert_id($conn);
        $sql_stock = "INSERT INTO stock (item_id, batch_id, location_id, quantity) 
                      VALUES ('$item_id', '$new_batch_id', 1, '$qty')";

        if (mysqli_query($conn, $sql_stock)) {
            $message = "<script>alert('✅ Batch Added Successfully!'); window.location.href='batch-expiry.php';</script>";
        } else {
            $error = mysqli_error($conn);
            $message = "<div style='background:#9f1239; color:white; padding:10px; margin-bottom:15px; border-radius:8px;'>❌ Stock Error: $error</div>";
        }
    } else {
        $error = mysqli_error($conn);
        $message = "<div style='background:#9f1239; color:white; padding:10px; margin-bottom:15px; border-radius:8px;'>❌ Batch Error: $error</div>";
    }
}

// --- 2. GET DATA ---
$items_result = mysqli_query($conn, "SELECT item_id, item_name FROM items ORDER BY item_name ASC");

$sql = "SELECT 
            b.batch_id, b.expiry_date, b.received_date, 
            i.item_name, i.item_code,
            COALESCE(SUM(s.quantity), 0) as batch_qty
        FROM item_batches b
        JOIN items i ON b.item_id = i.item_id
        LEFT JOIN stock s ON b.batch_id = s.batch_id
        GROUP BY b.batch_id
        ORDER BY b.expiry_date ASC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en" class="admin-dash-page">

<head>
    <meta charset="UTF-8">
    <title>Batch & Expiry</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        /* 1. The Overlay - This makes it FLOAT */
        .custom-modal {
            display: none;
            /* Hidden by default */
            position: fixed;
            /* STICK TO SCREEN */
            z-index: 999999;
            /* TOP PRIORITY */
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            /* Dark Dim */
            backdrop-filter: blur(4px);
            align-items: center;
            justify-content: center;
        }

        /* 2. The Box */
        .custom-modal-content {
            background-color: #0f172a;
            /* Dark Blue */
            padding: 30px;
            border-radius: 16px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            border: 1px solid #1e293b;
            animation: zoomIn 0.2s ease-out;
        }

        @keyframes zoomIn {
            from {
                transform: scale(0.95);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        /* 3. Inputs - WHITE (Vishwa Style) */
        .custom-modal label {
            display: block;
            color: #cbd5e1;
            margin-bottom: 6px;
            font-size: 13px;
            font-weight: 600;
        }

        .custom-modal input,
        .custom-modal select {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            background: #ffffff !important;
            /* FORCE WHITE */
            color: #000000 !important;
            /* FORCE BLACK TEXT */
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            outline: none;
        }

        /* 4. Buttons - SMALL & RIGHT ALIGNED */
        .btn-group {
            display: flex;
            justify-content: flex-end;
            /* Push to right */
            gap: 10px;
            margin-top: 10px;
        }

        .btn-cancel {
            background: #334155;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            width: auto !important;
        }

        .btn-save {
            background: #4f46e5;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            width: auto !important;
            font-weight: bold;
        }
    </style>
</head>

<body>

    <div class="header">TrackFlow – Batch & Expiry</div>

    <div class="layout">
        <?php include 'menu.php'; ?>

        <div class="main">
            <div class="card">
                <?php echo $message; ?>

                <div class="top-bar">
                    <div class="filters">
                        <input type="text" id="searchInput" placeholder="Search...">
                        <select id="statusFilter">
                            <option value="all">All Status</option>
                            <option value="valid">Valid</option>
                            <option value="expiring">Expiring Soon</option>
                            <option value="expired">Expired</option>
                        </select>
                    </div>
                    <button class="add-btn" onclick="openPopup()">+ Add Batch</button>
                </div>

                <table id="batchTable">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Batch</th>
                            <th>Qty</th>
                            <th>Mfg Date</th>
                            <th>Exp Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                $expiry = $row['expiry_date'];
                                $days_left = round((strtotime($expiry) - strtotime(date('Y-m-d'))) / 86400);

                                $status = "valid";
                                $badge = "ok";
                                $text = "Valid";
                                if ($days_left < 0) {
                                    $status = "expired";
                                    $badge = "expired";
                                    $text = "Expired";
                                } elseif ($days_left <= 30) {
                                    $status = "expiring";
                                    $badge = "expiring";
                                    $text = "Expiring";
                                }

                                echo "<tr data-status='$status'>";
                                echo "<td><strong>" . $row['item_name'] . "</strong><br><span style='font-size:12px; opacity:0.7;'>" . $row['item_code'] . "</span></td>";
                                echo "<td style='color:#a5b4fc;'>BCH-" . str_pad($row['batch_id'], 3, '0', STR_PAD_LEFT) . "</td>";
                                echo "<td>" . $row['batch_qty'] . "</td>";
                                echo "<td>" . $row['received_date'] . "</td>";
                                echo "<td>" . $row['expiry_date'] . "</td>";
                                echo "<td><span class='badge $badge'>$text</span></td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' style='text-align:center; padding:30px; opacity:0.5;'>No batches found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="batchModal" class="custom-modal">
        <div class="custom-modal-content">
            <h3 style="margin-top:0; color:white; margin-bottom:20px;">Add New Batch</h3>

            <form method="POST" action="">
                <label>Item Name</label>
                <select name="item_id" required>
                    <?php
                    if (mysqli_num_rows($items_result) > 0) {
                        mysqli_data_seek($items_result, 0);
                        while ($item = mysqli_fetch_assoc($items_result)) {
                            echo "<option value='" . $item['item_id'] . "'>" . $item['item_name'] . "</option>";
                        }
                    }
                    ?>
                </select>

                <label>Quantity</label>
                <input type="number" name="quantity" placeholder="0" required>

                <label>Received Date</label>
                <input type="date" name="received_date" required>

                <label>Expiry Date</label>
                <input type="date" name="expiry_date" required>

                <div class="btn-group">
                    <button type="button" class="btn-cancel" onclick="closePopup()">Cancel</button>
                    <button type="submit" class="btn-save">Save</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Force Flex Display to Center the Modal
        function openPopup() {
            document.getElementById("batchModal").style.display = "flex";
        }

        function closePopup() {
            document.getElementById("batchModal").style.display = "none";
        }

        // Close if clicking outside
        window.onclick = function(event) {
            if (event.target == document.getElementById("batchModal")) {
                closePopup();
            }
        }

        // Search Logic
        const searchInput = document.getElementById("searchInput");
        const statusFilter = document.getElementById("statusFilter");
        const table = document.getElementById("batchTable");

        function filterTable() {
            const val = searchInput.value.toLowerCase();
            const stat = statusFilter.value.toLowerCase();
            table.querySelectorAll("tbody tr").forEach(row => {
                const text = row.innerText.toLowerCase();
                const status = row.getAttribute("data-status");
                const matchSearch = text.includes(val);
                const matchStatus = stat === "all" || status === stat;
                row.style.display = (matchSearch && matchStatus) ? "" : "none";
            });
        }

        searchInput.addEventListener("keyup", filterTable);
        statusFilter.addEventListener("change", filterTable);
    </script>

</body>

</html>