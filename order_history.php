<?php
require_once 'auth.php';
require_once 'db.php';

$user_id = $_SESSION['user_id'];

// Fetch User's Orders
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();

foreach ($orders as &$order) {
    $item_stmt = $pdo->prepare("
        SELECT oi.*, p.name as p_name, ps.name as s_name 
        FROM order_items oi 
        LEFT JOIN products p ON oi.product_id = p.id 
        LEFT JOIN product_sets ps ON oi.set_id = ps.id 
        WHERE oi.order_id = ?
    ");
    $item_stmt->execute([$order['id']]);
    $order['items'] = $item_stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History - Premium Store</title>
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
            --warning: #f59e0b;
        }

        body {
            font-family: 'Outfit', 'Prompt', sans-serif;
            background: var(--bg);
            color: var(--text-main);
            padding: 3rem 5%;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 3rem;
        }

        .order-card {
            background: var(--card-bg);
            border-radius: 1.25rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            padding-bottom: 1rem;
            margin-bottom: 1rem;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 99px;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .status-pending {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
            border: 1px solid var(--warning);
        }

        .status-paid {
            background: rgba(16, 185, 129, 0.1);
            color: var(--accent);
            border: 1px solid var(--accent);
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            font-size: 0.9rem;
        }

        .btn-back {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>

<body>
    <header>
        <h1>📜 ประวัติการสั่งซื้อ</h1>
        <a href="buyer_dashboard.php" class="btn-back">⬅ กลับไปที่ร้านค้า</a>
    </header>

    <?php if (empty($orders)): ?>
        <div style="text-align: center; padding: 5rem; color: var(--text-dim);">
            <p>คุณยังไม่มีประวัติการสั่งซื้อ</p>
        </div>
    <?php else: ?>
        <?php foreach ($orders as $o): ?>
            <div class="order-card">
                <div class="order-header">
                    <div>
                        <span style="font-weight: 700;">Order #
                            <?php echo $o['id']; ?>
                        </span>
                        <span style="color: var(--text-dim); margin-left: 1rem;">
                            <?php echo date('d/m/Y H:i', strtotime($o['created_at'])); ?>
                        </span>
                    </div>
                    <span class="status-badge status-<?php echo $o['status']; ?>">
                        <?php echo strtoupper($o['status']); ?>
                    </span>
                </div>
                <div class="order-items">
                    <?php foreach ($o['items'] as $item): ?>
                        <div class="order-item">
                            <span>
                                <?php echo htmlspecialchars($item['type'] === 'product' ? $item['p_name'] : $item['s_name']); ?>
                                (x
                                <?php echo $item['quantity']; ?>)
                            </span>
                            <span>฿
                                <?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div style="text-align: right; margin-top: 1rem; font-size: 1.25rem; font-weight: 700; color: var(--accent);">
                    รวมทั้งหมด: ฿
                    <?php echo number_format($o['total_price'], 2); ?>
                </div>
                <div style="font-size: 0.8rem; color: var(--text-dim); margin-top: 0.5rem;">
                    ช่องทางชำระเงิน:
                    <?php echo strtoupper($o['payment_method']); ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</body>

</html>