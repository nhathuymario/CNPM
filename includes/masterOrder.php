<?php 
  include 'config.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Styles -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/header.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/menu.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/footer.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/order.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/login.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/user.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/payment.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
      // Dùng URL tuyệt đối cho các request JS (tránh lỗi đường dẫn tương đối)
      window.CNPM_BASE_URL = "<?php echo BASE_URL; ?>";
    </script>

    <script src="<?php echo BASE_URL; ?>assets/js/iconuser.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/menu.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/user.js"></script>

    <!-- Scripts UI -->
    <script defer src="<?php echo BASE_URL; ?>assets/js/order.js"></script>
    <script defer src="<?php echo BASE_URL; ?>assets/js/call-staff.js"></script>

    <title>Order</title>
</head>
<body>
<?php include __DIR__ . '/headerOrder.php'; ?>
<main><?php echo $content; ?></main> 
<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>