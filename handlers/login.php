<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php');
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$remember = isset($_POST['remember']);

$_SESSION['old'] = ['email' => $email];

// Validate
if (empty($email) || empty($password)) {
    $_SESSION['error'] = 'Vui lòng nhập đầy đủ thông tin';
    header('Location: ../login.php');
    exit;
}

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($password, $user['password'])) {
        $_SESSION['error'] = 'Email hoặc mật khẩu không đúng';
        header('Location: ../login.php');
        exit;
    }
    
    // Đăng nhập thành công
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['user_role'] = $user['role'];
    
    unset($_SESSION['old'], $_SESSION['error']);
    
    // Chuyển về trang trước đó hoặc trang chủ
    $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
    unset($_SESSION['redirect_after_login']);
    
    header('Location: ../' . $redirect);
    exit;
    
} catch (PDOException $e) {
    $_SESSION['error'] = 'Có lỗi xảy ra. Vui lòng thử lại sau.';
    header('Location: ../login.php');
    exit;
}
?>