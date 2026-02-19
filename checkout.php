<?php
require_once 'auth.php';
require_once 'db.php';

if (empty($_SESSION['cart'])) {
    header('Location: buyer_dashboard.php');
    exit();
}

$cart_total = 0;
foreach ($_SESSION['cart'] as $item) {
    $cart_total += $item['price'] * $item['quantity'];
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $payment_method = $_POST['payment_method'];
    $user_id = $_SESSION['user_id'];

    try {
        $pdo->beginTransaction();

        // 1. Create Order
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_price, payment_method, status) VALUES (?, ?, ?, 'pending')");
        $stmt->execute([$user_id, $cart_total, $payment_method]);
        $order_id = $pdo->lastInsertId();

        // 2. Add Items and Update Stock
        foreach ($_SESSION['cart'] as $key => $item) {
            $type = $item['type'];
            $id = $item['id'];
            $qty = $item['quantity'];
            $price = $item['price'];

            if ($type === 'product') {
                // Check stock
                $st = $pdo->prepare("SELECT quantity FROM products WHERE id = ?");
                $st->execute([$id]);
                $current_qty = $st->fetchColumn();

                if ($current_qty < $qty) {
                    throw new Exception("สินค้า {$item['name']} มีจำนวนไม่พอในสต็อก (คงเหลือ $current_qty)");
                }

                // Add to order_items
                $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, type) VALUES (?, ?, ?, ?, 'product')");
                $stmt->execute([$order_id, $id, $qty, $price]);

                // Reduce Stock
                $stmt = $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
                $stmt->execute([$qty, $id]);

                // Log Stock
                $stmt = $pdo->prepare("INSERT INTO stock_log (product_id, change_amount, current_quantity, type, note) VALUES (?, ?, ?, 'out', ?)");
                $stmt->execute([$id, -$qty, $current_qty - $qty, "Order #$order_id"]);

            } else if ($type === 'set') {
                // Add to order_items
                $stmt = $pdo->prepare("INSERT INTO order_items (order_id, set_id, quantity, price, type) VALUES (?, ?, ?, ?, 'set')");
                $stmt->execute([$order_id, $id, $qty, $price]);

                // Get products in set
                $st = $pdo->prepare("SELECT product_id, quantity FROM product_set_items WHERE set_id = ?");
                $st->execute([$id]);
                $set_items = $st->fetchAll();

                foreach ($set_items as $si) {
                    $prod_id = $si['product_id'];
                    $needed_qty = $si['quantity'] * $qty;

                    // Check stock
                    $st2 = $pdo->prepare("SELECT name, quantity FROM products WHERE id = ?");
                    $st2->execute([$prod_id]);
                    $p_data = $st2->fetch();

                    if ($p_data['quantity'] < $needed_qty) {
                        throw new Exception("อุปกรณ์ในเซ็ต ({$p_data['name']}) มีจำนวนไม่พอในสต็อก");
                    }

                    // Reduce Stock
                    $stmt = $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
                    $stmt->execute([$needed_qty, $prod_id]);

                    // Log Stock
                    $stmt = $pdo->prepare("INSERT INTO stock_log (product_id, change_amount, current_quantity, type, note) VALUES (?, ?, ?, 'out', ?)");
                    $stmt->execute([$prod_id, -$needed_qty, $p_data['quantity'] - $needed_qty, "Order #$order_id (Part of Set: {$item['name']})"]);
                }
            }
        }

        $pdo->commit();
        $_SESSION['cart'] = []; // Clear Cart
        $message = "สั่งซื้อสินค้าเรียบร้อยแล้ว! เลขที่ใบสั่งซื้อของคุณคือ #$order_id";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Premium Store</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&family=Prompt:wght@300;400;600&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --bg: #0f172a;
            --card-bg: rgba(30, 41, 59, 0.7);
            --text-main: #f8fafc;
            --text-dim: #94a3b8;
            --accent: #10b981;
            --danger: #ef4444;
        }

        body {
            font-family: 'Outfit', 'Prompt', sans-serif;
            background-color: var(--bg);
            color: var(--text-main);
            min-height: 100vh;
            padding: 2rem 5%;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .checkout-card {
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            padding: 2.5rem;
            border-radius: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            width: 100%;
            max-width: 600px;
        }

        h1 {
            margin-bottom: 1.5rem;
            font-size: 1.75rem;
            color: var(--primary);
        }

        .order-summary {
            margin-bottom: 2rem;
        }

        .item-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 1.5rem;
            font-weight: 700;
            margin-top: 1rem;
            color: var(--accent);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-dim);
        }

        select {
            width: 100%;
            padding: 0.8rem;
            border-radius: 0.75rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 1rem;
        }

        .btn-pay {
            width: 100%;
            padding: 1rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 0.75rem;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-pay:hover {
            background: #4f46e5;
            box-shadow: 0 0 20px rgba(99, 102, 241, 0.4);
        }

        .alert {
            padding: 1rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--accent);
            border: 1px solid var(--accent);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border: 1px solid var(--danger);
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 1.5rem;
            color: var(--text-dim);
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="checkout-card">
        <?php if ($message): ?>
            <div class="alert alert-success">
                <h2 style="margin-bottom: 0.5rem;">🎉 สำเร็จ!</h2>
                <p>
                    <?php echo $message; ?>
                </p>
            </div>
            <a href="buyer_dashboard.php" class="btn-pay"
                style="text-decoration: none; display: block; text-align: center;">กลับไปยังหน้าร้าน</a>
        <?php else: ?>
            <h1>💳 ชำระเงิน (Checkout)</h1>
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="order-summary">
                <h3 style="margin-bottom: 1rem;">รายการสั่งซื้อของคุณ:</h3>
                <?php foreach ($_SESSION['cart'] as $item): ?>
                    <div class="item-row">
                        <span>
                            <?php echo htmlspecialchars($item['name']); ?> (x
                            <?php echo $item['quantity']; ?>)
                        </span>
                        <span>฿
                            <?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                        </span>
                    </div>
                <?php endforeach; ?>
                <div class="total-row">
                    <span>ยอดรวมทั้งสิ้น:</span>
                    <span>฿
                        <?php echo number_format($cart_total, 2); ?>
                    </span>
                </div>
            </div>

            <form method="POST">
                <div class="form-group">
                    <label>เลือกวิธีการชำระเงิน</label>
                    <select name="payment_method" required>
                        <option value="transfer">โอนเงินผ่านธนาคาร</option>
                        <option value="cod">เก็บเงินปลายทาง</option>
                        <option value="qr">PromptPay QR Code</option>
                    </select>
                </div>
                <button type="submit" name="place_order" class="btn-pay">ยืนยันการสั่งซื้อ</button>
                <a href="buyer_dashboard.php" class="back-link">ยกเลิกและกลับไปหน้าร้าน</a>
            </form>
        <?php endif; ?>
    </div>
</body>

</html>