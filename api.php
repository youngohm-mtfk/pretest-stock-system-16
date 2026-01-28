<?php
header('Content-Type: application/json');
require_once 'db.php';

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_products':
            $stmt = $pdo->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC");
            echo json_encode($stmt->fetchAll());
            break;

        case 'get_categories':
            $stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
            echo json_encode($stmt->fetchAll());
            break;

        case 'add_product':
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $pdo->prepare("INSERT INTO products (name, sku, category_id, brand, model, price, quantity) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['name'],
                $data['sku'],
                $data['category_id'],
                $data['brand'],
                $data['model'],
                $data['price'],
                $data['quantity']
            ]);
            $productId = $pdo->lastInsertId();

            // Log initial stock
            if ($data['quantity'] > 0) {
                $log = $pdo->prepare("INSERT INTO stock_log (product_id, change_amount, current_quantity, type, note) VALUES (?, ?, ?, 'in', 'Initial Stock')");
                $log->execute([$productId, $data['quantity'], $data['quantity']]);
            }

            echo json_encode(['success' => true, 'id' => $productId]);
            break;

        case 'update_stock':
            $data = json_decode(file_get_contents('php://input'), true);
            $pdo->beginTransaction();

            // Get current quantity
            $stmt = $pdo->prepare("SELECT quantity FROM products WHERE id = ?");
            $stmt->execute([$data['id']]);
            $current = $stmt->fetchColumn();

            $newQuantity = $current + $data['amount'];
            if ($newQuantity < 0)
                throw new Exception("Insufficient stock");

            // Update product
            $update = $pdo->prepare("UPDATE products SET quantity = ? WHERE id = ?");
            $update->execute([$newQuantity, $data['id']]);

            // Log movement
            $type = $data['amount'] > 0 ? 'in' : 'out';
            $log = $pdo->prepare("INSERT INTO stock_log (product_id, change_amount, current_quantity, type, note) VALUES (?, ?, ?, ?, ?)");
            $log->execute([$data['id'], $data['amount'], $newQuantity, $type, $data['note'] ?? '']);

            $pdo->commit();
            echo json_encode(['success' => true, 'new_quantity' => $newQuantity]);
            break;

        case 'delete_product':
            $id = $_GET['id'] ?? 0;
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
            break;

        default:
            echo json_encode(['error' => 'Invalid action']);
    }
} catch (Exception $e) {
    if ($pdo->inTransaction())
        $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>