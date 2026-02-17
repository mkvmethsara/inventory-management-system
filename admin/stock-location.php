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

// 1. Get all Locations for the Dropdown Filter
$loc_sql = "SELECT * FROM locations ORDER BY location_code ASC";
$loc_result = mysqli_query($conn, $loc_sql);

// 2. Get Stock Data (Joining Items, Locations, and Batches)
// We join 4 tables here: Stock -> Items -> Locations -> Batches
$sql = "SELECT 
            s.quantity, 
            i.item_name, 
            i.item_code, 
            l.location_code, 
            b.batch_id
        FROM stock s
        JOIN items i ON s.item_id = i.item_id
        JOIN locations l ON s.location_id = l.location_id
        LEFT JOIN item_batches b ON s.batch_id = b.batch_id
        ORDER BY i.item_name ASC";

$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en" class="admin-dash-page">

<head>
    <meta charset="UTF-8">
    <title>Stock by Location</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

    <div class="header">TrackFlow – Stock by Location</div>

    <div class="layout">
        <?php include 'menu.php'; ?>

        <div class="main">
            <div class="card">

                <div class="top-bar">
                    <div class="filters">
                        <input type="text" id="searchInput" placeholder="Search item..." style="width: 250px;">

                        <select id="locationFilter">
                            <option value="all">All Locations</option>
                            <?php
                            if (mysqli_num_rows($loc_result) > 0) {
                                while ($loc = mysqli_fetch_assoc($loc_result)) {
                                    echo "<option value='" . strtolower($loc['location_code']) . "'>" . $loc['location_code'] . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <table id="stockTable">
                    <thead>
                        <tr>
                            <th>Item ID</th>
                            <th>Item Name</th>
                            <th>Location</th>
                            <th>Batch No</th>
                            <th>Available Qty</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                $qty = $row['quantity'];

                                // Determine Badge Color based on Quantity
                                $badge_class = "qty-high";
                                if ($qty == 0) {
                                    $badge_class = "qty-zero";
                                } elseif ($qty < 20) {
                                    $badge_class = "qty-low";
                                }

                                // Format Batch Display
                                $batch_display = $row['batch_id'] ? "BCH-" . str_pad($row['batch_id'], 3, '0', STR_PAD_LEFT) : "N/A";

                                echo "<tr>";
                                echo "<td style='opacity:0.7; font-family:monospace;'>" . $row['item_code'] . "</td>";
                                echo "<td style='font-weight:bold; color:#c9d6ff;'>" . $row['item_name'] . "</td>";
                                echo "<td class='loc-cell'>" . $row['location_code'] . "</td>";
                                echo "<td style='color:#a5b4fc;'>" . $batch_display . "</td>";
                                echo "<td><span class='qty-badge $badge_class'>" . $qty . "</span></td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' style='text-align:center; padding:30px; opacity:0.5;'>No stock records found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>

            </div>
        </div>
    </div>

    <script>
        const searchInput = document.getElementById("searchInput");
        const locationFilter = document.getElementById("locationFilter");
        const table = document.getElementById("stockTable");

        function filterTable() {
            const searchText = searchInput.value.toLowerCase();
            const locationVal = locationFilter.value.toLowerCase();
            const rows = table.querySelectorAll("tbody tr");

            rows.forEach(row => {
                const rowText = row.innerText.toLowerCase();
                // The location is in the 3rd column (index 2)
                const rowLocation = row.querySelector(".loc-cell").innerText.toLowerCase();

                const matchesSearch = rowText.includes(searchText);
                const matchesLocation = locationVal === "all" || rowLocation.includes(locationVal);

                if (matchesSearch && matchesLocation) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        }

        searchInput.addEventListener("keyup", filterTable);
        locationFilter.addEventListener("change", filterTable);
    </script>

</body>

</html>