<?php
require_once 'auth.php';
require_once 'db.php';

// Only admin or seller can access this page
if (getRole() !== 'admin' && getRole() !== 'seller') {
    header('Location: buyer_dashboard.php');
    exit();
}

// Fetch products
$stmt = $pdo->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id");
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Dashboard - Stock System</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Prompt', sans-serif;
            background: #f8fafc;
            color: #1e293b;
            padding: 2rem;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 1rem;
        }

        .header h1 {
            margin: 0;
            font-size: 1.5rem;
            color: #4f46e5;
        }

        .user-info {
            font-size: 0.875rem;
            color: #64748b;
        }

        .logout-btn {
            color: #ef4444;
            text-decoration: none;
            font-weight: 600;
            margin-left: 1rem;
        }

        .product-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .product-table th,
        .product-table td {
            text-align: left;
            padding: 1rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .product-table th {
            background: #f8fafc;
            font-weight: 600;
        }

        .badge {
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-admin {
            background: #dcfce7;
            color: #166534;
        }

        .badge-seller {
            background: #dbeafe;
            color: #1e40af;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>📦 ระบบหลังบ้าน (ผู้ขาย/แอดมิน)</h1>
                <div class="user-info">
                    ยินดีต้อนรับ, <strong>
                        <?php echo $_SESSION['username']; ?>
                    </strong>
                    <span class="badge badge-<?php echo $_SESSION['role']; ?>">
                        <?php echo strtoupper($_SESSION['role']); ?>
                    </span>
                </div>
            </div>
            <a href="login.php?logout=1" class="logout-btn" onclick="return confirm('ยืนยันระบบ?')">ออกจากระบบ</a>
        </div>

        <h3>รายการสินค้าทั้งหมด</h3>
        <table class="product-table">
            <thead>
                <tr>
                    <th>สินค้า</th>
                    <th>หมวดหมู่</th>
                    <th>ราคา</th>
                    <th>คงเหลือ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                    <tr>
                        <td><strong>
                                <?php echo htmlspecialchars($p['name']); ?>
                            </strong><br><small>
                                <?php echo htmlspecialchars($p['sku']); ?>
                            </small></td>
                        <td>
                            <?php echo htmlspecialchars($p['category_name'] ?: 'ทั่วไป'); ?>
                        </td>
                        <td>฿
                            <?php echo number_format($p['price'], 2); ?>
                        </td>
                        <td>
                            <?php echo $p['quantity']; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>

</html>