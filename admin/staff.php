<?php
include '../config/db.php';

// --- 1. HANDLE ADD NEW STAFF ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $role     = mysqli_real_escape_string($conn, $_POST['role']);
    $raw_pass = $_POST['password'];

    // 🔒 Security: We hash the password. 
    // (If your login page uses plain text, you might need to change this, but hashing is best!)
    $hashed_pass = password_hash($raw_pass, PASSWORD_DEFAULT);

    // Check if username already exists
    $check = mysqli_query($conn, "SELECT user_id FROM users WHERE username='$username'");
    if (mysqli_num_rows($check) > 0) {
        echo "<script>alert('❌ Username already exists!');</script>";
    } else {
        // FIXED: Removed 'email' from the INSERT command
        $sql = "INSERT INTO users (username, password, role) 
                VALUES ('$username', '$hashed_pass', '$role')";

        if (mysqli_query($conn, $sql)) {
            echo "<script>alert('✅ Staff Member Added!'); window.location.href='staff.php';</script>";
        } else {
            echo "<script>alert('❌ Database Error: " . mysqli_error($conn) . "');</script>";
        }
    }
}

// --- 2. FETCH USERS ---
$sql = "SELECT * FROM users ORDER BY user_id ASC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>TrackFlow – Staff Management</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="admin-dash-page">

    <div class="header">TrackFlow – Staff Management</div>

    <div class="layout">
        <?php include 'menu.php'; ?>

        <div class="main">
            <div class="card">
                <div class="top-bar">
                    <div class="filters">
                        <input type="text" id="searchInput" placeholder="Search staff...">
                    </div>
                    <button class="add-btn" onclick="openStaffModal()">+ Add Staff</button>
                </div>

                <table id="staffTable">
                    <thead>
                        <tr>
                            <th>Staff ID</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                // Safety check for Role
                                $role_raw = isset($row['role']) ? $row['role'] : 'staff';
                                $role = ucfirst($role_raw);
                                $badge = ($role_raw === 'admin') ? 'role-admin' : 'role-staff';

                                // Auto-generate ID (STF-001)
                                $pretty_id = "STF-" . str_pad($row['user_id'], 3, '0', STR_PAD_LEFT);

                                echo "<tr>";
                                echo "<td style='opacity:0.6; font-family:monospace;'>$pretty_id</td>";
                                echo "<td style='font-weight:bold; color:#c9d6ff;'>" . $row['username'] . "</td>";
                                // FIXED: Removed Email Column Body
                                echo "<td><span class='badge-role $badge'>$role</span></td>";
                                echo "<td><span style='color:#22c55e; font-size:12px;'>● Active</span></td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='4' style='text-align:center; padding:30px; opacity:0.5;'>No staff members found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="STAFF_MODAL_OVERLAY">
        <div class="staff-modal-box">
            <h3 style="color:white; margin-top:0; margin-bottom:20px;">Add New Staff Member</h3>

            <form method="POST">
                <label>Username</label>
                <input type="text" name="username" placeholder="e.g. johndoe" required>

                <label>Password</label>
                <input type="password" name="password" placeholder="••••••••" required>

                <label>Role</label>
                <select name="role" required>
                    <option value="staff">Staff (Standard Access)</option>
                    <option value="admin">Admin (Full Control)</option>
                </select>

                <div class="staff-modal-footer">
                    <button type="button" class="staff-btn-cancel" onclick="closeStaffModal()">Cancel</button>
                    <button type="submit" class="staff-btn-save">Create User</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openStaffModal() {
            document.getElementById("STAFF_MODAL_OVERLAY").classList.add("staff-active");
        }

        function closeStaffModal() {
            document.getElementById("STAFF_MODAL_OVERLAY").classList.remove("staff-active");
        }
        window.onclick = function(e) {
            if (e.target == document.getElementById("STAFF_MODAL_OVERLAY")) closeStaffModal();
        }

        document.getElementById("searchInput").addEventListener("keyup", function() {
            let val = this.value.toLowerCase();
            document.querySelectorAll("#staffTable tbody tr").forEach(row => {
                row.style.display = row.innerText.toLowerCase().includes(val) ? "" : "none";
            });
        });
    </script>
</body>

</html>