<?php
session_start();

// Sample cart data (replace with your actual cart logic)
$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
$totalAmount = 0;
foreach ($cart as $item) {
    $totalAmount += $item['price'] * $item['quantity'];
}

$merchantOrderId = 'TX' . rand(100000, 999999); // Generate unique order ID
$redirectUrl = 'http://yourwebsite.com/payment_response.php'; // Replace with your actual redirect URL
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart - Proceed to Payment</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; }
        .cart-item { margin-bottom: 10px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input { width: 100%; padding: 8px; margin-bottom: 10px; }
        button { padding: 10px 20px; background-color: #007bff; color: white; border: none; cursor: pointer; }
        button:hover { background-color: #0056b3; }
    </style>
</head>
<body>
    <h2>Your Cart</h2>
    <?php if (empty($cart)): ?>
        <p>Your cart is empty.</p>
    <?php else: ?>
        <?php foreach ($cart as $item): ?>
            <div class="cart-item">
                <p><strong><?php echo htmlspecialchars($item['name']); ?></strong>: 
                   <?php echo htmlspecialchars($item['quantity']); ?> x 
                   <?php echo htmlspecialchars($item['price']); ?> INR = 
                   <?php echo htmlspecialchars($item['quantity'] * $item['price']); ?> INR</p>
            </div>
        <?php endforeach; ?>
        <p><strong>Total:</strong> <?php echo htmlspecialchars($totalAmount); ?> INR</p>
        <h3>Proceed to Payment</h3>
        <form action="initiate_payment.php" method="POST">
            <div class="form-group">
                <label for="merchantOrderId">Merchant Order ID</label>
                <input type="text" id="merchantOrderId" name="merchantOrderId" value="<?php echo htmlspecialchars($merchantOrderId); ?>" readonly>
            </div>
            <div class="form-group">
                <label for="amount">Amount (in INR)</label>
                <input type="number" id="amount" name="amount" value="<?php echo htmlspecialchars($totalAmount); ?>" readonly>
            </div>
            <div class="form-group">
                <label for="redirectUrl">Redirect URL</label>
                <input type="text" id="redirectUrl" name="redirectUrl" value="<?php echo htmlspecialchars($redirectUrl); ?>" readonly>
            </div>
            <button type="submit">Pay with PhonePe</button>
        </form>
    <?php endif; ?>
</body>
</html>