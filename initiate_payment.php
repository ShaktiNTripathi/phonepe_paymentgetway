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
    $error = curl_error($ch);
    curl_close($ch);
    return ['response' => $response, 'httpCode' => $httpCode, 'error' => $error];
}

// Step 1: Fetch Auth Token
$authUrl = PHONEPE_BASE_URLS[PHONEPE_ENV]['auth'];
$authHeaders = [
    'Content-Type: application/x-www-form-urlencoded',
];
$authData = http_build_query([
    'client_id' => PHONEPE_CLIENT_ID,
    'client_version' => PHONEPE_CLIENT_VERSION,
    'client_secret' => PHONEPE_CLIENT_SECRET,
    'grant_type' => 'client_credentials',
]);

$authResponse = makeCurlRequest($authUrl, 'POST', $authHeaders, $authData);
$authData = json_decode($authResponse['response'], true);

if ($authResponse['httpCode'] != 200 || !isset($authData['access_token'])) {
    header('Content-Type: application/json');
    $errorMessage = isset($authData['message']) ? $authData['message'] : 'Unknown error';
    echo json_encode(['success' => false, 'message' => 'Failed to fetch auth token: ' . $errorMessage]);
    exit;
}

$_SESSION['access_token'] = $authData['access_token'];
$_SESSION['expires_at'] = $authData['expires_at'];

// Step 2: Process Payment Initiation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $merchantOrderId = filter_input(INPUT_POST, 'merchantOrderId', FILTER_SANITIZE_STRING) ?? '';
    $amount = (int)(floatval(filter_input(INPUT_POST, 'amount', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) ?? 0) * 100); // Convert INR to paisa
    $redirectUrl = filter_input(INPUT_POST, 'redirectUrl', FILTER_SANITIZE_URL) ?? '';
    $cartItems = isset($_POST['cart']) && is_array($_POST['cart']) ? $_POST['cart'] : [];

    // Validate required fields
    if (empty($merchantOrderId) || $amount <= 0 || empty($redirectUrl)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Missing or invalid required fields (merchantOrderId, amount, or redirectUrl)']);
        exit;
    }

    // Validate and sanitize cart items
    $sanitizedCart = [];
    if (!empty($cartItems)) {
        foreach ($cartItems as $index => $item) {
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
    if (abs($calculatedTotal * 100 - $amount) > 1) { // Allow minor rounding differences
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Amount does not match cart total']);
        exit;
    }

    // Store cart and order details in session for callback processing
    $_SESSION['cart_data'] = $sanitizedCart;
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
            'cart' => $sanitizedCart
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

    // Store PhonePe order ID in session
    $_SESSION['orderId'] = $payDataResponse['orderId'];

    // Return JSON response for AJAX handling in cart.php
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'redirectUrl' => $payDataResponse['redirectUrl']
    ]);
    exit;
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}
?> noew recoved all things name email any all about custmers details in this page this is initiate_payment.php and pass to payment response.php