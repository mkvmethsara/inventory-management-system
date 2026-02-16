<?php
include '../config/db.php';

// --- 1. HANDLE DELETE (The 'D' in CRUD) ---
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];

    // We try to delete. If the database says "NO" (because of history), we show a smart error.
    $sql = "DELETE FROM items WHERE item_id = '$id'";

    try {
        if (mysqli_query($conn, $sql)) {
            // SUCCESS: Item had no history, so it's gone.
            echo "<script>alert('✅ Item Deleted Successfully!'); window.location.href='items-inventory.php';</script>";
        } else {
            throw new Exception(mysqli_error($conn));
        }
    } catch (Exception $e) {
        // FAILURE: Item has history.
        echo "<script>alert('⚠️ SECURITY ALERT: You cannot delete this item because it has Transaction History. \\n\\nTo remove it, set the quantity to 0 instead.'); window.location.href='items-inventory.php';</script>";
    }
}

// --- 2. HANDLE ADD (The 'C' in CRUD) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $i_name = $_POST['item_name'];
    $i_code = $_POST['item_code'];
    $cat    = $_POST['category'];
    $supp   = $_POST['supplier_id'];

    // Default dummy values for required fields
    $rfid   = "RF-" . rand(1000, 9999);
    $min    = 10;

    $sql = "INSERT INTO items (item_name, item_code, category, supplier_id, rfid_tag_id, minimum_level, created_at) 
            VALUES ('$i_name', '$i_code', '$cat', '$supp', '$rfid', '$min', NOW())";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('✅ Item Added!'); window.location.href='items-inventory.php';</script>";
    } else {
        echo "<script>alert('❌ Error: " . mysqli_error($conn) . "');</script>";
    }
}

// --- 3. FETCH DATA (The 'R' in CRUD) ---
// Using a LEFT JOIN to get the total quantity safely
$sql = "SELECT i.*, 
        COALESCE(SUM(s.quantity), 0) as total_qty 
        FROM items i 
        LEFT JOIN stock s ON i.item_id = s.item_id 
        GROUP BY i.item_id 
        ORDER BY i.item_id DESC";
$result = mysqli_query($conn, $sql);

// Fetch Suppliers for the Add Modal
$sup_q = mysqli_query($conn, "SELECT * FROM suppliers");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>TrackFlow – Items Inventory</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Delete Button Styling */
        .btn-delete {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: 1px solid #ef4444;
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 700;
            transition: 0.2s;
        }

        .btn-delete:hover {
            background: #ef4444;
            color: white;
        }
    </style>
</head>

<body class="admin-dash-page">

    <div class="header">TrackFlow – Items Inventory</div>

    <div class="layout">
        <?php include 'menu.php'; ?>

        <div class="main">
            <div class="card">
                <div class="top-bar">
                    <div class="filters">
                        <input type="text" id="searchInput" placeholder="Search inventory...">
                    </div>
                    <button class="add-btn" onclick="openItemModal()">+ Add Item</button>
                </div>

                <table id="invTable">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Qty</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                $qty = $row['total_qty'];

                                // Status Logic
                                $status = "In Stock";
                                $badge = "badge-in"; // You need these classes in CSS
                                if ($qty == 0) {
                                    $status = "Out of Stock";
                                    $badge = "badge-out";
                                } elseif ($qty < 20) {
                                    $status = "Low Stock";
                                    $badge = "badge-low";
                                }

                                echo "<tr>";
                                echo "<td style='opacity:0.7; font-family:monospace;'>" . $row['item_code'] . "</td>";
                                echo "<td style='font-weight:bold; color:#c9d6ff;'>" . $row['item_name'] . "</td>";
                                echo "<td>" . $row['category'] . "</td>";
                                echo "<td>" . $qty . "</td>";

                                // Status Badge (Inline style for safety)
                                $color = ($qty == 0) ? '#ef4444' : (($qty < 20) ? '#f59e0b' : '#22c55e');
                                echo "<td><span style='color:$color; font-weight:bold; font-size:12px;'>● $status</span></td>";

                                // --- DELETE BUTTON ---
                                echo "<td>";
                                echo "<a href='items-inventory.php?delete_id=" . $row['item_id'] . "' 
                                         class='btn-delete'
                                         onclick=\"return confirm('⚠️ Are you sure you want to delete \'" . $row['item_name'] . "\'?');\">
                                         🗑 Delete
                                      </a>";
                                echo "</td>";

                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' style='text-align:center; padding:30px; opacity:0.5;'>No items found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="ITEM_MODAL_OVERLAY" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; align-items:center; justify-content:center;">
        <div style="background:#111c3a; padding:30px; border-radius:15px; width:400px; border:1px solid #1e293b;">
            <h3 style="color:white; margin-top:0;">Add New Item</h3>
            <form method="POST">
                <input type="text" name="item_name" placeholder="Item Name" required style="width:100%; padding:10px; margin-bottom:10px; border-radius:5px; border:none;">
                <input type="text" name="item_code" placeholder="Item Code (e.g. GM-001)" required style="width:100%; padding:10px; margin-bottom:10px; border-radius:5px; border:none;">

                <select name="category" required style="width:100%; padding:10px; margin-bottom:10px; border-radius:5px; border:none;">
                    <option value="Electronics">Electronics</option>
                    <option value="Groceries">Groceries</option>
                    <option value="Stationery">Stationery</option>
                </select>

                <select name="supplier_id" required style="width:100%; padding:10px; margin-bottom:20px; border-radius:5px; border:none;">
                    <?php
                    while ($s = mysqli_fetch_assoc($sup_q)) {
                        echo "<option value='" . $s['supplier_id'] . "'>" . $s['supplier_name'] . "</option>";
                    }
                    ?>
                </select>

                <div style="display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" onclick="document.getElementById('ITEM_MODAL_OVERLAY').style.display='none'" style="padding:10px 20px; border-radius:5px; border:none; cursor:pointer;">Cancel</button>
                    <button type="submit" style="background:#4f46e5; color:white; padding:10px 20px; border-radius:5px; border:none; cursor:pointer; font-weight:bold;">Save Item</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openItemModal() {
            document.getElementById("ITEM_MODAL_OVERLAY").style.display = "flex";
        }

        // Search Logic
        document.getElementById("searchInput").addEventListener("keyup", function() {
            let val = this.value.toLowerCase();
            document.querySelectorAll("#invTable tbody tr").forEach(row => {
                row.style.display = row.innerText.toLowerCase().includes(val) ? "" : "none";
            });
        });
    </script>
</body>

</html>