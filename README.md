# 🌟 PhonePe Payment Gateway Integration

*A Modern, Secure, and Stylish PHP Integration for PhonePe Payments*

Welcome to the **PhonePe PG Integration**, crafted with 💖 for **SAF Accessories**. Power your e-commerce platform with smooth payment flows and a beautifully tailored user experience.

---

## 📋 Table of Contents

* [🌈 Overview](#-overview)
* [🛠️ Prerequisites](#-prerequisites)
* [📦 Installation](#-installation)
* [⚙️ Configuration](#-configuration)
* [🚀 Usage](#-usage)
* [👤 Customer Details](#-customer-details)
* [📂 File Structure](#-file-structure)
* [🗃️ Database Schema](#-database-schema)
* [🔧 Troubleshooting](#-troubleshooting)
* [🤝 Contributing](#-contributing)
* [📜 License](#-license)

---

## 🌈 Overview

This PHP-based integration offers:
✅ **Secure Authentication** via PhonePe client credentials
✅ **Seamless Checkout Redirection**
✅ **Stylish Tailwind CSS Receipts**
✅ **MySQL-based Order & Customer Management**

> 🔁 Test in **UAT mode** before switching to live mode for error-free performance!

---

## 🛠️ Prerequisites

| Requirement        | Details                       |
| ------------------ | ----------------------------- |
| 🐘 PHP             | 7.4+ with `curl`, `pdo_mysql` |
| 🗄️ MySQL          | Version 5.7+                  |
| 🔐 PhonePe Account | Client ID, Secret, Version    |
| 🌐 Web Server      | Apache/Nginx with HTTPS       |
| 🎨 Tailwind CSS    | Included via CDN              |

---

## 📦 Installation

### 🧾 Step-by-Step

#### 1. Clone the Repository

```bash
git clone https://github.com/your-repo/phonepe-payment-gateway.git  
cd phonepe-payment-gateway
```

#### 2. Database Setup

Create a MySQL DB:

```sql
CREATE DATABASE saf_accessories;
```

Use schema below for `orders` and `customers`.

#### 3. Copy Files

Place the following in your server root (`/var/www/html/`):

* `config.php`
* `initiate_payment.php`
* `payment_response.php`

#### 4. Verify Environment

* PHP with required extensions
* HTTPS enabled
* Tailwind CDN accessible

✅ Setup complete!

---

## ⚙️ Configuration

Update `config.php`:

```php
define('PHONEPE_ENV', 'UAT'); // UAT or PROD  
define('PHONEPE_CLIENT_ID', 'your_client_id');  
define('PHONEPE_CLIENT_SECRET', 'your_client_secret');  
define('PHONEPE_CLIENT_VERSION', '1');  
define('DB_HOST', 'localhost');  
define('DB_NAME', 'saf_accessories');  
define('DB_USER', 'db_user');  
define('DB_PASS', 'db_password');
```

---

## 🚀 Usage

### 🔁 1. Initiate Payment

Send a POST request to `initiate_payment.php` with:

```javascript
$.ajax({
  url: 'initiate_payment.php',
  type: 'POST',
  data: {
    merchantOrderId: 'ORDER_123',
    amount: 599.00,
    redirectUrl: 'https://your-site.com/payment_response.php',
    cart: [
      { product_id: 'P1', product_name: 'Necklace', product_price: 499, quantity: 1 }
    ],
    customer: {
      name: 'John Doe',
      email: 'john@example.com',
      address: '123 Main St, City'
    }
  },
  success: function(response) {
    if (response.success) {
      window.location.href = response.redirectUrl;
    } else {
      alert(response.message);
    }
  }
});
```

### 🧾 2. Handle Response

PhonePe redirects to `payment_response.php`:

* Verifies status
* Stores order data
* Shows a **Tailwind-styled** receipt

---

## 👤 Customer Details

Handled via sessions for secure & temporary storage.

```php
$customerDetails = [
  'name' => filter_var($_POST['customer']['name'], FILTER_SANITIZE_STRING),
  'email' => filter_var($_POST['customer']['email'], FILTER_SANITIZE_EMAIL),
  'address' => filter_var($_POST['customer']['address'], FILTER_SANITIZE_STRING)
];
$_SESSION['customer_data'] = $customerDetails;
```

Session is cleared after successful payment.

---

## 📂 File Structure

```bash
phonepe-payment-gateway/
├── config.php               # 🔧 Config file  
├── initiate_payment.php     # 💸 Payment Initiation  
├── payment_response.php     # 📄 Payment Verification & Receipt  
├── assets/
│   └── images/
│       └── logo.png         # SAF Logo  
├── index.php                # 🏠 Optional homepage  
├── cart.php                 # 🛒 Optional cart page  
└── README.md                # 📖 This documentation  
```

---

## 🗃️ Database Schema

### 📦 Orders Table (Auto-created)

```sql
CREATE TABLE orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT DEFAULT NULL,
  product_id VARCHAR(255),
  product_name VARCHAR(255),
  product_price DECIMAL(10,2),
  quantity INT,
  total_price DECIMAL(10,2),
  shipping DECIMAL(10,2),
  grand_total DECIMAL(10,2),
  order_id VARCHAR(255),
  order_status VARCHAR(50),
  order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  transaction_status VARCHAR(50),
  payment_amount DECIMAL(10,2),
  payment_mode VARCHAR(50),
  payment_id VARCHAR(255)
);
```

### 👥 Customers Table

```sql
CREATE TABLE customers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  fullname VARCHAR(255),
  email VARCHAR(255),
  address TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

## 🔧 Troubleshooting

| Issue                   | Solution                                 |
| ----------------------- | ---------------------------------------- |
| 🔐 Auth Error           | Check PhonePe credentials & environment  |
| 💳 Payment Failure      | Verify POST data (cart & customer)       |
| 🗄️ DB Connection Error | Check DB config and table existence      |
| 🌐 Redirect Issue       | Ensure redirect URL is HTTPS & reachable |
| 🎨 Styling Issue        | Confirm Tailwind CDN is loading          |

> 💡 **Enable PHP error reporting** for real-time debugging.

---

## 🤝 Contributing

We welcome contributions:

```bash
# Fork → Branch → Commit → PR 🎉
git checkout -b feature/YourFeature
git commit -m "Add YourFeature"
git push origin feature/YourFeature
```

---

## 📜 License

MIT License. Use freely, modify openly, and share proudly.

---

*Crafted with ❤️ by Shakti Narayan Tripathi*
📧 [shaktitripathi12298@gmail.com](mailto:shaktitripathi12298@gmail.com)
📞 +91 7310213636

---
