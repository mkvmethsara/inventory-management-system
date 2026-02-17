<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// SECURITY GATE 🔒
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    // If not logged in, or not an Admin, kick them out
    header("Location: ../index.php");
    exit();
}
?>

<?php
include '../config/db.php';

// --- 1. HANDLE UPDATE ITEM (The 'U' in CRUD) ---
if (isset($_POST['update_item_btn'])) {
    $id    = $_POST['item_id'];
    $name  = $_POST['item_name'];
    $code  = $_POST['item_code'];
    $cat   = $_POST['category'];
    $sup   = $_POST['supplier_id'];

    $sql = "UPDATE items SET item_name='$name', item_code='$code', category='$cat', supplier_id='$sup' WHERE item_id='$id'";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('✅ Item Updated Successfully!'); window.location.href='items-inventory.php';</script>";
    } else {
        echo "<script>alert('❌ Error: " . mysqli_error($conn) . "');</script>";
    }
}

// --- 2. HANDLE DELETE ITEM ---
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    try {
        if (mysqli_query($conn, "DELETE FROM items WHERE item_id = '$id'")) {
            echo "<script>alert('✅ Item Deleted!'); window.location.href='items-inventory.php';</script>";
        }
    } catch (Exception $e) {
        echo "<script>alert('⚠️ SECURITY ALERT: Cannot delete this item because it has transaction history. \\n\\nSet quantity to 0 instead.'); window.location.href='items-inventory.php';</script>";
    }
}

// --- 3. HANDLE ADD ITEM ---
if (isset($_POST['add_item_btn'])) {
    $name = $_POST['item_name'];
    $code = $_POST['item_code'];
    $cat  = $_POST['category'];
    $sup  = $_POST['supplier_id'];
    // Auto-generate some required fields for now
    $rfid = "RF-" . rand(1000, 9999);
    $min  = 10;

    $sql = "INSERT INTO items (item_name, item_code, category, supplier_id, rfid_tag_id, minimum_level, created_at) 
            VALUES ('$name', '$code', '$cat', '$sup', '$rfid', '$min', NOW())";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('✅ New Item Added!'); window.location.href='items-inventory.php';</script>";
    } else {
        echo "<script>alert('Error: " . mysqli_error($conn) . "');</script>";
    }
}

// --- 4. PREPARE EDIT DATA ---
$edit_mode = false;
$edit_data = ['item_name' => '', 'item_code' => '', 'category' => '', 'supplier_id' => '', 'item_id' => ''];

if (isset($_GET['edit_id'])) {
    $edit_mode = true;
    $id = $_GET['edit_id'];
    $res = mysqli_query($conn, "SELECT * FROM items WHERE item_id='$id'");
    $edit_data = mysqli_fetch_assoc($res);
}

// --- 5. FETCH LIST & SUPPLIERS ---
$items_res = mysqli_query($conn, "SELECT i.*, COALESCE(SUM(s.quantity), 0) as total_qty FROM items i LEFT JOIN stock s ON i.item_id = s.item_id GROUP BY i.item_id ORDER BY i.item_id DESC");
$sup_res   = mysqli_query($conn, "SELECT * FROM suppliers");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>TrackFlow – Items Inventory</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* BUTTON STYLES */
        .btn-action {
            padding: 5px 10px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 12px;
            font-weight: bold;
            margin-right: 5px;
            display: inline-block;
        }

        .btn-edit {
            background: rgba(251, 191, 36, 0.2);
            color: #fbbf24;
            border: 1px solid #fbbf24;
        }

        .btn-edit:hover {
            background: #fbbf24;
            color: black;
        }

        .btn-delete {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: 1px solid #ef4444;
        }

        .btn-delete:hover {
            background: #ef4444;
            color: white;
        }

        /* MODAL STYLES */
        .modal-overlay {
            display: none;
            /* Hidden by default */
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }

        .modal-box {
            background: #1e293b;
            padding: 30px;
            border-radius: 15px;
            width: 400px;
            border: 1px solid #334155;
        }

        .modal-input {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            border: none;
            background: #ffffff;
            color: black;
            /* High Visibility */
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
                    <button class="add-btn" onclick="openModal()">+ Add Item</button>
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
                        if (mysqli_num_rows($items_res) > 0) {
                            while ($row = mysqli_fetch_assoc($items_res)) {
                                $qty = $row['total_qty'];
                                $status = ($qty == 0) ? "Out of Stock" : (($qty < 20) ? "Low Stock" : "In Stock");
                                $color  = ($qty == 0) ? "#ef4444" : (($qty < 20) ? "#f59e0b" : "#22c55e");

                                echo "<tr>";
                                echo "<td style='opacity:0.7; font-family:monospace;'>" . $row['item_code'] . "</td>";
                                echo "<td style='font-weight:bold; color:#c9d6ff;'>" . $row['item_name'] . "</td>";
                                echo "<td>" . $row['category'] . "</td>";
                                echo "<td>" . $qty . "</td>";
                                echo "<td><span style='color:$color; font-weight:bold; font-size:12px;'>● $status</span></td>";

                                echo "<td>";
                                // EDIT BUTTON (Refreshes page with ?edit_id=X)
                                echo "<a href='items-inventory.php?edit_id=" . $row['item_id'] . "' class='btn-action btn-edit'>✏️ Edit</a>";
                                // DELETE BUTTON
                                echo "<a href='items-inventory.php?delete_id=" . $row['item_id'] . "' class='btn-action btn-delete' onclick=\"return confirm('⚠️ Are you sure?');\">🗑 Delete</a>";
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' style='text-align:center;'>No items found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="ITEM_MODAL" class="modal-overlay">
        <div class="modal-box" style="<?php echo $edit_mode ? 'border-color:#fbbf24;' : ''; ?>">

            <h3 style="color:white; margin-top:0;">
                <?php echo $edit_mode ? '✏️ Edit Item' : '+ Add New Item'; ?>
            </h3>

            <form method="POST">
                <input type="hidden" name="item_id" value="<?php echo $edit_data['item_id']; ?>">

                <label style="color:#94a3b8; font-size:12px;">Item Name</label>
                <input type="text" name="item_name" class="modal-input" placeholder="e.g. Gaming Mouse" required
                    value="<?php echo $edit_data['item_name']; ?>">

                <label style="color:#94a3b8; font-size:12px;">Item Code</label>
                <input type="text" name="item_code" class="modal-input" placeholder="e.g. GM-001" required
                    value="<?php echo $edit_data['item_code']; ?>">

                <label style="color:#94a3b8; font-size:12px;">Category</label>
                <select name="category" class="modal-input">
                    <option value="Electronics" <?php if ($edit_data['category'] == 'Electronics') echo 'selected'; ?>>Electronics</option>
                    <option value="Groceries" <?php if ($edit_data['category'] == 'Groceries') echo 'selected'; ?>>Groceries</option>
                    <option value="Stationery" <?php if ($edit_data['category'] == 'Stationery') echo 'selected'; ?>>Stationery</option>
                    <option value="Beverages" <?php if ($edit_data['category'] == 'Beverages') echo 'selected'; ?>>Beverages</option>
                    <option value="Snacks" <?php if ($edit_data['category'] == 'Snacks') echo 'selected'; ?>>Snacks</option>
                </select>

                <label style="color:#94a3b8; font-size:12px;">Supplier</label>
                <select name="supplier_id" class="modal-input">
                    <?php
                    // Reset pointer for the loop
                    mysqli_data_seek($sup_res, 0);
                    while ($s = mysqli_fetch_assoc($sup_res)) {
                        $selected = ($edit_data['supplier_id'] == $s['supplier_id']) ? 'selected' : '';
                        echo "<option value='" . $s['supplier_id'] . "' $selected>" . $s['supplier_name'] . "</option>";
                    }
                    ?>
                </select>

                <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                    <a href="items-inventory.php" style="padding:10px 20px; border-radius:5px; background:#475569; color:white; text-decoration:none;">Cancel</a>

                    <?php if ($edit_mode): ?>
                        <button type="submit" name="update_item_btn" style="background:#fbbf24; color:black; padding:10px 20px; border-radius:5px; border:none; font-weight:bold; cursor:pointer;">Update Item</button>
                    <?php else: ?>
                        <button type="submit" name="add_item_btn" style="background:#4f46e5; color:white; padding:10px 20px; border-radius:5px; border:none; font-weight:bold; cursor:pointer;">Save Item</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Open Modal Function
        function openModal() {
            document.getElementById("ITEM_MODAL").style.display = "flex";
        }

        // Search Logic
        document.getElementById("searchInput").addEventListener("keyup", function() {
            let val = this.value.toLowerCase();
            document.querySelectorAll("#invTable tbody tr").forEach(row => {
                row.style.display = row.innerText.toLowerCase().includes(val) ? "" : "none";
            });
        });

        // AUTO-OPEN MODAL IF EDITING
        // PHP sets $edit_mode to true/false. We use that here.
        <?php if ($edit_mode): ?>
            openModal();
        <?php endif; ?>
    </script>
</body>

</html>