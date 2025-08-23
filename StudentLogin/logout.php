<?php
session_start();
session_unset();
session_destroy();

// Prevent caching of the last page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Redirect to login
header("Location: login.php");
exit;
?>