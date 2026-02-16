<?php
include '../config/db.php';

// --- 1. HANDLE UPDATE LOCATION ---
if (isset($_POST['update_btn'])) {
    $id   = $_POST['location_id'];
    $code = $_POST['location_code'];
    $desc = $_POST['description'];

    $sql = "UPDATE locations SET location_code='$code', description='$desc' WHERE location_id='$id'";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('✅ Location Updated!'); window.location.href='locations.php';</script>";
    } else {
        echo "<script>alert('❌ Error: " . mysqli_error($conn) . "');</script>";
    }
}

// --- 2. HANDLE DELETE LOCATION ---
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    try {
        if (mysqli_query($conn, "DELETE FROM locations WHERE location_id = '$id'")) {
            echo "<script>alert('✅ Location Deleted!'); window.location.href='locations.php';</script>";
        }
    } catch (Exception $e) {
        // This proves you understand Data Integrity!
        echo "<script>alert('⚠️ SECURITY ALERT: Cannot delete this Location! \\n\\nThere is currently stock stored here. Please move the stock first.'); window.location.href='locations.php';</script>";
    }
}

// --- 3. HANDLE ADD LOCATION ---
if (isset($_POST['add_btn'])) {
    $code = $_POST['location_code'];
    $desc = $_POST['description'];

    $sql = "INSERT INTO locations (location_code, description) VALUES ('$code', '$desc')";
    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('✅ New Location Added!'); window.location.href='locations.php';</script>";
    }
}

// --- 4. PREPARE EDIT DATA ---
$edit_mode = false;
$edit_data = ['location_code' => '', 'description' => '', 'location_id' => ''];

if (isset($_GET['edit_id'])) {
    $edit_mode = true;
    $id = $_GET['edit_id'];
    $res = mysqli_query($conn, "SELECT * FROM locations WHERE location_id='$id'");
    $edit_data = mysqli_fetch_assoc($res);
}

// --- 5. FETCH LIST ---
// We also count how many stock batches are currently in this location
$sql = "SELECT l.*, (SELECT COUNT(*) FROM stock s WHERE s.location_id = l.location_id) as stock_count FROM locations l";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>TrackFlow – Locations</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* --- UI STYLES (Matches Suppliers Page) --- */
        .form-box-fixed {
            background: #1e293b;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #334155;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .input-fixed {
            background-color: #ffffff !important;
            color: black !important;
            padding: 10px 15px;
            border-radius: 5px;
            font-size: 14px;
            flex: 1;
            border: 1px solid #cbd5e1;
        }

        .btn-fixed {
            padding: 10px 25px;
            border-radius: 5px;
            border: none;
            font-weight: bold;
            cursor: pointer;
            color: white;
            white-space: nowrap;
            width: auto !important;
        }

        /* Table Buttons */
        .btn-edit {
            background: rgba(251, 191, 36, 0.2);
            color: #fbbf24;
            border: 1px solid #fbbf24;
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 12px;
            font-weight: bold;
            margin-right: 5px;
        }

        .btn-delete {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: 1px solid #ef4444;
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 12px;
            font-weight: bold;
        }
    </style>
</head>

<body class="admin-dash-page">

    <div class="header">TrackFlow – Warehouse Locations</div>

    <div class="layout">
        <?php include 'menu.php'; ?>

        <div class="main">

            <div class="form-box-fixed" style="<?php echo $edit_mode ? 'border-color:#fbbf24;' : ''; ?>">

                <h4 style="margin:0; color:<?php echo $edit_mode ? '#fbbf24' : '#c9d6ff'; ?>; width:100px; flex-shrink:0;">
                    <?php echo $edit_mode ? '✏️ Edit:' : '+ Add New:'; ?>
                </h4>

                <form method="POST" style="display:flex; gap:10px; width:100%; align-items:center;">
                    <input type="hidden" name="location_id" value="<?php echo $edit_data['location_id']; ?>">

                    <input type="text" name="location_code" class="input-fixed" placeholder="Location Code (e.g. Aisle-01)" required
                        value="<?php echo $edit_data['location_code']; ?>">

                    <input type="text" name="description" class="input-fixed" placeholder="Description (e.g. Main Rack for Snacks)" required
                        value="<?php echo $edit_data['description']; ?>" style="flex:2;">

                    <?php if ($edit_mode): ?>
                        <button type="submit" name="update_btn" class="btn-fixed" style="background:#fbbf24; color:black;">Update Location</button>
                        <a href="locations.php" class="btn-fixed" style="background:#475569; text-decoration:none; display:inline-block; text-align:center;">Cancel</a>
                    <?php else: ?>
                        <button type="submit" name="add_btn" class="btn-fixed" style="background:#4f46e5;">Save Location</button>
                    <?php endif; ?>
                </form>
            </div>

            <div class="card">
                <h3 style="margin-top:0;">Storage Locations</h3>
                <table id="locTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Location Code</th>
                            <th>Description</th>
                            <th>Occupied By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                echo "<tr>";
                                echo "<td style='opacity:0.5;'>#" . $row['location_id'] . "</td>";
                                echo "<td style='font-weight:bold; color:#c9d6ff; font-family:monospace;'>" . $row['location_code'] . "</td>";
                                echo "<td>" . $row['description'] . "</td>";

                                // Show count of stock batches here
                                $badge_color = ($row['stock_count'] > 0) ? '#22c55e' : '#64748b';
                                $bg_color = ($row['stock_count'] > 0) ? 'rgba(34,197,94,0.2)' : 'rgba(100,116,139,0.2)';
                                echo "<td><span style='background:$bg_color; color:$badge_color; padding:3px 8px; border-radius:4px; font-size:11px; font-weight:bold;'>" . $row['stock_count'] . " Batches</span></td>";

                                echo "<td>";
                                echo "<a href='locations.php?edit_id=" . $row['location_id'] . "' class='btn-edit'>✏️ Edit</a>";
                                echo "<a href='locations.php?delete_id=" . $row['location_id'] . "' class='btn-delete' onclick=\"return confirm('⚠️ Are you sure?');\">🗑 Delete</a>";
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' style='text-align:center;'>No locations found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>

</html>