<?php
session_start();
session_unset();
session_destroy();

// 🚫 Prevent browser from going back
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Redirect to login
header("Location: ../StudentLogin/login.php");
exit;
?>
