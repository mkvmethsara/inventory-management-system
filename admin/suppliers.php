<?php
include '../config/db.php';

// --- 1. HANDLE FORM SUBMISSION ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // We only capture Name, Phone, and Email now
    $sup_name    = mysqli_real_escape_string($conn, $_POST['sup_name']);
    $sup_phone   = mysqli_real_escape_string($conn, $_POST['sup_phone']);
    $sup_email   = mysqli_real_escape_string($conn, $_POST['sup_email']);

    // INSERT only into the columns you actually have
    $sql_insert = "INSERT INTO suppliers (supplier_name, phone, email) 
                   VALUES ('$sup_name', '$sup_phone', '$sup_email')";

    if (mysqli_query($conn, $sql_insert)) {
        echo "<script>alert('✅ Supplier Added Successfully!'); window.location.href='suppliers.php';</script>";
    } else {
        echo "<script>alert('❌ Error: " . mysqli_error($conn) . "');</script>";
    }
}

// --- 2. FETCH SUPPLIERS DATA ---
$sql = "SELECT * FROM suppliers ORDER BY supplier_id DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>TrackFlow – Suppliers</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="admin-dash-page">

    <div class="header">TrackFlow – Suppliers</div>

    <div class="layout">
        <?php include 'menu.php'; ?>

        <div class="main">
            <div class="card">
                <div class="top-bar">
                    <div class="filters">
                        <input type="text" id="searchInput" placeholder="Search supplier...">
                    </div>
                    <button class="add-btn" onclick="openSupPopup()">+ Add Supplier</button>
                </div>

                <table id="suppliersTable">
                    <thead>
                        <tr>
                            <th>Supplier ID</th>
                            <th>Supplier Name</th>
                            <th>Phone</th>
                            <th>Email</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                // Auto-generate a pretty ID (e.g., SUP-001) based on the real ID
                                $pretty_id = "SUP-" . str_pad($row['supplier_id'], 3, '0', STR_PAD_LEFT);

                                echo "<tr>";
                                echo "<td style='opacity:0.6; font-family:monospace;'>" . $pretty_id . "</td>";
                                echo "<td style='font-weight:bold; color:#c9d6ff;'>" . $row['supplier_name'] . "</td>";
                                echo "<td>" . $row['phone'] . "</td>";
                                echo "<td>" . $row['email'] . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='4' style='text-align:center; padding:30px; opacity:0.5;'>No suppliers found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="SUP_MODAL_OVERLAY">
        <div class="sup-modal-box">
            <h3 style="color:white; margin-top:0; margin-bottom:20px;">Add New Supplier</h3>

            <form method="POST">
                <label>Supplier Name</label>
                <input type="text" name="sup_name" placeholder="e.g. ABC Electronics" required>

                <label>Phone Number</label>
                <input type="text" name="sup_phone" placeholder="e.g. 0771234567" required>

                <label>Email Address</label>
                <input type="email" name="sup_email" placeholder="e.g. abc@mail.com" required>

                <div class="sup-modal-footer">
                    <button type="button" class="btn-sup-cancel" onclick="closeSupPopup()">Cancel</button>
                    <button type="submit" class="btn-sup-save">Save Supplier</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal Logic
        function openSupPopup() {
            document.getElementById("SUP_MODAL_OVERLAY").classList.add("tms-active");
        }

        function closeSupPopup() {
            document.getElementById("SUP_MODAL_OVERLAY").classList.remove("tms-active");
        }
        window.onclick = function(e) {
            if (e.target == document.getElementById("SUP_MODAL_OVERLAY")) closeSupPopup();
        }

        // Live Search Logic
        document.getElementById("searchInput").addEventListener("keyup", function() {
            let val = this.value.toLowerCase();
            document.querySelectorAll("#suppliersTable tbody tr").forEach(row => {
                row.style.display = row.innerText.toLowerCase().includes(val) ? "" : "none";
            });
        });
    </script>

</body>

</html>