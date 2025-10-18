<?php
session_start();
session_destroy();
header("Location: ../ADMIN/index.php");
exit();
?>