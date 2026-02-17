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

// --- 1. HANDLE ADD/UPDATE SUPPLIER ---
if (isset($_POST['save_supplier_btn'])) {
    $id    = $_POST['supplier_id']; // Empty if adding new
    $name  = mysqli_real_escape_string($conn, $_POST['supplier_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);

    if (!empty($id)) {
        // UPDATE
        $sql = "UPDATE suppliers SET supplier_name='$name', email='$email', phone='$phone' WHERE supplier_id='$id'";
        $msg = "✅ Supplier Updated!";
    } else {
        // INSERT
        $sql = "INSERT INTO suppliers (supplier_name, email, phone) VALUES ('$name', '$email', '$phone')";
        $msg = "✅ New Supplier Added!";
    }

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('$msg'); window.location.href='suppliers.php';</script>";
    } else {
        echo "<script>alert('Error: " . mysqli_error($conn) . "');</script>";
    }
}

// --- 2. HANDLE DELETE ---
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    try {
        if (mysqli_query($conn, "DELETE FROM suppliers WHERE supplier_id = '$id'")) {
            echo "<script>window.location.href='suppliers.php';</script>";
        }
    } catch (Exception $e) {
        echo "<script>alert('⚠️ Cannot delete: This supplier has provided items currently in inventory.'); window.location.href='suppliers.php';</script>";
    }
}

// --- 3. FETCH DATA ---
$sql = "SELECT s.*, (SELECT COUNT(*) FROM items i WHERE i.supplier_id = s.supplier_id) as item_count FROM suppliers s ORDER BY s.supplier_id DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>TrackFlow – Suppliers Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=14">
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
                <h2>Suppliers Management</h2>
                <p>Manage supplier information and contacts</p>
            </div>

            <button onclick="openModal()" class="tf-btn-primary" style="background:#111827;">
                <i class="bi bi-plus-lg"></i> Add New Supplier
            </button>
        </div>

        <div class="tf-table-container">
            <div style="padding: 20px; border-bottom: 1px solid #f3f4f6;">
                <input type="text" id="searchInput" placeholder="Search suppliers..."
                    style="padding: 10px 15px; width: 300px; border: 1px solid #e5e7eb; border-radius: 8px; background:#f9fafb; outline:none;">
            </div>

            <table class="tf-table" id="supTable">
                <thead>
                    <tr>
                        <th style="padding-left:30px;">Supplier Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Items Supplied</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            // Prepare data for JS
                            $id = $row['supplier_id'];
                            $name = htmlspecialchars($row['supplier_name']);
                            $email = htmlspecialchars($row['email']);
                            $phone = htmlspecialchars($row['phone']);

                            echo "<tr>";

                            // 1. Name with Icon
                            echo "<td style='padding-left:30px;'>
                                    <div class='sup-profile'>
                                        <div class='sup-icon'><i class='bi bi-building'></i></div>
                                        <span class='sup-name'>$name</span>
                                    </div>
                                  </td>";

                            // 2. Email with Icon
                            echo "<td>
                                    <div class='contact-item'>
                                        <i class='bi bi-envelope'></i> $email
                                    </div>
                                  </td>";

                            // 3. Phone with Icon
                            echo "<td>
                                    <div class='contact-item'>
                                        <i class='bi bi-telephone'></i> $phone
                                    </div>
                                  </td>";

                            // 4. Items Count
                            echo "<td>
                                    <span class='metric-value'>" . $row['item_count'] . "</span> 
                                    <span class='metric-label'>items</span>
                                  </td>";

                            // 5. Actions
                            echo "<td style='text-align:right;'>
                                    <button class='btn-text edit-btn' onclick='editSupplier(\"$id\", \"$name\", \"$email\", \"$phone\")'>
                                        <i class='bi bi-pencil-square'></i> Edit
                                    </button>
                                    <a href='suppliers.php?delete_id=$id' class='btn-text delete-btn' onclick=\"return confirm('Delete this supplier?');\">
                                        <i class='bi bi-trash'></i> Delete
                                    </a>
                                  </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' style='text-align:center; padding:50px; color:#9ca3af;'>No suppliers found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

    </main>

    <div id="SUP_MODAL" class="modal-overlay">
        <div class="modal-box">
            <h3 id="modalTitle" style="margin-top:0; margin-bottom:20px;">Add New Supplier</h3>

            <form method="POST">
                <input type="hidden" name="supplier_id" id="sup_id_input">

                <label style="font-size:13px; font-weight:600; color:#6b7280; display:block; margin-bottom:5px;">Company Name</label>
                <input type="text" name="supplier_name" id="sup_name_input" placeholder="e.g. MedCorp Inc." required>

                <label style="font-size:13px; font-weight:600; color:#6b7280; display:block; margin-bottom:5px;">Email Address</label>
                <input type="email" name="email" id="sup_email_input" placeholder="contact@company.com" required>

                <label style="font-size:13px; font-weight:600; color:#6b7280; display:block; margin-bottom:5px;">Phone Number</label>
                <input type="text" name="phone" id="sup_phone_input" placeholder="+1 555 000 0000" required>

                <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                    <button type="button" onclick="closeModal()" class="btn-cancel" style="background:transparent; border:1px solid #e5e7eb; color:#374151;">Cancel</button>
                    <button type="submit" name="save_supplier_btn" class="tf-btn-primary">Save Supplier</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal Logic
        const modal = document.getElementById("SUP_MODAL");
        const title = document.getElementById("modalTitle");
        const idInput = document.getElementById("sup_id_input");
        const nameInput = document.getElementById("sup_name_input");
        const emailInput = document.getElementById("sup_email_input");
        const phoneInput = document.getElementById("sup_phone_input");

        function openModal() {
            // Reset for "Add New"
            title.innerText = "Add New Supplier";
            idInput.value = "";
            nameInput.value = "";
            emailInput.value = "";
            phoneInput.value = "";
            modal.style.display = "flex";
        }

        function editSupplier(id, name, email, phone) {
            // Fill for "Edit"
            title.innerText = "Edit Supplier";
            idInput.value = id;
            nameInput.value = name;
            emailInput.value = email;
            phoneInput.value = phone;
            modal.style.display = "flex";
        }

        function closeModal() {
            modal.style.display = "none";
        }
        window.onclick = function(e) {
            if (e.target == modal) closeModal();
        }

        // Search Logic
        document.getElementById("searchInput").addEventListener("keyup", function() {
            let val = this.value.toLowerCase();
            document.querySelectorAll("#supTable tbody tr").forEach(row => {
                row.style.display = row.innerText.toLowerCase().includes(val) ? "" : "none";
            });
        });
    </script>
</body>

</html>