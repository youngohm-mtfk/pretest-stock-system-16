<?php
require_once 'auth.php';
requireRole('admin');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | PC Build Station</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .sidebar {
            width: 280px;
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border-right: 1px solid var(--border);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            padding: 2rem;
            display: flex;
            flex-direction: column;
        }

        .main-content {
            margin-left: 280px;
            padding: 2rem 4rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            color: var(--text-muted);
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            margin-bottom: 0.5rem;
        }

        .nav-link:hover,
        .nav-link.active {
            background: var(--glass);
            color: var(--text-main);
        }

        .nav-link.active {
            color: var(--primary);
            border: 1px solid var(--border);
        }

        .user-profile {
            margin-top: auto;
            display: flex;
            align-items: center;
            gap: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border);
        }

        body {
            padding: 0;
        }
    </style>
</head>

<body>
    <div class="sidebar">
        <div style="margin-bottom: 3rem">
            <h2 style="color: var(--text-main); font-size: 1.5rem">
                <i class="fas fa-microchip" style="color: var(--primary)"></i> PC Station
            </h2>
            <p style="color: var(--text-muted); font-size: 0.8rem">Admin Control Panel</p>
        </div>

        <nav>
            <a href="#" class="nav-link active"><i class="fas fa-th-large"></i> Dashboard</a>
            <a href="#" class="nav-link" onclick="openModal('addModal')"><i class="fas fa-plus"></i> Add Product</a>
            <a href="logout.php" class="nav-link" style="color: var(--danger)"><i class="fas fa-sign-out-alt"></i>
                Logout</a>
        </nav>

        <div class="user-profile">
            <div
                style="width: 40px; height: 40px; border-radius: 10px; background: var(--primary); display: flex; align-items: center; justify-content: center">
                <i class="fas fa-user-shield"></i>
            </div>
            <div>
                <p style="font-size: 0.9rem; font-weight: 600">
                    <?php echo $_SESSION['username']; ?>
                </p>
                <p style="font-size: 0.75rem; color: var(--text-muted)">Administrator</p>
            </div>
        </div>
    </div>

    <div class="main-content">
        <header>
            <div>
                <h1>Inventory Overview</h1>
                <p style="color: var(--text-muted)">Manage your hardware stock and components</p>
            </div>
            <button class="btn btn-primary" onclick="openModal('addModal')">
                <i class="fas fa-plus"></i> Add New Component
            </button>
        </header>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Components</h3>
                <div class="value" id="totalParts">0</div>
            </div>
            <div class="stat-card">
                <h3>Low Stock Items</h3>
                <div class="value" style="color: var(--danger)" id="lowStockCount">0</div>
            </div>
            <div class="stat-card">
                <h3>Total Value</h3>
                <div class="value" id="totalValue">฿0</div>
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
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="inventoryBody">
                    <!-- Rows will be injected by JavaScript -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modals (Add and Stock) -->
    <!-- Add Product Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <h2 style="margin-bottom: 1.5rem">Add Component</h2>
            <form id="addForm">
                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem">
                    <div class="form-group">
                        <label>SKU</label>
                        <input type="text" name="sku" placeholder="CPU-001" required>
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <select id="categorySelect" name="category_id" required></select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Product Name</label>
                    <input type="text" name="name" placeholder="Intel Core i9-14900K" required>
                </div>
                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem">
                    <div class="form-group">
                        <label>Brand</label>
                        <input type="text" name="brand">
                    </div>
                    <div class="form-group">
                        <label>Model</label>
                        <input type="text" name="model">
                    </div>
                </div>
                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem">
                    <div class="form-group">
                        <label>Price (฿)</label>
                        <input type="number" name="price" value="0" step="0.01">
                    </div>
                    <div class="form-group">
                        <label>Initial Stock</label>
                        <input type="number" name="quantity" value="0">
                    </div>
                </div>
                <div style="display: flex; gap: 1rem; margin-top: 1rem">
                    <button type="submit" class="btn btn-primary" style="flex: 1">Save Component</button>
                    <button type="button" class="btn" onclick="closeModal('addModal')"
                        style="background: var(--glass)">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Stock Adjustment Modal -->
    <div id="stockModal" class="modal">
        <div class="modal-content">
            <h2 id="stockModalTitle">Update Stock</h2>
            <form id="stockForm">
                <input type="hidden" name="id" id="stockProductId">
                <div class="form-group">
                    <label>Adjustment Amount (+ for In, - for Out)</label>
                    <input type="number" name="amount" id="stockAmount" required>
                </div>
                <div class="form-group">
                    <label>Note</label>
                    <input type="text" name="note" placeholder="Shipment received">
                </div>
                <div style="display: flex; gap: 1rem; margin-top: 1rem">
                    <button type="submit" class="btn btn-primary" style="flex: 1">Confirm</button>
                    <button type="button" class="btn" onclick="closeModal('stockModal')"
                        style="background: var(--glass)">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Shared JS Logic from index.php with Admin specifics
        async function fetchData(action, method = 'GET', body = null) {
            const options = { method };
            if (body) {
                options.body = JSON.stringify(body);
                options.headers = { 'Content-Type': 'application/json' };
            }
            const res = await fetch(`api.php?action=${action}`, options);
            return res.json();
        }

        async function refreshUI() {
            const products = await fetchData('get_products');
            const categories = await fetchData('get_categories');

            const tbody = document.getElementById('inventoryBody');
            tbody.innerHTML = products.map(p => `
                <tr>
                    <td><code style="color: var(--text-muted)">${p.sku}</code></td>
                    <td><strong style="color: var(--text-main)">${p.name}</strong></td>
                    <td><span class="badge" style="background: rgba(99, 102, 241, 0.1); color: var(--primary)">${p.category_name}</span></td>
                    <td>${p.brand} / ${p.model}</td>
                    <td>฿${parseFloat(p.price).toLocaleString()}</td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 1rem">
                            <strong>${p.quantity}</strong>
                            <button class="btn" style="padding: 0.25rem 0.5rem; background: var(--glass)" onclick="openStockModal(${p.id}, '${p.name}')">
                                <i class="fas fa-boxes-stacked"></i>
                            </button>
                        </div>
                    </td>
                    <td>
                        <span class="badge ${p.quantity <= p.min_quantity ? 'badge-danger' : 'badge-success'}">
                            ${p.quantity <= p.min_quantity ? 'Low Stock' : 'In Stock'}
                        </span>
                    </td>
                    <td>
                        <button class="btn" style="color: var(--danger); background: none" onclick="deletePart(${p.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `).join('');

            document.getElementById('totalParts').innerText = products.length;
            document.getElementById('lowStockCount').innerText = products.filter(p => p.quantity <= p.min_quantity).length;
            const totalVal = products.reduce((acc, p) => acc + (p.price * p.quantity), 0);
            document.getElementById('totalValue').innerText = '฿' + totalVal.toLocaleString();

            const catSelect = document.getElementById('categorySelect');
            catSelect.innerHTML = categories.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
        }

        function openModal(id) { document.getElementById(id).style.display = 'flex'; }
        function closeModal(id) { document.getElementById(id).style.display = 'none'; }

        function openStockModal(id, name) {
            document.getElementById('stockProductId').value = id;
            document.getElementById('stockModalTitle').innerText = `Update Stock: ${name}`;
            openModal('stockModal');
        }

        document.getElementById('addForm').onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());
            await fetchData('add_product', 'POST', data);
            closeModal('addModal');
            e.target.reset();
            refreshUI();
        };

        document.getElementById('stockForm').onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());
            await fetchData('update_stock', 'POST', data);
            closeModal('stockModal');
            e.target.reset();
            refreshUI();
        };

        async function deletePart(id) {
            if (confirm('Are you sure you want to delete this part?')) {
                await fetch(`api.php?action=delete_product&id=${id}`);
                refreshUI();
            }
        }

        window.onload = refreshUI;
    </script>
</body>

</html>