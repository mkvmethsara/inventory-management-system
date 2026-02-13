<?php
$db_server = "localhost";
$db_user   = "root";
$db_pass   = "";
$db_name   = "warehouse_db";
$conn      = "";


try {
    $conn = mysqli_connect(
        $db_server,
        $db_user,
        $db_pass,
        $db_name
    );
} catch (mysqli_sql_exception) {

    echo "System Error: We are having trouble reaching the database. Please contact the administrator.";
}
