<?php
session_start();

// 1. Clear all session variables
session_unset();

// 2. Destroy the session completely
session_destroy();

// 3. Redirect to the root index.php
// The "../" tells the server to look in the parent (root) folder
header("Location: ../index.php");
exit();
