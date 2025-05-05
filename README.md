PhonePe Payment Gateway Integration 🚀
A sleek PHP-based integration for PhonePe Payment Gateway 
Welcome to the PhonePe Payment Gateway Integration for e-commerce applications! This project provides a secure, robust, and user-friendly way to process online payments using PhonePe. Built for SAF Accessories, it supports both UAT (testing) and PROD (production) environments with a stylish receipt page powered by Tailwind CSS. 🎨

📑 Table of Contents

✨ Overview
🛠 Prerequisites
📥 Installation
⚙️ Configuration
🚀 Usage
👤 Collecting Customer Details
📂 File Structure
🗄 Database Schema
🔍 Troubleshooting
🤝 Contributing
📜 License


✨ Overview
This project empowers merchants to:

🔐 Authenticate with PhonePe using client credentials.
💸 Initiate payments with cart and customer details, redirecting users to PhonePe's checkout.
📄 Process responses, display a responsive receipt, and store orders in a MySQL database.
📋 Collect customer details (name, email, address) during payment initiation.

Designed for SAF Accessories, the integration features a modern receipt page with Tailwind CSS, ensuring a delightful user experience. 🌟

🛠 Prerequisites
Before you begin, ensure you have:

PHP: 7.4+ with curl and pdo_mysql extensions enabled.
MySQL: 5.7+ for storing order and customer data.
PhonePe Merchant Account: Client ID, Client Secret, and Client Version from PhonePe.
Web Server: Apache/Nginx with HTTPS enabled.
Tailwind CSS: Included via CDN (https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css).


📥 Installation
Follow these steps to set up the project:

Clone the Repository:
git clone https://github.com/your-repo/phonepe-payment-gateway.git
cd phonepe-payment-gateway


Set Up the Database:

Create a MySQL database (e.g., saf_accessories).
Set up the orders and customers tables (see Database Schema).


Copy Files:

Place config.php, initiate_payment.php, and payment_response.php in your web server’s root (e.g., /var/www/html).


Verify Dependencies:

No external PHP libraries required (uses native cURL and PDO).
Ensure Tailwind CSS CDN is accessible.




⚙️ Configuration
Update config.php with your PhonePe and database credentials:
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




Field
Description



PHONEPE_ENV
Set to UAT for testing or PROD for live.


PHONEPE_CLIENT_ID
Your PhonePe Client ID.


PHONEPE_CLIENT_SECRET
Your PhonePe Client Secret.


DB_NAME
Your MySQL database name.



🚀 Usage
1. Initiate Payment
Send a POST request to initiate_payment.php with:

merchantOrderId: Unique order ID (string).
amount: Payment amount in INR (float).
redirectUrl: Redirect URL after payment (string).
cart: Array of items (product_id, product_name, product_price, quantity).
customer: Array with customer details (name, email, address).

Example AJAX Call (from cart.php):
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

2. Process Payment Response

PhonePe redirects to payment_response.php after payment.
The script verifies the status, saves order details, and displays a styled receipt.

3. Print or Refund

Users can print the receipt or initiate a refund (if payment is completed) from the receipt page.


👤 Collecting Customer Details
Customer details (name, email, address) are collected in initiate_payment.php and passed to payment_response.php via session storage.
Updated initiate_payment.php
Handles customer POST data and stores it in $_SESSION['customer_data']:
$customer = isset($_POST['customer']) && is_array($_POST['customer']) ? $_POST['customer'] : [];
$customerDetails = [
    'name' => filter_var($customer['name'] ?? 'Guest User', FILTER_SANITIZE_STRING),
    'email' => filter_var($customer['email'] ?? 'N/A', FILTER_SANITIZE_EMAIL),
    'address' => filter_var($customer['address'] ?? 'N/A', FILTER_SANITIZE_STRING)
];
$_SESSION['customer_data'] = $customerDetails;

Updated payment_response.php
Retrieves customer details from $_SESSION['customer_data'] or falls back to database/guest details:
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

Session data is cleared after a successful payment:
if ($state === 'COMPLETED') {
    unset($_SESSION['customer_data']);
    // Other session variables
}


📂 File Structure
phonepe-payment-gateway/
├── 📜 config.php              # PhonePe and database config
├── 📜 initiate_payment.php    # Payment initiation logic
├── 📜 payment_response.php    # Payment response and receipt
├── 📂 assets/
│   └── 📂 images/
│       └── 🖼 logo.png        # SAF Accessories logo
├── 📜 index.php               # (Optional) Main page
├── 📜 cart.php                # (Optional) Cart page
└── 📜 README.md               # You're here!


🗄 Database Schema
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


🔍 Troubleshooting



Issue
Solution



Auth Token Failure
Verify Client ID/Secret in config.php. Check PHONEPE_ENV.


Payment Initiation Failure
Ensure cart and customer POST data are valid JSON.


Database Errors
Check DB credentials and table existence.


Redirect Issues
Use HTTPS for redirectUrl and ensure it’s accessible.


Styling Issues
Confirm Tailwind CSS CDN availability.



🤝 Contributing
We love contributions! To get started:

🍴 Fork the repository.
🌿 Create a feature branch (git checkout -b feature/YourFeature).
💾 Commit changes (git commit -m 'Add YourFeature').
🚀 Push to the branch (git push origin feature/YourFeature).
📬 Open a pull request.


📜 License
This project is licensed under the MIT License. Feel free to use, modify, and distribute! 🎉

Built with 💖 by SAF AccessoriesFor support, contact: shaktitripathi12298@gmail.com | 📞 +91 7310213636
