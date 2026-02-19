<?php
require_once 'auth.php';
require_once 'db.php';

// Initialize Cart
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle Add to Cart
if (isset($_POST['add_to_cart'])) {
    $item_id = $_POST['item_id'];
    $name = $_POST['name'];
    $price = $_POST['price'];
    $type = $_POST['type']; // 'product' or 'set'
    $cart_key = $type . '_' . $item_id;

    if (isset($_SESSION['cart'][$cart_key])) {
        $_SESSION['cart'][$cart_key]['quantity']++;
    } else {
        $_SESSION['cart'][$cart_key] = [
            'id' => $item_id,
            'name' => $name,
            'price' => $price,
            'quantity' => 1,
            'type' => $type
        ];
    }
    header('Location: buyer_dashboard.php' . (isset($_GET['cat']) ? '?cat=' . $_GET['cat'] : ''));
    exit();
}

// Handle Remove from Cart
if (isset($_GET['remove'])) {
    $id = $_GET['remove'];
    unset($_SESSION['cart'][$id]);
    header('Location: buyer_dashboard.php' . (isset($_GET['cat']) ? '?cat=' . $_GET['cat'] : ''));
    exit();
}

// Fetch Categories
$cat_stmt = $pdo->query("SELECT * FROM categories");
$categories = $cat_stmt->fetchAll();

// Fetch Products with Filtering
$selected_cat = $_GET['cat'] ?? null;
if ($selected_cat) {
    $stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.category_id = ?");
    $stmt->execute([$selected_cat]);
} else {
    $stmt = $pdo->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id");
}
$products = $stmt->fetchAll();

// Fetch Product Sets
$sets_stmt = $pdo->query("SELECT * FROM product_sets ORDER BY created_at DESC");
$product_sets = $sets_stmt->fetchAll();

foreach ($product_sets as &$set) {
    $item_stmt = $pdo->prepare("SELECT p.name FROM product_set_items psi JOIN products p ON psi.product_id = p.id WHERE psi.set_id = ?");
    $item_stmt->execute([$set['id']]);
    $set['items'] = $item_stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Calculate Cart Stats
$cart_count = 0;
$cart_total = 0;
foreach ($_SESSION['cart'] as $item) {
    $cart_count += $item['quantity'];
    $cart_total += $item['price'] * $item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium Store - Buyer Dashboard</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&family=Prompt:wght@300;400;600&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-glow: rgba(99, 102, 241, 0.4);
            --bg: #0f172a;
            --card-bg: rgba(30, 41, 59, 0.7);
            --text-main: #f8fafc;
            --text-dim: #94a3b8;
            --accent: #10b981;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Outfit', 'Prompt', sans-serif;
            background-color: var(--bg);
            background-image:
                radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(16, 185, 129, 0.1) 0px, transparent 50%);
            color: var(--text-main);
            min-height: 100vh;
            padding-bottom: 50px;
        }

        /* Glass Header */
        header {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1rem 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(90deg, #818cf8, #34d399);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .cart-status {
            position: relative;
            background: var(--card-bg);
            padding: 0.5rem 1rem;
            border-radius: 0.75rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            cursor: pointer;
            transition: all 0.3s;
        }

        .cart-status:hover {
            border-color: var(--primary);
            box-shadow: 0 0 15px var(--primary-glow);
        }

        .cart-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--primary);
            color: white;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 99px;
            font-weight: 700;
        }

        /* Layout */
        .main-container {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 2rem;
            padding: 2rem 5%;
        }

        /* Sidebar */
        .sidebar {
            position: sticky;
            top: 100px;
            height: fit-content;
        }

        .category-list {
            list-style: none;
        }

        .category-item {
            margin-bottom: 0.5rem;
        }

        .category-link {
            display: block;
            padding: 0.75rem 1rem;
            border-radius: 0.75rem;
            color: var(--text-dim);
            text-decoration: none;
            transition: all 0.2s;
            border: 1px solid transparent;
        }

        .category-link:hover,
        .category-link.active {
            background: var(--card-bg);
            color: var(--text-main);
            border-color: rgba(255, 255, 255, 0.1);
        }

        .category-link.active {
            border-left: 4px solid var(--primary);
            background: rgba(99, 102, 241, 0.1);
        }

        /* Product Grid */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .product-card {
            background: var(--card-bg);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 1.25rem;
            padding: 1.5rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            position: relative;
        }

        .product-card:hover {
            transform: translateY(-8px);
            border-color: rgba(99, 102, 241, 0.3);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3);
        }

        .product-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.03), transparent);
            transform: translateX(-100%);
            transition: 0.5s;
        }

        .product-card:hover::before {
            transform: translateX(100%);
        }

        .product-image {
            width: 100%;
            height: 180px;
            background: #0f172a;
            border-radius: 0.75rem;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
        }

        .product-cat {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--accent);
            margin-bottom: 0.5rem;
            font-weight: 700;
        }

        .product-name {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-main);
        }

        .product-price {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--text-main);
            margin-top: auto;
            margin-bottom: 1rem;
        }

        .btn-add {
            width: 100%;
            padding: 0.8rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-add:hover {
            background: #4f46e5;
            box-shadow: 0 0 15px var(--primary-glow);
        }

        /* Cart Sidebar (hidden by default) */
        .cart-overlay {
            position: fixed;
            top: 0;
            right: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            z-index: 1000;
            display: none;
        }

        .cart-drawer {
            position: fixed;
            top: 0;
            right: -400px;
            width: 400px;
            height: 100%;
            background: var(--bg);
            border-left: 1px solid rgba(255, 255, 255, 0.1);
            z-index: 1001;
            padding: 2rem;
            transition: right 0.3s ease-out;
            display: flex;
            flex-direction: column;
        }

        .cart-drawer.open {
            right: 0;
        }

        .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .cart-items {
            flex: 1;
            overflow-y: auto;
            padding-right: 0.5rem;
        }

        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: var(--card-bg);
            border-radius: 0.75rem;
            margin-bottom: 1rem;
        }

        .remove-item {
            color: #ef4444;
            text-decoration: none;
            font-size: 0.8rem;
        }

        .cart-footer {
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }

        .btn-checkout {
            width: 100%;
            padding: 1rem;
            background: var(--accent);
            color: white;
            border: none;
            border-radius: 0.75rem;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
        }

        @media (max-width: 900px) {
            .main-container {
                grid-template-columns: 1fr;
            }

            .sidebar {
                position: relative;
                top: 0;
                margin-bottom: 1rem;
            }

            .category-list {
                display: flex;
                overflow-x: auto;
                gap: 0.5rem;
                padding-bottom: 1rem;
            }

            .category-link {
                white-space: nowrap;
            }
        }
    </style>
</head>

<body>

    <header>
        <div class="logo">TECH-STORE Premium</div>
        <div class="header-actions">
            <div class="cart-status" onclick="toggleCart()">
                🛒 ตะกร้า
                <?php if ($cart_count > 0): ?>
                    <span class="cart-badge"><?php echo $cart_count; ?></span>
                <?php endif; ?>
            </div>
            <div style="font-size: 0.9rem;">
                Hi, <strong><?php echo $_SESSION['username']; ?></strong>
                <a href="order_history.php" style="color: var(--primary); text-decoration: none; margin-left: 1rem;">My
                    Orders</a>
                <a href="login.php?logout=1"
                    style="color: #ef4444; text-decoration: none; margin-left: 0.5rem;">Logout</a>
            </div>
        </div>
    </header>

    <div class="main-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <h3 style="margin-bottom: 1rem; color: var(--accent);">Categories</h3>
            <ul class="category-list">
                <li class="category-item">
                    <a href="buyer_dashboard.php"
                        class="category-link <?php echo !$selected_cat ? 'active' : ''; ?>">All Products</a>
                </li>
                <?php foreach ($categories as $cat): ?>
                    <li class="category-item">
                        <a href="buyer_dashboard.php?cat=<?php echo $cat['id']; ?>"
                            class="category-link <?php echo $selected_cat == $cat['id'] ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </aside>

        <!-- Main Content -->
        <main>
            <?php if (!$selected_cat): ?>
                <h2 style="margin-bottom: 1.5rem; color: var(--accent);">💻 Computer Sets (เซ็ตสุดคุ้ม)</h2>
                <div class="product-grid" style="margin-bottom: 3rem;">
                    <?php foreach ($product_sets as $s): ?>
                        <div class="product-card" style="border-color: rgba(16, 185, 129, 0.2);">
                            <div class="product-image">🖥️</div>
                            <div class="product-cat" style="color: #10b981;">SET BUNDLE</div>
                            <div class="product-name"><?php echo htmlspecialchars($s['name']); ?></div>
                            <div style="font-size: 0.8rem; color: var(--text-dim); margin-bottom: 1rem;">
                                <?php echo implode(', ', array_map('htmlspecialchars', $s['items'])); ?>
                            </div>
                            <div class="product-price">฿<?php echo number_format($s['price'], 2); ?></div>
                            <form method="POST">
                                <input type="hidden" name="item_id" value="<?php echo $s['id']; ?>">
                                <input type="hidden" name="name" value="<?php echo htmlspecialchars($s['name']); ?>">
                                <input type="hidden" name="price" value="<?php echo $s['price']; ?>">
                                <input type="hidden" name="type" value="set">
                                <button type="submit" name="add_to_cart" class="btn-add" style="background: #10b981;">
                                    ➕ ใส่ตะกร้า
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
                <hr style="border: 0; border-top: 1px solid rgba(255,255,255,0.1); margin-bottom: 3rem;">
            <?php endif; ?>

            <h2 style="margin-bottom: 1.5rem; color: var(--primary);">📦 Individual Components (อุปกรณ์แยกชิ้น)</h2>
            <div class="product-grid">
                <?php if (empty($products)): ?>
                    <div style="grid-column: 1/-1; text-align: center; padding: 4rem; color: var(--text-dim);">
                        <p>No products found in this category.</p>
                    </div>
                <?php endif; ?>

                <?php foreach ($products as $p): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <?php
                            // Emoji icon based on category
                            $icon = '📦';
                            if (stripos($p['category_name'], 'CPU') !== false)
                                $icon = '💻';
                            if (stripos($p['category_name'], 'GPU') !== false)
                                $icon = '🎮';
                            if (stripos($p['category_name'], 'RAM') !== false)
                                $icon = '⚡';
                            if (stripos($p['category_name'], 'SSD') !== false || stripos($p['category_name'], 'HDD') !== false)
                                $icon = '💾';
                            if (stripos($p['category_name'], 'Power') !== false || stripos($p['category_name'], 'PSU') !== false)
                                $icon = '🔌';
                            echo $icon;
                            ?>
                        </div>
                        <div class="product-cat"><?php echo htmlspecialchars($p['category_name'] ?: 'General'); ?></div>
                        <div class="product-name"><?php echo htmlspecialchars($p['name']); ?></div>
                        <div class="product-price">฿<?php echo number_format($p['price'], 2); ?></div>
                        <form method="POST">
                            <input type="hidden" name="item_id" value="<?php echo $p['id']; ?>">
                            <input type="hidden" name="name" value="<?php echo htmlspecialchars($p['name']); ?>">
                            <input type="hidden" name="price" value="<?php echo $p['price']; ?>">
                            <input type="hidden" name="type" value="product">
                            <button type="submit" name="add_to_cart" class="btn-add">
                                ➕ ใส่ตะกร้า
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <!-- Cart Drawer -->
    <div id="cartOverlay" class="cart-overlay" onclick="toggleCart()"></div>
    <div id="cartDrawer" class="cart-drawer">
        <div class="cart-header">
            <h2>Shopping Cart</h2>
            <button onclick="toggleCart()"
                style="background:none; border:none; color:white; font-size:1.5rem; cursor:pointer;">&times;</button>
        </div>

        <div class="cart-items">
            <?php if (empty($_SESSION['cart'])): ?>
                <p style="text-align: center; color: var(--text-dim); margin-top: 3rem;">Your cart is empty.</p>
            <?php else: ?>
                <?php foreach ($_SESSION['cart'] as $id => $item): ?>
                    <div class="cart-item">
                        <div>
                            <div style="font-weight: 600;"><?php echo htmlspecialchars($item['name']); ?></div>
                            <div style="font-size: 0.85rem; color: var(--text-dim);">
                                <?php echo $item['quantity']; ?> x ฿<?php echo number_format($item['price'], 2); ?>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-weight: 600;">฿<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                            </div>
                            <a href="buyer_dashboard.php?remove=<?php echo $id; ?><?php echo $selected_cat ? '&cat=' . $selected_cat : ''; ?>"
                                class="remove-item">ลบออก</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="cart-footer">
            <div class="total-row">
                <span>Total</span>
                <span>฿<?php echo number_format($cart_total, 2); ?></span>
            </div>
            <form action="checkout.php" method="POST">
                <button type="submit" class="btn-checkout" <?php echo empty($_SESSION['cart']) ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : ''; ?>>
                    Checkout (฿<?php echo number_format($cart_total, 2); ?>)
                </button>
            </form>
        </div>
    </div>

    <script>
        function toggleCart() {
            const drawer = document.getElementById('cartDrawer');
            const overlay = document.getElementById('cartOverlay');
            const isOpen = drawer.classList.contains('open');

            if (isOpen) {
                drawer.classList.remove('open');
                overlay.style.display = 'none';
            } else {
                drawer.classList.add('open');
                overlay.style.display = 'block';
            }
        }

        // Open cart automatically if an item was added or removed
        <?php if (isset($_POST['add_to_cart']) || isset($_GET['remove'])): ?>
            // window.onload = toggleCart; // Optionally auto-open cart
        <?php endif; ?>
    </script>

</body>

</html>