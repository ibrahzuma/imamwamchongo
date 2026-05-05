# PharmaCare Plus — Pharmacy Management System

A complete, production-ready Pharmacy Management System built with **PHP** (core PHP, MVC architecture) and **MySQL**, featuring role-based access control, AJAX-powered POS, inventory tracking, supplier and purchase management, and detailed reporting.

---

## ✨ Features

### Core
- **Authentication & Roles** — Admin, Pharmacist, Cashier with role-based access control
- **Dashboard** — KPIs (today/week/month sales, inventory value, low stock, expiring soon) + 7-day sales chart
- **Medicine Management** — Full CRUD with batch numbers, expiry dates, suppliers, cost & retail prices, barcodes, reorder levels
- **Inventory Management** — Stock movement log, manual adjustments (with reason tracking), low-stock alerts
- **Sales / POS** — AJAX-powered point of sale with live search, barcode scanner support, cart, tax & discount, printable receipt
- **Purchase Management** — Record purchases that auto-update stock and batch/expiry data
- **Supplier Management** — Full CRUD
- **Reports** — Sales (date range + chart), Inventory (with valuation), Expiry (with configurable threshold)
- **User Management** — Admin-only, with password reset, status toggle

### Bonus features
- ✅ **Barcode scanning** — Scan-to-cart in POS via standard USB barcode reader
- ✅ **Multi-branch support** — Branch table & schema-ready
- ✅ **REST API** — `/api/` endpoint for medicines, low-stock, expiring, and sales data

### Security
- 🔒 PDO with prepared statements throughout (zero SQL injection surface)
- 🔒 Password hashing via `password_hash()` / `password_verify()` (bcrypt)
- 🔒 CSRF tokens on every form
- 🔒 Role-based access control on every controller action
- 🔒 Input sanitization & validation
- 🔒 Transactional sale/purchase creation with row locking (`SELECT ... FOR UPDATE`) to prevent overselling

---

## 📋 Requirements

| Component | Version |
|-----------|---------|
| PHP       | 7.4+ (8.x recommended) |
| MySQL     | 5.7+ / MariaDB 10.3+ |
| Web server| Apache, Nginx, or PHP built-in server |
| Extensions| `pdo_mysql`, `mbstring`, `json` |

---

## 🚀 Installation

### Option A: XAMPP / WAMP / LAMP

1. **Extract** the project into your web root:
   - XAMPP: `C:\xampp\htdocs\pharmacy_system`
   - WAMP: `C:\wamp64\www\pharmacy_system`
   - LAMP: `/var/www/html/pharmacy_system`

2. **Start** Apache and MySQL from your control panel.

3. **Create the database** — open phpMyAdmin (`http://localhost/phpmyadmin`):
   - Click **New** → name it `pharmacy_db` → **Create**
   - Select `pharmacy_db` → **Import** tab
   - Choose `database/pharmacy_db.sql` → **Go**

4. **Configure DB credentials** — open `config/database.php`:
   ```php
   private $host = 'localhost';
   private $db_name = 'pharmacy_db';
   private $username = 'root';
   private $password = '';   // set if your MySQL has a password
   ```

5. **Set folder permissions** (Linux/Mac only):
   ```bash
   chmod -R 755 pharmacy_system
   chmod -R 775 pharmacy_system/uploads
   ```

6. **Open** `http://localhost/pharmacy_system` in your browser.

### Option B: Built-in PHP server (quickest)

```bash
cd pharmacy_system
mysql -u root -p -e "CREATE DATABASE pharmacy_db;"
mysql -u root -p pharmacy_db < database/pharmacy_db.sql
php -S localhost:8000
```
Visit `http://localhost:8000`.

---

## 🔑 Demo Credentials

All demo accounts use the password **`password123`**.

| Username     | Role        | Access |
|--------------|-------------|--------|
| `admin`      | Admin       | Full system access including user management |
| `pharmacist` | Pharmacist  | Medicines, inventory, sales, purchases, suppliers, reports |
| `cashier`    | Cashier     | POS, sales history, view-only on most areas |

> ⚠️ **Change these passwords immediately** in production via the User Management screen.

---

## 📁 Project Structure

```
pharmacy_system/
├── api/                    # REST API endpoint
│   └── index.php
├── assets/
│   ├── css/style.css       # Custom styles
│   └── js/
│       ├── app.js          # Global helpers
│       └── pos.js          # POS AJAX logic
├── config/
│   ├── config.php          # App config, helpers, auth, CSRF
│   └── database.php        # PDO connection
├── controllers/            # MVC controllers
│   ├── AuthController.php
│   ├── DashboardController.php
│   ├── MedicineController.php
│   ├── SupplierController.php
│   ├── SaleController.php
│   ├── PurchaseController.php
│   ├── InventoryController.php
│   ├── ReportController.php
│   └── UserController.php
├── database/
│   └── pharmacy_db.sql     # Schema + sample data
├── models/                 # Data layer (PDO prepared statements)
│   ├── User.php
│   ├── Medicine.php
│   ├── Supplier.php
│   ├── Category.php
│   ├── Sale.php
│   └── Purchase.php
├── views/                  # MVC views (PHP templates)
│   ├── auth/login.php
│   ├── dashboard/
│   ├── medicines/
│   ├── inventory/
│   ├── sales/{pos,index,invoice}.php
│   ├── purchases/
│   ├── suppliers/
│   ├── reports/
│   ├── users/
│   └── layouts/{header,footer}.php
├── uploads/                # User-uploaded images (writable)
├── index.php               # Front controller / router
└── README.md               # This file
```

### MVC flow
```
Browser → index.php (router) → Controller → Model (PDO) → View (PHP template)
```

The router parses `?page=X&action=Y` and dispatches to the right controller method. Models use prepared statements exclusively. Views are plain PHP templates that include `layouts/header.php` and `layouts/footer.php`.

---

## 🗄️ Database Schema

Eleven tables with proper foreign keys and indexes:

| Table | Purpose |
|-------|---------|
| `branches` | Multi-branch support |
| `users` | System users with bcrypt-hashed passwords |
| `categories` | Medicine categories |
| `suppliers` | Pharmaceutical suppliers |
| `medicines` | Medicine master with batch, expiry, prices, stock, barcode |
| `stock_movements` | Audit log of every stock change |
| `sales` | Sale headers (invoice, totals, tax, discount) |
| `sale_items` | Line items per sale |
| `purchases` | Purchase headers |
| `purchase_items` | Line items per purchase |
| `settings` | Configurable app settings (currency, tax rate, pharmacy name) |

See `database/pharmacy_db.sql` for the complete DDL.

---

## 🔌 REST API

### Authentication
Pass either:
- `X-API-Key: demo-api-key-change-me-in-production` header, **or**
- `?api_key=demo-api-key-change-me-in-production` query parameter, **or**
- An active session cookie (browser users)

> Change the API key in `api/index.php` before deploying.

### Endpoints

| Method | URL | Description |
|--------|-----|-------------|
| GET | `/api/?resource=medicines` | List all medicines |
| GET | `/api/?resource=medicines&id=1` | Single medicine |
| GET | `/api/?resource=medicines&search=para` | Search medicines |
| GET | `/api/?resource=low-stock` | Items at/below reorder level |
| GET | `/api/?resource=expiring&days=30` | Items expiring within N days |
| GET | `/api/?resource=sales&from=2025-01-01&to=2025-12-31` | Sales in date range |

### Example

```bash
curl -H "X-API-Key: demo-api-key-change-me-in-production" \
     "http://localhost/pharmacy_system/api/?resource=low-stock"
```

---

## 🛒 Using the POS

1. Login as `cashier` or `pharmacist`.
2. Click **Sales → New Sale (POS)**.
3. Type in the search box (live AJAX search) **or** scan a barcode (cursor focuses the barcode input — just scan).
4. Click a result to add to cart.
5. Adjust quantities, apply discount or change tax.
6. Click **Complete Sale** → invoice opens with Print button.

Receipt prints to standard or thermal (80mm) printers via `@media print` CSS.

---

## 🔒 Security Notes

| Layer | Protection |
|-------|-----------|
| SQL injection | PDO prepared statements only — no string concatenation anywhere |
| XSS | All output passed through `htmlspecialchars()` / `escapeHtml()` |
| CSRF | Token on every form, verified server-side (`verifyCsrf()`) |
| Authentication | Bcrypt passwords, session-based auth, role checks per route |
| File access | `.htaccess` blocks direct access to `config/`, `models/`, `*.sql`, `*.md` |
| Race conditions | `SELECT ... FOR UPDATE` inside transactions for stock decrements |

### Production checklist
- [ ] Change all demo passwords
- [ ] Update database credentials in `config/database.php`
- [ ] Change the API key in `api/index.php`
- [ ] Set `display_errors = Off` in `php.ini`
- [ ] Enable HTTPS
- [ ] Set proper file permissions (755 for code, 775 for `uploads/`)
- [ ] Configure regular database backups

---

## 🐛 Troubleshooting

| Problem | Solution |
|---------|----------|
| **"Could not connect to database"** | Verify MySQL running, credentials in `config/database.php` correct, database `pharmacy_db` exists |
| **Blank page** | Enable PHP errors temporarily: add `error_reporting(E_ALL); ini_set('display_errors', 1);` to top of `index.php` |
| **"Invalid CSRF token"** | Clear browser cookies; sessions may have expired |
| **POS search not working** | Open browser console (F12) — check for JS errors. Verify the URL bar shows `?page=...` and not a 404 |
| **Login fails with correct password** | The bcrypt hash for "password123" must be intact in DB. Re-import `pharmacy_db.sql` to reset |

---

## 🧪 Sample Data Included

- 2 branches (Main, City Center)
- 3 users (admin / pharmacist / cashier)
- 5 medicine categories
- 3 suppliers
- 8 sample medicines with realistic batches and expiry dates
- App settings (TZS currency, 18% VAT, pharmacy name)

Currency is set to **TZS (Tanzanian Shilling)** but easily changed in the `settings` table or `config/config.php`.

---

## 📝 License

Free to use and modify for educational, commercial, and personal projects.

---

## 🆘 Support

For issues, open an issue in the repository or contact the developer. Pull requests welcome.

**Built with ❤️ using PHP, MySQL, Bootstrap 5, jQuery, Chart.js**
