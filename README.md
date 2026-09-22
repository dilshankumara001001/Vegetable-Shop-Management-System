<div align="center">

# 🥦 Vegie Pro

### Vegetable Shop Management System

**A complete, modern, animated PHP + MySQL solution for managing vegetable wholesale & retail shops.**

[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![License](https://img.shields.io/badge/License-MIT-27ae60?style=for-the-badge)](LICENSE)
[![PRs Welcome](https://img.shields.io/badge/PRs-Welcome-brightgreen?style=for-the-badge)](CONTRIBUTING.md)
[![Made in Sri Lanka](https://img.shields.io/badge/Made%20in-Sri%20Lanka%20🇱🇰-e67e22?style=for-the-badge)]()

[Features](#-features) • [Screenshots](#-screenshots) • [Installation](#-installation) • [Usage](#-usage) • [Tech Stack](#-tech-stack) • [Contributing](#-contributing)

</div>

---

## 📖 About

**Vegie Pro** is a lightweight, browser-based **vegetable shop management system** built with pure PHP and MySQL — no frameworks, no heavy dependencies. It handles everything a small-to-medium vegetable wholesale shop needs: stock tracking (FIFO), supplier management, cart-based sales, automatic invoicing, profit analysis, and rich reporting.

Perfect as a **final-year project**, **university assignment**, or a **real-world starter** for shop owners who want a simple but powerful POS system.

---

## ✨ Features

### 🔐 Authentication & Users
- Secure login with **bcrypt** password hashing (auto-upgrades legacy SHA2)
- Two roles: **Admin** and **Staff**
- Admin-only user management, password reset
- Session-based access control with CSRF-safe prepared statements

### 🥦 Vegetable Management
- Add / delete vegetables with category, unit, reorder level
- Live search + category filter
- Visual stock-level progress bars
- Auto low-stock highlighting with pulsing badges

### 👨‍🌾 Supplier Management
- Register suppliers with contact details
- Track purchase value per supplier
- Copy phone number to clipboard
- Batch count & total value analytics

### 📥 Stock In (Purchases)
- Record stock batches linked to suppliers
- FIFO-based remaining quantity tracking
- Today's stock-in & purchase value summaries
- Remaining/used progress visualization

### 📦 Inventory
- Real-time stock overview with 4 animated stat cards
- Last buying price & auto-suggested selling price (+20%)
- Profit-per-kg calculation
- Filter by Low / OK status

### 💰 Sales & Billing
- **Multi-item shopping cart** stored in session
- Automatic FIFO stock deduction (oldest batch first)
- Insufficient stock prevention
- One-click invoice generation
- Real-time cart total & item count

### 🧾 Invoice
- Printable, professional A4 invoice
- Auto-generated invoice numbers (`INV-YYYYMMDD-XXXX`)
- Buyer info, cashier, itemized lines, grand total
- Print to paper or PDF

### 📈 Reports (with Print!)
- Date-range filtered analytics
- 5 animated KPI counters (Revenue, Cost, Profit, Quantity, Invoices)
- Profit margin progress bar
- Daily sales bar chart
- Top-selling vegetables & top buyers
- **Print-optimized layout** with signature blocks
- **CSV export** for Excel / Google Sheets

### 🎨 UI / UX
- **100+ CSS animations** — fade, slide, bounce, ripple, pulse, shimmer
- Glassmorphism login with animated gradient background
- Animated number counters
- Live clock in the topbar
- Toast notifications
- Sortable tables, live search
- Skeleton loading bars & ripple buttons
- Fully responsive (mobile-friendly)
- Beautiful green/emerald theme 🌱

---

## 🖼 Screenshots

> _Add your own screenshots here. Suggested sizes: 1280×720._

| Dashboard | Sales & Billing |
|:-:|:-:|
| ![Dashboard](docs/screenshots/dashboard.png) | ![Sales](docs/screenshots/sales.png) |

| Invoice | Reports |
|:-:|:-:|
| ![Invoice](docs/screenshots/invoice.png) | ![Reports](docs/screenshots/reports.png) |

---

## 🛠 Tech Stack

| Layer | Technology |
|---|---|
| **Backend** | PHP 7.4+ (procedural, prepared statements) |
| **Database** | MySQL 5.7+ / MariaDB |
| **Frontend** | Vanilla HTML5, CSS3, JavaScript (ES6) |
| **Server** | Apache (XAMPP / WAMP / LAMP) |
| **Fonts** | Google Fonts – Inter |
| **Icons** | Native Unicode Emoji (no external library) |

**Zero dependencies.** No Composer, no npm, no CDN required (except optional Google Fonts).

---

## 🚀 Installation

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache web server (XAMPP / WAMP / MAMP / LAMP recommended)

https://github.com/dilshankumara001001/Vegetable-Shop-Management-System

**2. Move the folder to your web server root**

```bash
# XAMPP (Windows)
C:\xampp\htdocs\vegie_pro\

# WAMP
C:\wamp64\www\vegie_pro\

# Linux
/var/www/html/vegie_pro/
```

**3. Import the database**

- Open phpMyAdmin → **http://localhost/phpmyadmin**
- Click **Import** → choose `install.sql` → **Go**

This creates the `vegie_pro` database with all tables and 10 sample records per table.

**4. Configure database credentials** (optional)

Open `db.php` and adjust if needed:

```php
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';       // XAMPP default is empty
$DB_NAME = 'vegie_pro';
```

**5. Run it!**

Open your browser and visit:

```
http://localhost/vegie_pro/login.php
```

---

## 🔑 Default Login Credentials

| Username | Password | Role |
|---|---|---|
| `admin` | `admin123` | 👑 Admin |
| `staff` | `staff123` | 🧑‍💼 Staff |
| `kasun` | `kasun123` | Staff |
| `nimali` | `nimali123` | Staff |
| `dilini` | `dilini123` | Admin |

> ⚠️ **Change the default passwords immediately after first login!**

---

## 📂 Project Structure

```
vegie_pro/
├── install.sql          # Database schema + sample data
├── db.php               # MySQL connection
├── auth.php             # Auth + helper functions (FIFO, invoice, etc.)
├── header.php           # Shared sidebar + layout (top)
├── footer.php           # Shared layout (bottom)
├── app.js               # All JavaScript animations
├── style.css            # All styling + animations
│
├── login.php            # Login page
├── logout.php           # Session destroy
├── index.php            # Dashboard
├── vegetables.php       # Vegetable CRUD
├── suppliers.php        # Supplier CRUD
├── stock_in.php         # Purchase / Stock In
├── inventory.php        # Inventory status
├── sales.php            # Cart + billing
├── invoice.php          # Printable invoice
├── reports.php          # Analytics + CSV export + Print
└── users.php            # User management (admin only)
```

---

## 📋 Usage Workflow

```
1. Login → 2. Add Vegetables & Suppliers →
3. Record Stock In (purchases) →
4. View Inventory →
5. Create Sale (cart) → 6. Generate & Print Invoice →
7. Analyze Reports → 8. Print or Export CSV
```

---

## 🎯 Database Schema

| Table | Purpose |
|---|---|
| `users` | Login accounts with roles |
| `vegetables` | Vegetable master list |
| `suppliers` | Supplier contact info |
| `stock` | FIFO purchase batches (with `remaining_kg`) |
| `sales` | Sale line items (linked to invoice) |
| `stock_movements` | Full audit ledger of in/out movements |

Every table ships with **10 sample records** so you can explore the system immediately.

---

## 🔒 Security Notes

- ✅ All queries use **prepared statements** — no SQL injection
- ✅ Passwords hashed with **bcrypt** (`PASSWORD_DEFAULT`)
- ✅ Legacy SHA2 hashes auto-upgraded on first login
- ✅ Session-based authentication on every page
- ✅ `htmlspecialchars()` on all user output — XSS safe
- ✅ Role-based access (Admin / Staff) enforced server-side

> ⚠️ This project is intended for **local / LAN deployment**. For public production, add HTTPS, change default passwords, and restrict `phpMyAdmin` access.

---

## 🖨 Print & Export

- **Reports page** → `🖨 Print Report` opens a clean, signature-ready A4 layout
- `⬇ Export CSV` downloads a UTF-8 CSV for Excel / Google Sheets
- **Invoice page** → `🖨 Print` generates a professional receipt

**Tip:** In the print dialog, enable **Background graphics** to keep colors.

---





## 📜 License

This project is licensed under the **MIT License** — see the [LICENSE](LICENSE) file for details.

```
MIT License

Copyright (c) 2025 Vegie Pro

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.
```

---

## 👨‍💻 Author

**Your Name**
- GitHub: [@your-username](https://github.com/dilshankumara001001)
- Email: dileepadilsham46@gmail.com
---

## 🙏 Acknowledgements

- Google Fonts — [Inter](https://fonts.google.com/specimen/Inter)
- All emojis provided natively by the OS
- Inspired by real vegetable wholesale shops in Sri Lanka 🇱🇰

---

## ⭐ Show Your Support

If this project helped you, please give it a **⭐ star** on GitHub — it means a lot!

<div align="center">

**Made with 💚 and 🌱 in Sri Lanka**

[⬆ Back to Top](#-vegie-pro)

</div>
