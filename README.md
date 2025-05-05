PhonePe Payment Gateway Integration
This project provides a PHP-based integration for the PhonePe Payment Gateway, enabling secure online payments for e-commerce applications. It includes functionality to initiate payments, process payment responses, and store order details in a MySQL database. The integration supports both UAT (testing) and PROD (production) environments.
Table of Contents

Overview
Prerequisites
Installation
Configuration
Usage
Collecting Customer Details
File Structure
Database Schema
Troubleshooting
Contributing
License

Overview
The PhonePe Payment Gateway integration allows merchants to:

Authenticate with PhonePe using client credentials.
Initiate payments with cart details and redirect users to the PhonePe checkout page.
Process payment responses, display receipts, and store order details.
Collect customer details (name, email, address) during payment initiation and pass them to the response page.

The integration is built for SAF Accessories, an online retailer, and includes a responsive receipt page with Tailwind CSS styling.
Prerequisites

PHP: Version 7.4 or higher with curl and pdo_mysql extensions enabled.
MySQL: Version 5.7 or higher.
PhonePe Merchant Account: Obtain Client ID, Client Secret, and Client Version from PhonePe.
Web Server: Apache or Nginx with HTTPS configured.
Composer: Optional for dependency management (not used in this project).
Tailwind CSS: Included via CDN for styling the receipt page.

Installation

Clone the Repository:
git clone https://github.com/your-repo/phonepe-payment-gateway.git
cd phonepe-payment-gateway


Set Up the Database:

Create a MySQL database (e.g., saf_accessories).
Ensure the orders and customers tables are set up (see Database Schema).


Copy Files:

Place config.php, initiate_payment.php, and payment_response.php in your web server's root directory (e.g., /var/www/html).


Install Dependencies:

No external PHP dependencies are required, as the project uses native PHP cURL and PDO.
Ensure Tailwind CSS CDN is accessible (https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css).



Configuration
Edit config.php to set up your PhonePe and database credentials:
<?php
// PhonePe Configuration
define('PHONEPE_ENV', 'UAT'); // Set to 'UAT' for testing, 'PROD' for production
define('PHONEPE_CLIENT_ID', 'your_client_id'); // Replace with your Client ID
define('PHONEPE_CLIENT_SECRET', 'your_client_secret'); // Replace with your Client Secret
define('PHONEPE_CLIENT_VERSION', '1'); // Replace with your Client Version (1 for UAT)
define('DB_HOST', 'localhost');
define('DB_NAME', 'saf_accessories'); // Replace with your database name
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
?>


PHONEPE_ENV: Use UAT for testing or PROD for production.
Client Credentials: Obtain from PhonePe's merchant dashboard.
Database Credentials: Ensure they match your MySQL setup.

Usage

Initiate Payment:

Send a POST request to initiate_payment.php with the following parameters:
merchantOrderId: Unique order ID (string).
amount: Payment amount in INR (float).
redirectUrl: URL to redirect after payment (string).
cart: Array of cart items (product_id, product_name, product_price, quantity).
customer: Array with customer details (name, email, address).



Example AJAX call from cart.php:
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


Process Payment Response:

After payment, PhonePe redirects to payment_response.php.
The script verifies the payment status, saves order details, and displays a receipt.
The receipt includes customer details, order items, payment status, and company information.


Print or Refund:

Users can print the receipt or initiate a refund (if payment is completed) from the receipt page.



Collecting Customer Details
To collect customer details in initiate_payment.php and pass them to payment_response.php, the following changes are implemented:
Modifications in initiate_payment.php

Collect Customer Data:

Added handling for customer POST parameter (name, email, address).
Sanitized and validated customer details.
Stored customer details in the session for use in payment_response.php.


Updated Code (replace the existing initiate_payment.php POST processing section):
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $merchantOrderId = filter_input(INPUT_POST, 'merchantOrderId', FILTER_SANITIZE_STRING) ?? '';
    $amount = (int)(floatval(filter_input(INPUT_POST, 'amount', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) ?? 0) * 100);
    $redirectUrl = filter_input(INPUT_POST, 'redirectUrl', FILTER_SANITIZE_URL) ?? '';
    $cartItems = isset($_POST['cart']) && is_array($_POST['cart']) ? $_POST['cart'] : [];
    $customer = isset($_POST['customer']) && is_array($_POST['customer']) ? $_POST['customer'] : [];

    // Validate required fields
    if (empty($merchantOrderId) || $amount <= 0 || empty($redirectUrl)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Missing or invalid required fields']);
        exit;
    }

    // Validate and sanitize customer details
    $customerDetails = [
        'name' => filter_var($customer['name'] ?? 'Guest User', FILTER_SANITIZE_STRING),
        'email' => filter_var($customer['email'] ?? 'N/A', FILTER_SANITIZE_EMAIL),
        'address' => filter_var($customer['address'] ?? 'N/A', FILTER_SANITIZE_STRING)
    ];
    if (!$customerDetails['email'] || !filter_var($customerDetails['email'], FILTER_VALIDATE_EMAIL)) {
        $customerDetails['email'] = 'N/A';
    }

    // Validate and sanitize cart items
    $sanitizedCart = [];
    if (!empty($cartItems)) {
        foreach ($cartItems as $item) {
            $product_id = filter_var($item['product_id'] ?? '', FILTER_SANITIZE_STRING);
            $product_name = filter_var($item['product_name'] ?? '', FILTER_SANITIZE_STRING);
            $product_price = floatval($item['product_price'] ?? 0);
            $quantity = intval($item['quantity'] ?? 0);
            if ($product_id && $product_name && $product_price > 0 && $quantity > 0) {
                $sanitizedCart[] = [
                    'product_id' => $product_id,
                    'product_name' => $product_name,
                    'product_price' => $product_price,
                    'quantity' => $quantity
                ];
            }
        }
    }

    // Verify amount matches cart total
    $calculatedTotal = 0;
    foreach ($sanitizedCart as $item) {
        $calculatedTotal += $item['product_price'] * $item['quantity'];
    }
    $shipping = ($calculatedTotal > 499) ? 0 : 1;
    $calculatedTotal += $shipping;
    if (abs($calculatedTotal * 100 - $amount) > 1) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Amount does not match cart total']);
        exit;
    }

    // Store cart, customer, and order details in session
    $_SESSION['cart_data'] = $sanitizedCart;
    $_SESSION['customer_data'] = $customerDetails;
    $_SESSION['merchantOrderId'] = $merchantOrderId;
    $_SESSION['order_amount'] = $amount;

    // Prepare payment request
    $payUrl = PHONEPE_BASE_URLS[PHONEPE_ENV]['pay'];
    $payHeaders = [
        'Content-Type: application/json',
        'Authorization: O-Bearer ' . $_SESSION['access_token'],
    ];
    $payData = [
        'merchantOrderId' => $merchantOrderId,
        'amount' => $amount,
        'expireAfter' => 1200,
        'paymentFlow' => [
            'type' => 'PG_CHECKOUT',
            'message' => 'Payment for order ' . $merchantOrderId,
            'merchantUrls' => [
                'redirectUrl' => $redirectUrl,
            ],
        ],
        'additionalData' => [
            'cart' => $sanitizedCart,
            'customer' => $customerDetails
        ]
    ];

    $payResponse = makeCurlRequest($payUrl, 'POST', $payHeaders, json_encode($payData));
    $payDataResponse = json_decode($payResponse['response'], true);

    if ($payResponse['httpCode'] != 200 || !isset($payDataResponse['redirectUrl'])) {
        header('Content-Type: application/json');
        $errorMessage = isset($payDataResponse['message']) ? $payDataResponse['message'] : 'Unknown error';
        echo json_encode(['success' => false, 'message' => 'Failed to create payment: ' . $errorMessage]);
        exit;
    }

    $_SESSION['orderId'] = $payDataResponse['orderId'];
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'redirectUrl' => $payDataResponse['redirectUrl']
    ]);
    exit;
}



Modifications in payment_response.php

Retrieve Customer Data:

Fetch customer details from $_SESSION['customer_data'] if available.
Fall back to database or guest details if session data is missing.


Updated Code (replace the customer details section in payment_response.php):
// Retrieve cart and user data
$cartItems = $_SESSION['cart_data'] ?? [];
$customerDetails = $_SESSION['customer_data'] ?? null;

// Check if user is logged in or use session customer details
try {
    if ($customerDetails) {
        // Use customer details from session
        $userDetails = [
            'id' => null,
            'name' => $customerDetails['name'],
            'email' => $customerDetails['email'],
            'address' => $customerDetails['address']
        ];
    } elseif (isset($_SESSION['user_id'])) {
        // Fetch customer details from the customers table
        $stmt = $pdo->prepare("
            SELECT id, fullname, email, address
            FROM customers
            WHERE id = :user_id
        ");
        $stmt->execute([':user_id' => $_SESSION['user_id']]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        $userDetails = $customer ? [
            'id' => $customer['id'],
            'name' => $customer['fullname'],
            'email' => $customer['email'],
            'address' => $customer['address']
        ] : [
            'id' => null,
            'name' => 'Guest User',
            'email' => 'N/A',
            'address' => 'N/A'
        ];
    } else {
        // Fallback to guest details
        $userDetails = [
            'id' => null,
            'name' => 'Guest User',
            'email' => 'N/A',
            'address' => 'N/A'
        ];
    }
} catch (PDOException $e) {
    error_log("Failed to fetch customer details: " . $e->getMessage());
    $userDetails = [
        'id' => null,
        'name' => 'Guest User',
        'email' => 'N/A',
        'address' => 'N/A'
    ];
}


Clear Session Data:

Ensure customer data is cleared when the payment is completed:

if ($state === 'COMPLETED') {
    unset($_SESSION['cart']);
    unset($_SESSION['cart_data']);
    unset($_SESSION['customer_data']);
    unset($_SESSION['merchantOrderId']);
    unset($_SESSION['orderId']);
    unset($_SESSION['order_amount']);
    unset($_SESSION['access_token']);
    unset($_SESSION['expires_at']);
}



File Structure
phonepe-payment-gateway/
├── config.php              # Configuration for PhonePe and database
├── initiate_payment.php    # Initiates payment and redirects to PhonePe
├── payment_response.php    # Processes payment response and displays receipt
├── assets/
│   └── images/
│       └── logo.png        # Company logo
├── index.php               # (Optional) Main page or cart page
├── cart.php                # (Optional) Cart page for initiating payment
└── README.md               # Project documentation

Database Schema
The integration uses two tables: orders and customers.
Orders Table
Created automatically by payment_response.php:
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
Create manually or via your application:
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    address TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

Troubleshooting

Auth Token Failure: Verify Client ID, Client Secret, and Client Version in config.php.
Payment Initiation Failure: Ensure cart and customer data are correctly formatted in the POST request.
Database Errors: Check database credentials and ensure the orders and customers tables exist.
Redirect Issues: Confirm redirectUrl is HTTPS and accessible.
Styling Issues: Ensure the Tailwind CSS CDN is accessible.

Contributing
Contributions are welcome! Please:

Fork the repository.
Create a feature branch (git checkout -b feature/YourFeature).
Commit changes (git commit -m 'Add YourFeature').
Push to the branch (git push origin feature/YourFeature).
Open a pull request.

License
This project is licensed under the MIT License. See the LICENSE file for details.
