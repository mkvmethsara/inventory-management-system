<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// SECURITY GATE 🔒
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

include '../config/db.php';

// --- 1. HANDLE ADD / UPDATE BATCH ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $item_id  = $_POST['item_id'];
    $qty      = $_POST['quantity'];
    $received = $_POST['received_date'];
    $expiry   = $_POST['expiry_date'];
    $batch_id = $_POST['batch_id'] ?? ''; // Capture ID if editing

    if (!empty($batch_id)) {
        // --- UPDATE EXISTING BATCH ---
        // 1. Update Batch Details
        $sql_update = "UPDATE item_batches SET received_date='$received', expiry_date='$expiry' WHERE batch_id='$batch_id'";
        mysqli_query($conn, $sql_update);
        
        // 2. Update Stock Quantity (Simple overwrite for this demo)
        $sql_stock = "UPDATE stock SET quantity='$qty' WHERE batch_id='$batch_id'";
        mysqli_query($conn, $sql_stock);
        
        echo "<script>alert('✅ Batch Updated!'); window.location.href='batch-expiry.php';</script>";

    } else {
        // --- ADD NEW BATCH ---
        $sql_batch = "INSERT INTO item_batches (item_id, received_date, expiry_date) VALUES ('$item_id', '$received', '$expiry')";
        if (mysqli_query($conn, $sql_batch)) {
            $new_batch_id = mysqli_insert_id($conn);
            // Defaulting to Location ID 1 for simplicity
            $sql_stock = "INSERT INTO stock (item_id, batch_id, location_id, quantity) VALUES ('$item_id', '$new_batch_id', 1, '$qty')";
            mysqli_query($conn, $sql_stock);
            echo "<script>alert('✅ New Batch Added!'); window.location.href='batch-expiry.php';</script>";
        }
    }
}

// --- 2. HANDLE DELETE ---
if (isset($_GET['delete_id'])) {
    $del_id = $_GET['delete_id'];
    // Delete from Stock first (Foreign Key)
    mysqli_query($conn, "DELETE FROM stock WHERE batch_id='$del_id'");
    // Then Delete Batch
    mysqli_query($conn, "DELETE FROM item_batches WHERE batch_id='$del_id'");
    echo "<script>alert('🗑️ Batch Deleted'); window.location.href='batch-expiry.php';</script>";
}

// --- 3. GET DATA ---
$items_result = mysqli_query($conn, "SELECT item_id, item_name FROM items ORDER BY item_name ASC");

// Fetch Batches
$sql = "SELECT b.batch_id, b.expiry_date, b.received_date, b.item_id, i.item_name, i.category, i.item_code, COALESCE(SUM(s.quantity), 0) as batch_qty
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
    <link rel="stylesheet" href="../assets/css/style.css?v=25">

    <style>
        /* DROPDOWN & MENU STYLES */
        .tf-search-select {
            padding: 10px 16px; border: 1px solid #e5e7eb; border-radius: 8px;
            background: white; color: #374151; font-size: 14px; font-weight: 600;
            cursor: pointer; outline: none; min-width: 180px;
        }

        /* The Container for the dots */
        .menu-container { position: relative; display: inline-block; cursor: pointer; }
        
        /* The Dots Icon */
        .menu-dots {
            padding: 8px; border-radius: 50%; color: #9ca3af; transition: 0.2s;
        }
        .menu-dots:hover { background: #f3f4f6; color: #374151; }

        /* The Hidden Dropdown Menu */
        .dropdown-menu {
            display: none; /* Hidden by default */
            position: absolute;
            right: 0; top: 30px;
            background: white;
            min-width: 140px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border-radius: 8px;
            border: 1px solid #f3f4f6;
            z-index: 100;
            overflow: hidden;
        }

        .dropdown-item {
            display: block;
            padding: 10px 15px;
            font-size: 13px;
            color: #374151;
            text-decoration: none;
            transition: 0.2s;
            text-align: left;
        }
        .dropdown-item:hover { background: #f9fafb; color: #4f46e5; }
        .dropdown-item.delete-link:hover { background: #fee2e2; color: #dc2626; }
        
        .show-menu { display: block !important; }
    </style>
</head>

<body class="trackflow-body">

    <aside class="tf-sidebar">
        <div class="tf-logo"><i class="bi bi-box-seam-fill"></i> TRACKFLOW</div>
        <nav class="tf-nav">
            <a href="dashboard.php"><i class="bi bi-grid-fill"></i> Dashboard</a>
            <a href="items-inventory.php"><i class="bi bi-box"></i> Items Inventory</a>
            <a href="batch-expiry.php" class="active"><i class="bi bi-clock-history"></i> Batch & Expiry</a>
            <a href="stock-location.php"><i class="bi bi-shop"></i> Stock by Location</a>
            <a href="locations.php"><i class="bi bi-geo-alt"></i> Locations</a>
            <a href="suppliers.php"><i class="bi bi-truck"></i> Suppliers</a>
            <a href="staff.php"><i class="bi bi-people"></i> Staff Management</a>
            <div class="nav-label">ADMINISTRATION</div>
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
                    // Styles & Logic
                    $cat = $row['category'] ?? 'General';
                    $icon_color = ($cat == 'Medicine') ? 'purple' : (($cat == 'Supplies') ? 'orange' : 'blue');

                    $expiry_date = $row['expiry_date'];
                    $days_left = round((strtotime($expiry_date) - strtotime(date('Y-m-d'))) / 86400);

                    $expiry_class = ""; $expiry_icon = ""; $status_tag = "valid";
                    if ($days_left < 0) {
                        $expiry_class = "expiry-danger"; 
                        $expiry_icon = "<i class='bi bi-exclamation-triangle-fill'></i> ";
                        $status_tag = "expired";
                    } elseif ($days_left <= 90) {
                        $expiry_class = "expiry-warning";
                        $expiry_icon = "<i class='bi bi-clock-history'></i> ";
                        $status_tag = "expiring";
                    }
                    
                    // Pre-paring Data for Edit
                    $b_id = $row['batch_id'];
                    $b_item = $row['item_id'];
                    $b_qty = $row['batch_qty'];
                    $b_rec = $row['received_date'];
                    $b_exp = $row['expiry_date'];

                    echo '
                    <div class="tf-batch-card" data-status="' . $status_tag . '">
                        <div class="batch-left">
                            <div class="batch-icon ' . $icon_color . '"><i class="bi bi-box-seam"></i></div>
                            <div class="batch-info">
                                <h4>' . htmlspecialchars($row['item_name']) . '</h4>
                                <div class="batch-meta">
                                    <span>BATCH ID: B' . $b_id . '</span>
                                    <span>• &nbsp; RECEIVED: ' . $b_rec . '</span>
                                </div>
                            </div>
                        </div>

                        <div class="batch-right">
                            <div class="stat-group">
                                <span class="stat-label">Quantity</span>
                                <span class="stat-value">' . number_format($b_qty) . '</span>
                            </div>
                            <div class="stat-group" style="width: 140px;">
                                <span class="stat-label">Expiry Date</span>
                                <span class="stat-value ' . $expiry_class . '">' . $expiry_icon . $b_exp . '</span>
                            </div>
                            
                            <div class="menu-container">
                                <i class="bi bi-three-dots menu-dots" onclick="toggleMenu(\'menu_' . $b_id . '\')"></i>
                                <div id="menu_' . $b_id . '" class="dropdown-menu">
                                    <a href="javascript:void(0)" class="dropdown-item" 
                                       onclick="editBatch(\'' . $b_id . '\', \'' . $b_item . '\', \'' . $b_qty . '\', \'' . $b_rec . '\', \'' . $b_exp . '\')">
                                       <i class="bi bi-pencil-square"></i> Edit
                                    </a>
                                    <a href="batch-expiry.php?delete_id=' . $b_id . '" class="dropdown-item delete-link" 
                                       onclick="return confirm(\'Permanently delete this batch?\')">
                                       <i class="bi bi-trash"></i> Delete
                                    </a>
                                </div>
                            </div>
                            
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
            <h3 id="modalTitle" style="margin-top:0; margin-bottom:20px;">Add New Batch</h3>

            <form method="POST" action="">
                <input type="hidden" name="batch_id" id="batch_id_input">

                <label>Select Item</label>
                <select name="item_id" id="item_select" required>
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
                <input type="number" name="quantity" id="qty_input" placeholder="0" required>

                <label>Received Date</label>
                <input type="date" name="received_date" id="rec_input" required>

                <label>Expiry Date</label>
                <input type="date" name="expiry_date" id="exp_input" required>

                <div class="btn-group">
                    <button type="button" class="btn-cancel" onclick="closePopup()">Cancel</button>
                    <button type="submit" id="saveBtn" class="btn-save">Save Batch</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // 1. Modal Logic (Add vs Edit)
        function openPopup() {
            // Reset for Add
            document.getElementById("modalTitle").innerText = "Add New Batch";
            document.getElementById("saveBtn").innerText = "Save Batch";
            document.getElementById("batch_id_input").value = "";
            document.getElementById("qty_input").value = "";
            document.getElementById("rec_input").value = "";
            document.getElementById("exp_input").value = "";
            document.getElementById("batchModal").style.display = "flex";
        }

        function editBatch(id, itemId, qty, rec, exp) {
            // Fill for Edit
            document.getElementById("modalTitle").innerText = "Edit Batch Details";
            document.getElementById("saveBtn").innerText = "Update Batch";
            document.getElementById("batch_id_input").value = id;
            document.getElementById("item_select").value = itemId;
            document.getElementById("qty_input").value = qty;
            document.getElementById("rec_input").value = rec;
            document.getElementById("exp_input").value = exp;
            
            closeAllMenus(); // Hide the dots menu
            document.getElementById("batchModal").style.display = "flex";
        }

        function closePopup() { document.getElementById("batchModal").style.display = "none"; }

        // 2. Dropdown Menu Logic
        function toggleMenu(menuId) {
            // Close others first
            const allMenus = document.querySelectorAll('.dropdown-menu');
            allMenus.forEach(menu => {
                if (menu.id !== menuId) menu.classList.remove('show-menu');
            });
            // Toggle specific menu
            document.getElementById(menuId).classList.toggle('show-menu');
        }

        function closeAllMenus() {
            document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.remove('show-menu'));
        }

        // Close menu if clicking outside
        window.onclick = function(event) {
            if (event.target == document.getElementById("batchModal")) closePopup();
            if (!event.target.matches('.menu-dots')) closeAllMenus();
        }

        // 3. Filter Logic
        function filterBatches() {
            const filterValue = document.getElementById('statusFilter').value;
            document.querySelectorAll('.tf-batch-card').forEach(row => {
                row.style.display = (filterValue === 'all' || filterValue === row.getAttribute('data-status')) ? "flex" : "none";
            });
        }
    </script>
</body>
</html>