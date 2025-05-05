<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PhonePe Payment</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input, select { width: 100%; padding: 8px; margin-bottom: 10px; }
        button { padding: 10px 20px; background-color: #007bff; color: white; border: none; cursor: pointer; }
        button:hover { background-color: #0056b3; }
    </style>
</head>
<body>
    <h2>PhonePe Payment Form</h2>
    <form action="initiate_payment.php" method="POST">
        <div class="form-group">
            <label for="merchantOrderId">Merchant Order ID</label>
            <input type="text" id="merchantOrderId" name="merchantOrderId" value="TX<?php echo rand(100000, 999999); ?>" required>
        </div>
        <div class="form-group">
            <label for="amount">Amount (in INR)</label>
            <input type="number" id="amount" name="amount" value="1" min="1" required>
        </div>
        <div class="form-group">
            <label for="redirectUrl">Redirect URL</label>
            <input type="text" id="redirectUrl" name="redirectUrl" value="https://safaccessories.in/front-end/phonepe/payment_response.php" required>
        </div>
        <button type="submit">Proceed to Payment</button>
    </form>
</body>
</html>