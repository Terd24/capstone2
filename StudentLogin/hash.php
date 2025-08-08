<?php
$pass = 'parentpass'; 
$hashed = password_hash($pass, PASSWORD_DEFAULT);
echo $hashed;
?>
