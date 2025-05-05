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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['access_token'])) {
    $refundUrl = PHONEPE_BASE_URLS[PHONEPE_ENV]['refund'];
    $refundHeaders = [
        'Content-Type: application/json',
        'Authorization: O-Bearer ' . $_SESSION['access_token'],
    ];
    $refundData = [
        'merchantRefundId' => $_POST['merchantRefundId'],
        'originalMerchantOrderId' => $_POST['originalMerchantOrderId'],
        'amount' => (int)$_POST['amount'],
    ];

    $refundResponse = makeCurlRequest($refundUrl, 'POST', $refundHeaders, json_encode($refundData));
    $refundData = json_decode($refundResponse['response'], true);

    if ($refundResponse['httpCode'] != 200) {
        $errorMessage = $refundData['message'] ?? 'Failed to initiate refund';
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Refund Error</title>
            <style>
                body { font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; }
                .error { padding: 20px; border: 1px solid #721c24; background-color: #f8d7da; color: #721c24; border-radius: 5px; }
            </style>
        </head>
        <body>
            <h2>Refund Error</h2>
            <div class="error">
                <p><strong>Error:</strong> <?php echo htmlspecialchars($errorMessage); ?></p>
                <a href="../cart.php" class="btn btn-primary">Return to Cart</a>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <?php include '../includes/meta.php'; ?>
        <title>Refund Status</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; }
            .status { padding: 20px; border: 1px solid #155724; border-radius: 5px; background-color: #d4edda; color: #155724; }
        </style>
    </head>
    <body>
        <?php include '../includes/header.php'; ?>
        <h2>Refund Status</h2>
        <div class="status">
            <p><strong>Refund ID:</strong> <?php echo htmlspecialchars($refundData['refundId']); ?></p>
            <p><strong>Amount:</strong> <?php echo htmlspecialchars($refundData['amount'] / 100); ?> INR</p>
            <p><strong>Status:</strong> <?php echo htmlspecialchars($refundData['state']); ?></p>
        </div>
        <a href="../cart.php" class="btn btn-primary">Return to Cart</a>
        <?php include '../includes/footer.php'; ?>
    </body>
    </html>
    <?php
} else {
    header('Location: ../cart.php');
    exit;
}
?>