<?php
$pass = 'registrar123'; 
$hashed = password_hash($pass, PASSWORD_DEFAULT);
echo $hashed;
?>
