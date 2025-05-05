🌟 PhonePe Payment Gateway Integration
A Modern, Secure, and Stylish PHP Integration for PhonePe Payments  

Welcome to the PhonePe Payment Gateway Integration! This PHP-based solution empowers e-commerce platforms with seamless payment processing, built specifically for SAF Accessories. With a sleek Tailwind CSS-powered receipt page, it supports both UAT and PROD environments, ensuring a delightful user experience. 🚀

Note: Ready to elevate your payment system? Let’s dive in! 🎉


📋 Table of Contents

🌈 Overview
🛠️ Prerequisites
📦 Installation
⚙️ Configuration
🚀 Usage
👤 Customer Details
📂 File Structure
🗃️ Database Schema
🔧 Troubleshooting
🤝 Contributing
📜 License


🌈 Overview
This integration is your one-stop solution for:

🔒 Secure Authentication: Connect with PhonePe using client credentials.
💳 Payment Initiation: Redirect users to PhonePe’s checkout with cart and customer data.
📜 Responsive Receipts: Display stylish payment receipts with Tailwind CSS.
🗄️ Order Management: Store orders and customer details in a MySQL database.

Designed for SAF Accessories, it combines functionality with aesthetics, making payments smooth and visually appealing. ✨

Tip: Test in UAT mode before going live to ensure everything works perfectly! 🧪


🛠️ Prerequisites
Ensure you have the following ready:



Requirement
Details



🐘 PHP
7.4+ with curl and pdo_mysql extensions


🗄️ MySQL
5.7+ for database storage


🔑 PhonePe Account
Client ID, Secret, and Version from PhonePe


🌐 Web Server
Apache/Nginx with HTTPS enabled


🎨 Tailwind CSS
Included via CDN



📦 Installation
Let’s get started with a seamless setup! 🛠️
Progress: [██████████ 100%]

Clone the Repository:
git clone https://github.com/your-repo/phonepe-payment-gateway.git
cd phonepe-payment-gateway

[█████ 50%] Cloning complete

Set Up the Database:

Create a MySQL database (e.g., saf_accessories).
Configure the orders and customers tables (see Database Schema).[███████ 75%] Database ready


Copy Files:

Place config.php, initiate_payment.php, and payment_response.php in your web server’s root (e.g., /var/www/html).[█████████ 90%] Files copied


Verify Dependencies:

No external PHP libraries needed (uses native cURL and PDO).
Ensure Tailwind CSS CDN is accessible.[██████████ 100%] Setup complete




Warning: Ensure HTTPS is enabled on your server to avoid redirect issues! 🔐


⚙️ Configuration
Customize config.php with your credentials:
<?php
// PhonePe Configuration
define('PHONEPE_ENV', 'UAT'); // 'UAT' for testing, 'PROD' for production
define('PHONEPE_CLIENT_ID', 'your_client_id'); // Your Client ID
define('PHONEPE_CLIENT_SECRET', 'your_client_secret'); // Your Client Secret
define('PHONEPE_CLIENT_VERSION', '1'); // Client Version (1 for UAT)
define('DB_HOST', 'localhost');
define('DB_NAME', 'saf_accessories'); // Your database name
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
?>

Configuration Breakdown



Field
Description
Example



PHONEPE_ENV
Environment mode
UAT or PROD


PHONEPE_CLIENT_ID
PhonePe Client ID
your_client_id


PHONEPE_CLIENT_SECRET
PhonePe Client Secret
your_client_secret


DB_NAME
MySQL database name
saf_accessories



🚀 Usage
1. Initiate Payment 💸
Send a POST request to initiate_payment.php with:

merchantOrderId: Unique order ID (string).
amount: Amount in INR (float).
redirectUrl: Post-payment redirect URL (string).
cart: Array of items (product_id, product_name, product_price, quantity).
customer: Array of customer details (name, email, address).

Example AJAX Call:
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

2. Process Payment Response 📄

PhonePe redirects to payment_response.php.
The script verifies payment status, saves data, and displays a gorgeous receipt.

3. User Actions 🖨️

Print Receipt: Save or print the receipt.
Initiate Refund: Available for completed payments.


Note: Ensure your redirectUrl is HTTPS and matches your domain! 🌐


👤 Customer Details
Customer details are collected in initiate_payment.php and passed to payment_response.php via session.
In initiate_payment.php

Captures customer POST data (name, email, address).
Sanitizes and stores in $_SESSION['customer_data']:

$customer = isset($_POST['customer']) && is_array($_POST['customer']) ? $_POST['customer'] : [];
$customerDetails = [
    'name' => filter_var($customer['name'] ?? 'Guest User', FILTER_SANITIZE_STRING),
    'email' => filter_var($customer['email'] ?? 'N/A', FILTER_SANITIZE_EMAIL),
    'address' => filter_var($customer['address'] ?? 'N/A', FILTER_SANITIZE_STRING)
];
$_SESSION['customer_data'] = $customerDetails;

In payment_response.php

Retrieves from $_SESSION['customer_data'] or falls back to database/guest details:

$customerDetails = $_SESSION['customer_data'] ?? null;
if ($customerDetails) {
    $userDetails = [
        'id' => null,
        'name' => $customerDetails['name'],
        'email' => $customerDetails['email'],
        'address' => $customerDetails['address']
    ];
} elseif (isset($_SESSION['user_id'])) {
    // Fetch from customers table
} else {
    $userDetails = [
        'id' => null,
        'name' => 'Guest User',
        'email' => 'N/A',
        'address' => 'N/A'
    ];
}


Clears session data after successful payment:

if ($state === 'COMPLETED') {
    unset($_SESSION['customer_data']);
    // Other session variables
}


📂 File Structure
phonepe-payment-gateway/
├── 📜 config.php              # 🔧 PhonePe and DB config
├── 📜 initiate_payment.php    # 💳 Payment initiation
├── 📜 payment_response.php    # 📄 Payment response & receipt
├── 📂 assets/
│   └── 📂 images/
│       └── 🖼️ logo.png       # SAF Accessories logo
├── 📜 index.php               # 🏠 (Optional) Main page
├── 📜 cart.php                # 🛒 (Optional) Cart page
└── 📜 README.md               # 📖 This file!


🗃️ Database Schema
Orders Table
Auto-created by payment_response.php:
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    product_id VARCHAR(255) DEFAULT NULL,
    product_name VARCHAR(255) DEFAULT NULL,
    product_price DECIMAL(10,2) DEFAULT NULL,
    quantity INT DEFAULT NULL,
    total_price DECIMAL(10,2) DEFAULT NULL,
    shipping DECIMAL(10,2) DEFAULT NULL,
    grand_total DECIMAL(10,2) DEFAULT NULL,
    order_id VARCHAR(255) NOT NULL,
    order_status VARCHAR(50) NOT NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    customer_ip VARCHAR(45) DEFAULT NULL,
    customer_isp VARCHAR(255) DEFAULT NULL,
    transaction_status VARCHAR(50) DEFAULT NULL,
    payment_amount DECIMAL(10,2) DEFAULT NULL,
    payment_mode VARCHAR(50) DEFAULT NULL,
    payment_id VARCHAR(255) DEFAULT NULL,
    user_ip VARCHAR(45) DEFAULT NULL,
    isp VARCHAR(255) DEFAULT NULL
);

Customers Table
Create manually:
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    address TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


🔧 Troubleshooting



Issue
Solution



🔐 Auth Token Failure
Verify Client ID/Secret and PHONEPE_ENV in config.php.


💳 Payment Failure
Check cart and customer POST data for valid JSON.


🗄️ Database Errors
Confirm DB credentials and table existence.


🌐 Redirect Issues
Ensure redirectUrl is HTTPS and accessible.


🎨 Styling Issues
Verify Tailwind CSS CDN connectivity.



Tip: Enable PHP error logging to debug issues faster! 🐞


🤝 Contributing
We’re thrilled to welcome contributions! 🙌

🍴 Fork the repository.
🌿 Create a feature branch: git checkout -b feature/YourFeature.
💾 Commit changes: git commit -m 'Add YourFeature'.
🚀 Push to the branch: git push origin feature/YourFeature.
📬 Open a pull request.


📜 License
This project is licensed under the MIT License. Use, modify, and share freely! 🎉

Crafted with 💖 by Shakti Narayan Tripathi 📧 shaktitripathi12298@gmail.com | 📞 +91 7310213636 Happy Coding! 🌟
