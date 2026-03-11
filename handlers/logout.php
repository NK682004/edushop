<?php
session_start();

// Xóa tất cả session
session_unset();
session_destroy();

// Chuyển về trang chủ
header('Location: ../index.php');
exit;
?>