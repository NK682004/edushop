# 📚 EDUSHOP - BÁO CÁO DỰ ÁN

## 📋 Mục Lục
1. [Tổng Quan Dự Án](#tổng-quan-dự-án)
2. [Cấu Trúc Dự Án](#cấu-trúc-dự-án)
3. [Chức Năng Chi Tiết](#chức-năng-chi-tiết)
4. [Công Nghệ Sử Dụng](#công-nghệ-sử-dụng)
5. [Cơ Sở Dữ Liệu](#cơ-sở-dữ-liệu)
6. [Hướng Dẫn Cài Đặt](#hướng-dẫn-cài-đặt)

---

## 🎯 Tổng Quan Dự Án

**EduShop** là một nền tảng **thương mại điện tử (E-Commerce)** chuyên về bán **đồ dùng học tập và văn phòng phẩm** được xây dựng bằng **PHP** và **MySQL**. 

Hệ thống hỗ trợ:
- 👥 **Giao diện khách hàng (Customer)** - Duyệt, tìm kiếm, mua sản phẩm
- 🔧 **Giao diện quản trị (Admin Panel)** - Quản lý sản phẩm, đơn hàng, khách hàng
- 📊 **Báo cáo thống kê** - Doanh thu, sản phẩm bán chạy, khách hàng
- 🚚 **Hệ thống vận chuyển** - Tính phí ship tự động theo giá trị đơn hàng

### Đặc điểm nổi bật:
- ✅ Miễn phí vận chuyển cho đơn hàng > 200k
- ✅ Quản lý danh mục đa cấp (parent-child categories)
- ✅ Hệ thống giỏ hàng và thanh toán đầy đủ
- ✅ Phân quyền admin/customer
- ✅ Tìm kiếm và lọc sản phẩm nâng cao
- ✅ Phân trang dữ liệu

---

## 📁 Cấu Trúc Dự Án

```
edushop/
├── 📄 Các trang chính (PHP files)
│   ├── index.php                    # Trang landing page
│   ├── home.php                     # Danh sách sản phẩm
│   ├── product.php                  # Chi tiết sản phẩm
│   ├── category.php                 # Danh mục sản phẩm
│   ├── search.php                   # Tìm kiếm sản phẩm
│   ├── cart.php                     # Quản lý giỏ hàng
│   ├── checkout.php                 # Thanh toán
│   ├── login.php                    # Đăng nhập
│   ├── signup.php                   # Đăng ký
│   ├── profile.php                  # Hồ sơ cá nhân
│   ├── my-orders.php                # Đơn hàng của tôi
│   ├── my-order-detail.php          # Chi tiết đơn hàng (customer)
│   │
│   ├── 🔐 Admin Pages
│   ├── dashboard.php                # Bảng điều khiển chính
│   ├── products.php                 # Quản lý sản phẩm
│   ├── product-add.php              # Thêm sản phẩm
│   ├── product-edit.php             # Chỉnh sửa sản phẩm
│   ├── categories.php               # Quản lý danh mục
│   ├── category-add.php             # Thêm danh mục
│   ├── category-edit.php            # Chỉnh sửa danh mục
│   ├── orders.php                   # Quản lý đơn hàng
│   ├── order-detail.php             # Chi tiết đơn hàng (admin)
│   ├── order-edit.php               # Chỉnh sửa đơn hàng
│   ├── customers.php                # Quản lý khách hàng
│   ├── customer-detail.php          # Chi tiết khách hàng
│   ├── customer-orders.php          # Đơn hàng của khách hàng
│   ├── reports.php                  # Báo cáo thống kê
│   ├── shipping-settings.php        # Cấu hình phí vận chuyển
│   └── 404.php                      # Trang lỗi 404
│
├── 📦 config/
│   └── database.php                 # Cấu hình kết nối database (PDO)
│
├── ⚙️ handlers/                      # Xử lý logic nghiệp vụ
│   ├── register.php                 # Đăng ký tài khoản
│   ├── login.php                    # Đăng nhập
│   ├── logout.php                   # Đăng xuất
│   ├── add_to_cart.php              # Thêm vào giỏ hàng
│   ├── remove_cart.php              # Xóa khỏi giỏ hàng
│   ├── update_cart.php              # Cập nhật giỏ hàng
│   ├── place_order.php              # Đặt hàng
│   ├── reorder.php                  # Đặt lại đơn hàng
│   ├── product-save.php             # Lưu sản phẩm mới
│   ├── product-update.php           # Cập nhật sản phẩm
│   ├── product-delete.php           # Xóa sản phẩm
│   ├── category-save.php            # Lưu danh mục mới
│   ├── category-update.php          # Cập nhật danh mục
│   ├── category-delete.php          # Xóa danh mục
│   ├── update_order.php             # Cập nhật đơn hàng
│   ├── cancel-order.php             # Hủy đơn hàng
│   └── shipping-caculator.php       # Tính phí vận chuyển
│
├── 📄 includes/                      # Include files chung
│   ├── header.php                   # Header cho khách hàng
│   ├── footer.php                   # Footer
│   ├── admin-header.php             # Header cho admin
│   ├── sidebar.php                  # Sidebar menu
│   └── ShippingCalculator.php       # Class tính phí vận chuyển
│
├── 🎨 assets/
│   ├── css/                         # Các file CSS
│   │   ├── style.css
│   │   ├── auth.css                 # CSS cho login/signup
│   │   ├── home.css
│   │   ├── product.css
│   │   ├── cart.css
│   │   ├── checkout.css
│   │   ├── orders.css
│   │   ├── dashboard.css
│   │   └── ... (các file CSS khác)
│   └── images/                      # Hình ảnh sản phẩm
│
└── 📝 README.md                     # Tài liệu dự án
```

---

## ✨ Chức Năng Chi Tiết

### **A. PHÍA KHÁCH HÀNG (Customer)**

#### 1️⃣ Trang Chủ & Duyệt Sản Phẩm

| Trang | Chức Năng |
|-------|----------|
| **index.php** | • Landing page với hero section<br>• Banner khuyến mãi<br>• Hiển thị các tính năng chính (giao hàng nhanh, chất lượng, giá tốt, ưu đãi) |
| **home.php** | • Danh sách sản phẩm<br>• Hiển thị danh mục sản phẩm<br>• Sản phẩm nổi bật (featured)<br>• Sản phẩm mới nhất<br>• Phân trang sản phẩm |
| **product.php** | • Chi tiết sản phẩm đầy đủ (tên, giá, mô tả, hình ảnh)<br>• Tính giá cuối cùng (giảm giá)<br>• Hiển thị sản phẩm liên quan<br>• Tính phí vận chuyển<br>• Nút thêm vào giỏ hàng |
| **category.php** | • Hiển thị sản phẩm theo danh mục<br>• Hỗ trợ danh mục con<br>• Phân trang<br>• Lọc và sắp xếp sản phẩm |
| **search.php** | • Tìm kiếm theo tên sản phẩm<br>• Tìm kiếm trong mô tả<br>• Phân trang kết quả<br>• Ưu tiên kết quả: tên > mô tả |

#### 2️⃣ Giỏ Hàng & Thanh Toán

| Trang | Chức Năng |
|-------|----------|
| **cart.php** | • Xem danh sách sản phẩm trong giỏ<br>• Cập nhật số lượng sản phẩm<br>• Xóa sản phẩm<br>• Tính tổng giá tiền<br>• Hiển thị thông báo khi giỏ trống |
| **checkout.php** | • Hiển thị đầy đủ thông tin đơn hàng<br>• Tính subtotal (tạm tính)<br>• **Tính phí vận chuyển tự động:**<br>  - Miễn phí nếu > 200k<br>  - 30k nếu < 200k<br>• Tính tổng tiền (subtotal + ship)<br>• Nhập/chỉnh sửa thông tin giao hàng<br>• Xác nhận đặt hàng |

#### 3️⃣ Quản Lý Tài Khoản

| Trang | Chức Năng |
|-------|----------|
| **signup.php** | • Điền thông tin: họ tên, email, điện thoại, mật khẩu<br>• Xác nhận mật khẩu<br>• Nhập địa chỉ<br>• Đồng ý điều khoản<br>• Validate dữ liệu đầu vào |
| **login.php** | • Đăng nhập bằng email & mật khẩu<br>• Ghi nhớ đăng nhập<br>• Chuyển hướng đến trang yêu cầu |
| **profile.php** | • Xem/chỉnh sửa thông tin cá nhân<br>• Hiển thị thống kê (tổng đơn hàng, tổng chi tiêu)<br>• Menu nhanh đến đơn hàng<br>• Đăng xuất |

#### 4️⃣ Quản Lý Đơn Hàng (Khách Hàng)

| Trang | Chức Năng |
|-------|----------|
| **my-orders.php** | • Liệt kê tất cả đơn hàng của user<br>• Lọc theo trạng thái (pending, processing, shipping, completed, cancelled)<br>• Thống kê số đơn hàng theo trạng thái<br>• Sắp xếp theo thời gian (mới nhất trước) |
| **my-order-detail.php** | • Xem thông tin đơn hàng đầy đủ<br>• Danh sách sản phẩm trong đơn (tên, giá, số lượng)<br>• Trạng thái đơn hàng<br>• Địa chỉ giao hàng<br>• Tổng tiền<br>• (Chỉ xem được đơn của chính mình) |

#### 5️⃣ Handler Xử Lý (Customer)
- `handlers/register.php` - Xử lý đăng ký tài khoản
- `handlers/login.php` - Xử lý đăng nhập
- `handlers/logout.php` - Đăng xuất
- `handlers/add_to_cart.php` - Thêm sản phẩm vào giỏ
- `handlers/remove_cart.php` - Xóa sản phẩm khỏi giỏ
- `handlers/update_cart.php` - Cập nhật số lượng
- `handlers/place_order.php` - Xử lý đặt hàng
- `handlers/reorder.php` - Đặt lại đơn hàng cũ

---

### **B. PHÍA QUẢN TRỊ (ADMIN PANEL)**

#### 1️⃣ Dashboard - Bảng Điều Khiển

**dashboard.php** - Bảng điều khiển chính hiển thị:
- 📊 Thống kê tổng đơn hàng
- 📈 Đơn hàng hôm nay
- 💰 Tổng doanh thu (từ đơn completed)
- 📅 Doanh thu tháng hiện tại
- 📦 Tổng sản phẩm
- 📉 Biểu đồ thống kê doanh thu
- 🆕 Danh sách đơn hàng mới nhất
- 🔥 Top sản phẩm bán chạy

#### 2️⃣ Quản Lý Sản Phẩm

| Trang | Chức Năng |
|-------|----------|
| **products.php** | • Liệt kê tất cả sản phẩm<br>• Tìm kiếm theo tên<br>• Lọc theo danh mục<br>• Lọc theo trạng thái (active/inactive)<br>• Phân trang<br>• Xem, chỉnh sửa, xóa sản phẩm |
| **product-add.php** | • Form nhập thông tin: tên, slug, giá, giá bán, mô tả<br>• Chọn danh mục<br>• Upload hình ảnh<br>• Nhập số lượng tồn kho<br>• Đánh dấu nổi bật (featured)<br>• Bật/tắt sản phẩm |
| **product-edit.php** | • Cập nhật thông tin sản phẩm<br>• Thay đổi hình ảnh<br>• Cập nhật giá, số lượng<br>• Cập nhật trạng thái |

**Handler xử lý:**
- `handlers/product-save.php` - Lưu sản phẩm mới
- `handlers/product-update.php` - Cập nhật sản phẩm
- `handlers/product-delete.php` - Xóa sản phẩm

#### 3️⃣ Quản Lý Danh Mục

| Trang | Chức Năng |
|-------|----------|
| **categories.php** | • Hiển thị danh mục cha<br>• Hiển thị danh mục con theo cha<br>• Số lượng sản phẩm trong mỗi danh mục<br>• Xem, chỉnh sửa, xóa danh mục |
| **category-add.php** | • Nhập tên danh mục<br>• Nhập slug (URL-friendly)<br>• Mô tả danh mục<br>• Chọn danh mục cha (nếu là subcategory)<br>• Order hiển thị |
| **category-edit.php** | • Cập nhật thông tin danh mục |

**Handler xử lý:**
- `handlers/category-save.php` - Lưu danh mục mới
- `handlers/category-update.php` - Cập nhật danh mục
- `handlers/category-delete.php` - Xóa danh mục

#### 4️⃣ Quản Lý Đơn Hàng (Admin)

| Trang | Chức Năng |
|-------|----------|
| **orders.php** | • Liệt kê tất cả đơn hàng<br>• Lọc theo trạng thái<br>• Phân trang<br>• Thông tin khách (tên, email)<br>• Tổng tiền, ngày đặt<br>• Xem, chỉnh sửa, hủy đơn hàng |
| **order-detail.php** | • Thông tin đơn hàng đầy đủ<br>• Danh sách sản phẩm<br>• Thông tin giao hàng<br>• Trạng thái<br>• Tổng tiền, phí ship, subtotal |
| **order-edit.php** | • Cập nhật trạng thái đơn hàng<br>• Cập nhật thông tin giao hàng<br>• Cập nhật ghi chú |

**Trạng thái đơn hàng:**
- `pending` - Chờ xử lý
- `processing` - Đang xử lý
- `shipping` - Đang giao
- `completed` - Hoàn thành
- `cancelled` - Đã hủy

**Handler xử lý:**
- `handlers/update_order.php` - Cập nhật đơn hàng
- `handlers/cancel-order.php` - Hủy đơn hàng

#### 5️⃣ Quản Lý Khách Hàng

| Trang | Chức Năng |
|-------|----------|
| **customers.php** | • Liệt kê tất cả khách hàng<br>• Tìm kiếm theo tên, email, SĐT<br>• Thông tin: họ tên, email, SĐT, địa chỉ<br>• Tổng đơn hàng, tổng chi tiêu<br>• Xem chi tiết khách hàng |
| **customer-detail.php** | • Thông tin cá nhân đầy đủ<br>• Lịch sử đơn hàng<br>• Tổng tiền đã mua<br>• Danh sách các đơn hàng |
| **customer-orders.php** | • Liệt kê đơn hàng của 1 khách hàng<br>• Chi tiết từng đơn hàng |

#### 6️⃣ Báo Cáo & Thống Kê

**reports.php** - Báo cáo chi tiết:
- 📊 **Doanh thu theo ngày** (daily revenue)
- 📅 Lọc theo khoảng thời gian (start_date, end_date)
- 🔥 **Sản phẩm bán chạy nhất** - Số lượng bán, tổng doanh thu
- 📦 **Danh mục bán chạy**
- 👥 **Khách hàng chi tiêu nhiều nhất**

#### 7️⃣ Cấu Hình Vận Chuyển

**shipping-settings.php** - Quản lý phí vận chuyển:
- Thêm/sửa/xóa cấu hình phí ship
- Cấu hình theo khoảng giá đơn hàng (min_order, max_order)
- Cấu hình theo khu vực (region)
- Ngưỡng miễn phí ship (free_shipping_threshold)
- Bật/tắt cấu hình

---

## 🛠️ Công Nghệ Sử Dụng

| Thành Phần | Công Nghệ |
|-----------|-----------|
| **Backend** | PHP (Vanilla - không Framework) |
| **Database** | MySQL 5.7+ |
| **Frontend** | HTML5, CSS3, JavaScript |
| **Kiến Trúc** | MVC cơ bản |
| **Database Connection** | PDO (PHP Data Objects) |
| **Session Management** | PHP Sessions |
| **Web Server** | Apache (XAMPP) |

### ShippingCalculator Class
Xử lý logic tính phí vận chuyển:
- Tính phí ship dựa vào giá trị đơn hàng
- Lấy cấu hình từ database
- Kiểm tra miễn phí ship

**Logic mặc định:**
- Đơn hàng < 200k: 30,000 VND
- Đơn hàng ≥ 200k: Miễn phí

---

## 💾 Cơ Sở Dữ Liệu

**Database Name:** `edushop`

**Các bảng chính:**

| Bảng | Mô Tả |
|-----|-------|
| **users** | Tài khoản (customer/admin) |
| **categories** | Danh mục sản phẩm (hỗ trợ parent-child) |
| **products** | Danh sách sản phẩm |
| **cart** | Giỏ hàng của khách hàng |
| **orders** | Đơn hàng |
| **order_items** | Chi tiết sản phẩm trong đơn hàng |
| **shipping_configs** | Cấu hình phí vận chuyển |

---

## 📊 Tóm Tắt Chức Năng Theo Loại Người Dùng

### 👥 Khách Hàng (Customer)
- ✅ Xem sản phẩm
- ✅ Tìm kiếm sản phẩm
- ✅ Duyệt theo danh mục
- ✅ Thêm vào giỏ hàng
- ✅ Quản lý giỏ hàng
- ✅ Thanh toán (tính phí ship tự động)
- ✅ Xem đơn hàng của mình
- ✅ Quản lý hồ sơ cá nhân
- ✅ Đặt lại đơn hàng cũ

### 🔐 Quản Trị Viên (Admin)
- ✅ Quản lý sản phẩm (CRUD)
- ✅ Quản lý danh mục (CRUD)
- ✅ Quản lý đơn hàng (xem, cập nhật trạng thái, hủy)
- ✅ Quản lý khách hàng (xem, tìm kiếm)
- ✅ Xem báo cáo thống kê
- ✅ Cấu hình phí vận chuyển
- ✅ Xem dashboard với KPI chính

---

## 📝 Ghi Chú

### Phí Vận Chuyển
- Mặc định: 30,000 VND cho đơn < 200,000 VND
- Miễn phí cho đơn >= 200,000 VND
- Cấu hình chi tiết trong Admin Panel (shipping-settings.php)

### Trạng Thái Đơn Hàng
- **pending**: Chờ xử lý
- **processing**: Đang xử lý
- **shipping**: Đang giao hàng
- **completed**: Hoàn thành
- **cancelled**: Đã hủy

### Phân Quyền
- **customer**: Khách hàng thường
- **admin**: Quản trị viên (truy cập Admin Panel)

---

**Phiên bản:** 1.0.0  
**Cập nhật lần cuối:** December 2025  
**Trạng thái:** Production Ready ✅
