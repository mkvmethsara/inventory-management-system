<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

include '../config/db.php';

// --- 1. HANDLE ADD/UPDATE LOCATION ---
if (isset($_POST['save_location_btn'])) {
    $id   = $_POST['location_id'];
    $desc = mysqli_real_escape_string($conn, $_POST['description']);
    
    // Auto-generate the location code: Type - Column - Bin
    $type = $_POST['loc_type']; // 1 or 0
    $col  = str_pad($_POST['loc_col'], 2, '0', STR_PAD_LEFT); // Turns "1" into "01"
    $bin  = str_pad($_POST['loc_bin'], 2, '0', STR_PAD_LEFT);
    $code = "$type-$col-$bin"; // Result: e.g., 1-01-01

    if (!empty($id)) {
        $sql = "UPDATE locations SET location_code='$code', description='$desc' WHERE location_id='$id'";
        $msg = "✅ Location Updated!";
    } else {
        $sql = "INSERT INTO locations (location_code, description) VALUES ('$code', '$desc')";
        $msg = "✅ New Location Created!";
    }

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('$msg'); window.location.href='locations.php';</script>";
    } else {
        echo "<script>alert('Error: " . mysqli_error($conn) . "');</script>";
    }
}

// --- 2. HANDLE DELETE LOCATION ---
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    try {
        if (mysqli_query($conn, "DELETE FROM locations WHERE location_id = '$id'")) {
            echo "<script>window.location.href='locations.php';</script>";
        }
    } catch (Exception $e) {
        echo "<script>alert('⚠️ Cannot delete! Stock is currently stored in this location.'); window.location.href='locations.php';</script>";
    }
}

// --- 3. FETCH DATA ---
$sql = "SELECT l.location_id, l.location_code, l.description, COUNT(DISTINCT s.item_id) as unique_items, COALESCE(SUM(s.quantity), 0) as total_units
        FROM locations l LEFT JOIN stock s ON l.location_id = s.location_id
        GROUP BY l.location_id ORDER BY l.location_code ASC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>TrackFlow – Locations Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=13">
</head>
<body class="trackflow-body">
    <aside class="tf-sidebar">
        <div class="tf-logo"><i class="bi bi-box-seam-fill"></i> TRACKFLOW</div>
        <nav class="tf-nav">
            <a href="dashboard.php"><i class="bi bi-grid-fill"></i> Dashboard</a>
            <a href="items-inventory.php"><i class="bi bi-box"></i> Items Inventory</a>
            <a href="batch-expiry.php"><i class="bi bi-clock-history"></i> Batch & Expiry</a>
            <a href="stock-location.php"><i class="bi bi-shop"></i> Stock by Location</a>
            <a href="locations.php" class="active"><i class="bi bi-geo-alt"></i> Locations</a>
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
                <h2>Locations Management</h2>
                <p>Manage warehouse storage locations</p>
            </div>
            <button onclick="openModal()" class="tf-btn-primary" style="background:#111827;">
                <i class="bi bi-plus-lg"></i> Add New Location
            </button>
        </div>

        <div class="tf-table-container">
            <div style="padding: 20px; border-bottom: 1px solid #f3f4f6;">
                <input type="text" id="searchInput" placeholder="Search locations..." style="padding: 10px 15px; width: 300px; border: 1px solid #e5e7eb; border-radius: 8px; outline:none;">
            </div>

            <table class="tf-table" id="locTable">
                <thead>
                    <tr>
                        <th style="padding-left:30px;">Location Code</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Items Stored</th>
                        <th>Total Quantity</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            $id = $row['location_id'];
                            $code = htmlspecialchars($row['location_code']);
                            $desc = htmlspecialchars($row['description']);
                            
                            // Determine Type for display
                            $type_badge = (substr($code, 0, 1) === '1') ? "<span style='color:#b45309; background:#fef3c7; padding:4px 8px; border-radius:4px; font-weight:bold; font-size:12px;'>DRY</span>" : "<span style='color:#1d4ed8; background:#dbeafe; padding:4px 8px; border-radius:4px; font-weight:bold; font-size:12px;'>DRINKS</span>";

                            echo "<tr>";
                            echo "<td style='padding-left:30px;'><span class='loc-pill'><i class='bi bi-geo-alt-fill'></i> $code</span></td>";
                            echo "<td>$type_badge</td>";
                            echo "<td style='color:#4b5563; font-size:14px;'>$desc</td>";
                            echo "<td><span class='metric-value'>" . $row['unique_items'] . "</span> <span class='metric-label'>items</span></td>";
                            echo "<td><span class='metric-value'>" . number_format($row['total_units']) . "</span> <span class='metric-label'>units</span></td>";
                            echo "<td style='text-align:right;'>
                                    <button class='btn-text edit-btn' onclick='editLocation(\"$id\", \"$code\", \"$desc\")'><i class='bi bi-pencil-square'></i> Edit</button>
                                    <a href='locations.php?delete_id=$id' class='btn-text delete-btn' onclick=\"return confirm('Delete this location?');\"><i class='bi bi-trash'></i> Delete</a>
                                  </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' style='text-align:center; padding:50px; color:#9ca3af;'>No locations found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </main>

    <div id="LOC_MODAL" class="modal-overlay">
        <div class="modal-box">
            <h3 id="modalTitle" style="margin-top:0; margin-bottom:20px;">Add New Location</h3>
            <form method="POST">
                <input type="hidden" name="location_id" id="loc_id_input">

                <div style="display:flex; gap:10px; margin-bottom:15px;">
                    <div style="flex:1;">
                        <label style="font-size:13px; font-weight:600; color:#6b7280; display:block;">Type</label>
                        <select name="loc_type" id="loc_type_input" style="width:100%; padding:10px; border:1px solid #e5e7eb; border-radius:8px;" required>
                            <option value="1">1 - Dry</option>
                            <option value="0">0 - Drinks</option>
                        </select>
                    </div>
                    <div style="flex:1;">
                        <label style="font-size:13px; font-weight:600; color:#6b7280; display:block;">Column</label>
                        <input type="number" name="loc_col" id="loc_col_input" placeholder="1-99" min="1" max="99" style="width:100%; padding:10px; border:1px solid #e5e7eb; border-radius:8px;" required>
                    </div>
                    <div style="flex:1;">
                        <label style="font-size:13px; font-weight:600; color:#6b7280; display:block;">Bin</label>
                        <input type="number" name="loc_bin" id="loc_bin_input" placeholder="1-99" min="1" max="99" style="width:100%; padding:10px; border:1px solid #e5e7eb; border-radius:8px;" required>
                    </div>
                </div>

                <label style="font-size:13px; font-weight:600; color:#6b7280; display:block; margin-bottom:5px;">Description</label>
                <input type="text" name="description" id="loc_desc_input" placeholder="e.g. Rack A, Top Shelf" style="width:100%; padding:10px; border:1px solid #e5e7eb; border-radius:8px;" required>

                <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                    <button type="button" onclick="closeModal()" class="btn-cancel" style="background:transparent; border:1px solid #e5e7eb; color:#374151;">Cancel</button>
                    <button type="submit" name="save_location_btn" class="tf-btn-primary">Save Location</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById("LOC_MODAL");
        function openModal() {
            document.getElementById("modalTitle").innerText = "Add New Location";
            document.getElementById("loc_id_input").value = "";
            document.getElementById("loc_type_input").value = "1";
            document.getElementById("loc_col_input").value = "";
            document.getElementById("loc_bin_input").value = "";
            document.getElementById("loc_desc_input").value = "";
            modal.style.display = "flex";
        }

        function editLocation(id, code, desc) {
            document.getElementById("modalTitle").innerText = "Edit Location";
            document.getElementById("loc_id_input").value = id;
            
            // Split "1-01-01" into parts
            let parts = code.split('-');
            document.getElementById("loc_type_input").value = parts[0];
            document.getElementById("loc_col_input").value = parseInt(parts[1]);
            document.getElementById("loc_bin_input").value = parseInt(parts[2]);
            
            document.getElementById("loc_desc_input").value = desc;
            modal.style.display = "flex";
        }

        function closeModal() { modal.style.display = "none"; }
        window.onclick = function(e) { if (e.target == modal) closeModal(); }
    </script>
</body>
</html>