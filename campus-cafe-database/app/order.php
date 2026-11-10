<?php
// One order: add/remove items, change status, record a payment.
require 'db.php';
$db = get_db();

$order_id = (int) ($_GET['id'] ?? 0);

$stmt = $db->prepare('
    SELECT o.*, c.customer_name, c.email
    FROM cafe_orders o JOIN customers c ON c.customer_id = o.customer_id
    WHERE o.order_id = ?
');
$stmt->execute([$order_id]);
$order = $stmt->fetch();
if (!$order) {
    flash('Order not found.', 'error');
    redirect('orders.php');
}

$payment_stmt = $db->prepare('SELECT * FROM payments WHERE order_id = ?');
$payment_stmt->execute([$order_id]);
$payment = $payment_stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $back = "order.php?id={$order_id}";

    try {
        if (in_array($action, ['add_item', 'remove_item'], true) && $payment) {
            flash('This order is already paid, so its items are locked.', 'error');
            redirect($back);
        }

        if ($action === 'add_item') {
            $menu_item_id = (int) $_POST['menu_item_id'];
            $quantity = (int) $_POST['quantity'];
            if ($quantity < 1) {
                flash('Quantity must be at least 1.', 'error');
                redirect($back);
            }
            // Copy the current menu price so later price changes don't rewrite old orders.
            $price_stmt = $db->prepare('SELECT price FROM menu_items WHERE menu_item_id = ? AND is_available = TRUE');
            $price_stmt->execute([$menu_item_id]);
            $price = $price_stmt->fetchColumn();
            if ($price === false) {
                flash('That menu item isn’t available.', 'error');
                redirect($back);
            }
            $stmt = $db->prepare('INSERT INTO order_items (order_id, menu_item_id, quantity, unit_price) VALUES (?, ?, ?, ?)');
            $stmt->execute([$order_id, $menu_item_id, $quantity, $price]);
            flash('Item added.');
        } elseif ($action === 'remove_item') {
            $stmt = $db->prepare('DELETE FROM order_items WHERE order_item_id = ? AND order_id = ?');
            $stmt->execute([(int) $_POST['order_item_id'], $order_id]);
            flash('Item removed.');
        } elseif ($action === 'update_status') {
            $status = $_POST['order_status'] ?? '';
            if (in_array($status, ORDER_STATUSES, true)) {
                $stmt = $db->prepare('UPDATE cafe_orders SET order_status = ? WHERE order_id = ?');
                $stmt->execute([$status, $order_id]);
                flash("Order status set to {$status}.");
            }
        } elseif ($action === 'pay') {
            $method = $_POST['payment_method'] ?? '';
            $total_stmt = $db->prepare('SELECT COALESCE(SUM(quantity * unit_price), 0) FROM order_items WHERE order_id = ?');
            $total_stmt->execute([$order_id]);
            $total = $total_stmt->fetchColumn();
            if (!in_array($method, PAYMENT_METHODS, true) || $total <= 0) {
                flash('Add at least one item and choose a payment method first.', 'error');
                redirect($back);
            }
            $stmt = $db->prepare("INSERT INTO payments (order_id, payment_method, amount, payment_status) VALUES (?, ?, ?, 'Paid')");
            $stmt->execute([$order_id, $method, $total]);
            flash('Payment of ' . money($total) . ' recorded.');
        }
    } catch (PDOException $e) {
        // 1062 = duplicate key: payments.order_id is UNIQUE, so an order can only be paid once.
        if ($e->errorInfo[1] == 1062) {
            flash('This order already has a payment.', 'error');
        } else {
            flash('Database error: ' . $e->getMessage(), 'error');
        }
    }
    redirect($back);
}

$items_stmt = $db->prepare('
    SELECT oi.order_item_id, oi.quantity, oi.unit_price, m.item_name, m.category,
           oi.quantity * oi.unit_price AS line_total
    FROM order_items oi JOIN menu_items m ON m.menu_item_id = oi.menu_item_id
    WHERE oi.order_id = ?
    ORDER BY oi.order_item_id
');
$items_stmt->execute([$order_id]);
$items = $items_stmt->fetchAll();
$total = array_sum(array_column($items, 'line_total'));

$menu = $db->query('SELECT menu_item_id, item_name, price FROM menu_items WHERE is_available = TRUE ORDER BY category, item_name')->fetchAll();

$page_title = "Order #{$order_id}";
require 'header.php';
?>
<h1>Order #<?= $order_id ?></h1>
<p>
    Customer: <b><?= e($order['customer_name']) ?></b> ·
    Placed: <?= e($order['order_date']) ?> ·
    Status: <span class="badge <?= e($order['order_status']) ?>"><?= e($order['order_status']) ?></span>
</p>

<table>
    <tr><th>Item</th><th>Category</th><th class="num">Qty</th><th class="num">Unit price</th><th class="num">Line total</th><th></th></tr>
    <?php foreach ($items as $item): ?>
        <tr>
            <td><?= e($item['item_name']) ?></td>
            <td><?= e($item['category']) ?></td>
            <td class="num"><?= $item['quantity'] ?></td>
            <td class="num"><?= money($item['unit_price']) ?></td>
            <td class="num"><?= money($item['line_total']) ?></td>
            <td>
                <?php if (!$payment): ?>
                    <form method="post" class="inline">
                        <input type="hidden" name="action" value="remove_item">
                        <input type="hidden" name="order_item_id" value="<?= $item['order_item_id'] ?>">
                        <button class="small danger">Remove</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$items): ?>
        <tr><td colspan="6" class="muted">No items yet.</td></tr>
    <?php endif; ?>
    <tr><th colspan="4" class="num">Order total</th><th class="num"><?= money($total) ?></th><th></th></tr>
</table>

<?php if (!$payment): ?>
<div class="card">
    <h3>Add an item</h3>
    <form method="post" class="form-row">
        <input type="hidden" name="action" value="add_item">
        <label>Menu item
            <select name="menu_item_id" required>
                <?php foreach ($menu as $m): ?>
                    <option value="<?= $m['menu_item_id'] ?>"><?= e($m['item_name']) ?> (<?= money($m['price']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Quantity <input name="quantity" type="number" min="1" value="1" required></label>
        <button type="submit">Add to order</button>
    </form>
</div>
<?php endif; ?>

<div class="card">
    <h3>Order status</h3>
    <form method="post" class="form-row">
        <input type="hidden" name="action" value="update_status">
        <label>Status
            <select name="order_status">
                <?php foreach (ORDER_STATUSES as $s): ?>
                    <option <?= $order['order_status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit">Update status</button>
    </form>
</div>

<div class="card">
    <h3>Payment</h3>
    <?php if ($payment): ?>
        <p><span class="badge <?= e($payment['payment_status']) ?>"><?= e($payment['payment_status']) ?></span>
            <?= money($payment['amount']) ?> by <?= e($payment['payment_method']) ?> on <?= e($payment['payment_date']) ?></p>
    <?php else: ?>
        <form method="post" class="form-row">
            <input type="hidden" name="action" value="pay">
            <label>Method
                <select name="payment_method">
                    <?php foreach (PAYMENT_METHODS as $pm): ?><option><?= $pm ?></option><?php endforeach; ?>
                </select>
            </label>
            <button type="submit">Record payment of <?= money($total) ?></button>
        </form>
    <?php endif; ?>
</div>

<p><a href="orders.php">← Back to all orders</a></p>
<?php require 'footer.php'; ?>
