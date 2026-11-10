<?php
// Orders list + start a new order for a customer.
require 'db.php';
$db = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    $customer_id = (int) ($_POST['customer_id'] ?? 0);
    try {
        $stmt = $db->prepare("INSERT INTO cafe_orders (customer_id, order_status) VALUES (?, 'Pending')");
        $stmt->execute([$customer_id]);
        $new_id = $db->lastInsertId();
        flash("Order #{$new_id} started. Add items below.");
        redirect("order.php?id={$new_id}");
    } catch (PDOException $e) {
        flash('Could not create the order. Pick a customer first.', 'error');
        redirect('orders.php');
    }
}

$status_filter = $_GET['status'] ?? '';
$sql = "
    SELECT o.order_id, o.order_date, o.order_status, c.customer_name,
           COUNT(oi.order_item_id) AS line_count,
           COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS total,
           p.payment_status
    FROM cafe_orders o
    JOIN customers c ON c.customer_id = o.customer_id
    LEFT JOIN order_items oi ON oi.order_id = o.order_id
    LEFT JOIN payments p ON p.order_id = o.order_id
";
$params = [];
if (in_array($status_filter, ORDER_STATUSES, true)) {
    $sql .= ' WHERE o.order_status = ?';
    $params[] = $status_filter;
}
$sql .= ' GROUP BY o.order_id, o.order_date, o.order_status, c.customer_name, p.payment_status
          ORDER BY o.order_date DESC, o.order_id DESC';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$customers = $db->query('SELECT customer_id, customer_name FROM customers ORDER BY customer_name')->fetchAll();

$page_title = 'Orders';
require 'header.php';
?>
<h1>Orders</h1>

<div class="card">
    <h3>Start a new order</h3>
    <form method="post" class="form-row">
        <input type="hidden" name="action" value="create">
        <label>Customer
            <select name="customer_id" required>
                <option value="">Choose…</option>
                <?php foreach ($customers as $c): ?>
                    <option value="<?= $c['customer_id'] ?>"><?= e($c['customer_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit">Create order</button>
    </form>
</div>

<form method="get" class="form-row" style="margin-bottom:12px">
    <label>Filter by status
        <select name="status" onchange="this.form.submit()">
            <option value="">All</option>
            <?php foreach (ORDER_STATUSES as $s): ?>
                <option <?= $status_filter === $s ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
        </select>
    </label>
</form>

<table>
    <tr><th>Order #</th><th>Customer</th><th>Date</th><th>Status</th><th class="num">Items</th><th class="num">Total</th><th>Payment</th></tr>
    <?php foreach ($orders as $o): ?>
        <tr>
            <td><a href="order.php?id=<?= $o['order_id'] ?>">#<?= $o['order_id'] ?></a></td>
            <td><?= e($o['customer_name']) ?></td>
            <td><?= e($o['order_date']) ?></td>
            <td><span class="badge <?= e($o['order_status']) ?>"><?= e($o['order_status']) ?></span></td>
            <td class="num"><?= $o['line_count'] ?></td>
            <td class="num"><?= money($o['total']) ?></td>
            <td><span class="badge <?= e($o['payment_status'] ?? 'Unpaid') ?>"><?= e($o['payment_status'] ?? 'Unpaid') ?></span></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$orders): ?>
        <tr><td colspan="7" class="muted">No orders found.</td></tr>
    <?php endif; ?>
</table>
<?php require 'footer.php'; ?>
