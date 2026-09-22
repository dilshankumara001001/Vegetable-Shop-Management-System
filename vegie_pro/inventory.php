<?php
require_once 'auth.php';
requireLogin();
$pageTitle = 'Inventory Status';

$list = $conn->query("SELECT v.id,v.name,v.category,v.unit,v.reorder_level,
        COALESCE(SUM(s.remaining_kg),0) qty,
        (SELECT buying_price FROM stock WHERE veg_id=v.id ORDER BY date_added DESC,id DESC LIMIT 1) last_buy
        FROM vegetables v LEFT JOIN stock s ON s.veg_id=v.id
        GROUP BY v.id ORDER BY qty ASC");
$totalValue = (float)$conn->query("SELECT COALESCE(SUM(remaining_kg*buying_price),0) v FROM stock")->fetch_assoc()['v'];
$totalKg = (float)$conn->query("SELECT COALESCE(SUM(remaining_kg),0) v FROM stock")->fetch_assoc()['v'];
$potentialRevenue = 0;
$rows = []; while($r=$list->fetch_assoc()) { $r['suggested'] = $r['last_buy']*1.2; $potentialRevenue += $r['qty']*$r['suggested']; $rows[]=$r; }
$lowCount = count(array_filter($rows, fn($r) => $r['qty'] <= $r['reorder_level']));
include 'header.php';
?>
<div class="cards">
    <div class="card border-green animate-bounce delay-1">
        <div class="icon-badge">📦</div>
        <h4>Total Stock</h4>
        <h2 data-counter data-target="<?= $totalKg ?>" data-decimals="2" data-suffix=" kg">0</h2>
    </div>
    <div class="card border-blue animate-bounce delay-2">
        <div class="icon-badge">💎</div>
        <h4>Stock Value</h4>
        <h2 data-counter data-target="<?= $totalValue ?>" data-decimals="2" data-prefix="Rs. ">0</h2>
    </div>
    <div class="card border-orange animate-bounce delay-3">
        <div class="icon-badge">💰</div>
        <h4>Potential Revenue</h4>
        <h2 data-counter data-target="<?= $potentialRevenue ?>" data-decimals="2" data-prefix="Rs. ">0</h2>
    </div>
    <div class="card border-red animate-bounce delay-4">
        <div class="icon-badge">⚠️</div>
        <h4>Low Stock</h4>
        <h2 data-counter data-target="<?= $lowCount ?>" data-suffix=" items">0</h2>
    </div>
</div>

<div class="panel animate-up">
    <h3>📦 Inventory Overview <span class="badge-count"><?= count($rows) ?> vegetables</span></h3>
    <div class="filter-bar">
        <input type="text" id="invSearch" data-search="invTable" placeholder="Search vegetables...">
        <select id="invStatus">
            <option value="">All Status</option>
            <option value="low">Only Low Stock</option>
            <option value="ok">Only In Stock</option>
        </select>
    </div>
    <div class="table-wrap">
    <table id="invTable" data-sortable>
        <thead>
            <tr>
                <th>Vegetable</th><th>Category</th>
                <th>Available</th><th>Last Buying</th>
                <th>Suggested Sell (+20%)</th><th>Profit /kg</th><th>Status</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r):
            $low = $r['qty'] <= $r['reorder_level'];
            $profit = $r['last_buy'] ? ($r['suggested'] - $r['last_buy']) : 0;
            $pct = $r['reorder_level'] > 0 ? min(100, ($r['qty']/($r['reorder_level']*3))*100) : 100;
        ?>
            <tr data-status="<?= $low ? 'low' : 'ok' ?>">
                <td><b><?= e($r['name']) ?></b></td>
                <td><span class="badge badge-blue"><?= e($r['category'] ?: 'N/A') ?></span></td>
                <td>
                    <b class="<?= $low ? 'danger' : 'ok' ?>"><?= money($r['qty']) ?> <?= e($r['unit']) ?></b>
                    <div class="progress">
                        <div class="progress-bar <?= $low ? 'danger' : '' ?>" style="--target:<?= $pct ?>%"></div>
                    </div>
                </td>
                <td><?= $r['last_buy'] ? 'Rs. '.money($r['last_buy']) : '—' ?></td>
                <td><?= $r['suggested'] ? '<b>Rs. '.money($r['suggested']).'</b>' : '—' ?></td>
                <td class="ok"><?= $profit ? 'Rs. '.money($profit) : '—' ?></td>
                <td>
                    <?php if ($low): ?>
                        <span class="badge badge-red badge-pulse">⚠ Low</span>
                    <?php else: ?>
                        <span class="badge badge-green">✓ OK</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>

<script>
document.getElementById('invStatus').addEventListener('change', function(){
    const s = this.value;
    document.querySelectorAll('#invTable tbody tr').forEach(tr => {
        tr.style.display = (!s || tr.dataset.status === s) ? '' : 'none';
    });
});
</script>
<?php include 'footer.php'; ?>