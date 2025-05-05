<?php
session_start();
require_once 'config.php';

function makeCurlRequest($url, $method, $headers, $data = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['response' => $response, 'httpCode' => $httpCode];
}

// Create orders table if it doesn't exist
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $createTableQuery = "
        CREATE TABLE IF NOT EXISTS orders (
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
        )";
    $pdo->exec($createTableQuery);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

if (!isset($_SESSION['merchantOrderId']) || !isset($_SESSION['access_token'])) {
    header('Location: ../cart.php');
    exit;
}

// Retrieve cart and user data
$cartItems = $_SESSION['cart_data'] ?? [];

// Check if user is logged in and fetch customer details
try {
    if (isset($_SESSION['user_id'])) {
        // Fetch customer details from the customers table
        $stmt = $pdo->prepare("
            SELECT id, fullname, email, address
            FROM customers
            WHERE id = :user_id
        ");
        $stmt->execute([':user_id' => $_SESSION['user_id']]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($customer) {
            // User found, set user details
            $userDetails = [
                'id' => $customer['id'],
                'name' => $customer['fullname'],
                'email' => $customer['email'],
                'address' => $customer['address']
            ];
        } else {
            // User not found in database, fallback to guest
            $userDetails = [
                'id' => null,
                'name' => 'Guest User',
                'email' => 'N/A',
                'phone' => 'N/A'
            ];
        }
    } else {
        // User not logged in, use guest details
        $userDetails = [
            'id' => null,
            'name' => 'Guest User',
            'email' => 'N/A',
            'phone' => 'N/A'
        ];
    }
} catch (PDOException $e) {
    error_log("Failed to fetch customer details: " . $e->getMessage());
    // Fallback to guest details
    $userDetails = [
        'id' => null,
        'name' => 'Guest User',
        'email' => 'N/A',
        'phone' => 'N/A'
    ];
}

// Company details
$companyDetails = [
    'name' => 'SAF Accessories',
    'address' => 'Dubagga , Lucknow ',
    'info' => 'SAF Accessories is a leading online retailer specializing in high-quality fashion and lifestyle accessories. Established in 2020, we are committed to providing our customers with trendy, affordable products and exceptional customer service.',
    'contact_email' => 'support@safaccessories.in',
    'contact_phone' => '+91 9118646999'
];

// Get customer IP and ISP
$customerIp = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
$customerIsp = gethostbyaddr($customerIp) ?: 'Unknown';

// Fetch payment status
$statusUrl = PHONEPE_BASE_URLS[PHONEPE_ENV]['status'] . $_SESSION['merchantOrderId'] . '/status?details=false';
$statusHeaders = [
    'Content-Type: application/json',
    'Authorization: O-Bearer ' . $_SESSION['access_token'],
];

$statusResponse = makeCurlRequest($statusUrl, 'GET', $statusHeaders);
$statusData = json_decode($statusResponse['response'], true);

if ($statusResponse['httpCode'] != 200) {
    $errorMessage = $statusData['message'] ?? 'Failed to fetch order status';
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Payment Error - <?php echo htmlspecialchars($companyDetails['name']); ?></title>
        <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
        <style>
            .animate-pulse { animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
            @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
            .gradient-bg { background: linear-gradient(135deg, #1e3a8a, #3b82f6); }
        </style>
    </head>
    <body class="gradient-bg flex items-center justify-center min-h-screen">
        <div class="bg-white p-10 rounded-2xl shadow-2xl max-w-md w-full text-center transform transition-all hover:scale-105">
            <img src="../assets/images/logo.png" alt="<?php echo htmlspecialchars($companyDetails['name']); ?> Logo" class="mx-auto mb-6 w-36 animate-pulse">
            <h2 class="text-3xl font-extrabold text-gray-800 mb-4">Payment Error</h2>
            <p class="text-red-600 mb-6 font-medium"><?php echo htmlspecialchars($errorMessage); ?></p>
            <a href="../index.php" class="inline-block bg-blue-600 text-white px-8 py-3 rounded-full hover:bg-blue-700 transition transform hover:scale-110">Back to Home</a>
            <p class="mt-4 text-sm text-gray-600">Contact us at <?php echo htmlspecialchars($companyDetails['contact_email']); ?></p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$state = $statusData['state'] ?? 'UNKNOWN';
$transactionStatus = $state === 'COMPLETED' ? 'SUCCESS' : ($state === 'FAILED' ? 'FAILED' : 'PENDING');
$paymentMode = $statusData['paymentMode'] ?? 'UPI'; // Default to UPI if not provided
$paymentId = $statusData['transactionId'] ?? $_SESSION['orderId'];

// Save order data to database
try {
    // Calculate totals for the order
    $totalPrice = 0;
    foreach ($cartItems as $item) {
        $totalPrice += $item['product_price'] * $item['quantity'];
    }
    $shipping = ($totalPrice > 499) ? 0 : 1;
    $grandTotal = $totalPrice + $shipping;

    // Insert each cart item as a separate order entry (to match table structure)
    foreach ($cartItems as $item) {
        $insertQuery = "
            INSERT INTO orders (
                user_id, product_id, product_name, product_price, quantity, total_price, shipping, grand_total,
                order_id, order_status, customer_ip, customer_isp, transaction_status, payment_amount,
                payment_mode, payment_id, user_ip, isp
            ) VALUES (
                :user_id, :product_id, :product_name, :product_price, :quantity, :total_price, :shipping, :grand_total,
                :order_id, :order_status, :customer_ip, :customer_isp, :transaction_status, :payment_amount,
                :payment_mode, :payment_id, :user_ip, :isp
            )";
        
        $stmt = $pdo->prepare($insertQuery);
        $stmt->execute([
            ':user_id' => $userDetails['id'],
            ':product_id' => $item['product_id'],
            ':product_name' => $item['product_name'],
            ':product_price' => $item['product_price'],
            ':quantity' => $item['quantity'],
            ':total_price' => $item['product_price'] * $item['quantity'],
            ':shipping' => $shipping,
            ':grand_total' => $grandTotal,
            ':order_id' => $statusData['orderId'],
            ':order_status' => $state,
            ':customer_ip' => $customerIp,
            ':customer_isp' => $customerIsp,
            ':transaction_status' => $transactionStatus,
            ':payment_amount' => $statusData['amount'] / 100,
            ':payment_mode' => $paymentMode,
            ':payment_id' => $paymentId,
            ':user_ip' => $customerIp,
            ':isp' => $customerIsp
        ]);
    }
} catch (PDOException $e) {
    error_log("Failed to save order: " . $e->getMessage());
    // Continue with displaying the receipt, as DB error shouldn't block user feedback
}

// Clear session data if payment is completed
if ($state === 'COMPLETED') {
    unset($_SESSION['cart']);
    unset($_SESSION['cart_data']);
    unset($_SESSION['merchantOrderId']);
    unset($_SESSION['orderId']);
    unset($_SESSION['order_amount']);
    unset($_SESSION['access_token']);
    unset($_SESSION['expires_at']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt - <?php echo htmlspecialchars($companyDetails['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        .gradient-bg { background: linear-gradient(135deg, #1e3a8a, #3b82f6); }
        .card-hover { transition: transform 0.3s ease, box-shadow 0.3s ease; }
        .card-hover:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2); }
        .btn-anim { transition: all 0.3s ease; }
        .btn-anim:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3); }
        .fade-in { animation: fadeIn 1s ease-in; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .status-success { color: #10b981; }
        .status-failed { color: #ef4444; }
        @media print {
            body { background: #fff; margin: 0; }
            .receipt { box-shadow: none; border: none; padding: 1rem; }
            .no-print { display: none; }
            .logo { max-width: 100px; }
            table { font-size: 12px; }
            .terms { font-size: 10px; }
            .gradient-bg { background: #fff; }
            .card-hover { transform: none; box-shadow: none; }
        }
    </style>
</head>
<body class="gradient-bg flex items-center justify-center min-h-screen py-12">
    <div class="receipt bg-white p-10 rounded-2xl shadow-2xl max-w-4xl w-full fade-in">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <img src="https://safaccessories.in/admin/assets/images/logo.png" alt="<?php echo htmlspecialchars($companyDetails['name']); ?> Logo" class="logo w-40 card-hover">
            <div class="text-right">
                <h2 class="text-4xl font-extrabold text-gray-900"><?php echo htmlspecialchars($companyDetails['name']); ?></h2>
                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($companyDetails['address']); ?></p>
            </div>
        </div>

        <!-- Company Info -->
        <div class="mb-8 bg-gray-50 p-6 rounded-xl card-hover">
            <h3 class="text-xl font-semibold text-gray-800 mb-3">About Us</h3>
            <p class="text-gray-600"><?php echo htmlspecialchars($companyDetails['info']); ?></p>
            <p class="text-gray-600 mt-2">Email: <?php echo htmlspecialchars($companyDetails['contact_email']); ?> | Phone: <?php echo htmlspecialchars($companyDetails['contact_phone']); ?></p>
        </div>

        <!-- Customer and Order Details -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
<div class="bg-gray-50 p-6 rounded-xl card-hover">
                <h3 class="text-xl font-semibold text-gray-800 mb-3">Customer Details</h3>
                <p class="text-gray-600 mb-2"><strong>Name:</strong> <?php echo htmlspecialchars($userDetails['name']); ?></p>
                <p class="text-gray-600 mb-2"><strong>Email:</strong> <?php echo htmlspecialchars($userDetails['email']); ?></p>
                <p class="text-gray-600"><strong>Address:</strong> <?php echo htmlspecialchars($userDetails['address']); ?></p>
            </div>
            <div class="bg-gray-50 p-6 rounded-xl card-hover">
                <h3 class="text-xl font-semibold text-gray-800 mb-3">Order Details</h3>
                <p class="text-gray-600 mb-2"><strong>Order ID:</strong> <?php echo htmlspecialchars($statusData['orderId']); ?></p>
                <p class="text-gray-600 mb-2"><strong>Status:</strong> <span class="<?php echo $state === 'COMPLETED' ? 'status-success' : 'status-failed'; ?>"><?php echo htmlspecialchars($transactionStatus); ?></span></p>
                <p class="text-gray-600 mb-2"><strong>Payment Amount:</strong> <?php echo htmlspecialchars($statusData['amount'] / 100); ?> INR</p>
                <p class="text-gray-600 mb-2"><strong>Payment Mode:</strong> <?php echo htmlspecialchars($paymentMode); ?></p>
                <p class="text-gray-600"><strong>Payment ID:</strong> <?php echo htmlspecialchars($paymentId); ?></p>
                <?php if ($state === 'FAILED' && isset($statusData['errorContext'])): ?>
                    <p class="text-red-600 font-semibold mt-2"><strong>Error:</strong> <?php echo htmlspecialchars($statusData['errorContext']['description']); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Order Items -->
        <?php if (!empty($cartItems)): ?>
            <h3 class="text-xl font-semibold text-gray-800 mb-4">Order Items</h3>
            <div class="overflow-x-auto mb-8">
                <table class="w-full border-collapse bg-white rounded-lg shadow-md">
                    <thead>
                        <tr class="bg-blue-100">
                            <th class="border border-blue-200 px-6 py-3 text-left text-gray-700 font-semibold">Product ID</th>
                            <th class="border border-blue-200 px-6 py-3 text-left text-gray-700 font-semibold">Product Name</th>
                            <th class="border border-blue-200 px-6 py-3 text-left text-gray-700 font-semibold">Price (INR)</th>
                            <th class="border border-blue-200 px-6 py-3 text-left text-gray-700 font-semibold">Qty</th>
                            <th class="border border-blue-200 px-6 py-3 text-left text-gray-700 font-semibold">Total (INR)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cartItems as $item): ?>
                            <tr class="hover:bg-blue-50 transition">
                                <td class="border border-blue-200 px-6 py-3"><?php echo htmlspecialchars($item['product_id']); ?></td>
                                <td class="border border-blue-200 px-6 py-3"><?php echo htmlspecialchars($item['product_name']); ?></td>
                                <td class="border border-blue-200 px-6 py-3"><?php echo number_format($item['product_price'], 2); ?></td>
                                <td class="border border-blue-200 px-6 py-3"><?php echo htmlspecialchars($item['quantity']); ?></td>
                                <td class="border border-blue-200 px-6 py-3"><?php echo number_format($item['product_price'] * $item['quantity'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- Terms and Conditions -->
        <div class="terms mt-10 p-6 bg-gray-50 rounded-xl card-hover">
            <h3 class="text-xl font-semibold text-gray-800 mb-3">Terms and Conditions</h3>
            <ul class="list-disc list-inside space-y-2 text-gray-600">
                <li>All sales are final unless otherwise stated. Refunds are processed as per our refund policy, available on our website.</li>
                <li><?php echo htmlspecialchars($companyDetails['name']); ?> is not responsible for damages caused during shipping after the product leaves our facility.</li>
                <li>Please verify your order details before completing the payment. No changes can be made post-payment.</li>
                <li>For queries, contact our support team at <?php echo htmlspecialchars($companyDetails['contact_email']); ?> or <?php echo htmlspecialchars($companyDetails['contact_phone']); ?>.</li>
                <li>We reserve the right to cancel orders due to stock unavailability or pricing errors.</li>
                <li>By placing an order, you agree to abide by our privacy policy and terms of service.</li>
            </ul>
        </div>

        <!-- Action Buttons -->
        <div class="mt-10 flex justify-center space-x-6 no-print">
            <?php if ($state === 'COMPLETED'): ?>
                <form action="initiate_refund.php" method="POST">
                    <input type="hidden" name="merchantRefundId" value="REFUND_<?php echo time(); ?>">
                    <input type="hidden" name="originalMerchantOrderId" value="<?php echo htmlspecialchars($_SESSION['merchantOrderId'] ?? ''); ?>">
                    <input type="hidden" name="amount" value="<?php echo htmlspecialchars($statusData['amount']); ?>">
                    <button type="submit" class="bg-red-600 text-white px-8 py-3 rounded-full btn-anim hover:bg-red-700">Initiate Refund</button>
                </form>
            <?php endif; ?>
            <button onclick="window.print()" class="bg-green-600 text-white px-8 py-3 rounded-full btn-anim hover:bg-green-700">Print Receipt</button>
            <a href="../index.php" class="bg-blue-600 text-white px-8 py-3 rounded-full btn-anim hover:bg-blue-700">Back to Home</a>
        </div>
    </div>
</body>
</html>