<?php
// Customers: create, read, update, delete.
require 'db.php';
$db = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $name  = trim($_POST['customer_name'] ?? '');
    $email = trim($_POST['email'] ?? '') ?: null;
    $phone = trim($_POST['phone'] ?? '') ?: null;

    try {
        if (($action === 'create' || $action === 'update') && $name === '') {
            flash('Customer name is required.', 'error');
            redirect('customers.php');
        }
        if (($action === 'create' || $action === 'update') && $email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('That email address doesn’t look valid.', 'error');
            redirect('customers.php');
        }

        if ($action === 'create') {
            $stmt = $db->prepare('INSERT INTO customers (customer_name, email, phone) VALUES (?, ?, ?)');
            $stmt->execute([$name, $email, $phone]);
            flash("Added customer {$name}.");
        } elseif ($action === 'update') {
            $stmt = $db->prepare('UPDATE customers SET customer_name = ?, email = ?, phone = ? WHERE customer_id = ?');
            $stmt->execute([$name, $email, $phone, (int) $_POST['customer_id']]);
            flash("Updated {$name}.");
        } elseif ($action === 'delete') {
            $stmt = $db->prepare('DELETE FROM customers WHERE customer_id = ?');
            $stmt->execute([(int) $_POST['customer_id']]);
            flash('Customer deleted.');
        }
    } catch (PDOException $e) {
        if ($e->errorInfo[1] == 1451) {
            flash('That customer has orders, so they can’t be deleted.', 'error');
        } else {
            flash('Database error: ' . $e->getMessage(), 'error');
        }
    }
    redirect('customers.php');
}

$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare('SELECT * FROM customers WHERE customer_id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $editing = $stmt->fetch();
}

$customers = $db->query('
    SELECT c.*, COUNT(o.order_id) AS order_count
    FROM customers c
    LEFT JOIN cafe_orders o ON o.customer_id = c.customer_id
    GROUP BY c.customer_id, c.customer_name, c.email, c.phone
    ORDER BY c.customer_name
')->fetchAll();

$page_title = 'Customers';
require 'header.php';
?>
<h1>Customers</h1>

<div class="card">
    <h3><?= $editing ? 'Edit customer' : 'Add a customer' ?></h3>
    <form method="post" class="form-row">
        <input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>">
        <?php if ($editing): ?>
            <input type="hidden" name="customer_id" value="<?= $editing['customer_id'] ?>">
        <?php endif; ?>
        <label>Name <input name="customer_name" required value="<?= e($editing['customer_name'] ?? '') ?>"></label>
        <label>Email <input name="email" type="email" value="<?= e($editing['email'] ?? '') ?>"></label>
        <label>Phone <input name="phone" value="<?= e($editing['phone'] ?? '') ?>"></label>
        <button type="submit"><?= $editing ? 'Save changes' : 'Add customer' ?></button>
        <?php if ($editing): ?><a class="btn" href="customers.php">Cancel</a><?php endif; ?>
    </form>
</div>

<table>
    <tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th class="num">Orders</th><th></th></tr>
    <?php foreach ($customers as $c): ?>
        <tr>
            <td><?= $c['customer_id'] ?></td>
            <td><?= e($c['customer_name']) ?></td>
            <td><?= e($c['email'] ?? '') ?></td>
            <td><?= e($c['phone'] ?? '') ?></td>
            <td class="num"><?= $c['order_count'] ?></td>
            <td>
                <a class="btn small" href="customers.php?edit=<?= $c['customer_id'] ?>">Edit</a>
                <form method="post" class="inline" onsubmit="return confirm('Delete this customer?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="customer_id" value="<?= $c['customer_id'] ?>">
                    <button class="small danger">Delete</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<?php require 'footer.php'; ?>
