<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../signup.php');
    exit;
}

$full_name = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$address = trim($_POST['address'] ?? '');
$agree_terms = isset($_POST['agree_terms']);

// Lưu dữ liệu cũ để hiển thị lại khi có lỗi
$_SESSION['old'] = [
    'full_name' => $full_name,
    'email' => $email,
    'phone' => $phone,
    'address' => $address
];

// Validate
$errors = [];

if (empty($full_name)) {
    $errors[] = 'Vui lòng nhập họ tên';
}

if (empty($email)) {
    $errors[] = 'Vui lòng nhập email';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Email không hợp lệ';
}

if (empty($password)) {
    $errors[] = 'Vui lòng nhập mật khẩu';
} elseif (strlen($password) < 6) {
    $errors[] = 'Mật khẩu phải có ít nhất 6 ký tự';
}

if ($password !== $confirm_password) {
    $errors[] = 'Mật khẩu xác nhận không khớp';
}

if (!$agree_terms) {
    $errors[] = 'Vui lòng đồng ý với điều khoản sử dụng';
}

// Kiểm tra email đã tồn tại
if (empty($errors)) {
    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        $errors[] = 'Email đã được sử dụng';
    }
}

// Nếu có lỗi
if (!empty($errors)) {
    $_SESSION['error'] = implode('<br>', $errors);
    header('Location: ../signup.php');
    exit;
}

// Thêm user mới
try {
    $db = getDB();
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $db->prepare("INSERT INTO users (email, password, full_name, phone, address, role) 
                          VALUES (?, ?, ?, ?, ?, 'customer')");
    $stmt->execute([$email, $hashed_password, $full_name, $phone, $address]);
    
    $_SESSION['success'] = 'Đăng ký thành công! Vui lòng đăng nhập.';
    unset($_SESSION['old']);
    header('Location: ../login.php');
    exit;
    
} catch (PDOException $e) {
    $_SESSION['error'] = 'Có lỗi xảy ra. Vui lòng thử lại sau.';
    header('Location: ../signup.php');
    exit;
}
?>