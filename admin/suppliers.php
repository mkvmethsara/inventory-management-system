<?php
include '../config/db.php';

// --- 1. HANDLE ACTIONS ---
if (isset($_POST['update_btn'])) {
    $id = $_POST['supplier_id'];
    $name = $_POST['supplier_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    mysqli_query($conn, "UPDATE suppliers SET supplier_name='$name', email='$email', phone='$phone' WHERE supplier_id='$id'");
    echo "<script>window.location.href='suppliers.php';</script>";
}
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    try {
        if (mysqli_query($conn, "DELETE FROM suppliers WHERE supplier_id = '$id'")) echo "<script>window.location.href='suppliers.php';</script>";
    } catch (Exception $e) {
        echo "<script>alert('⚠️ SECURITY ALERT: Cannot delete this supplier because they have items in the inventory.'); window.location.href='suppliers.php';</script>";
    }
}
if (isset($_POST['add_btn'])) {
    $name = $_POST['supplier_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    mysqli_query($conn, "INSERT INTO suppliers (supplier_name, email, phone) VALUES ('$name', '$email', '$phone')");
    echo "<script>window.location.href='suppliers.php';</script>";
}

// --- 2. PREPARE EDIT DATA ---
$edit_mode = false;
$edit_data = ['supplier_name' => '', 'email' => '', 'phone' => '', 'supplier_id' => ''];
if (isset($_GET['edit_id'])) {
    $edit_mode = true;
    $id = $_GET['edit_id'];
    $res = mysqli_query($conn, "SELECT * FROM suppliers WHERE supplier_id='$id'");
    $edit_data = mysqli_fetch_assoc($res);
}

// --- 3. FETCH LIST ---
$sql = "SELECT s.*, (SELECT COUNT(*) FROM items i WHERE i.supplier_id = s.supplier_id) as item_count FROM suppliers s";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>TrackFlow – Suppliers</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* --- FORCEFUL UI FIXES --- */

        /* 1. Container: Row Layout */
        .form-box-fixed {
            background: #1e293b;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #334155;
            display: flex;
            align-items: center;
            gap: 15px;
            /* Space between items */
            width: 100%;
            box-sizing: border-box;
        }

        /* 2. Inputs: WHITE Background (High Visibility) & Grow to fill space */
        .input-fixed {
            background-color: #ffffff !important;
            /* Force White */
            color: #000000 !important;
            /* Force Black Text */
            border: 1px solid #cbd5e1;
            padding: 10px 15px;
            border-radius: 5px;
            font-size: 14px;
            flex: 1;
            /* This makes inputs grow */
            min-width: 150px;
            /* Prevents them from disappearing */
            outline: none;
        }

        .input-fixed::placeholder {
            color: #64748b;
        }

        /* 3. Button: STOP STRETCHING */
        .btn-fixed {
            background-color: #4f46e5;
            color: white;
            padding: 10px 25px;
            border-radius: 5px;
            border: none;
            font-weight: bold;
            cursor: pointer;
            white-space: nowrap;
            /* Keep text on one line */
            flex-shrink: 0;
            /* Prevent button from shrinking */
            width: auto !important;
            /* Stop it from being full width */
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

    <div class="header">TrackFlow – Suppliers Management</div>

    <div class="layout">
        <?php include 'menu.php'; ?>

        <div class="main">

            <div class="form-box-fixed" style="<?php echo $edit_mode ? 'border-color:#fbbf24;' : ''; ?>">

                <h4 style="margin:0; color:<?php echo $edit_mode ? '#fbbf24' : '#c9d6ff'; ?>; width:100px; flex-shrink:0;">
                    <?php echo $edit_mode ? '✏️ Edit:' : '+ Add New:'; ?>
                </h4>

                <form method="POST" style="display:flex; gap:10px; width:100%; align-items:center;">
                    <input type="hidden" name="supplier_id" value="<?php echo $edit_data['supplier_id']; ?>">

                    <input type="text" name="supplier_name" class="input-fixed" placeholder="Company Name"
                        value="<?php echo $edit_data['supplier_name']; ?>" required>

                    <input type="email" name="email" class="input-fixed" placeholder="Email Address"
                        value="<?php echo $edit_data['email']; ?>" required>

                    <input type="text" name="phone" class="input-fixed" placeholder="Phone Number"
                        value="<?php echo $edit_data['phone']; ?>" required>

                    <?php if ($edit_mode): ?>
                        <button type="submit" name="update_btn" class="btn-fixed" style="background:#fbbf24; color:black;">Update Supplier</button>
                        <a href="suppliers.php" class="btn-fixed" style="background:#475569; text-decoration:none; display:inline-block; text-align:center;">Cancel</a>
                    <?php else: ?>
                        <button type="submit" name="add_btn" class="btn-fixed">Save Supplier</button>
                    <?php endif; ?>
                </form>
            </div>

            <div class="card">
                <h3 style="margin-top:0;">Supplier List</h3>
                <table id="supTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Company</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Active Items</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                echo "<tr>";
                                echo "<td style='opacity:0.5;'>#" . $row['supplier_id'] . "</td>";
                                echo "<td style='font-weight:bold; color:#c9d6ff;'>" . $row['supplier_name'] . "</td>";
                                echo "<td>" . $row['email'] . "</td>";
                                echo "<td>" . $row['phone'] . "</td>";
                                echo "<td><span style='background:rgba(34,197,94,0.2); color:#22c55e; padding:3px 8px; border-radius:4px; font-size:11px;'>" . $row['item_count'] . " Items</span></td>";
                                echo "<td>";
                                echo "<a href='suppliers.php?edit_id=" . $row['supplier_id'] . "' class='btn-edit'>✏️ Edit</a>";
                                echo "<a href='suppliers.php?delete_id=" . $row['supplier_id'] . "' class='btn-delete' onclick=\"return confirm('⚠️ Are you sure?');\">🗑 Delete</a>";
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' style='text-align:center;'>No suppliers found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>

</html>