<?php
$page_title = 'Cấu Hình Phí Vận Chuyển - EduShop Admin';

require_once __DIR__ . '../config/database.php';
include __DIR__ . '/includes/admin-header.php';

// Check admin
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$db = getDB();
$stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Xử lý cập nhật
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            if ($_POST['action'] === 'add') {
                $stmt = $db->prepare("
                    INSERT INTO shipping_configs (region, min_order, max_order, fee, free_shipping_threshold)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $_POST['region'],
                    $_POST['min_order'],
                    $_POST['max_order'] ?: null,
                    $_POST['fee'],
                    $_POST['free_shipping_threshold'] ?: null
                ]);
                $_SESSION['success'] = 'Thêm cấu hình thành công';
            } 
            elseif ($_POST['action'] === 'update') {
                $stmt = $db->prepare("
                    UPDATE shipping_configs 
                    SET region = ?, min_order = ?, max_order = ?, fee = ?, free_shipping_threshold = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $_POST['region'],
                    $_POST['min_order'],
                    $_POST['max_order'] ?: null,
                    $_POST['fee'],
                    $_POST['free_shipping_threshold'] ?: null,
                    $_POST['id']
                ]);
                $_SESSION['success'] = 'Cập nhật thành công';
            }
            elseif ($_POST['action'] === 'toggle') {
                $stmt = $db->prepare("
                    UPDATE shipping_configs 
                    SET is_active = !is_active 
                    WHERE id = ?
                ");
                $stmt->execute([$_POST['id']]);
                $_SESSION['success'] = 'Cập nhật trạng thái thành công';
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Có lỗi: ' . $e->getMessage();
        }
        header('Location: shipping-settings.php');
        exit;
    }
}

// Lấy danh sách cấu hình
$stmt = $db->query("SELECT * FROM shipping_configs ORDER BY min_order ASC");
$configs = $stmt->fetchAll();

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>

<link rel="stylesheet" href="assets/css/dashboard.css">
<link rel="stylesheet" href="assets/css/shipping-settings.css">

<main class="main-content">
    <div class="dashboard-container">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <div class="dashboard-main">
            <div class="page-header">
                <h1>Cấu Hình Phí Vận Chuyển</h1>
                <button onclick="showAddForm()" class="btn btn-primary">Thêm cấu hình</button>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- Add/Edit Form (Hidden) -->
            <div id="configForm" class="config-form" style="display: none;">
                <div class="form-card">
                    <div class="card-header">
                        <h3 id="formTitle">Thêm Cấu Hình Mới</h3>
                        <button onclick="hideForm()" class="btn-close">✕</button>
                    </div>
                    <form method="POST" class="card-body">
                        <input type="hidden" name="action" id="formAction" value="add">
                        <input type="hidden" name="id" id="configId">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Khu vực</label>
                                <input type="text" name="region" id="region" value="default" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Giá trị đơn tối thiểu (đ)</label>
                                <input type="number" name="min_order" id="min_order" step="1000" required>
                            </div>
                            <div class="form-group">
                                <label>Giá trị đơn tối đa (đ)</label>
                                <input type="number" name="max_order" id="max_order" step="1000" placeholder="Để trống = không giới hạn">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Phí vận chuyển (đ)</label>
                                <input type="number" name="fee" id="fee" step="1000" required>
                            </div>
                            <div class="form-group">
                                <label>Ngưỡng miễn phí ship (đ)</label>
                                <input type="number" name="free_shipping_threshold" id="free_shipping_threshold" step="1000" placeholder="Để trống = không có">
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">💾 Lưu</button>
                            <button type="button" onclick="hideForm()" class="btn btn-secondary">Hủy</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Shipping Info Cards -->
            <div class="info-cards">
                <div class="info-card">
                 
                    <div class="info-content">
                        <h4>Chính sách miễn phí ship</h4>
                        <p>Đơn hàng từ <strong>200,000đ</strong> trở lên</p>
                    </div>
                </div>
                <div class="info-card">
                   
                    <div class="info-content">
                        <h4>Phí ship tiêu chuẩn</h4>
                        <p>Từ <strong>15,000đ - 30,000đ</strong></p>
                    </div>
                </div>
                <div class="info-card">
                    
                    <div class="info-content">
                        <h4>Tổng cấu hình</h4>
                        <p><strong><?php echo count($configs); ?></strong> bậc giá</p>
                    </div>
                </div>
            </div>

            <!-- Configs Table -->
            <div class="table-card">
                <div class="card-header">
                    <h3>Danh Sách Cấu Hình</h3>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Khu vực</th>
                                <th>Giá trị đơn hàng</th>
                                <th>Phí ship</th>
                                <th>Miễn phí từ</th>
                                <th>Trạng thái</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($configs as $config): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($config['region']); ?></td>
                                <td>
                                    <?php 
                                    echo number_format($config['min_order']);
                                    echo ' - ';
                                    echo $config['max_order'] ? number_format($config['max_order']) : '∞';
                                    ?>đ
                                </td>
                                <td><strong class="fee"><?php echo number_format($config['fee']); ?>đ</strong></td>
                                <td>
                                    <?php 
                                    if ($config['free_shipping_threshold']) {
                                        echo '≥ ' . number_format($config['free_shipping_threshold']) . 'đ';
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="id" value="<?php echo $config['id']; ?>">
                                        <button type="submit" class="status-toggle <?php echo $config['is_active'] ? 'active' : 'inactive'; ?>">
                                            <?php echo $config['is_active'] ? '✓ Đang dùng' : '✗ Tắt'; ?>
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <button onclick="editConfig(<?php echo htmlspecialchars(json_encode($config)); ?>)" 
                                            class="btn-action btn-edit" title="Sửa">✏️</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
function showAddForm() {
    document.getElementById('formTitle').textContent = 'Thêm Cấu Hình Mới';
    document.getElementById('formAction').value = 'add';
    document.getElementById('configForm').style.display = 'block';
    document.getElementById('configId').value = '';
    document.querySelector('form').reset();
}

function hideForm() {
    document.getElementById('configForm').style.display = 'none';
}

function editConfig(config) {
    document.getElementById('formTitle').textContent = 'Chỉnh Sửa Cấu Hình';
    document.getElementById('formAction').value = 'update';
    document.getElementById('configId').value = config.id;
    document.getElementById('region').value = config.region;
    document.getElementById('min_order').value = config.min_order;
    document.getElementById('max_order').value = config.max_order || '';
    document.getElementById('fee').value = config.fee;
    document.getElementById('free_shipping_threshold').value = config.free_shipping_threshold || '';
    document.getElementById('configForm').style.display = 'block';
    document.getElementById('configForm').scrollIntoView({ behavior: 'smooth' });
}
</script>
