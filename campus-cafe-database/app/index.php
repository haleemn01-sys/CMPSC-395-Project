<?php
// Dashboard: proves PHP is connected to MariaDB and summarizes the data.
require 'db.php';
$db = get_db();

$server_version = $db->query('SELECT VERSION()')->fetchColumn();
$database_name  = $db->query('SELECT DATABASE()')->fetchColumn();

$counts = [];
foreach (['customers', 'menu_items', 'cafe_orders', 'order_items', 'payments'] as $table) {
    // Table names come from the fixed list above, not user input.
    $counts[$table] = $db->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
}

$revenue = $db->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_status = 'Paid'")->fetchColumn();

$recent_orders = $db->query("
    SELECT o.order_id, o.order_date, o.order_status, c.customer_name,
           COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS total,
           p.payment_status
    FROM cafe_orders o
    JOIN customers c ON c.customer_id = o.customer_id
    LEFT JOIN order_items oi ON oi.order_id = o.order_id
    LEFT JOIN payments p ON p.order_id = o.order_id
    GROUP BY o.order_id, o.order_date, o.order_status, c.customer_name, p.payment_status
    ORDER BY o.order_date DESC, o.order_id DESC
    LIMIT 5
")->fetchAll();

$page_title = 'Dashboard';
require 'header.php';
?>
<h1>Dashboard</h1>

<div class="card">
    <p class="status-ok">✔ Connected to MariaDB</p>
    <p>Database: <b><?= e($database_name) ?></b> · Server version: <b><?= e($server_version) ?></b></p>
</div>

<div class="stats">
    <div class="stat"><b><?= $counts['customers'] ?></b>Customers</div>
    <div class="stat"><b><?= $counts['menu_items'] ?></b>Menu items</div>
    <div class="stat"><b><?= $counts['cafe_orders'] ?></b>Orders</div>
    <div class="stat"><b><?= $counts['order_items'] ?></b>Order items</div>
    <div class="stat"><b><?= $counts['payments'] ?></b>Payments</div>
    <div class="stat"><b><?= money($revenue) ?></b>Paid revenue</div>
</div>

<h2>Recent orders</h2>
<table>
    <tr><th>Order #</th><th>Customer</th><th>Date</th><th>Status</th><th class="num">Total</th><th>Payment</th></tr>
    <?php foreach ($recent_orders as $o): ?>
        <tr>
            <td><a href="order.php?id=<?= $o['order_id'] ?>">#<?= $o['order_id'] ?></a></td>
            <td><?= e($o['customer_name']) ?></td>
            <td><?= e($o['order_date']) ?></td>
            <td><span class="badge <?= e($o['order_status']) ?>"><?= e($o['order_status']) ?></span></td>
            <td class="num"><?= money($o['total']) ?></td>
            <td><span class="badge <?= e($o['payment_status'] ?? 'Unpaid') ?>"><?= e($o['payment_status'] ?? 'Unpaid') ?></span></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$recent_orders): ?>
        <tr><td colspan="6" class="muted">No orders yet.</td></tr>
    <?php endif; ?>
</table>
<?php require 'footer.php'; ?>
