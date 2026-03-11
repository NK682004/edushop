<?php
$page_title = 'Báo Cáo Thống Kê - EduShop Admin';

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

// Lấy khoảng thời gian
$period = $_GET['period'] ?? 'month';
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Thống kê doanh thu theo ngày
$stmt = $db->prepare("SELECT DATE(created_at) as date, 
                             SUM(total_amount) as revenue,
                             COUNT(*) as orders
                      FROM orders 
                      WHERE status = 'completed' 
                        AND DATE(created_at) BETWEEN ? AND ?
                      GROUP BY DATE(created_at)
                      ORDER BY date");
$stmt->execute([$start_date, $end_date]);
$daily_revenue = $stmt->fetchAll();

// Sản phẩm bán chạy
$stmt = $db->prepare("SELECT p.name, p.price, p.sale_price,
                             SUM(oi.quantity) as sold_count,
                             SUM(oi.quantity * oi.price) as total_revenue
                      FROM products p
                      JOIN order_items oi ON p.id = oi.product_id
                      JOIN orders o ON oi.order_id = o.id
                      WHERE o.status = 'completed'
                        AND DATE(o.created_at) BETWEEN ? AND ?
                      GROUP BY p.id
                      ORDER BY sold_count DESC
                      LIMIT 10");
$stmt->execute([$start_date, $end_date]);
$top_products = $stmt->fetchAll();

// Danh mục bán chạy
$stmt = $db->prepare("SELECT c.name,
                             COUNT(DISTINCT oi.id) as items_sold,
                             SUM(oi.quantity * oi.price) as revenue
                      FROM categories c
                      JOIN products p ON c.id = p.category_id
                      JOIN order_items oi ON p.id = oi.product_id
                      JOIN orders o ON oi.order_id = o.id
                      WHERE o.status = 'completed'
                        AND DATE(o.created_at) BETWEEN ? AND ?
                      GROUP BY c.id
                      ORDER BY revenue DESC
                      LIMIT 10");
$stmt->execute([$start_date, $end_date]);
$top_categories = $stmt->fetchAll();

// Thống kê tổng quan
$stmt = $db->prepare("SELECT 
                        COUNT(*) as total_orders,
                        SUM(total_amount) as total_revenue,
                        AVG(total_amount) as avg_order_value,
                        COUNT(DISTINCT user_id) as total_customers
                      FROM orders 
                      WHERE status = 'completed'
                        AND DATE(created_at) BETWEEN ? AND ?");
$stmt->execute([$start_date, $end_date]);
$summary = $stmt->fetch();
?>

<link rel="stylesheet" href="assets/css/dashboard.css">
<link rel="stylesheet" href="assets/css/reports.css">

<main class="main-content">
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="dashboard-main">
            <div class="page-header">
                <h1>📊 Báo Cáo Thống Kê</h1>
            </div>

            <!-- Date Filter -->
            <div class="filter-card">
                <form method="GET" class="date-filter-form">
                    <div class="filter-group">
                        <label>Từ ngày:</label>
                        <input type="date" name="start_date" value="<?php echo $start_date; ?>" required>
                    </div>
                    <div class="filter-group">
                        <label>Đến ngày:</label>
                        <input type="date" name="end_date" value="<?php echo $end_date; ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Xem báo cáo</button>
                    <a href="reports.php" class="btn btn-secondary">Reset</a>
                </form>
            </div>

            <!-- Summary Stats -->
            <div class="summary-grid">
                <div class="summary-card">
                    <div class="summary-icon">🛒</div>
                    <div class="summary-content">
                        <h3><?php echo number_format($summary['total_orders'] ?? 0); ?></h3>
                        <p>Đơn hàng hoàn thành</p>
                    </div>
                </div>

                <div class="summary-card">
                    <div class="summary-icon">💰</div>
                    <div class="summary-content">
                        <h3><?php echo number_format($summary['total_revenue'] ?? 0); ?>đ</h3>
                        <p>Tổng doanh thu</p>
                    </div>
                </div>

                <div class="summary-card">
                    <div class="summary-icon">📈</div>
                    <div class="summary-content">
                        <h3><?php echo number_format($summary['avg_order_value'] ?? 0); ?>đ</h3>
                        <p>Giá trị đơn TB</p>
                    </div>
                </div>

                <div class="summary-card">
                    <div class="summary-icon">👥</div>
                    <div class="summary-content">
                        <h3><?php echo number_format($summary['total_customers'] ?? 0); ?></h3>
                        <p>Khách hàng mua</p>
                    </div>
                </div>
            </div>

            <!-- Revenue Chart -->
            <div class="chart-card">
                <h3>📈 Biểu Đồ Doanh Thu Theo Ngày</h3>
                <?php if (empty($daily_revenue)): ?>
                    <div class="no-data">Không có dữ liệu trong khoảng thời gian này</div>
                <?php else: ?>
                <div class="chart-container">
                    <canvas id="revenueChart"></canvas>
                </div>
                <?php endif; ?>
            </div>

            <!-- Top Products & Categories -->
            <div class="reports-grid">
                <!-- Top Products -->
                <div class="report-card">
                    <h3>🔥 Top 10 Sản Phẩm Bán Chạy</h3>
                    <?php if (empty($top_products)): ?>
                        <div class="no-data">Chưa có dữ liệu</div>
                    <?php else: ?>
                    <div class="report-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>STT</th>
                                    <th>Sản phẩm</th>
                                    <th>Đã bán</th>
                                    <th>Doanh thu</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_products as $index => $product): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td class="product-name"><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td><?php echo number_format($product['sold_count']); ?></td>
                                    <td class="revenue"><?php echo number_format($product['total_revenue']); ?>đ</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Top Categories -->
                <div class="report-card">
                    <h3>📁 Top 10 Danh Mục Bán Chạy</h3>
                    <?php if (empty($top_categories)): ?>
                        <div class="no-data">Chưa có dữ liệu</div>
                    <?php else: ?>
                    <div class="report-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>STT</th>
                                    <th>Danh mục</th>
                                    <th>SP đã bán</th>
                                    <th>Doanh thu</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_categories as $index => $category): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td class="category-name"><?php echo htmlspecialchars($category['name']); ?></td>
                                    <td><?php echo number_format($category['items_sold']); ?></td>
                                    <td class="revenue"><?php echo number_format($category['revenue']); ?>đ</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Export Actions -->
            <div class="export-actions">
                <h3>📥 Xuất Báo Cáo</h3>
                <div class="export-buttons">
                    <button class="btn btn-export" onclick="exportPDF()">📄 Xuất PDF</button>
                    <button class="btn btn-export" onclick="exportExcel()">📊 Xuất Excel</button>
                    <button class="btn btn-export" onclick="window.print()">🖨️ In báo cáo</button>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
<?php if (!empty($daily_revenue)): ?>
// Prepare data for chart
const dates = <?php echo json_encode(array_column($daily_revenue, 'date')); ?>;
const revenues = <?php echo json_encode(array_column($daily_revenue, 'revenue')); ?>;
const orders = <?php echo json_encode(array_column($daily_revenue, 'orders')); ?>;

// Create chart
const ctx = document.getElementById('revenueChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: dates.map(d => {
            const date = new Date(d);
            return date.getDate() + '/' + (date.getMonth() + 1);
        }),
        datasets: [{
            label: 'Doanh thu (đ)',
            data: revenues,
            borderColor: '#3498db',
            backgroundColor: 'rgba(52, 152, 219, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'top'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'Doanh thu: ' + context.parsed.y.toLocaleString('vi-VN') + 'đ';
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return value.toLocaleString('vi-VN') + 'đ';
                    }
                }
            }
        }
    }
});
<?php endif; ?>

// Export functions
function exportPDF() {
    alert('Chức năng xuất PDF đang được phát triển!');
}

function exportExcel() {
    alert('Chức năng xuất Excel đang được phát triển!');
}
</script>

<style>
@media print {
    .dashboard-sidebar,
    .page-header,
    .filter-card,
    .export-actions {
        display: none;
    }
}
</style>

