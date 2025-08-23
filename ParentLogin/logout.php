<?php
session_start();

// Destroy all session data
$_SESSION = [];
session_unset();
session_destroy();

// Redirect to parent login page
header("Location: ParentLogin.html");
exit();
?>
