<?php 
$title = "Trang chủ";
include 'includes/header.php'; // Thêm header vào đầu trang
?>


   



<?php
$content = ob_get_clean();
include 'includes/master.php';
?>