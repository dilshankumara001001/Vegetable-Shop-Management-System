<?php
require_once __DIR__.'/auth.php';
requireLogin();
$u = currentUser();
$current = basename($_SERVER['PHP_SELF']);
$nav = [
    'index.php'      => ['📊','Dashboard'],
    'vegetables.php' => ['🥦','Vegetables'],
    'suppliers.php'  => ['👨‍🌾','Suppliers'],
    'stock_in.php'   => ['📥','Stock In'],
    'inventory.php'  => ['📦','Inventory'],
    'sales.php'      => ['💰','Sales & Billing'],
    'reports.php'    => ['📈','Reports'],
];
if (isAdmin()) $nav['users.php'] = ['👤','Users'];
?>
<!DOCTYPE html>
<html lang="si">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle ?? 'Vegie Pro') ?> | Vegie Pro</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="loading-bar"></div>

<div class="sidebar">
    <h2>🥦 VEGIE PRO</h2>
    <nav>
        <?php $i=1; foreach ($nav as $file => $info): ?>
            <a href="<?= e($file) ?>" class="animate-left delay-<?= min($i,10) ?> <?= $current === $file ? 'active' : '' ?>">
                <span><?= $info[0] ?></span> <?= e($info[1]) ?>
            </a>
        <?php $i++; endforeach; ?>
    </nav>
    <div class="sidebar-foot animate-up delay-5">
        <div class="who">
            <strong><?= e($u['name']) ?></strong>
            <small><?= e($u['role']) ?></small>
        </div>
        <a class="logout" href="logout.php">Logout →</a>
    </div>
</div>

<div class="main-wrap">
    <div class="topbar">
        <h1><?= e($pageTitle ?? 'Dashboard') ?></h1>
        <span class="date">
            📅 <?= date('Y-m-d') ?>
            <span class="live-clock">--:--:--</span>
        </span>
    </div>
    <div class="main-content">
        <?php if ($f = getFlash()): ?>
            <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
        <?php endif; ?>