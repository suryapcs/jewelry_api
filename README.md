# Jwell Project – PHP/MySQL Backend

This is a **complete rewrite** of the original Node.js/MongoDB backend using **PHP 8+** and **MySQL**.  
All API routes, business logic, and JSON response shapes are preserved.

---

## 📁 Directory Structure

```
backend_php/
├── .env                      # Environment variables
├── .htaccess                 # Apache URL rewriting (same routes as Node.js)
├── create_admin.php          # CLI script to seed first admin
├── config/
│   ├── db.php                # PDO MySQL connection
│   └── env.php               # .env file loader
├── helpers/
│   ├── response.php          # sendResponse() / sendError() helpers
│   ├── cors.php              # CORS headers
│   └── auth_middleware.php   # Session-based admin auth
├── database/
│   └── schema.sql            # Full MySQL schema – run once
├── api/
│   ├── admin/
│   │   ├── create.php        # POST /api/admin/create
│   │   ├── login.php         # POST /api/admin/login
│   │   ├── logout.php        # POST /api/admin/logout
│   │   └── dashboard.php     # GET  /api/admin/dashboard
│   ├── customer/
│   │   ├── index.php         # GET /api/customer  |  POST /api/customer
│   │   ├── existing.php      # GET /api/customer/existing
│   │   ├── detail.php        # GET|PUT|DELETE /api/customer/:id
│   │   ├── restore.php       # PUT /api/customer/restore/:id
│   │   ├── restore_item.php  # PUT /api/customer/restore-item/:cId/:code
│   │   ├── item_detail.php   # GET /api/customer/:cId/item/:code
│   │   ├── close_loan.php    # POST /api/customer/customer/:cId/item/:code/close-loan
│   │   ├── next_item_code.php# GET /api/customer/next-item-code
│   │   ├── dashboard_stats.php # GET /api/customer/dashboard/stats
│   │   ├── monthly_count.php # GET /api/customer/monthly-customer-count
│   │   ├── upload_image.php  # POST /api/customer/upload/:id
│   │   └── invoice.php       # GET /api/customer/invoice/:cId/:code
│   ├── interests/
│   │   ├── index.php         # GET | POST /api/interests
│   │   ├── detail.php        # GET|PUT|DELETE /api/interests/detail?id=
│   │   ├── by_customer.php   # GET /api/interests/:customerId
│   │   └── summary.php       # GET /api/interests/summary/:cId/:code
│   ├── jewellery/
│   │   ├── add.php           # POST /api/jewellery/:customerId/add
│   │   └── summary.php       # GET  /api/jewellery/summary
│   └── stats/
│       ├── loan_by_item.php       # GET /api/stats/loan-by-item
│       ├── loan_gold_by_item.php  # GET /api/stats/loan-gold-by-item
│       ├── monthly_interest.php   # GET /api/stats/monthly-interest
│       └── item_distribution.php  # GET /api/stats/item-distribution
└── uploads/
    └── customers/            # Customer images stored here
```

---

## 🚀 Setup Instructions

### 1. Prerequisites
- **PHP 8.0+** with PDO and pdo_mysql extensions
- **MySQL 5.7+** or **MariaDB 10.3+**
- **Apache** with `mod_rewrite` enabled (or use `php -S`)

### 2. Create the Database
```sql
-- In MySQL Workbench or phpMyAdmin, run:
SOURCE backend_php/database/schema.sql;
```

### 3. Configure Environment
Edit `backend_php/.env`:
```
DB_HOST=localhost
DB_NAME=jwelleryshop
DB_USER=root
DB_PASS=your_mysql_password
CLIENT_URL=http://localhost:3000
```

### 4. Create First Admin
```bash
cd backend_php
php create_admin.php
```
Default credentials: `pcs@gmail.com` / `123456`

### 5. Run the Server

#### Option A – Apache (XAMPP/WAMP)
Place `backend_php/` in your Apache `htdocs/Jwell_project/` folder.  
Access at: `http://localhost/Jwell_project/backend_php/api/...`

#### Option B – PHP built-in server (development)
```bash
cd backend_php
php -S localhost:5000
```
Access at: `http://localhost:5000/api/...`

> **Note:** The built-in PHP server doesn't use `.htaccess`. You must pass the correct `?param=value` query strings manually, or use Apache.

### 6. PDF Invoice (Optional)
Install FPDF for PDF generation:
```bash
composer require setasign/fpdf
```
Without it, the invoice endpoint returns JSON with all fields needed for client-side PDF.

---

## 🔄 Route Mapping (Node.js → PHP)

| Original Node.js Route | PHP Endpoint |
|---|---|
| `POST /api/admin/create` | `api/admin/create.php` |
| `POST /api/admin/login` | `api/admin/login.php` |
| `POST /api/admin/logout` | `api/admin/logout.php` |
| `GET  /api/admin/dashboard` | `api/admin/dashboard.php` |
| `GET  /api/customer` | `api/customer/index.php` |
| `POST /api/customer` | `api/customer/index.php` |
| `GET  /api/customer/existing` | `api/customer/existing.php` |
| `GET  /api/customer/next-item-code` | `api/customer/next_item_code.php` |
| `GET  /api/customer/monthly-customer-count` | `api/customer/monthly_count.php` |
| `GET  /api/customer/dashboard/stats` | `api/customer/dashboard_stats.php` |
| `GET  /api/customer/:id` | `api/customer/detail.php?id=` |
| `PUT  /api/customer/:id` | `api/customer/detail.php?id=` |
| `DELETE /api/customer/:id` | `api/customer/detail.php?id=` |
| `PUT  /api/customer/restore/:id` | `api/customer/restore.php?id=` |
| `PUT  /api/customer/restore-item/:cId/:code` | `api/customer/restore_item.php` |
| `GET  /api/customer/:cId/item/:code` | `api/customer/item_detail.php` |
| `POST /api/customer/customer/:cId/item/:code/close-loan` | `api/customer/close_loan.php` |
| `GET  /api/customer/invoice/:cId/:code` | `api/customer/invoice.php` |
| `POST /api/customer/upload/:id` | `api/customer/upload_image.php?id=` |
| `POST /api/interests` | `api/interests/index.php` |
| `GET  /api/interests` | `api/interests/index.php` |
| `GET  /api/interests/:customerId` | `api/interests/by_customer.php` |
| `GET  /api/interests/single/:id` | `api/interests/detail.php?id=` |
| `PUT  /api/interests/:id` | `api/interests/detail.php?id=` |
| `DELETE /api/interests/:id` | `api/interests/detail.php?id=` |
| `GET  /api/interests/summary/:cId/:code` | `api/interests/summary.php` |
| `GET  /api/jewellery/summary` | `api/jewellery/summary.php` |
| `POST /api/jewellery/:cId/add` | `api/jewellery/add.php?customerId=` |
| `GET  /api/stats/loan-by-item` | `api/stats/loan_by_item.php` |
| `GET  /api/stats/loan-gold-by-item` | `api/stats/loan_gold_by_item.php` |
| `GET  /api/stats/monthly-interest` | `api/stats/monthly_interest.php` |
| `GET  /api/stats/item-distribution` | `api/stats/item_distribution.php` |
