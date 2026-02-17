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

// --- PHP LOGIC (Add/Edit/Delete) ---

// 1. UPDATE ITEM
if (isset($_POST['update_item_btn'])) {
    $id    = $_POST['item_id'];
    $name  = $_POST['item_name'];
    $code  = $_POST['item_code'];
    $cat   = $_POST['category'];
    $sup   = $_POST['supplier_id'];

    $sql = "UPDATE items SET item_name='$name', item_code='$code', category='$cat', supplier_id='$sup' WHERE item_id='$id'";
    if (mysqli_query($conn, $sql)) {
        echo "<script>window.location.href='items-inventory.php';</script>";
    }
}

// 2. DELETE ITEM
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

// 3. ADD ITEM
if (isset($_POST['add_item_btn'])) {
    $name = $_POST['item_name'];
    $code = $_POST['item_code'];
    $cat  = $_POST['category'];
    $sup  = $_POST['supplier_id'];
    // Auto-generate RFID and Min Level for demo
    $rfid = "RF-" . rand(1000, 9999);
    $min  = 10;

    $sql = "INSERT INTO items (item_name, item_code, category, supplier_id, rfid_tag_id, minimum_level, created_at) 
            VALUES ('$name', '$code', '$cat', '$sup', '$rfid', '$min', NOW())";

    if (mysqli_query($conn, $sql)) {
        echo "<script>window.location.href='items-inventory.php';</script>";
    }
}

// 4. FETCH DATA
$items_res = mysqli_query($conn, "SELECT i.*, sp.supplier_name FROM items i LEFT JOIN suppliers sp ON i.supplier_id = sp.supplier_id ORDER BY i.item_id DESC");
$sup_res   = mysqli_query($conn, "SELECT * FROM suppliers");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>TrackFlow – Product Catalog</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=3">
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
                <h2>Product Catalog</h2>
                <p>Manage item details and RFID assignments</p>
            </div>
            <button onclick="openModal()" class="tf-btn-primary">
                <i class="bi bi-plus-lg"></i> Register New Item
            </button>
        </div>

        <div class="tf-table-container">
            <table class="tf-table">
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
                                    <span class="item-name"><?php echo $row['item_name']; ?></span>
                                    <span class="item-code"><?php echo $row['item_code']; ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="badge-cat <?php echo $row['category']; ?>">
                                    <?php echo $row['category']; ?>
                                </span>
                            </td>
                            <td style="font-family:monospace; color:#6b7280;"><?php echo $row['rfid_tag_id']; ?></td>
                            <td style="font-weight:700;"><?php echo $row['minimum_level']; ?></td>
                            <td style="color:#6b7280;"><?php echo $row['supplier_name'] ?? 'Unknown'; ?></td>
                            <td class="action-icons" style="text-align:right">
                                <a href="#" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                <a href="#" title="View"><i class="bi bi-eye"></i></a>
                                <a href="items-inventory.php?delete_id=<?php echo $row['item_id']; ?>" class="delete" title="Delete" onclick="return confirm('Delete item?')"><i class="bi bi-trash"></i></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

    </main>

    <div id="ADD_MODAL" class="modal-overlay">
        <div class="modal-box">
            <h3 style="margin-top:0; margin-bottom:20px;">Register New Item</h3>
            <form method="POST">
                <label style="font-size:13px; font-weight:600; color:#6b7280; display:block; margin-bottom:5px;">Item Name</label>
                <input type="text" name="item_name" placeholder="e.g. Paracetamol" required>

                <label style="font-size:13px; font-weight:600; color:#6b7280; display:block; margin-bottom:5px;">Item Code</label>
                <input type="text" name="item_code" placeholder="e.g. MED-001" required>

                <label style="font-size:13px; font-weight:600; color:#6b7280; display:block; margin-bottom:5px;">Category</label>
                <select name="category">
                    <option value="Medicine">Medicine</option>
                    <option value="Supplies">Supplies</option>
                    <option value="Equipment">Equipment</option>
                </select>

                <label style="font-size:13px; font-weight:600; color:#6b7280; display:block; margin-bottom:5px;">Supplier</label>
                <select name="supplier_id">
                    <?php
                    mysqli_data_seek($sup_res, 0);
                    while ($s = mysqli_fetch_assoc($sup_res)) {
                        echo "<option value='" . $s['supplier_id'] . "'>" . $s['supplier_name'] . "</option>";
                    }
                    ?>
                </select>

                <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:10px;">
                    <button type="button" onclick="closeModal()" style="background:transparent; border:1px solid #e5e7eb; padding:10px 20px; border-radius:8px; cursor:pointer; color:#374151;">Cancel</button>
                    <button type="submit" name="add_item_btn" class="tf-btn-primary">Save Item</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById("ADD_MODAL").style.display = "flex";
        }

        function closeModal() {
            document.getElementById("ADD_MODAL").style.display = "none";
        }
    </script>
</body>

</html>