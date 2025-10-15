<?php
// filepath: c:\Users\Diego\Desktop\HOTEL3\logout.php
session_start();
session_destroy();
header("Location: login.php");
exit;
?>