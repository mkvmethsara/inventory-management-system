<?php
session_start();
include '../config/db.php';

// --- 1. HANDLE UPDATE STAFF (The 'U' in CRUD) ---
if (isset($_POST['update_btn'])) {
    $id     = $_POST['user_id'];
    $u_name = $_POST['username'];
    $u_role = $_POST['role'];
    $u_pass = $_POST['password']; // This might be empty

    // LOGIC: Did they type a new password?
    if (!empty($u_pass)) {
        // Yes -> Encrypt it and update everything
        $hashed = password_hash($u_pass, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET username='$u_name', role='$u_role', password='$hashed' WHERE user_id='$id'";
    } else {
        // No -> Keep old password, update only Name & Role
        $sql = "UPDATE users SET username='$u_name', role='$u_role' WHERE user_id='$id'";
    }

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('✅ User Updated Successfully!'); window.location.href='staff.php';</script>";
    } else {
        echo "<script>alert('❌ Error: " . mysqli_error($conn) . "');</script>";
    }
}

// --- 2. HANDLE DELETE STAFF ---
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];

    // Prevent deleting yourself
    if ($id == $_SESSION['user_id']) {
        echo "<script>alert('⚠️ SECURITY WARNING: You cannot delete your own account while logged in!'); window.location.href='staff.php';</script>";
    } else {
        mysqli_query($conn, "DELETE FROM users WHERE user_id = '$id'");
        echo "<script>alert('✅ User Removed!'); window.location.href='staff.php';</script>";
    }
}

// --- 3. HANDLE ADD STAFF ---
if (isset($_POST['add_btn'])) {
    $u_name = $_POST['username'];
    $u_pass = $_POST['password'];
    $u_role = $_POST['role'];

    // Always encrypt new passwords
    $hashed = password_hash($u_pass, PASSWORD_DEFAULT);

    // Check if username taken
    $check = mysqli_query($conn, "SELECT * FROM users WHERE username='$u_name'");
    if (mysqli_num_rows($check) > 0) {
        echo "<script>alert('❌ Username already exists!');</script>";
    } else {
        $sql = "INSERT INTO users (username, password, role) VALUES ('$u_name', '$hashed', '$u_role')";
        if (mysqli_query($conn, $sql)) {
            echo "<script>alert('✅ New Staff Added!'); window.location.href='staff.php';</script>";
        }
    }
}

// --- 4. PREPARE EDIT DATA ---
$edit_mode = false;
$edit_data = ['username' => '', 'role' => 'staff', 'user_id' => ''];

if (isset($_GET['edit_id'])) {
    $edit_mode = true;
    $id = $_GET['edit_id'];
    $res = mysqli_query($conn, "SELECT * FROM users WHERE user_id='$id'");
    $edit_data = mysqli_fetch_assoc($res);
}

// --- 5. FETCH LIST ---
$result = mysqli_query($conn, "SELECT * FROM users");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>TrackFlow – Staff Management</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* --- UI STYLES --- */
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

        /* Badge & Table Buttons */
        .role-badge {
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge-admin {
            background: rgba(34, 197, 94, 0.2);
            color: #22c55e;
            border: 1px solid #22c55e;
        }

        .badge-staff {
            background: rgba(56, 189, 248, 0.2);
            color: #38bdf8;
            border: 1px solid #38bdf8;
        }

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

    <div class="header">TrackFlow – Staff & Access Control</div>

    <div class="layout">
        <?php include 'menu.php'; ?>

        <div class="main">

            <div class="form-box-fixed" style="<?php echo $edit_mode ? 'border-color:#fbbf24;' : ''; ?>">

                <h4 style="margin:0; color:<?php echo $edit_mode ? '#fbbf24' : '#c9d6ff'; ?>; width:100px; flex-shrink:0;">
                    <?php echo $edit_mode ? '✏️ Edit:' : '+ New User:'; ?>
                </h4>

                <form method="POST" style="display:flex; gap:10px; width:100%; align-items:center;">
                    <input type="hidden" name="user_id" value="<?php echo $edit_data['user_id']; ?>">

                    <input type="text" name="username" class="input-fixed" placeholder="Username" required
                        value="<?php echo $edit_data['username']; ?>">

                    <input type="text" name="password" class="input-fixed"
                        placeholder="<?php echo $edit_mode ? '(Leave blank to keep current)' : 'Password'; ?>"
                        <?php echo $edit_mode ? '' : 'required'; ?>>

                    <select name="role" class="input-fixed" style="cursor:pointer;">
                        <option value="staff" <?php if ($edit_data['role'] == 'staff') echo 'selected'; ?>>Staff</option>
                        <option value="admin" <?php if ($edit_data['role'] == 'admin') echo 'selected'; ?>>Admin</option>
                    </select>

                    <?php if ($edit_mode): ?>
                        <button type="submit" name="update_btn" class="btn-fixed" style="background:#fbbf24; color:black;">Update User</button>
                        <a href="staff.php" class="btn-fixed" style="background:#475569; text-decoration:none; display:inline-block; text-align:center;">Cancel</a>
                    <?php else: ?>
                        <button type="submit" name="add_btn" class="btn-fixed" style="background:#4f46e5;">Create User</button>
                    <?php endif; ?>
                </form>
            </div>

            <div class="card">
                <h3 style="margin-top:0;">Authorized Users</h3>
                <table id="staffTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Password Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                $role_badge = ($row['role'] == 'admin') ? 'badge-admin' : 'badge-staff';

                                echo "<tr>";
                                echo "<td style='opacity:0.5;'>#" . $row['user_id'] . "</td>";
                                echo "<td style='font-weight:bold; color:#c9d6ff;'>" . $row['username'] . "</td>";
                                echo "<td><span class='role-badge $role_badge'>" . strtoupper($row['role']) . "</span></td>";
                                echo "<td style='color:#94a3b8; font-size:11px; font-family:monospace;'>🔒 Encrypted</td>";

                                echo "<td>";
                                // Don't allow editing/deleting yourself to prevent lockouts
                                if ($row['user_id'] == $_SESSION['user_id']) {
                                    echo "<span style='color:#64748b; font-size:12px;'>(Current User)</span>";
                                } else {
                                    echo "<a href='staff.php?edit_id=" . $row['user_id'] . "' class='btn-edit'>✏️ Edit</a>";
                                    echo "<a href='staff.php?delete_id=" . $row['user_id'] . "' class='btn-delete' onclick=\"return confirm('⚠️ Are you sure?');\">🗑 Delete</a>";
                                }
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' style='text-align:center;'>No users found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>

</html>