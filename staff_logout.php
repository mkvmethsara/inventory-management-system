<?php
session_start();
session_unset();
session_destroy();

// Redirect to the STAFF Login page, not Admin
header("Location: user-login.php");
exit();
