<?php
require_once 'auth.php';
requireLogin();
$pageTitle = 'System Overview';

$totalKg = (float)($conn->query("SELECT COALESCE(SUM(remaining_kg),0) s FROM stock")->fetch_assoc()['s'] ?? 0);
$stockValue = (float)($conn->query("SELECT COALESCE(SUM(remaining_kg*buying_price),0) v FROM stock")->fetch_assoc()['v'] ?? 0);
$today = $conn->query("SELECT COALESCE(SUM(quantity_kg*selling_price),0) income,
                              COALESCE(SUM(quantity_kg*selling_price - quantity_kg*cost_price),0) profit
                       FROM sales WHERE DATE(sale_date)=CURDATE()")->fetch_assoc();
$low = (int)($conn->query("SELECT COUNT(*) c FROM (SELECT v.id, COALESCE(SUM(s.remaining_kg),0) q, v.reorder_level FROM vegetables v LEFT JOIN stock s ON s.veg_id=v.id GROUP BY v.id HAVING q<=v.reorder_level) t")->fetch_assoc()['c'] ?? 0);
$totalSales = (float)($conn->query("SELECT COALESCE(SUM(quantity_kg*selling_price),0) v FROM sales")->fetch_assoc()['v'] ?? 0);
$totalInvoices = (int)($conn->query("SELECT COUNT(DISTINCT invoice_no) c FROM sales")->fetch_assoc()['c'] ?? 0);

$recent = $conn->query("SELECT s.*,v.name FROM sales s JOIN vegetables v ON v.id=s.veg_id ORDER BY s.id DESC LIMIT 6");
$lowList = $conn->query("SELECT v.name,v.reorder_level,COALESCE(SUM(s.remaining_kg),0) q FROM vegetables v LEFT JOIN stock s ON s.veg_id=v.id GROUP BY v.id HAVING q<=v.reorder_level ORDER BY q ASC LIMIT 6");

// Last 7 days chart
$chart = [];
for ($i=6; $i>=0; $i--) {
    $d = date('Y-m-d', strtotime("-$i day"));
    $stmt = $conn->prepare("SELECT COALESCE(SUM(quantity_kg*selling_price),0) v FROM sales WHERE DATE(sale_date)=?");
    $stmt->bind_param('s', $d); $stmt->execute();
    $chart[] = ['d'=>date('D', strtotime($d)), 'v'=>(float)$stmt->get_result()->fetch_assoc()['v']];
    $stmt->close();
}
$maxChart = max(array_column($chart,'v')) ?: 1;
$topVeg = $conn->query("SELECT v.name, SUM(s.quantity_kg) q FROM sales s JOIN vegetables v ON v.id=s.veg_id GROUP BY v.id ORDER BY q DESC LIMIT 3");
include 'header.php';
?>
<!-- Feature 1: Animated Stat Cards with counters -->
<div class="cards">
    <div class="card border-green animate-bounce delay-1">
        <div class="icon-badge">📦</div>
        <h4>Total Inventory</h4>
        <h2 data-counter data-target="<?= $totalKg ?>" data-decimals="2" data-suffix=" kg">0</h2>
    </div>
    <div class="card border-blue animate-bounce delay-2">
        <div class="icon-badge">💎</div>
        <h4>Stock Value</h4>
        <h2 data-counter data-target="<?= $stockValue ?>" data-decimals="2" data-prefix="Rs. ">0</h2>
    </div>
    <div class="card border-orange animate-bounce delay-3">
        <div class="icon-badge">💰</div>
        <h4>Today's Income</h4>
        <h2 data-counter data-target="<?= $today['income'] ?? 0 ?>" data-decimals="2" data-prefix="Rs. ">0</h2>
    </div>
    <div class="card border-purple animate-bounce delay-4">
        <div class="icon-badge">📈</div>
        <h4>Today's Profit</h4>
        <h2 data-counter data-target="<?= $today['profit'] ?? 0 ?>" data-decimals="2" data-prefix="Rs. ">0</h2>
    </div>
    <div class="card border-red animate-bounce delay-5">
        <div class="icon-badge">⚠️</div>
        <h4>Low Stock Items</h4>
        <h2 data-counter data-target="<?= $low ?>" data-suffix=" items">0</h2>
    </div>
</div>

<div class="grid-2">
    <!-- Feature 5: CSS bar chart -->
    <div class="panel animate-up">
        <h3>📊 Last 7 Days Sales <span class="badge-count">Total: Rs. <?= money(array_sum(array_column($chart,'v'))) ?></span></h3>
        <div class="chart-bars">
            <?php foreach ($chart as $c): ?>
                <div class="chart-bar" data-label="<?= e($c['d']) ?>"
                     style="height: <?= max(15, ($c['v']/$maxChart)*100) ?>%"
                     title="<?= e($c['d']) ?>: Rs. <?= money($c['v']) ?>"></div>
            <?php endforeach; ?>
        </div>
        <p style="text-align:center;color:var(--muted);font-size:12px;margin-top:12px">Hover bars for details</p>
    </div>

    <!-- Feature 6: Progress ring + stats -->
    <div class="panel animate-up delay-1">
        <h3>🎯 Overall Performance</h3>
        <div class="ring-wrap">
            <svg width="120" height="120">
                <circle class="ring-bg" cx="60" cy="60" r="45"></circle>
                <circle class="ring-fill" cx="60" cy="60" r="45" style="--target:<?= max(30, min(283, (int)(283 - ($low*20)))) ?>"></circle>
            </svg>
            <div class="ring-label">
                <strong><?= $low == 0 ? '100%' : max(0, 100 - $low*10) . '%' ?></strong>
                <small>Health</small>
            </div>
        </div>
        <div style="margin-top:20px">
            <div class="stat-row"><span>Total Sales Revenue</span><strong>Rs. <?= money($totalSales) ?></strong></div>
            <div class="stat-row"><span>Total Invoices</span><strong><?= $totalInvoices ?></strong></div>
            <div class="stat-row"><span>Total Vegetables</span><strong><?= (int)$conn->query("SELECT COUNT(*) c FROM vegetables")->fetch_assoc()['c'] ?></strong></div>
            <div class="stat-row"><span>Total Suppliers</span><strong><?= (int)$conn->query("SELECT COUNT(*) c FROM suppliers")->fetch_assoc()['c'] ?></strong></div>
        </div>
    </div>
</div>

<div class="grid-2">
    <!-- Feature 7: Recent sales with hover -->
    <div class="panel animate-up delay-2">
        <h3>🕐 Recent Sales <span class="badge-count"><?= (int)$totalInvoices ?> total</span></h3>
        <div class="table-wrap">
        <table data-sortable>
            <thead><tr><th>Invoice</th><th>Buyer</th><th>Veg</th><th>Qty</th><th>Total</th></tr></thead>
            <tbody>
            <?php while ($r = $recent->fetch_assoc()): ?>
                <tr>
                    <td><a href="invoice.php?inv=<?= urlencode($r['invoice_no']) ?>"><?= e(substr($r['invoice_no'],-8)) ?></a></td>
                    <td><?= e($r['buyer_name']) ?></td>
                    <td><?= e($r['name']) ?></td>
                    <td><?= money($r['quantity_kg']) ?> kg</td>
                    <td><b>Rs. <?= money($r['quantity_kg']*$r['selling_price']) ?></b></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        </div>
    </div>

    <!-- Feature 8: Low stock pulse alerts -->
    <div class="panel animate-up delay-3">
        <h3>⚠️ Low Stock Alerts <?php if($low): ?><span class="badge badge-red badge-pulse"><?= $low ?> items</span><?php endif; ?></h3>
        <div class="table-wrap">
        <table>
            <thead><tr><th>Vegetable</th><th>Available</th><th>Reorder Lvl</th></tr></thead>
            <tbody>
            <?php if ($lowList->num_rows === 0): ?>
                <tr><td colspan="3" class="muted">✅ All good! No low stock.</td></tr>
            <?php else: while ($r = $lowList->fetch_assoc()): ?>
                <tr>
                    <td><b><?= e($r['name']) ?></b></td>
                    <td class="danger"><?= money($r['q']) ?> kg</td>
                    <td><?= money($r['reorder_level']) ?> kg</td>
                </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<!-- Feature 9: Top vegetables -->
<div class="panel animate-up delay-4">
    <h3>🏆 Top Selling Vegetables</h3>
    <div class="grid-3">
        <?php $rank=1; while($t=$topVeg->fetch_assoc()): ?>
        <div class="card border-<?= ['green','blue','orange'][$rank-1] ?? 'green' ?>" style="cursor:default">
            <div class="icon-badge"><?= ['🥇','🥈','🥉'][$rank-1] ?? '⭐' ?></div>
            <h4><?= e($t['name']) ?></h4>
            <h2><?= money($t['q']) ?> kg</h2>
        </div>
        <?php $rank++; endwhile; ?>
    </div>
</div>

<!-- Feature 10: Quick actions -->
<div class="panel animate-up delay-5">
    <h3>⚡ Quick Actions</h3>
    <div class="grid-3">
        <a href="sales.php" class="btn btn-accent">💰 New Sale</a>
        <a href="stock_in.php" class="btn">📥 Stock In</a>
        <a href="inventory.php" class="btn btn-blue">📦 View Inventory</a>
    </div>
</div>

<?php include 'footer.php'; ?>