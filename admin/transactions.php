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

// --- 1. HANDLE ADD TRANSACTION ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $t_date = $_POST['t_date'];
    $t_type = $_POST['t_type'];
    $qty    = $_POST['quantity'];
    $user_id = $_POST['user_id'];

    // Get both Item ID and Batch ID
    $batch_data = explode(',', $_POST['batch_data']);
    $batch_id = $batch_data[0];
    $item_id  = $batch_data[1];

    // Auto-detect a valid Location ID
    $loc_q = mysqli_query($conn, "SELECT location_id FROM locations LIMIT 1");
    $loc_row = mysqli_fetch_assoc($loc_q);
    $valid_loc_id = $loc_row ? $loc_row['location_id'] : 1;

    $sql_insert = "INSERT INTO stock_transactions 
                   (transaction_time, item_id, batch_id, transaction_type, quantity, user_id, location_id) 
                   VALUES ('$t_date', '$item_id', '$batch_id', '$t_type', '$qty', '$user_id', '$valid_loc_id')";

    if (mysqli_query($conn, $sql_insert)) {
        echo "<script>alert('✅ Transaction Logged Successfully!'); window.location.href='transactions.php';</script>";
    } else {
        echo "<script>alert('❌ Database Error: " . mysqli_error($conn) . "');</script>";
    }
}

// --- 2. FETCH DATA ---
$sql = "SELECT t.*, i.item_name, u.username 
        FROM stock_transactions t
        LEFT JOIN items i ON t.item_id = i.item_id
        LEFT JOIN users u ON t.user_id = u.user_id
        ORDER BY t.transaction_time DESC";
$result = mysqli_query($conn, $sql);

// --- 3. FETCH ACTIVE BATCHES (FIXED) ---
// Removed 'b.batch_code' which was causing the crash
$batch_query = "SELECT b.batch_id, b.item_id, i.item_name 
                FROM item_batches b 
                JOIN items i ON b.item_id = i.item_id 
                ORDER BY i.item_name ASC";
$batches = mysqli_query($conn, $batch_query);

$users_dd = mysqli_query($conn, "SELECT user_id, username FROM users ORDER BY username ASC");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>TrackFlow – Transaction Logs</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="admin-dash-page">

    <div class="header">TrackFlow – Transaction Logs</div>

    <div class="layout">
        <?php include 'menu.php'; ?>

        <div class="main">
            <div class="card">
                <div class="top-bar">
                    <div class="filters">
                        <input type="text" id="searchInput" placeholder="Search logs...">
                    </div>
                    <button class="add-btn" onclick="openTransModal()">+ Add Transaction</button>
                </div>

                <table id="transTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Item Name</th>
                            <th>Type</th>
                            <th>Quantity</th>
                            <th>Staff Member</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                $type = strtoupper($row['transaction_type']);
                                $badge_class = ($type === 'IN') ? 'type-in' : 'type-out';
                                $date_display = date("Y-m-d", strtotime($row['transaction_time']));

                                echo "<tr>";
                                echo "<td style='font-family:monospace; opacity:0.8;'>$date_display</td>";
                                echo "<td style='font-weight:bold; color:#c9d6ff;'>" . ($row['item_name'] ?? 'Unknown') . "</td>";
                                echo "<td><span class='badge-trans $badge_class'>$type</span></td>";
                                echo "<td>" . $row['quantity'] . "</td>";
                                echo "<td>" . ($row['username'] ?? 'Unknown') . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' style='text-align:center; padding:30px; opacity:0.5;'>No transactions found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="TRANS_LOG_MODAL_OVERLAY">
        <div class="trans-modal-box">
            <h3 style="color:white; margin-top:0; margin-bottom:20px;">Add New Transaction</h3>

            <form method="POST">
                <label>Transaction Date</label>
                <input type="date" name="t_date" required value="<?php echo date('Y-m-d'); ?>">

                <label>Select Item Batch</label>
                <select name="batch_data" required>
                    <option value="">-- Choose Batch --</option>
                    <?php
                    if (mysqli_num_rows($batches) > 0) {
                        foreach ($batches as $b) {
                            $val = $b['batch_id'] . "," . $b['item_id'];
                            // Display Batch ID instead of Batch Code
                            echo "<option value='$val'>" . $b['item_name'] . " (Batch #" . $b['batch_id'] . ")</option>";
                        }
                    } else {
                        echo "<option disabled>No Batches Found! Create one first.</option>";
                    }
                    ?>
                </select>

                <div style="display:flex; gap:15px;">
                    <div style="flex:1;">
                        <label>Type</label>
                        <select name="t_type" required>
                            <option value="IN">Stock IN</option>
                            <option value="OUT">Stock OUT</option>
                        </select>
                    </div>
                    <div style="flex:1;">
                        <label>Quantity</label>
                        <input type="number" name="quantity" placeholder="0" required>
                    </div>
                </div>

                <label>Staff Member</label>
                <select name="user_id" required>
                    <option value="">-- Choose Staff --</option>
                    <?php
                    foreach ($users_dd as $u) {
                        echo "<option value='" . $u['user_id'] . "'>" . $u['username'] . "</option>";
                    }
                    ?>
                </select>

                <div class="trans-modal-footer">
                    <button type="button" class="trans-btn-cancel" onclick="closeTransModal()">Cancel</button>
                    <button type="submit" class="trans-btn-save">Save Log</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openTransModal() {
            document.getElementById("TRANS_LOG_MODAL_OVERLAY").classList.add("trans-active");
        }

        function closeTransModal() {
            document.getElementById("TRANS_LOG_MODAL_OVERLAY").classList.remove("trans-active");
        }
        window.onclick = function(e) {
            if (e.target == document.getElementById("TRANS_LOG_MODAL_OVERLAY")) closeTransModal();
        }

        document.getElementById("searchInput").addEventListener("keyup", function() {
            let val = this.value.toLowerCase();
            document.querySelectorAll("#transTable tbody tr").forEach(row => {
                row.style.display = row.innerText.toLowerCase().includes(val) ? "" : "none";
            });
        });
    </script>
</body>

</html>