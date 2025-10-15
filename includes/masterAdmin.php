<?php 
  include 'config.php';
  ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/header.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/menu.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/footer.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/order.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/login.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/user.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/listmenu.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/table.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/iconuser.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/menu.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/user.js"></script>
    <title>Master Layout</title>
</head>
<body>

<?php include __DIR__ . '/headerAdmin.php'; ?>

<main>
<?php echo $content; ?>
</main>

<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>