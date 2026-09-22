<?php
require_once 'auth.php';
requireLogin();
$pageTitle = 'Stock In (Purchase)';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $veg_id = (int)$_POST['veg_id'];
    $sup_id = (int)($_POST['supplier_id'] ?? 0) ?: null;
    $qty = (float)$_POST['qty'];
    $price = (float)$_POST['price'];
    if ($veg_id>0 && $qty>0 && $price>0) {
        $stmt = $conn->prepare("INSERT INTO stock (veg_id,supplier_id,quantity_kg,remaining_kg,buying_price) VALUES (?,?,?,?,?)");
        $stmt->bind_param('iiddd', $veg_id, $sup_id, $qty, $qty, $price);
        $stmt->execute(); $stmt->close();
        $mv = $conn->prepare("INSERT INTO stock_movements (veg_id,type,quantity_kg,ref,note) VALUES (?, 'in', ?, 'PURCHASE', 'Stock received')");
        $mv->bind_param('id', $veg_id, $qty); $mv->execute(); $mv->close();
        $_SESSION['flash_js']='Stock added successfully'; flash('Stock added successfully.');
    } else { flash('Invalid input.', 'error'); }
    header('Location: stock_in.php'); exit;
}

$vegs = $conn->query("SELECT id,name FROM vegetables ORDER BY name");
$sups = $conn->query("SELECT id,name FROM suppliers ORDER BY name");
$recent = $conn->query("SELECT s.*,v.name veg,sp.name sup FROM stock s JOIN vegetables v ON v.id=s.veg_id LEFT JOIN suppliers sp ON sp.id=s.supplier_id ORDER BY s.id DESC LIMIT 10");
$totalToday = (float)$conn->query("SELECT COALESCE(SUM(quantity_kg),0) s FROM stock WHERE DATE(date_added)=CURDATE()")->fetch_assoc()['s'];
$valueToday = (float)$conn->query("SELECT COALESCE(SUM(quantity_kg*buying_price),0) s FROM stock WHERE DATE(date_added)=CURDATE()")->fetch_assoc()['s'];
include 'header.php';
?>
<div class="cards">
    <div class="card border-green animate-bounce delay-1">
        <div class="icon-badge">📥</div>
        <h4>Today's Stock In</h4>
        <h2 data-counter data-target="<?= $totalToday ?>" data-decimals="2" data-suffix=" kg">0</h2>
    </div>
    <div class="card border-orange animate-bounce delay-2">
        <div class="icon-badge">💵</div>
        <h4>Today's Purchase Value</h4>
        <h2 data-counter data-target="<?= $valueToday ?>" data-decimals="2" data-prefix="Rs. ">0</h2>
    </div>
    <div class="card border-blue animate-bounce delay-3">
        <div class="icon-badge">🔄</div>
        <h4>Total Batches</h4>
        <h2 data-counter data-target="<?= (int)$conn->query("SELECT COUNT(*) c FROM stock")->fetch_assoc()['c'] ?>">0</h2>
    </div>
</div>

<div class="panel animate-up">
    <h3>📥 Add Stock Batch</h3>
    <form method="POST" class="row-form">
        <select name="veg_id" required>
            <option value="">-- Vegetable --</option>
            <?php while($v=$vegs->fetch_assoc()): ?>
                <option value="<?= (int)$v['id'] ?>"><?= e($v['name']) ?></option>
            <?php endwhile; ?>
        </select>
        <select name="supplier_id">
            <option value="">-- Supplier (optional) --</option>
            <?php while($s=$sups->fetch_assoc()): ?>
                <option value="<?= (int)$s['id'] ?>"><?= e($s['name']) ?></option>
            <?php endwhile; ?>
        </select>
        <input type="number" name="qty" step="0.01" placeholder="Quantity (kg)" required>
        <input type="number" name="price" step="0.01" placeholder="Buying price /kg" required>
        <button class="btn" type="submit">📥 Add Stock</button>
    </form>
</div>

<div class="panel animate-up delay-1">
    <h3>🕐 Recent Stock In <span class="badge-count">Last 10</span></h3>
    <div class="filter-bar">
        <input type="text" id="stockSearch" data-search="stockTable" placeholder="Search vegetable, supplier...">
    </div>
    <div class="table-wrap">
    <table id="stockTable" data-sortable>
        <thead><tr><th>#</th><th>Vegetable</th><th>Supplier</th><th>Qty</th><th>Remaining</th><th>Buying Price</th><th>Total</th><th>Date</th></tr></thead>
        <tbody>
        <?php while ($r = $recent->fetch_assoc()): ?>
            <tr>
                <td>#<?= (int)$r['id'] ?></td>
                <td><b><?= e($r['veg']) ?></b></td>
                <td><?= e($r['sup'] ?? '—') ?></td>
                <td><span class="badge badge-blue"><?= money($r['quantity_kg']) ?> kg</span></td>
                <td>
                    <b class="<?= $r['remaining_kg'] < $r['quantity_kg']*0.3 ? 'danger' : 'ok' ?>"><?= money($r['remaining_kg']) ?> kg</b>
                    <div class="progress">
                        <div class="progress-bar <?= $r['remaining_kg'] < $r['quantity_kg']*0.3 ? 'danger' : '' ?>" style="--target:<?= min(100, ($r['remaining_kg']/$r['quantity_kg'])*100) ?>%"></div>
                    </div>
                </td>
                <td>Rs. <?= money($r['buying_price']) ?></td>
                <td><b>Rs. <?= money($r['quantity_kg']*$r['buying_price']) ?></b></td>
                <td><?= e(date('Y-m-d', strtotime($r['date_added']))) ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    </div>
</div>
<?php include 'footer.php'; ?>