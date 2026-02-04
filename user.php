<?php
require_once 'auth.php';
requireRole('user');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PC Components Store | Shop Online</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding-top: 2rem;
        }

        .buyer-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 3rem;
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            padding: 1rem 2rem;
            border-radius: 24px;
            border: 1px solid var(--border);
        }

        .store-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 2rem;
            margin-bottom: 4rem;
        }

        .product-card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border);
            border-radius: 28px;
            padding: 1.5rem;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .product-card:hover {
            transform: translateY(-8px);
            border-color: var(--primary);
        }

        .product-image {
            width: 100%;
            height: 180px;
            background: rgba(255, 255, 255, 0.02);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: var(--glass);
        }

        .price-tag {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--accent);
        }

        .buy-btn {
            width: 100%;
            justify-content: center;
            background: rgba(16, 185, 129, 0.1);
            color: var(--accent);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .buy-btn:hover {
            background: var(--accent);
            color: white;
        }

        .buy-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="buyer-nav">
            <div style="display: flex; align-items: center; gap: 1rem">
                <i class="fas fa-shopping-cart" style="color: var(--accent); font-size: 1.5rem"></i>
                <h2 style="font-size: 1.2rem">PC Build Station <span
                        style="font-weight: 400; color: var(--text-muted)">Store</span></h2>
            </div>
            <div style="display: flex; align-items: center; gap: 2rem">
                <div style="text-align: right">
                    <p style="font-size: 0.9rem; font-weight: 600"><?php echo $_SESSION['username']; ?></p>
                    <p style="font-size: 0.75rem; color: var(--text-muted)">Valued Buyer</p>
                </div>
                <a href="logout.php" class="btn" style="background: var(--glass); color: var(--danger)">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <header style="margin-bottom: 2rem">
            <div>
                <h1>Browse Components</h1>
                <p style="color: var(--text-muted)">Premium hardware for your next build</p>
            </div>
        </header>

        <div id="storeBody" class="store-grid">
            <!-- Cards will be injected by JavaScript -->
        </div>
    </div>

    <!-- Notification Toast -->
    <div id="toast"
        style="position: fixed; bottom: 2rem; right: 2rem; background: var(--accent); color: white; padding: 1rem 2rem; border-radius: 12px; display: none; z-index: 2000; box-shadow: 0 10px 30px rgba(16,185,129,0.3)">
        Item added to order!
    </div>

    <script>
        async function buyItem(id, name) {
            const res = await fetch('api.php?action=update_stock', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, amount: -1, note: 'Purchase by Buyer' })
            });
            const data = await res.json();
            if (data.success) {
                showToast(`Successfully purchased ${name}!`);
                refreshUI();
            } else {
                alert('Purchase failed: ' + (data.error || 'Unknown error'));
            }
        }

        function showToast(msg) {
            const t = document.getElementById('toast');
            t.innerText = msg;
            t.style.display = 'block';
            setTimeout(() => t.style.display = 'none', 3000);
        }

        async function refreshUI() {
            const res = await fetch('api.php?action=get_products');
            const products = await res.json();

            const container = document.getElementById('storeBody');
            container.innerHTML = products.map(p => {
                const isOutOfStock = p.quantity <= 0;
                let icon = 'fa-microchip';
                if (p.category_name === 'GPU') icon = 'fa-vr-cardboard';
                if (p.category_name === 'RAM') icon = 'fa-memory';
                if (p.category_name === 'PSU') icon = 'fa-plug';

                return `
                <div class="product-card">
                    <div class="product-image">
                        <i class="fas ${icon}"></i>
                    </div>
                    <div>
                        <span class="badge" style="background: rgba(56, 189, 248, 0.1); color: var(--neon-blue); margin-bottom: 0.5rem; display: inline-block;">${p.category_name}</span>
                        <h3 style="margin-bottom: 0.25rem">${p.name}</h3>
                        <p style="color: var(--text-muted); font-size: 0.85rem">${p.brand} ${p.model}</p>
                    </div>
                    <div style="margin-top: auto">
                        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 1rem">
                            <span class="price-tag">฿${parseFloat(p.price).toLocaleString()}</span>
                            <span style="font-size: 0.8rem; color: ${isOutOfStock ? 'var(--danger)' : 'var(--text-muted)'}">
                                ${isOutOfStock ? 'Out of Stock' : p.quantity + ' available'}
                            </span>
                        </div>
                        <button class="btn buy-btn" onclick="buyItem(${p.id}, '${p.name}')" ${isOutOfStock ? 'disabled' : ''}>
                            <i class="fas fa-cart-plus"></i> ${isOutOfStock ? 'Out of Stock' : 'Buy Now'}
                        </button>
                    </div>
                </div>
            `}).join('');
        }

        window.onload = refreshUI;
    </script>
</body>

</html>