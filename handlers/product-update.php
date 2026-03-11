<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Check admin
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'Vui lòng đăng nhập!';
    header('Location: ../login.php');
    exit;
}

$db = getDB();
$stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['role'] !== 'admin') {
    $_SESSION['error'] = 'Bạn không có quyền thực hiện thao tác này!';
    header('Location: ../../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../products.php');
    exit;
}

try {
    $id = intval($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        throw new Exception('ID không hợp lệ!');
    }
    
    // Get current product
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $current_product = $stmt->fetch();
    
    if (!$current_product) {
        throw new Exception('Sản phẩm không tồn tại!');
    }
    
    // Validate input
    $name = trim($_POST['name'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $sale_price = !empty($_POST['sale_price']) ? floatval($_POST['sale_price']) : null;
    $quantity = intval($_POST['quantity'] ?? 0);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $image_url = trim($_POST['image_url'] ?? '');
    
    // Validation
    if (empty($name)) {
        throw new Exception('Tên sản phẩm không được để trống!');
    }
    
    if ($category_id <= 0) {
        throw new Exception('Vui lòng chọn danh mục!');
    }
    
    if ($price <= 0) {
        throw new Exception('Giá sản phẩm phải lớn hơn 0!');
    }
    
    if ($sale_price !== null && $sale_price >= $price) {
        throw new Exception('Giá khuyến mãi phải nhỏ hơn giá gốc!');
    }
    
    if ($quantity < 0) {
        throw new Exception('Số lượng không được âm!');
    }
    
    // Generate slug (cần thiết để đặt tên file ảnh)
    $slug = strtolower($name);
    $slug = preg_replace('/[àáạảãâầấậẩẫăằắặẳẵ]/u', 'a', $slug);
    $slug = preg_replace('/[èéẹẻẽêềếệểễ]/u', 'e', $slug);
    $slug = preg_replace('/[ìíịỉĩ]/u', 'i', $slug);
    $slug = preg_replace('/[òóọỏõôồốộổỗơờớợởỡ]/u', 'o', $slug);
    $slug = preg_replace('/[ùúụủũưừứựửữ]/u', 'u', $slug);
    $slug = preg_replace('/[ỳýỵỷỹ]/u', 'y', $slug);
    $slug = preg_replace('/đ/u', 'd', $slug);
    $slug = preg_replace('/[^a-z0-9\s-]/u', '', $slug);
    $slug = preg_replace('/\s+/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    
    // Handle image upload
    $image_path = $current_product['image'];
    
    if (!empty($_FILES['image']['name'])) {
        // FIX: Đường dẫn đúng từ handlers/ lên EDUSHOP/
        $upload_dir = __DIR__ . '/../assets/images/';
        
        // Tạo thư mục nếu chưa có
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Kiểm tra quyền ghi
        if (!is_writable($upload_dir)) {
            throw new Exception('Thư mục upload không có quyền ghi!');
        }
        
        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (!in_array($file_extension, $allowed_extensions)) {
            throw new Exception('Chỉ chấp nhận file ảnh: JPG, PNG, GIF, WEBP');
        }
        
        if ($_FILES['image']['size'] > 2 * 1024 * 1024) {
            throw new Exception('Kích thước file không được vượt quá 2MB');
        }
        
        // Kiểm tra lỗi upload
        if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Lỗi upload file: ' . $_FILES['image']['error']);
        }
        
        // Sử dụng slug làm tên file
        $new_filename = $slug . '_' . time() . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
            // Delete old image
            if ($current_product['image']) {
                $old_image_path = __DIR__ . '/../' . $current_product['image'];
                if (file_exists($old_image_path)) {
                    @unlink($old_image_path);
                    error_log("🗑️ Old image deleted: " . $old_image_path);
                }
            }
            
            $image_path = 'assets/images/' . $new_filename;
            
            // Debug log (xóa sau khi test)
            error_log("✅ Image uploaded successfully: " . $upload_path);
        } else {
            throw new Exception('Không thể upload file. Kiểm tra quyền thư mục!');
        }
    } elseif (!empty($image_url)) {
        $image_path = $image_url;
    }
    
    // Update product
    $stmt = $db->prepare("UPDATE products SET 
                         category_id = ?,
                         name = ?,
                         description = ?,
                         price = ?,
                         sale_price = ?,
                         quantity = ?,
                         image = ?,
                         is_featured = ?,
                         is_active = ?,
                         updated_at = NOW()
                         WHERE id = ?");
    
    $stmt->execute([
        $category_id,
        $name,
        $description,
        $price,
        $sale_price,
        $quantity,
        $image_path,
        $is_featured,
        $is_active,
        $id
    ]);
    
    $_SESSION['success'] = 'Cập nhật sản phẩm thành công!';
    header('Location: ../products.php');
    exit;
    
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header('Location: ../product-edit.php?id=' . ($id ?? 0));
    exit;
}