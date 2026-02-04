<?php
require_once 'auth.php';
require_once 'db.php';

// Everyone logged in can see this, but sellers/admins usually go to their dashboard
// For this demo, let's keep it simple.

// Fetch products
$stmt = $pdo->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id");
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buyer Showcase - Stock System</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Prompt', sans-serif;
            background: #f1f5f9;
            color: #1e293b;
            padding: 2rem;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            background: white;
            padding: 1.5rem 2rem;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .header h1 {
            margin: 0;
            font-size: 1.5rem;
            color: #0ea5e9;
        }

        .logout-btn {
            color: #64748b;
            text-decoration: none;
            font-size: 0.875rem;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .product-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s;
        }

        .product-card:hover {
            transform: translateY(-5px);
        }

        .product-card h3 {
            margin: 0.5rem 0;
            font-size: 1.1rem;
        }

        .price {
            color: #0ea5e9;
            font-weight: 600;
            font-size: 1.25rem;
        }

        .category {
            font-size: 0.75rem;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .stock {
            font-size: 0.875rem;
            color: <?php echo '#10b981'; ?>;
            margin-top: 1rem;
        }

        .buy-btn {
            width: 100%;
            margin-top: 1rem;
            padding: 0.75rem;
            background: #0ea5e9;
            color: white;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            font-weight: 600;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>🛒 เลือกซื้อสินค้า (ผู้ซื้อ)</h1>
            <div>
                <span>สวัสดี, <strong>
                        <?php echo $_SESSION['username']; ?>
                    </strong></span>
                <a href="login.php?logout=1" class="logout-btn" style="margin-left: 1rem;">ออกจากระบบ</a>
            </div>
        </div>

        <div class="grid">
            <?php foreach ($products as $p): ?>
                <div class="product-card">
                    <span class="category">
                        <?php echo htmlspecialchars($p['category_name'] ?: 'ทั่วไป'); ?>
                    </span>
                    <h3>
                        <?php echo htmlspecialchars($p['name']); ?>
                    </h3>
                    <div class="price">฿
                        <?php echo number_format($p['price'], 2); ?>
                    </div>
                    <div class="stock">มีสินค้าคงเหลือ:
                        <?php echo $p['quantity']; ?>
                    </div>
                    <button class="buy-btn">สั่งซื้อเลย</button>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>

</html>