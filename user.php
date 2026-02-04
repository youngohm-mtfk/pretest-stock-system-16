<?php
require_once 'auth.php';
requireRole('user');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard | PC Build Station</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding-top: 2rem;
        }

        .user-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 3rem;
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            padding: 1rem 2rem;
            border-radius: 20px;
            border: 1px solid var(--border);
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="user-nav">
            <div style="display: flex; align-items: center; gap: 1rem">
                <i class="fas fa-microchip" style="color: var(--primary); font-size: 1.5rem"></i>
                <h2 style="font-size: 1.2rem">PC Station Viewer</h2>
            </div>
            <div style="display: flex; align-items: center; gap: 2rem">
                <div style="text-align: right">
                    <p style="font-size: 0.9rem; font-weight: 600">
                        <?php echo $_SESSION['username']; ?>
                    </p>
                    <p style="font-size: 0.75rem; color: var(--text-muted)">Component Viewer</p>
                </div>
                <a href="logout.php" class="btn" style="background: var(--glass); color: var(--danger)">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <header>
            <div>
                <h1>Inventory Catalog</h1>
                <p style="color: var(--text-muted)">Current stock availability for PC components</p>
            </div>
        </header>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Items</h3>
                <div class="value" id="totalParts">0</div>
            </div>
            <div class="stat-card">
                <h3>Available Now</h3>
                <div class="value" style="color: var(--accent)" id="availableCount">0</div>
            </div>
        </div>

        <div class="inventory-table-container">
            <table>
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Component Name</th>
                        <th>Category</th>
                        <th>Brand/Model</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="inventoryBody">
                    <!-- Rows will be injected by JavaScript -->
                </tbody>
            </table>
        </div>
    </div>

    <script>
        async function refreshUI() {
            const res = await fetch('api.php?action=get_products');
            const products = await res.json();

            const tbody = document.getElementById('inventoryBody');
            tbody.innerHTML = products.map(p => `
                <tr>
                    <td><code style="color: var(--text-muted)">${p.sku}</code></td>
                    <td><strong style="color: var(--text-main)">${p.name}</strong></td>
                    <td><span class="badge" style="background: rgba(99, 102, 241, 0.1); color: var(--primary)">${p.category_name}</span></td>
                    <td>${p.brand} / ${p.model}</td>
                    <td>฿${parseFloat(p.price).toLocaleString()}</td>
                    <td><strong>${p.quantity}</strong></td>
                    <td>
                        <span class="badge ${p.quantity <= p.min_quantity ? 'badge-danger' : 'badge-success'}">
                            ${p.quantity <= p.min_quantity ? 'Low Stock' : 'In Stock'}
                        </span>
                    </td>
                </tr>
            `).join('');

            document.getElementById('totalParts').innerText = products.length;
            document.getElementById('availableCount').innerText = products.filter(p => p.quantity > 0).length;
        }

        window.onload = refreshUI;
    </script>
</body>

</html>