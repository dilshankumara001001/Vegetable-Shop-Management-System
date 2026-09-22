<?php
require_once 'auth.php';
requireLogin();
$inv = $_GET['inv'] ?? '';
if ($inv==='') { header('Location: sales.php'); exit; }
$stmt = $conn->prepare("SELECT s.*,v.name veg,u.full_name cashier FROM sales s JOIN vegetables v ON v.id=s.veg_id LEFT JOIN users u ON u.id=s.user_id WHERE s.invoice_no=?");
$stmt->bind_param('s', $inv); $stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();
if (!$rows) die('Invoice not found.');
$grand = 0; foreach ($rows as $r) $grand += $r['quantity_kg']*$r['selling_price'];
$first = $rows[0];
?>
<!DOCTYPE html>
<html lang="si">
<head>
<meta charset="UTF-8">
<title>Invoice <?= e($inv) ?></title>
<link rel="stylesheet" href="style.css">
</head>
<body class="invoice-body">
<div class="invoice">
    <div class="inv-head">
        <div>
            <h1>🥦 VEGIE PRO</h1>
            <p>Vegetable Wholesale Center<br>Colombo Road, Sri Lanka<br>Tel: 011-2345678</p>
        </div>
        <div class="inv-meta">
            <h2>INVOICE</h2>
            <p><b>No:</b> <?= e($first['invoice_no']) ?><br>
               <b>Date:</b> <?= e(date('Y-m-d H:i', strtotime($first['sale_date']))) ?><br>
               <b>Cashier:</b> <?= e($first['cashier'] ?? '-') ?></p>
        </div>
    </div>
    <div class="inv-buyer">
        <h3>Bill To</h3>
        <p><b><?= e($first['buyer_name']) ?></b><br><?= e($first['buyer_phone'] ?? '') ?></p>
    </div>
    <table class="invoice-table">
        <thead><tr><th>#</th><th>Item</th><th>Qty (kg)</th><th>Rate</th><th>Amount</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $i=>$r): ?>
            <tr>
                <td><?= $i+1 ?></td>
                <td><?= e($r['veg']) ?></td>
                <td><?= money($r['quantity_kg']) ?></td>
                <td>Rs. <?= money($r['selling_price']) ?></td>
                <td>Rs. <?= money($r['quantity_kg']*$r['selling_price']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr><th colspan="4" style="text-align:right">GRAND TOTAL</th><th>Rs. <?= money($grand) ?></th></tr>
        </tfoot>
    </table>
    <p class="thanks">🌱 Thank you for your business!</p>
    <div class="inv-actions">
        <button class="btn" onclick="window.print()">🖨 Print</button>
        <a class="btn btn-blue" href="sales.php">← New Sale</a>
        <a class="btn-ghost" href="reports.php">📊 Reports</a>
    </div>
</div>
</body>
</html>