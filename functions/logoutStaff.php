<?php
session_start();
session_destroy();
header("Location: ../staff/index.php");
exit();
?>