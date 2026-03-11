# EduShop - PHP E-commerce Platform

A robust, standard-structured PHP e-commerce web application designed for learning and small-to-medium businesses. This project follows a clean directory architecture separating core logic, public-facing pages, and an administrative dashboard.

## 📂 Project Structure

This repository is organized according to standard PHP web application best practices:

```
edushop/
├── admin/               # Admin dashboard & management interfaces
│   ├── dashboard.php
│   ├── products.php
│   ├── orders.php
│   └── ... (categories, customers, reports)
├── public/              # Client-facing pages (Front-end)
│   ├── index.php        # Entry point / Home
│   ├── product.php
│   ├── cart.php
│   ├── checkout.php
│   └── ... (login, signup, profile, my-orders)
├── config/              # Database connections & global configurations
├── handlers/            # Backend logic (Form submissions, API endpoints)
├── includes/            # Reusable UI components (Header, Footer, Sidebar, Navbar)
├── assets/              # Static files (CSS, JS, Images)
└── docs/                # Project documentation and reports
```

## ✨ Key Features

### For Customers (`/public`)
- **Product Browsing:** View products by category, search functionality.
- **Shopping Cart:** Add, remove, and update quantities.
- **Checkout Process:** Secure order placement (`checkout.php` -> `place-order.php`).
- **User Account:** Signup, login, profile management, and order history (`my-orders.php`).

### For Administrators (`/admin`)
- **Dashboard:** Overview of sales, orders, and customer statistics.
- **Catalog Management:** Add, edit, and delete Products and Categories.
- **Order Management:** View order details, update statuses (`order-edit.php`).
- **Customer Management:** View user details and their order history.
- **Reports:** Generate sales reports and analytics.

## 🚀 Installation & Setup

1. **Clone the repository:**
   ```bash
   git clone https://github.com/yourusername/edushop.git
   ```
2. **Move to a local server environment:**
   Place the folder inside your XAMPP (`htdocs`), WAMP (`www`), or equivalent local server directory.
3. **Database Configuration:**
   - Create a new MySQL database (e.g., `edushop_db`).
   - Import the provided SQL dump (if available in `/docs` or root).
   - Update the database connection credentials in the `/config` directory (e.g., `database.php` or `config.php`).
4. **Access the application:**
   - **Storefront:** `http://localhost/edushop/public/index.php`
   - **Admin Panel:** `http://localhost/edushop/admin/dashboard.php`

## 🛠️ Technologies Used
- **Backend:** Raw PHP (Procedural/OOP)
- **Frontend:** HTML5, CSS3, JavaScript (Vanilla/jQuery)
- **Database:** MySQL

## 📝 Documentation
For detailed insights into the project architecture, database schema, and development process, please refer to the files in the `/docs` directory (e.g., `PROJECT_REPORT.md`).
