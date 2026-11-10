<?php
// Menu items: create, read, update, delete.
require 'db.php';
$db = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $name     = trim($_POST['item_name'] ?? '');
    $category = $_POST['category'] ?? '';
    $price    = $_POST['price'] ?? '';
    $available = isset($_POST['is_available']) ? 1 : 0;

    try {
        if ($action === 'create' || $action === 'update') {
            if ($name === '' || !in_array($category, MENU_CATEGORIES, true) || !is_numeric($price) || $price < 0) {
                flash('Please enter a name, a category, and a price of 0 or more.', 'error');
                redirect($action === 'update' ? 'menu.php?edit=' . (int) $_POST['menu_item_id'] : 'menu.php');
            }
        }

        if ($action === 'create') {
            $stmt = $db->prepare('INSERT INTO menu_items (item_name, category, price, is_available) VALUES (?, ?, ?, ?)');
            $stmt->execute([$name, $category, $price, $available]);
            flash("Added “{$name}” to the menu.");
        } elseif ($action === 'update') {
            $stmt = $db->prepare('UPDATE menu_items SET item_name = ?, category = ?, price = ?, is_available = ? WHERE menu_item_id = ?');
            $stmt->execute([$name, $category, $price, $available, (int) $_POST['menu_item_id']]);
            flash("Updated “{$name}”.");
        } elseif ($action === 'delete') {
            $stmt = $db->prepare('DELETE FROM menu_items WHERE menu_item_id = ?');
            $stmt->execute([(int) $_POST['menu_item_id']]);
            flash('Menu item deleted.');
        }
    } catch (PDOException $e) {
        // 1451 = row is still referenced by a foreign key (it's in an order).
        if ($e->errorInfo[1] == 1451) {
            flash('That item is part of an existing order, so it can’t be deleted. Mark it unavailable instead.', 'error');
        } else {
            flash('Database error: ' . $e->getMessage(), 'error');
        }
    }
    redirect('menu.php');
}

$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare('SELECT * FROM menu_items WHERE menu_item_id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $editing = $stmt->fetch();
}

$items = $db->query('SELECT * FROM menu_items ORDER BY category, item_name')->fetchAll();

$page_title = 'Menu';
require 'header.php';
?>
<h1>Menu</h1>

<div class="card">
    <h3><?= $editing ? 'Edit menu item' : 'Add a menu item' ?></h3>
    <form method="post" class="form-row">
        <input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>">
        <?php if ($editing): ?>
            <input type="hidden" name="menu_item_id" value="<?= $editing['menu_item_id'] ?>">
        <?php endif; ?>
        <label>Name <input name="item_name" required value="<?= e($editing['item_name'] ?? '') ?>"></label>
        <label>Category
            <select name="category">
                <?php foreach (MENU_CATEGORIES as $cat): ?>
                    <option <?= ($editing['category'] ?? '') === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Price <input name="price" type="number" step="0.01" min="0" required value="<?= e($editing['price'] ?? '') ?>"></label>
        <label><span>Available</span>
            <input type="checkbox" name="is_available" <?= ($editing === null || $editing['is_available']) ? 'checked' : '' ?>>
        </label>
        <button type="submit"><?= $editing ? 'Save changes' : 'Add item' ?></button>
        <?php if ($editing): ?><a class="btn" href="menu.php">Cancel</a><?php endif; ?>
    </form>
</div>

<table>
    <tr><th>ID</th><th>Item</th><th>Category</th><th class="num">Price</th><th>Available</th><th></th></tr>
    <?php foreach ($items as $item): ?>
        <tr>
            <td><?= $item['menu_item_id'] ?></td>
            <td><?= e($item['item_name']) ?></td>
            <td><?= e($item['category']) ?></td>
            <td class="num"><?= money($item['price']) ?></td>
            <td><?= $item['is_available'] ? 'Yes' : 'No' ?></td>
            <td>
                <a class="btn small" href="menu.php?edit=<?= $item['menu_item_id'] ?>">Edit</a>
                <form method="post" class="inline" onsubmit="return confirm('Delete this menu item?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="menu_item_id" value="<?= $item['menu_item_id'] ?>">
                    <button class="small danger">Delete</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<?php require 'footer.php'; ?>
