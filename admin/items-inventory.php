<?php
include '../config/db.php';

// SAVE LOGIC
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $item_code = mysqli_real_escape_string($conn, $_POST['item_id']);
    $item_name = mysqli_real_escape_string($conn, $_POST['item_name']);
    $category  = mysqli_real_escape_string($conn, $_POST['category']);

    $sql = "INSERT INTO items (item_code, item_name, category) VALUES ('$item_code', '$item_name', '$category')";
    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('✅ Item Added Successfully!'); window.location.href='items-inventory.php';</script>";
    }
}

// FETCH DATA
$sql = "SELECT i.*, COALESCE(SUM(s.quantity), 0) as total_qty FROM items i LEFT JOIN stock s ON i.item_id = s.item_id GROUP BY i.item_id ORDER BY i.item_id DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>TrackFlow – Items Inventory</title>
    <link rel="stylesheet" href="../assets/css/style.css">
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
                    <button class="add-btn" onclick="openTmsPopup()">+ Add Item</button>
                </div>

                <table id="itemsTable">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Qty</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result)):
                            $qty = $row['total_qty'];
                            $s_class = ($qty > 20) ? "in" : (($qty > 0) ? "low" : "out");
                            $status = ($qty > 20) ? "In Stock" : (($qty > 0) ? "Low Stock" : "Out of Stock");
                        ?>
                            <tr>
                                <td style="opacity:0.6;"><?php echo $row['item_code']; ?></td>
                                <td><strong><?php echo $row['item_name']; ?></strong></td>
                                <td><?php echo $row['category']; ?></td>
                                <td><?php echo $qty; ?></td>
                                <td><span class="status <?php echo $s_class; ?>"><?php echo $status; ?></span></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="TMS_INVENTORY_MODAL_OVERLAY">
        <div id="TMS_MODAL_BOX_CONTENT">
            <h3>Add New Item</h3>
            <form method="POST">
                <label>Item Code (ID)</label>
                <input type="text" name="item_id" placeholder="e.g. ITM001" required>

                <label>Item Name</label>
                <input type="text" name="item_name" placeholder="e.g. Keyboard" required>

                <label>Category</label>
                <input type="text" name="category" placeholder="e.g. Electronics" required>

                <div class="tms-form-footer">
                    <button type="button" class="tms-cancel-btn" onclick="closeTmsPopup()">Cancel</button>
                    <button type="submit" class="tms-save-btn">Save Item</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openTmsPopup() {
            document.getElementById("TMS_INVENTORY_MODAL_OVERLAY").classList.add("tms-active");
        }

        function closeTmsPopup() {
            document.getElementById("TMS_INVENTORY_MODAL_OVERLAY").classList.remove("tms-active");
        }

        window.onclick = function(e) {
            if (e.target == document.getElementById("TMS_INVENTORY_MODAL_OVERLAY")) closeTmsPopup();
        }

        document.getElementById("searchInput").addEventListener("keyup", function() {
            let val = this.value.toLowerCase();
            document.querySelectorAll("#itemsTable tbody tr").forEach(row => {
                row.style.display = row.innerText.toLowerCase().includes(val) ? "" : "none";
            });
        });
    </script>

</body>

</html>