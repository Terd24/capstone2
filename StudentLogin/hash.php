<?php
$pass = 'studentpass'; 
$hashed = password_hash($pass, PASSWORD_DEFAULT);
echo $hashed;
?>
