<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// SECURITY GATE 🔒
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

include '../config/db.php';

// --- 1. HANDLE UPDATE ITEM (EDIT LOGIC) ---
if (isset($_POST['update_item_btn'])) {
    $id    = mysqli_real_escape_string($conn, $_POST['item_id']);
    $name  = mysqli_real_escape_string($conn, $_POST['item_name']);
    $code  = mysqli_real_escape_string($conn, $_POST['item_code']);
    $cat   = mysqli_real_escape_string($conn, $_POST['category']);
    $sup   = mysqli_real_escape_string($conn, $_POST['supplier_id']);

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
            echo "<script>window.location.href='items-inventory.php';</script>";
        }
    } catch (Exception $e) {
        echo "<script>alert('⚠️ Cannot delete: This item has transaction history.'); window.location.href='items-inventory.php';</script>";
    }
}

// --- 3. HANDLE ADD ITEM ---
if (isset($_POST['add_item_btn'])) {
    $name = mysqli_real_escape_string($conn, $_POST['item_name']);
    $code = mysqli_real_escape_string($conn, $_POST['item_code']);
    $cat  = $_POST['category'];
    $sup  = $_POST['supplier_id'];
    
    // Auto-generate RFID and Min Level for demo
    $rfid = "RF-" . rand(1000, 9999);
    $min  = 10;

    $sql = "INSERT INTO items (item_name, item_code, category, supplier_id, rfid_tag_id, minimum_level, created_at) 
            VALUES ('$name', '$code', '$cat', '$sup', '$rfid', '$min', NOW())";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('✅ New Item Added!'); window.location.href='items-inventory.php';</script>";
    } else {
        echo "<script>alert('❌ Error: " . mysqli_error($conn) . "');</script>";
    }
}

// --- 4. FETCH DATA ---
$items_res = mysqli_query($conn, "SELECT i.*, sp.supplier_name FROM items i LEFT JOIN suppliers sp ON i.supplier_id = sp.supplier_id ORDER BY i.item_id DESC");
$sup_res   = mysqli_query($conn, "SELECT * FROM suppliers ORDER BY supplier_name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>TrackFlow – Product Catalog</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=21">
</head>

<body class="trackflow-body">

    <aside class="tf-sidebar">
        <div class="tf-logo">
            <i class="bi bi-box-seam-fill"></i> TRACKFLOW
        </div>
        <nav class="tf-nav">
            <a href="dashboard.php"><i class="bi bi-grid-fill"></i> Dashboard</a>
            
            <a href="items-inventory.php" class="active"><i class="bi bi-box"></i> Items Inventory</a>
            
            <a href="batch-expiry.php"><i class="bi bi-clock-history"></i> Batch & Expiry</a>
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
                <h2>Product Catalog</h2>
                <p>Manage item details and RFID assignments</p>
            </div>
            <button onclick="openModal()" class="tf-btn-primary">
                <i class="bi bi-plus-lg"></i> Register New Item
            </button>
        </div>

        <div class="tf-table-container">
            <div style="padding: 20px; border-bottom: 1px solid #f3f4f6;">
                <input type="text" id="searchInput" placeholder="Search items..." 
                       style="padding: 10px 15px; width: 300px; border: 1px solid #e5e7eb; border-radius: 8px; background:#f9fafb; outline:none;">
            </div>

            <table class="tf-table" id="itemTable">
                <thead>
                    <tr>
                        <th>Item Details</th>
                        <th>Category</th>
                        <th>RFID Tag</th>
                        <th>Min Level</th>
                        <th>Supplier</th>
                        <th style="text-align:right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($items_res)): ?>
                        <tr>
                            <td>
                                <div class="item-meta">
                                    <span class="item-name"><?php echo htmlspecialchars($row['item_name']); ?></span>
                                    <span class="item-code"><?php echo htmlspecialchars($row['item_code']); ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="badge-cat <?php echo $row['category']; ?>">
                                    <?php echo $row['category']; ?>
                                </span>
                            </td>
                            <td style="font-family:monospace; color:#6b7280;"><?php echo $row['rfid_tag_id']; ?></td>
                            <td style="font-weight:700;"><?php echo $row['minimum_level']; ?></td>
                            <td style="color:#6b7280;"><?php echo htmlspecialchars($row['supplier_name'] ?? 'Unknown'); ?></td>
                            
                            <td class="action-icons" style="text-align:right">
                                <a href="javascript:void(0)" 
                                   onclick="openEditModal(
                                       '<?php echo $row['item_id']; ?>', 
                                       '<?php echo addslashes($row['item_name']); ?>', 
                                       '<?php echo $row['item_code']; ?>', 
                                       '<?php echo $row['category']; ?>', 
                                       '<?php echo $row['supplier_id']; ?>'
                                   )" 
                                   title="Edit" style="color:#4b5563; margin-right:15px;">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                
                                <a href="items-inventory.php?delete_id=<?php echo $row['item_id']; ?>" class="delete" title="Delete" onclick="return confirm('Delete this item?')" style="color:#ef4444;">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

    </main>

    <div id="ADD_MODAL" class="modal-overlay">
        <div class="modal-box">
            <h3 id="modal_title" style="margin-top:0; margin-bottom:20px;">Register New Item</h3>
            
            <form method="POST">
                <input type="hidden" name="item_id" id="item_id_input">

                <label style="font-size:13px; font-weight:600; color:#6b7280; display:block; margin-bottom:5px;">Item Name</label>
                <input type="text" name="item_name" id="item_name_input" placeholder="e.g. Paracetamol" required>

                <label style="font-size:13px; font-weight:600; color:#6b7280; display:block; margin-bottom:5px;">Item Code</label>
                <input type="text" name="item_code" id="item_code_input" placeholder="e.g. MED-001" required>

                <label style="font-size:13px; font-weight:600; color:#6b7280; display:block; margin-bottom:5px;">Category</label>
                <select name="category" id="category_input">
                    <option value="Medicine">Medicine</option>
                    <option value="Supplies">Supplies</option>
                    <option value="Equipment">Equipment</option>
                </select>

                <label style="font-size:13px; font-weight:600; color:#6b7280; display:block; margin-bottom:5px;">Supplier</label>
                <select name="supplier_id" id="supplier_input">
                    <?php
                    if (mysqli_num_rows($sup_res) > 0) {
                        mysqli_data_seek($sup_res, 0);
                        while ($s = mysqli_fetch_assoc($sup_res)) {
                            echo "<option value='" . $s['supplier_id'] . "'>" . $s['supplier_name'] . "</option>";
                        }
                    }
                    ?>
                </select>

                <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                    <button type="button" onclick="closeModal()" style="background:transparent; border:1px solid #e5e7eb; padding:10px 20px; border-radius:8px; cursor:pointer; color:#374151;">Cancel</button>
                    <button type="submit" name="add_item_btn" id="save_btn" class="tf-btn-primary">Save Item</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal & Search Logic
        
        function openModal() {
            // Reset for "Add New"
            document.getElementById("modal_title").innerText = "Register New Item";
            document.getElementById("item_id_input").value = "";
            document.getElementById("item_name_input").value = "";
            document.getElementById("item_code_input").value = "";
            document.getElementById("save_btn").name = "add_item_btn"; // Set PHP Action
            document.getElementById("save_btn").innerText = "Save Item";
            document.getElementById("ADD_MODAL").style.display = "flex";
        }

        function openEditModal(id, name, code, cat, sup) {
            // Pre-fill for "Edit"
            document.getElementById("modal_title").innerText = "Edit Item Details";
            document.getElementById("item_id_input").value = id;
            document.getElementById("item_name_input").value = name;
            document.getElementById("item_code_input").value = code;
            document.getElementById("category_input").value = cat;
            document.getElementById("supplier_input").value = sup;
            
            document.getElementById("save_btn").name = "update_item_btn"; // Set PHP Action
            document.getElementById("save_btn").innerText = "Update Item";
            document.getElementById("ADD_MODAL").style.display = "flex";
        }

        function closeModal() {
            document.getElementById("ADD_MODAL").style.display = "none";
        }
        
        window.onclick = function(e) {
            if (e.target == document.getElementById("ADD_MODAL")) closeModal();
        }

        document.getElementById("searchInput").addEventListener("keyup", function() {
            let val = this.value.toLowerCase();
            document.querySelectorAll("#itemTable tbody tr").forEach(row => {
                row.style.display = row.innerText.toLowerCase().includes(val) ? "" : "none";
            });
        });
    </script>
</body>
</html>