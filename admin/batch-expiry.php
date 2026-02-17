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

// --- 1. HANDLE FORM SUBMISSION ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $item_id = $_POST['item_id'];
    $qty = $_POST['quantity'];
    $received = $_POST['received_date'];
    $expiry = $_POST['expiry_date'];

    $sql_batch = "INSERT INTO item_batches (item_id, received_date, expiry_date) VALUES ('$item_id', '$received', '$expiry')";
    if (mysqli_query($conn, $sql_batch)) {
        $new_batch_id = mysqli_insert_id($conn);
        $sql_stock = "INSERT INTO stock (item_id, batch_id, location_id, quantity) VALUES ('$item_id', '$new_batch_id', 1, '$qty')";
        mysqli_query($conn, $sql_stock);
        echo "<script>window.location.href='batch-expiry.php';</script>";
    }
}

// --- 2. GET DATA ---
$items_result = mysqli_query($conn, "SELECT item_id, item_name FROM items ORDER BY item_name ASC");

// Fetch Batches
$sql = "SELECT b.batch_id, b.expiry_date, b.received_date, i.item_name, i.category, i.item_code, COALESCE(SUM(s.quantity), 0) as batch_qty
        FROM item_batches b
        JOIN items i ON b.item_id = i.item_id
        LEFT JOIN stock s ON b.batch_id = s.batch_id
        GROUP BY b.batch_id ORDER BY b.expiry_date ASC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>TrackFlow – Batch Control</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=8">

    <style>
        /* NEW DROPDOWN STYLE */
        .tf-search-select {
            padding: 10px 16px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: white;
            color: #374151;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            outline: none;
            min-width: 180px;
        }

        .tf-search-select:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
    </style>
</head>

<body class="trackflow-body">

    <aside class="tf-sidebar">
        <div class="tf-logo">
            <i class="bi bi-box-seam-fill"></i> TRACKFLOW
        </div>
        <nav class="tf-nav">
            <a href="dashboard.php"><i class="bi bi-grid-fill"></i> Dashboard</a>
            <a href="items-inventory.php"><i class="bi bi-box"></i> Items Inventory</a>
            <a href="batch-expiry.php"><i class="bi bi-clock-history"></i> Batch & Expiry</a>
            <a href="stock-location.php"><i class="bi bi-shop"></i> Stock by Location</a>
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
                <h2>Batch & Expiry Control</h2>
                <p>Monitor expiry dates and batch quantities</p>
            </div>

            <div style="display:flex; gap:10px;">
                <select class="tf-search-select" id="statusFilter" onchange="filterBatches()">
                    <option value="all">Show All Batches</option>
                    <option value="expiring">⚠️ Nearly Expire (90 Days)</option>
                    <option value="expired">⛔ Expired Items</option>
                </select>

                <button onclick="openPopup()" class="tf-btn-primary">
                    <i class="bi bi-plus-lg"></i> Add Manual Batch
                </button>
            </div>
        </div>

        <div class="batch-list">
            <?php
            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    // Logic for Icon Color
                    $cat = $row['category'] ?? 'General';
                    $icon_color = 'blue';
                    if ($cat == 'Medicine') $icon_color = 'purple';
                    if ($cat == 'Supplies') $icon_color = 'orange';

                    // Logic for Expiry Warning & Status Tag
                    $expiry_date = $row['expiry_date'];
                    $days_left = round((strtotime($expiry_date) - strtotime(date('Y-m-d'))) / 86400);

                    $expiry_class = "";
                    $expiry_icon = "";
                    $status_tag = "valid"; // Default Status

                    if ($days_left < 0) {
                        $expiry_class = "expiry-danger"; // Red
                        $expiry_icon = "<i class='bi bi-exclamation-triangle-fill'></i> ";
                        $status_tag = "expired";
                    } elseif ($days_left <= 90) {
                        $expiry_class = "expiry-warning"; // Orange
                        $expiry_icon = "<i class='bi bi-clock-history'></i> ";
                        $status_tag = "expiring";
                    }

                    // Added 'data-status' to the main div for JS filtering
                    echo '
                    <div class="tf-batch-card" data-status="' . $status_tag . '">
                        <div class="batch-left">
                            <div class="batch-icon ' . $icon_color . '">
                                <i class="bi bi-box-seam"></i>
                            </div>
                            <div class="batch-info">
                                <h4>' . $row['item_name'] . '</h4>
                                <div class="batch-meta">
                                    <span>BATCH ID: B' . $row['batch_id'] . '</span>
                                    <span>• &nbsp; RECEIVED: ' . $row['received_date'] . '</span>
                                </div>
                            </div>
                        </div>

                        <div class="batch-right">
                            <div class="stat-group">
                                <span class="stat-label">Quantity</span>
                                <span class="stat-value">' . number_format($row['batch_qty']) . '</span>
                            </div>

                            <div class="stat-group" style="width: 140px;">
                                <span class="stat-label">Expiry Date</span>
                                <span class="stat-value ' . $expiry_class . '">' . $expiry_icon . $row['expiry_date'] . '</span>
                            </div>

                            <i class="bi bi-three-dots menu-dots"></i>
                        </div>
                    </div>';
                }
            } else {
                echo "<p style='text-align:center; color:#9ca3af; margin-top:50px;'>No batches found.</p>";
            }
            ?>
        </div>

    </main>

    <div id="batchModal" class="custom-modal">
        <div class="custom-modal-content">
            <h3 style="margin-top:0; margin-bottom:20px;">Add New Batch</h3>

            <form method="POST" action="">
                <label>Select Item</label>
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
                    <button type="submit" class="btn-save">Save Batch</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal Logic
        function openPopup() {
            document.getElementById("batchModal").style.display = "flex";
        }

        function closePopup() {
            document.getElementById("batchModal").style.display = "none";
        }
        window.onclick = function(event) {
            if (event.target == document.getElementById("batchModal")) closePopup();
        }

        // ✅ NEW DROPDOWN FILTER LOGIC
        function filterBatches() {
            // 1. Get the value from the dropdown
            const filterValue = document.getElementById('statusFilter').value;
            const rows = document.querySelectorAll('.tf-batch-card');

            rows.forEach(row => {
                // 2. Get the status of the current card (valid, expiring, or expired)
                const cardStatus = row.getAttribute('data-status');

                // 3. Logic: If 'all' is selected, show everything.
                // Otherwise, only show if card status matches the filter.
                if (filterValue === 'all' || filterValue === cardStatus) {
                    row.style.display = "flex";
                } else {
                    row.style.display = "none";
                }
            });
        }
    </script>
</body>

</html>