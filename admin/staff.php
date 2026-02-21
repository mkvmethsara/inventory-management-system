<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// SECURITY GATE 🔒
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

include '../config/db.php';

// --- 1. HANDLE SAVE (ADD / EDIT / RESET PASSWORD) ---
if (isset($_POST['save_user_btn'])) {
    $id   = $_POST['user_id'];
    $name = mysqli_real_escape_string($conn, $_POST['username']);
    $role = $_POST['role']; // Now sends "Admin" or "Staff"
    $pass = $_POST['password'];

    // CHECK: Username conflict (Only for new users)
    if (empty($id)) {
        $check = mysqli_query($conn, "SELECT * FROM users WHERE username='$name'");
        if (mysqli_num_rows($check) > 0) {
            echo "<script>alert('❌ Username already exists!'); window.location.href='staff.php';</script>";
            exit();
        }
    }

    if (!empty($id)) {
        // --- UPDATE EXISTING USER ---
        if (!empty($pass)) {
            // Update Name, Role, AND Password
            $hashed = password_hash($pass, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET username='$name', role='$role', password='$hashed' WHERE user_id='$id'";
            $msg = "✅ Password & Details Updated!";
        } else {
            // Update ONLY Name and Role
            $sql = "UPDATE users SET username='$name', role='$role' WHERE user_id='$id'";
            $msg = "✅ Details Updated!";
        }
    } else {
        // --- CREATE NEW USER ---
        $hashed = password_hash($pass, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, password, role) VALUES ('$name', '$hashed', '$role')";
        $msg = "✅ New Staff Created!";
    }

    if (mysqli_query($conn, $sql)) {
        header("Location: staff.php?msg=" . urlencode($msg));
        exit();
    } else {
        echo "<script>alert('Error: " . mysqli_error($conn) . "');</script>";
    }
}

// --- 2. HANDLE DELETE ---
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    if ($id == $_SESSION['user_id']) {
        echo "<script>alert('⚠️ You cannot delete yourself!'); window.location.href='staff.php';</script>";
    } else {
        try {
            if (mysqli_query($conn, "DELETE FROM users WHERE user_id = '$id'")) {
                header("Location: staff.php?msg=User Deleted");
                exit();
            }
        } catch (Exception $e) {
            // This catches the error if the user has Transaction Logs
            echo "<script>alert('⚠️ CANNOT DELETE: This user has active Transaction Logs. You must delete their logs first.'); window.location.href='staff.php';</script>";
        }
    }
}

// --- 3. FETCH USERS ---
$result = mysqli_query($conn, "SELECT * FROM users ORDER BY user_id DESC");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>TrackFlow – Staff Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=35">

    <style>
        /* FIX FOR MESSY UI BUTTONS */
        .action-group {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            align-items: center;
        }

        .btn-icon {
            border: none;
            background: #f3f4f6;
            width: 32px;
            height: 32px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: 0.2s;
            text-decoration: none;
        }

        .btn-icon:hover { transform: translateY(-2px); box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); }
        .icon-edit { color: #4b5563; }
        .icon-edit:hover { background: #e5e7eb; color: #111827; }
        .icon-key { color: #f59e0b; }
        .icon-key:hover { background: #fef3c7; color: #d97706; }
        .icon-trash { color: #ef4444; }
        .icon-trash:hover { background: #fee2e2; color: #b91c1c; }

        /* FIX FOR INVISIBLE TEXT IN MODAL */
        .modal-box input, 
        .modal-box select {
            color: #111827 !important; /* Forces dark text */
            background-color: #ffffff !important; /* Ensures background is white */
        }
    </style>
</head>

<body class="trackflow-body">

    <aside class="tf-sidebar">
        <div class="tf-logo">
            <i class="bi bi-box-seam-fill"></i> TRACKFLOW
        </div>
        <nav class="tf-nav">
            <a href="dashboard.php"><i class="bi bi-grid-fill"></i> Dashboard</a>
            <a href="items-inventory.php"><i class="bi bi-box"></i> Items Inventory</a>
            <a href="batch-expiry.php"><i class="bi bi-clock-history"></i> Batch & Expiry</a>
            <a href="stock-location.php"><i class="bi bi-shop"></i> Stock by Location</a>
            <a href="locations.php"><i class="bi bi-geo-alt"></i> Locations</a>
            <a href="suppliers.php"><i class="bi bi-truck"></i> Suppliers</a>
            <a href="staff.php" class="active"><i class="bi bi-people"></i> Staff Management</a>
            <a href="transactions.php"><i class="bi bi-file-text"></i> Transaction Logs</a>
            <a href="logout.php" class="tf-logout"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </nav>
    </aside>

    <main class="tf-main">
        <div class="tf-page-header">
            <div class="tf-page-title">
                <h2>Staff Management</h2>
                <p>Manage system users and access roles</p>
            </div>
            <button onclick="openModal()" class="tf-btn-primary" style="background:#111827;">
                <i class="bi bi-person-plus-fill"></i> Add New Staff
            </button>
        </div>

        <div class="tf-table-container">
            <div style="padding: 20px; border-bottom: 1px solid #f3f4f6;">
                <input type="text" id="searchInput" placeholder="Search staff..."
                    style="padding: 10px 15px; width: 300px; border: 1px solid #e5e7eb; border-radius: 8px; background:#f9fafb; outline:none;">
            </div>

            <table class="tf-table" id="userTable">
                <thead>
                    <tr>
                        <th style="padding-left:30px;">Username</th>
                        <th>Role</th>
                        <th>Created At</th>
                        <th>Status</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            $id = $row['user_id'];
                            $name = htmlspecialchars($row['username']);
                            $raw_role = $row['role']; 

                            $role_display = ucfirst($raw_role);
                            $role_class = ($raw_role === 'Admin') ? 'badge-role-admin' : 'badge-role-staff';

                            $avatar_letter = strtoupper(substr($name, 0, 1));
                            $date = "Feb 17, 2026"; // You might want to pull this from DB later if you add a created_at column

                            echo "<tr>";
                            echo "<td style='padding-left:30px;'>
                                    <div class='user-profile'>
                                        <div class='user-avatar'>$avatar_letter</div>
                                        <span class='user-name'>$name</span>
                                    </div>
                                  </td>";
                            echo "<td><span class='$role_class'>$role_display</span></td>";
                            echo "<td style='color:#6b7280; font-size:14px;'>$date</td>";
                            echo "<td><span class='status-active'>Active</span></td>";
                            echo "<td style='text-align:right;'>
                                    <div class='action-group'>
                                        <button type='button' class='btn-icon icon-edit' onclick='editUser(\"$id\", \"$name\", \"$raw_role\")' title='Edit Details'>
                                            <i class='bi bi-pencil-square'></i>
                                        </button>
                                        <button type='button' class='btn-icon icon-key' onclick='resetPass(\"$id\", \"$name\", \"$raw_role\")' title='Reset Password'>
                                            <i class='bi bi-key'></i>
                                        </button>
                                        <a href='staff.php?delete_id=$id' class='btn-icon icon-trash' onclick=\"return confirm('Delete this user?');\" title='Delete'>
                                            <i class='bi bi-trash'></i>
                                        </a>
                                    </div>
                                  </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' style='text-align:center; padding:50px; color:#9ca3af;'>No staff found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </main>

    <div id="USER_MODAL" class="modal-overlay">
        <div class="modal-box">
            <h3 id="modalTitle" style="margin-top:0; margin-bottom:20px;">Add New Staff</h3>

            <form method="POST">
                <input type="hidden" name="user_id" id="user_id_input">

                <label style="font-size:13px; font-weight:600; color:#6b7280;">Username</label>
                <input type="text" name="username" id="username_input" placeholder="e.g. john.doe" required>

                <label style="font-size:13px; font-weight:600; color:#6b7280;">Role</label>
                <select name="role" id="role_input">
                    <option value="Staff">Warehouse Staff</option>
                    <option value="Admin">Admin</option>
                </select>

                <label style="font-size:13px; font-weight:600; color:#6b7280;" id="passLabel">Password</label>
                <input type="text" name="password" id="password_input" placeholder="Enter password" required>

                <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                    <button type="button" onclick="closeModal()" class="btn-cancel" style="background:transparent; border:1px solid #e5e7eb; color:#374151;">Cancel</button>
                    <button type="submit" name="save_user_btn" id="saveBtn" class="tf-btn-primary">Save Staff</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById("USER_MODAL");
        const title = document.getElementById("modalTitle");
        const saveBtn = document.getElementById("saveBtn");
        const idInput = document.getElementById("user_id_input");
        const nameInput = document.getElementById("username_input");
        const roleInput = document.getElementById("role_input");
        const passInput = document.getElementById("password_input");
        const passLabel = document.getElementById("passLabel");

        function openModal() {
            title.innerText = "Add New Staff";
            saveBtn.innerText = "Create Staff";
            idInput.value = "";
            nameInput.value = "";
            passInput.value = "";
            passInput.placeholder = "Enter password";
            passInput.required = true;
            modal.style.display = "flex";
        }

        function editUser(id, name, role) {
            title.innerText = "Edit Details";
            saveBtn.innerText = "Update Details";
            idInput.value = id;
            nameInput.value = name;
            roleInput.value = role;

            passLabel.innerText = "Change Password (Optional)";
            passInput.placeholder = "Leave blank to keep current";
            passInput.required = false;
            passInput.value = "";

            modal.style.display = "flex";
        }

        function resetPass(id, name, role) {
            title.innerText = "Reset Password for " + name;
            saveBtn.innerText = "Save New Password";
            idInput.value = id;
            nameInput.value = name;
            roleInput.value = role;

            passLabel.innerText = "New Password";
            passInput.placeholder = "Enter new password";
            passInput.required = true;
            passInput.value = "";
            passInput.focus();

            modal.style.display = "flex";
        }

        function closeModal() {
            modal.style.display = "none";
        }
        window.onclick = function(e) {
            if (e.target == modal) closeModal();
        }

        document.getElementById("searchInput").addEventListener("keyup", function() {
            let val = this.value.toLowerCase();
            document.querySelectorAll("#userTable tbody tr").forEach(row => {
                row.style.display = row.innerText.toLowerCase().includes(val) ? "" : "none";
            });
        });
    </script>
</body>

</html>