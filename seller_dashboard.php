<?php
require_once 'auth.php';
require_once 'db.php';

// Access Control: Only admin or seller
if (getRole() !== 'admin' && getRole() !== 'seller') {
    header('Location: buyer_dashboard.php');
    exit();
}

$message = '';
$error = '';

// Handle Product Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add / Edit Product
    if (isset($_POST['save_product'])) {
        $id = $_POST['id'] ?? null;
        $name = $_POST['name'];
        $sku = $_POST['sku'];
        $category_id = $_POST['category_id'];
        $price = $_POST['price'];
        $quantity = $_POST['quantity'];
        $min_quantity = $_POST['min_quantity'] ?: 5;

        try {
            if ($id) {
                $stmt = $pdo->prepare("UPDATE products SET name=?, sku=?, category_id=?, price=?, quantity=?, min_quantity=? WHERE id=?");
                $stmt->execute([$name, $sku, $category_id, $price, $quantity, $min_quantity, $id]);
                $message = "แก้ไขสินค้าเรียบร้อยแล้ว";
            } else {
                $stmt = $pdo->prepare("INSERT INTO products (name, sku, category_id, price, quantity, min_quantity) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $sku, $category_id, $price, $quantity, $min_quantity]);
                $message = "เพิ่มสินค้าเรียบร้อยแล้ว";
            }
        } catch (PDOException $e) {
            $error = "เกิดข้อผิดพลาด: " . $e->getMessage();
        }
    }

    // Delete Product
    if (isset($_POST['delete_product'])) {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM products WHERE id=?");
        $stmt->execute([$id]);
        $message = "ลบสินค้าเรียบร้อยแล้ว";
    }

    // Add Category
    if (isset($_POST['add_category'])) {
        $name = $_POST['name'];
        try {
            $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
            $stmt->execute([$name]);
            $message = "เพิ่มหมวดหมู่เรียบร้อยแล้ว";
        } catch (PDOException $e) {
            $error = "หมวดหมู่นี้อาจจะมีอยู่แล้ว";
        }
    }
}

// Fetch Data
$cat_stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $cat_stmt->fetchAll();

$prod_stmt = $pdo->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC");
$products = $prod_stmt->fetchAll();

// Stats
$total_products = count($products);
$low_stock = 0;
foreach ($products as $p) {
    if ($p['quantity'] <= $p['min_quantity'])
        $low_stock++;
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Stock System</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&family=Prompt:wght@300;400;600&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --primary: #8b5cf6;
            --primary-hover: #7c3aed;
            --bg: #0f172a;
            --card-bg: rgba(30, 41, 59, 0.7);
            --text-main: #f8fafc;
            --text-dim: #94a3b8;
            --danger: #ef4444;
            --success: #10b981;
            --warning: #f59e0b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Outfit', 'Prompt', sans-serif;
            background-color: var(--bg);
            background-image: radial-gradient(at 100% 0%, rgba(139, 92, 246, 0.15) 0px, transparent 50%);
            color: var(--text-main);
            min-height: 100vh;
            padding: 2rem 5%;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            padding: 1.5rem 2rem;
            border-radius: 1.25rem;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .header-title h1 {
            font-size: 1.5rem;
            background: linear-gradient(90deg, #c084fc, #818cf8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .logout-btn {
            color: var(--danger);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--card-bg);
            padding: 1.5rem;
            border-radius: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .stat-card h3 {
            font-size: 0.8rem;
            color: var(--text-dim);
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }

        .stat-card .value {
            font-size: 2rem;
            font-weight: 700;
        }

        .low-stock-alert {
            color: var(--warning);
        }

        /* Tables & UI Components */
        .actions-bar {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .btn {
            padding: 0.75rem 1.25rem;
            border-radius: 0.75rem;
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-family: inherit;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .table-container {
            background: var(--card-bg);
            border-radius: 1.25rem;
            border: 1px solid rgba(255, 255, 255, 0.05);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: rgba(255, 255, 255, 0.02);
            text-align: left;
            padding: 1rem;
            color: var(--text-dim);
            font-size: 0.85rem;
            text-transform: uppercase;
        }

        td {
            padding: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }

        tr:hover td {
            background: rgba(255, 255, 255, 0.01);
        }

        .badge {
            padding: 0.25rem 0.6rem;
            border-radius: 99px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .badge-warning {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
            border: 1px solid rgba(245, 158, 11, 0.2);
        }

        .badge-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .action-link {
            color: var(--text-dim);
            text-decoration: none;
            font-size: 0.8rem;
            margin-right: 1rem;
            transition: color 0.2s;
        }

        .action-link:hover {
            color: var(--primary);
        }

        .btn-delete {
            color: var(--danger);
            border: none;
            background: none;
            cursor: pointer;
            font-family: inherit;
            font-size: 0.8rem;
        }

        /* Modal Simple */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal-content {
            background: var(--bg);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 2.5rem;
            border-radius: 1.5rem;
            width: 100%;
            max-width: 500px;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-dim);
            font-size: 0.85rem;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border-radius: 0.5rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
        }

        .alert {
            padding: 1rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
    </style>
</head>

<body>

    <header>
        <div class="header-title">
            <h1>🛠️ ระบบจัดการหลังบ้าน</h1>
            <p style="font-size: 0.8rem; color: var(--text-dim);">ยินดีต้อนรับ,
                <strong><?php echo $_SESSION['username']; ?></strong> (<?php echo strtoupper($_SESSION['role']); ?>)</p>
        </div>
        <div style="display: flex; gap: 1rem; align-items: center;">
            <a href="buyer_dashboard.php" class="btn btn-secondary">หน้าร้านค้า</a>
            <a href="login.php?logout=1" class="logout-btn">ออกจากระบบ</a>
        </div>
    </header>

    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card">
            <h3>จำนวนสินค้าทั้งหมด</h3>
            <div class="value"><?php echo $total_products; ?></div>
        </div>
        <div class="stat-card">
            <h3>สินค้าใกล้หมด</h3>
            <div class="value <?php echo $low_stock > 0 ? 'low-stock-alert' : ''; ?>"><?php echo $low_stock; ?></div>
        </div>
        <div class="stat-card">
            <h3>หมวดหมู่</h3>
            <div class="value"><?php echo count($categories); ?></div>
        </div>
    </div>

    <div class="actions-bar">
        <button class="btn btn-primary" onclick="openModal('productModal')">➕ เพิ่มสินค้าใหม่</button>
        <button class="btn btn-secondary" onclick="openModal('categoryModal')">📁 จัดการหมวดหมู่</button>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>รูป/ไอคอน</th>
                    <th>SKU / ชื่อสินค้า</th>
                    <th>หมวดหมู่</th>
                    <th>ราคา</th>
                    <th>คงเหลือ</th>
                    <th>สถานะ</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                    <tr>
                        <td style="font-size: 1.5rem; width: 80px; text-align: center;">📦</td>
                        <td>
                            <div style="font-weight: 600;"><?php echo htmlspecialchars($p['name']); ?></div>
                            <div style="font-size: 0.75rem; color: var(--text-dim);">
                                <?php echo htmlspecialchars($p['sku']); ?></div>
                        </td>
                        <td><span class="badge"
                                style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: var(--text-main);"><?php echo htmlspecialchars($p['category_name'] ?: 'ทั่วไป'); ?></span>
                        </td>
                        <td style="font-weight: 600;">฿<?php echo number_format($p['price'], 2); ?></td>
                        <td>
                            <div style="font-weight: 700;"><?php echo $p['quantity']; ?></div>
                            <div style="font-size: 0.7rem; color: var(--text-dim);">Min: <?php echo $p['min_quantity']; ?>
                            </div>
                        </td>
                        <td>
                            <?php if ($p['quantity'] <= $p['min_quantity']): ?>
                                <span class="badge badge-warning">Low Stock</span>
                            <?php else: ?>
                                <span class="badge badge-success">In Stock</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="javascript:void(0)" onclick='editProduct(<?php echo json_encode($p); ?>)'
                                class="action-link">แก้ไข</a>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('ลบสินค้านี้ใช่หรือไม่?')">
                                <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                <button type="submit" name="delete_product" class="btn-delete">ลบ</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Product Modal -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <h2 id="modalTitle" style="margin-bottom: 1.5rem;">เพิ่มสินค้าใหม่</h2>
            <form method="POST">
                <input type="hidden" name="id" id="p_id">
                <div class="form-group">
                    <label>ชื่อสินค้า</label>
                    <input type="text" name="name" id="p_name" required>
                </div>
                <div class="form-group">
                    <label>SKU (รหัสสินค้า)</label>
                    <input type="text" name="sku" id="p_sku" required>
                </div>
                <div class="form-group">
                    <label>หมวดหมู่</label>
                    <select name="category_id" id="p_cat" required>
                        <option value="">-- เลือกหมวดหมู่ --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label>ราคา</label>
                        <input type="number" step="0.01" name="price" id="p_price" required>
                    </div>
                    <div class="form-group">
                        <label>จำนวนคงเหลือ</label>
                        <input type="number" name="quantity" id="p_qty" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>จำนวนขั้นต่ำที่ต้องแจ้งเตือน (Default: 5)</label>
                    <input type="number" name="min_quantity" id="p_min_qty" value="5">
                </div>
                <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                    <button type="submit" name="save_product" class="btn btn-primary"
                        style="flex:1;">บันทึกข้อมูล</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('productModal')">ยกเลิก</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Category Modal -->
    <div id="categoryModal" class="modal">
        <div class="modal-content">
            <h2 style="margin-bottom: 1.5rem;">จัดการหมวดหมู่</h2>
            <form method="POST" style="margin-bottom: 1.5rem;">
                <div class="form-group">
                    <label>ชื่อหมวดหมู่ใหม่</label>
                    <input type="text" name="name" required placeholder="เช่น CPU, RAM">
                </div>
                <button type="submit" name="add_category" class="btn btn-primary"
                    style="width: 100%;">เพิ่มหมวดหมู่</button>
            </form>
            <h3 style="font-size: 1rem; margin-bottom: 1rem; color: var(--text-dim);">หมวดหมู่ปัจจุบัน:</h3>
            <div style="max-height: 200px; overflow-y: auto;">
                <?php foreach ($categories as $cat): ?>
                    <div
                        style="padding: 0.5rem; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between;">
                        <span><?php echo htmlspecialchars($cat['name']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn btn-secondary" style="width: 100%; margin-top: 1.5rem;"
                onclick="closeModal('categoryModal')">ปิด</button>
        </div>
    </div>

    <script>
        function openModal(id) {
            document.getElementById(id).style.display = 'flex';
            if (id === 'productModal') {
                document.getElementById('modalTitle').innerText = 'เพิ่มสินค้าใหม่';
                document.getElementById('p_id').value = '';
                document.getElementById('p_name').value = '';
                document.getElementById('p_sku').value = '';
                document.getElementById('p_cat').value = '';
                document.getElementById('p_price').value = '';
                document.getElementById('p_qty').value = '';
                document.getElementById('p_min_qty').value = '5';
            }
        }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        function editProduct(product) {
            openModal('productModal');
            document.getElementById('modalTitle').innerText = 'แก้ไขสินค้า: ' + product.name;
            document.getElementById('p_id').value = product.id;
            document.getElementById('p_name').value = product.name;
            document.getElementById('p_sku').value = product.sku;
            document.getElementById('p_cat').value = product.category_id;
            document.getElementById('p_price').value = product.price;
            document.getElementById('p_qty').value = product.quantity;
            document.getElementById('p_min_qty').value = product.min_quantity;
        }

        window.onclick = function (event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        }
    </script>

</body>

</html>