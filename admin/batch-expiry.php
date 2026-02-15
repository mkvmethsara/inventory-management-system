<?php
include '../config/db.php';

// SQL: Get Batches + Item Info + Current Stock Quantity
// We use a JOIN to calculate the total quantity remaining in each batch
$sql = "SELECT 
            b.batch_id, 
            b.expiry_date, 
            b.received_date, 
            i.item_name, 
            i.item_code,
            COALESCE(SUM(s.quantity), 0) as batch_qty
        FROM item_batches b
        JOIN items i ON b.item_id = i.item_id
        LEFT JOIN stock s ON b.batch_id = s.batch_id
        GROUP BY b.batch_id
        ORDER BY b.expiry_date ASC"; // Show earliest expiry first

$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en" class="admin-dash-page">

<head>
    <meta charset="UTF-8">
    <title>Batch & Expiry</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

    <div class="header">
        TrackFlow – Batch & Expiry
    </div>

    <div class="layout">

        <?php include 'menu.php'; ?>

        <div class="main">

            <div class="card">

                <div class="top-bar">
                    <div class="filters">
                        <input type="text" placeholder="Search item / batch...">
                        <select>
                            <option>All Status</option>
                            <option>Valid</option>
                            <option>Expiring Soon</option>
                            <option>Expired</option>
                        </select>
                    </div>

                    <button class="add-btn">+ Add Batch</button>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Item ID</th>
                            <th>Item Name</th>
                            <th>Batch No</th>
                            <th>Quantity</th>
                            <th>Manufacture Date</th>
                            <th>Expiry Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                // --- LOGIC: CHECK EXPIRY ---
                                $expiry_date = $row['expiry_date'];
                                $today = date('Y-m-d');

                                // Calculate days difference
                                $diff = strtotime($expiry_date) - strtotime($today);
                                $days_left = round($diff / (60 * 60 * 24));

                                // Default Status: Valid
                                $status_class = "ok";
                                $status_text = "Valid";

                                if ($days_left < 0) {
                                    $status_class = "expired";
                                    $status_text = "Expired";
                                } elseif ($days_left <= 30) {
                                    // Warn if less than 30 days
                                    $status_class = "expiring";
                                    $status_text = "Expiring Soon";
                                }

                                // Format Batch ID like "BCH-001"
                                $batch_display = "BCH-" . str_pad($row['batch_id'], 4, '0', STR_PAD_LEFT);

                                // --- DISPLAY ROW ---
                                echo "<tr>";
                                echo "<td style='opacity:0.7; font-family:monospace;'>" . $row['item_code'] . "</td>";
                                echo "<td style='font-weight:bold; color:#c9d6ff;'>" . $row['item_name'] . "</td>";
                                echo "<td style='color:#a5b4fc;'>" . $batch_display . "</td>";
                                echo "<td style='font-weight:bold;'>" . $row['batch_qty'] . "</td>";
                                echo "<td>" . $row['received_date'] . "</td>";
                                echo "<td>" . $row['expiry_date'] . "</td>";
                                echo "<td><span class='badge " . $status_class . "'>" . $status_text . "</span></td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7' style='text-align:center; padding:30px; opacity:0.5;'>No batches found in database.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>

            </div>

        </div>

    </div>

</body>

</html>