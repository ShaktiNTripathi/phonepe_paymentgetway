
<?php
// PhonePe Configuration
define('PHONEPE_ENV', 'PROD'); // Set to 'UAT' for testing, 'PROD' for production
define('PHONEPE_CLIENT_ID', ''); // Replace with your Client ID
define('PHONEPE_CLIENT_SECRET', ''); // Replace with your Client Secret
define('PHONEPE_CLIENT_VERSION', '1'); // Replace with your Client Version (1 for UAT)
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name'); // Replace with your database name
define('DB_USER', '');
define('DB_PASS', '');

// API Endpoints
$baseUrls = [
    'UAT' => [
        'auth' => 'https://api-preprod.phonepe.com/apis/pg-sandbox/v1/oauth/token',
        'pay' => 'https://api-preprod.phonepe.com/apis/pg-sandbox/checkout/v2/pay',
        'status' => 'https://api-preprod.phonepe.com/apis/pg-sandbox/checkout/v2/order/',
        'refund' => 'https://api-preprod.phonepe.com/apis/pg-sandbox/payments/v2/refund',
    ],
    'PROD' => [
        'auth' => 'https://api.phonepe.com/apis/identity-manager/v1/oauth/token',
        'pay' => 'https://api.phonepe.com/apis/pg/checkout/v2/pay',
        'status' => 'https://api.phonepe.com/apis/pg/checkout/v2/order/',
        'refund' => 'https://api.phonepe.com/apis/pg/payments/v2/refund',
    ]
];

define('PHONEPE_BASE_URLS', $baseUrls);
?>