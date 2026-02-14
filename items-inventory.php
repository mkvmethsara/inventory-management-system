<?php
include 'config/db.php';

// SQL Query: Get Items + Calculate Total Quantity + Find Location
// We use GROUP BY because one item might be in multiple batches
$sql = "SELECT 
            i.item_id, 
            i.item_name, 
            i.category, 
            i.item_code,
            i.minimum_level,
            COALESCE(SUM(s.quantity), 0) as total_qty,
            MAX(l.location_code) as main_location
        FROM items i
        LEFT JOIN stock s ON i.item_id = s.item_id
        LEFT JOIN locations l ON s.location_id = l.location_id
        GROUP BY i.item_id";

$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en" class="admin-dash-page">

<head>
    <meta charset="UTF-8">
    <title>Items Inventory</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

    <div class="header">
        TrackFlow – Items Inventory
    </div>

    <div class="layout">

        <?php include 'menu.php'; ?>

        <div class="main">

            <div class="card">

                <div class="top-bar">
                    <div class="search">
                        <input type="text" placeholder="Search item...">
                    </div>
                    <a href="add-item.php" class="add-btn">+ Add Item</a>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Item ID</th>
                            <th>Item Name</th>
                            <th>Category</th>
                            <th>Quantity</th>
                            <th>Location</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                // 1. Determine Status Logic
                                $qty = $row['total_qty'];
                                $min = $row['minimum_level'];
                                $status_class = "in";
                                $status_text = "In Stock";

                                if ($qty == 0) {
                                    $status_class = "out";
                                    $status_text = "Out of Stock";
                                } elseif ($qty < $min) {
                                    $status_class = "low";
                                    $status_text = "Low Stock";
                                }

                                // 2. Output the Table Row
                                echo "<tr>";
                                echo "<td style='opacity:0.7;'>#" . $row['item_code'] . "</td>";
                                echo "<td style='font-weight:bold;'>" . $row['item_name'] . "</td>";
                                echo "<td>" . $row['category'] . "</td>";
                                echo "<td style='font-family:monospace; font-size:15px;'>" . $qty . "</td>";
                                echo "<td>" . ($row['main_location'] ? $row['main_location'] : 'Not Assigned') . "</td>";
                                echo "<td><span class='status " . $status_class . "'>" . $status_text . "</span></td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' style='text-align:center; padding:20px; opacity:0.5;'>No items found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>

            </div>

        </div>

    </div>

</body>

</html>