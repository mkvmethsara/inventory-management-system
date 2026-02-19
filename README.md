# 📦 Inventory Management System (IMS)
QR / RFID Based Warehouse Inventory System – PHP & MySQL

This is a web-based Inventory Management System developed as a campus project.
The system is designed to manage warehouse stock using batch & expiry tracking
and QR / RFID scanning.

---

## 📌 Project Overview

This system allows warehouse staff and administrators to:

- manage items and suppliers
- track stock by location
- maintain batch and expiry information
- record all stock movements
- update stock automatically using QR / RFID scanning

---

## 👨‍💻 Our Team

1. Methsara
2. Vishwa
3. Chamod
4. Bineth
5. Chamindu

---

## ⚙️ Tech Stack

- Backend : PHP
- Database : MySQL
- Frontend : HTML, CSS, JavaScript
- QR / RFID : Browser based camera scanner
- Version Control : GitHub

---

## 🧩 Main System Modules

- Admin login
- Staff dashboard
- Item management
- Supplier management
- Location management
- Batch & expiry management
- Stock by location
- Receive stock (IN)
- Move stock (TRANSFER / OUT)
- QR / RFID scan page
- Stock transaction history

---

## 📁 Project Structure
```
inventory-management-system
│
├── admin/
├── actions/
├── assets/
├── config/
├── includes/
├── project/
│ ├── move-stock.html
│ ├── receive-stock.html
│ ├── recent-activity.html
│ ├── stock-lookup.html
│ └── user.html
│
├── index.php
├── staff_dashboard.php
├── move-stock.php
├── receive-stock.php
├── stock-lookup.php
├── transactions.php
├── user-login.php
├── database.sql
└── README.md
```

---

## 🗄️ Database Tables

- suppliers
- items
- users
- locations
- item_batches
- stock
- stock_transactions

---

## 🔗 ER Relationship Summary

- One supplier → many items
- One item → many batches
- One batch → many locations (via stock table)
- Each transaction is linked to:
  - item
  - batch
  - location
  - user

---

## 📷 QR / RFID Scan Workflow

1. User opens scan page
2. QR / RFID value is scanned
3. Item and batch are identified
4. Stock table is updated
5. Transaction is recorded in stock_transactions

---

## 🔐 Login System

- Admin and staff users login using `user-login.php`
- After login users are redirected to the staff dashboard

---

## 🧪 Database Setup

1. Create database
2. Import
3. Update database connection inside

---

## ▶️ How to Run Locally

1. Install XAMPP
2. Copy project folder to
3. Start Apache & MySQL
4. Open browser



---

## 🌐 Hosting Requirements

- PHP hosting with MySQL
- HTTPS enabled (required for camera based QR scanning)

---

## 🧠 Key Features

- Batch based inventory tracking
- Expiry date handling
- Location wise stock control
- Automatic transaction logging
- QR / RFID based data entry
- Stock transfer between locations

---

## 🎓 Project Type

Academic – Campus Project

---

## 📄 License

For educational use only.
