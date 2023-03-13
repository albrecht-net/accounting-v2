<?php

session_start();
$_SESSION['id'] = rand(8,10);
$_SESSION['time'] = date("Y-m-d H:i:s");

?>