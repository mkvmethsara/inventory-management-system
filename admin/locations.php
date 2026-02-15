<?php
include '../config/db.php';

// --- 1. HANDLE FORM SUBMISSION ---
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $loc_code = $_POST['location_code'];
    $loc_desc = $_POST['description'];

    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

    $sql_insert = "INSERT INTO locations (location_code, description) 
                   VALUES ('$loc_code', '$loc_desc')";

    if (mysqli_query($conn, $sql_insert)) {
        $message = "<script>alert('✅ Location Added Successfully!'); window.location.href='locations.php';</script>";
    } else {
        $error = mysqli_error($conn);
        $message = "<div style='background:#9f1239; color:white; padding:10px; margin-bottom:15px; border-radius:8px;'>❌ Error: $error</div>";
    }
}

// --- 2. GET DATA ---
$sql = "SELECT * FROM locations ORDER BY location_id DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en" class="admin-dash-page">

<head>
    <meta charset="UTF-8">
    <title>Locations Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        /* 1. The Overlay */
        #locModal {
            display: none;
            /* Hidden by default */
            position: fixed !important;
            /* Force floating */
            z-index: 999999 !important;
            /* Force on top */
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8) !important;
            backdrop-filter: blur(5px);
            align-items: center;
            justify-content: center;
        }

        /* 2. The Box */
        .custom-modal-content {
            background-color: #0f172a;
            padding: 30px;
            border-radius: 16px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            border: 1px solid #1e293b;
            position: relative;
            display: block;
            /* Ensure content is visible */
        }

        /* 3. Inputs - White with Black Text */
        .custom-modal-content input,
        .custom-modal-content textarea {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            background: #ffffff !important;
            color: #000000 !important;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            outline: none;
            font-family: sans-serif;
            box-sizing: border-box;
        }

        .custom-modal-content h3 {
            margin-top: 0;
            color: white;
            margin-bottom: 20px;
        }

        .custom-modal-content label {
            color: #cbd5e1;
            margin-bottom: 5px;
            display: block;
            font-size: 13px;
            font-weight: 600;
        }

        /* 4. Buttons */
        .btn-group {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 10px;
        }

        .btn-cancel {
            background: #334155;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 6px;
            cursor: pointer;
            width: auto !important;
        }

        .btn-save {
            background: #4f46e5;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            width: auto !important;
        }
    </style>
</head>

<body>

    <div class="header">TrackFlow – Locations</div>

    <div class="layout">
        <?php include 'menu.php'; ?>

        <div class="main">
            <div class="card">
                <?php echo $message; ?>

                <div class="top-bar">
                    <div class="filters">
                        <input type="text" id="searchInput" placeholder="Search location..." style="width: 300px;">
                    </div>
                    <button class="add-btn" onclick="openPopup()">+ Add Location</button>
                </div>

                <table id="locTable">
                    <thead>
                        <tr>
                            <th>Location ID</th>
                            <th>Name / Code</th>
                            <th>Description</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                echo "<tr>";
                                echo "<td style='opacity:0.7;'>LOC-" . str_pad($row['location_id'], 3, '0', STR_PAD_LEFT) . "</td>";
                                echo "<td style='font-weight:bold; color:#c9d6ff;'>" . $row['location_code'] . "</td>";
                                echo "<td>" . ($row['description'] ? $row['description'] : 'No description') . "</td>";
                                echo "<td><span class='badge ok'>Active</span></td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='4' style='text-align:center; padding:30px; opacity:0.5;'>No locations found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="locModal">
        <div class="custom-modal-content">
            <h3>Add New Location</h3>

            <form method="POST" action="">
                <label>Location Name / Code</label>
                <input type="text" name="location_code" placeholder="E.g. Main Warehouse" required>

                <label>Description</label>
                <textarea name="description" rows="3" placeholder="E.g. Main storage facility in Colombo"></textarea>

                <div class="btn-group">
                    <button type="button" class="btn-cancel" onclick="closePopup()">Cancel</button>
                    <button type="submit" class="btn-save">Save Location</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Force Flex Display to Center the Modal
        function openPopup() {
            document.getElementById("locModal").style.display = "flex";
        }

        function closePopup() {
            document.getElementById("locModal").style.display = "none";
        }

        // Close on Outside Click
        window.onclick = function(event) {
            if (event.target == document.getElementById("locModal")) {
                closePopup();
            }
        }

        // Search Function
        const searchInput = document.getElementById("searchInput");
        const table = document.getElementById("locTable");

        searchInput.addEventListener("keyup", function() {
            const val = searchInput.value.toLowerCase();
            table.querySelectorAll("tbody tr").forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(val) ? "" : "none";
            });
        });
    </script>

</body>

</html>