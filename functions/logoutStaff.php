<?php
session_start();
session_destroy();
header("Location: ../STAFF/index.php");
exit();
?>