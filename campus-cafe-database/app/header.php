<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$current = basename($_SERVER['SCRIPT_NAME']);
$nav = [
    'index.php'     => 'Dashboard',
    'menu.php'      => 'Menu',
    'customers.php' => 'Customers',
    'orders.php'    => 'Orders',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title ?? 'Campus Cafe') ?> · Campus Cafe</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header class="topbar">
    <a class="brand" href="index.php">☕ Campus Cafe</a>
    <nav>
        <?php foreach ($nav as $file => $label): ?>
            <a href="<?= $file ?>" class="<?= $current === $file ? 'active' : '' ?>"><?= $label ?></a>
        <?php endforeach; ?>
    </nav>
</header>
<main>
<?php if (!empty($_SESSION['flash'])): ?>
    <div class="flash <?= e($_SESSION['flash']['type']) ?>"><?= e($_SESSION['flash']['message']) ?></div>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>
