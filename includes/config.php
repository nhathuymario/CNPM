<?php
    //đường dẫn thư mục
    $document_root = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));
    $script_dir = str_replace('\\', '/', realpath(dirname(__FILE__)));
    $base_folder = str_replace($document_root, '', $script_dir . "/.."); 
    $base_url = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $base_folder . "/";
    define('BASE_URL', $base_url);
?>
