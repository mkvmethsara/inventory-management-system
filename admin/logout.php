<?php
session_start();
session_unset();
session_destroy();

// Go UP one level (..) to find the Login Page
header("Location: ../index.php");
exit();
