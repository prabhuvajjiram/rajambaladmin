<?php
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'rajambal_admin');
define('DB_PASSWORD', 's3DqGWV22-A%');
//define('DB_NAME', 'rajambal_Rajambal'); - Tthis is for prod
define('DB_NAME', 'rajambal_rajambal');
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

if($conn === false){
    die("ERROR: Could not connect. " . mysqli_connect_error());
}
?>