<?php
require_once 'auth.php';
requireLogin();
$pageTitle = 'Reports';

$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to']   ?? date('Y-m-d');

// Summary
$stmt = $conn->prepare("SELECT COALESCE(SUM(quantity_kg*selling_price),0) rev,
                               COALESCE(SUM(quantity_kg*cost_price),0) cost,
                               COALESCE(SUM(quantity_kg),0) qty,
                               COUNT(DISTINCT invoice_no) invs
                        FROM sales WHERE DATE(sale_date) BETWEEN ? AND ?");
$stmt->bind_param('ss', $from, $to);
$stmt->execute();
$sum = $stmt->get_result()->fetch_assoc();
$stmt->close();
$profit = $sum['rev'] - $sum['cost'];
$margin = $sum['rev'] > 0 ? ($profit / $sum['rev']) * 100 : 0;

// Top selling
$top = $conn->prepare("SELECT v.name, SUM(s.quantity_kg) qty,
                              SUM(s.quantity_kg*s.selling_price) rev,
                              SUM(s.quantity_kg*s.cost_price) cost
                       FROM sales s JOIN vegetables v ON v.id=s.veg_id
                       WHERE DATE(s.sale_date) BETWEEN ? AND ?
                       GROUP BY v.id ORDER BY qty DESC LIMIT 10");
$top->bind_param('ss', $from, $to);
$top->execute();
$topRes = $top->get_result();
$top->close();

// Sales list
$list = $conn->prepare("SELECT s.*, v.name veg FROM sales s
                        JOIN vegetables v ON v.id=s.veg_id
                        WHERE DATE(s.sale_date) BETWEEN ? AND ?
                        ORDER BY s.id DESC LIMIT 200");
$list->bind_param('ss', $from, $to);
$list->execute();
$listRes = $list->get_result();
$list->close();

// Daily chart data
$chartRows = $conn->prepare("SELECT DATE(sale_date) d, SUM(quantity_kg*selling_price) v
                             FROM sales WHERE DATE(sale_date) BETWEEN ? AND ?
                             GROUP BY DATE(sale_date) ORDER BY d ASC LIMIT 14");
$chartRows->bind_param('ss', $from, $to);
$chartRows->execute();
$chartData = $chartRows->get_result()->fetch_all(MYSQLI_ASSOC);
$chartRows->close();
$maxC = !empty($chartData) ? max(array_column($chartData, 'v')) : 1;

// Payment / buyer summary
$buyers = $conn->prepare("SELECT buyer_name, COUNT(DISTINCT invoice_no) invs,
                                 SUM(quantity_kg*selling_price) total
                          FROM sales WHERE DATE(sale_date) BETWEEN ? AND ?
                          GROUP BY buyer_name ORDER BY total DESC LIMIT 5");
$buyers->bind_param('ss', $from, $to);
$buyers->execute();
$buyersRes = $buyers->get_result();
$buyers->close();
include 'header.php';
?>

<!-- Toolbar (screen only) -->
<div class="panel no-print" style="display:flex;flex-wrap:wrap;gap:10px;align-items:end">
    <form method="GET" class="row-form" style="flex:1;grid-template-columns:auto 1fr auto 1fr auto">
        <label style="align-self:center">From</label>
        <input type="date" name="from" value="<?= e($from) ?>">
        <label style="align-self:center">To</label>
        <input type="date" name="to" value="<?= e($to) ?>">
        <button class="btn" type="submit">🔎 Filter</button>
    </form>
    <button class="btn btn-accent" onclick="window.print()" style="width:auto;padding:12px 24px">🖨 Print Report</button>
    <button class="btn btn-blue" onclick="exportCSV()" style="width:auto;padding:12px 24px">⬇ Export CSV</button>
</div>

<!-- ============================================================
     PRINTABLE AREA
============================================================ -->
<div id="printArea">

    <!-- Print Header (visible only when printing) -->
    <div class="print-header">
        <div style="display:flex;justify-content:space-between;align-items:flex-start">
            <div>
                <h1 style="margin:0;color:#27ae60">🥦 VEGIE PRO</h1>
                <p style="margin:6px 0 0;color:#7f8c8d;font-size:12px;line-height:1.6">
                    Vegetable Wholesale Center<br>
                    No. 42, Colombo Road, Kandy<br>
                    Tel: 011-2345678 &nbsp;|&nbsp; Email: info@vegiepro.lk
                </p>
            </div>
            <div style="text-align:right">
                <h2 style="margin:0;color:#2c3e50;letter-spacing:1px">SALES REPORT</h2>
                <p style="margin:6px 0 0;color:#7f8c8d;font-size:12px">
                    Period: <b><?= e($from) ?></b> to <b><?= e($to) ?></b><br>
                    Generated: <?= date('Y-m-d H:i') ?><br>
                    By: <?= e(currentUser()['name']) ?>
                </p>
            </div>
        </div>
        <hr style="border:none;border-top:2px solid #2c3e50;margin:18px 0">
    </div>

    <!-- Summary Cards -->
    <div class="cards">
        <div class="card border-blue animate-bounce delay-1"><div class="icon-badge no-print">💵</div><h4>Revenue</h4><h2 data-counter data-target="<?= $sum['rev'] ?>" data-decimals="2" data-prefix="Rs. ">Rs. 0.00</h2></div>
        <div class="card border-orange animate-bounce delay-2"><div class="icon-badge no-print">📉</div><h4>Cost</h4><h2 data-counter data-target="<?= $sum['cost'] ?>" data-decimals="2" data-prefix="Rs. ">Rs. 0.00</h2></div>
        <div class="card border-green animate-bounce delay-3"><div class="icon-badge no-print">📈</div><h4>Profit</h4><h2 data-counter data-target="<?= $profit ?>" data-decimals="2" data-prefix="Rs. ">Rs. 0.00</h2></div>
        <div class="card border-purple animate-bounce delay-4"><div class="icon-badge no-print">⚖️</div><h4>Total Sold</h4><h2 data-counter data-target="<?= $sum['qty'] ?>" data-decimals="2" data-suffix=" kg">0 kg</h2></div>
        <div class="card border-red animate-bounce delay-5"><div class="icon-badge no-print">🧾</div><h4>Invoices</h4><h2 data-counter data-target="<?= (int)$sum['invs'] ?>">0</h2></div>
    </div>

    <!-- Profit Margin Banner -->
    <div class="panel animate-up" style="background:linear-gradient(135deg,#e8f8ee,#d4f0e0);border-left:5px solid var(--green)">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px">
            <div>
                <h4 style="margin:0;color:var(--green-d);font-size:12px;text-transform:uppercase;letter-spacing:1px">Profit Margin</h4>
                <h2 style="margin:6px 0 0;color:var(--green-d);font-size:26px"><?= number_format($margin, 2) ?>%</h2>
            </div>
            <div style="flex:1;min-width:250px">
                <div class="progress" style="height:14px">
                    <div class="progress-bar" style="--target:<?= min(100, max(0, $margin * 2)) ?>%"></div>
                </div>
                <p style="margin:8px 0 0;color:var(--green-d);font-size:12px">
                    Profit: <b>Rs. <?= money($profit) ?></b> of Rs. <?= money($sum['rev']) ?> revenue
                </p>
            </div>
        </div>
    </div>

    <!-- Daily Chart -->
    <div class="panel animate-up delay-1">
        <h3>📊 Daily Sales Chart</h3>
        <?php if (empty($chartData)): ?>
            <p class="muted">No data available for this period</p>
        <?php else: ?>
        <div class="chart-bars">
            <?php foreach ($chartData as $c): ?>
                <div class="chart-bar"
                     data-label="<?= e(date('m-d', strtotime($c['d']))) ?>"
                     style="height: <?= max(10, ($c['v']/$maxC)*100) ?>%;animation:none"
                     title="<?= e($c['d']) ?>: Rs. <?= money($c['v']) ?>"></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Two columns: Top Selling + Top Buyers -->
    <div class="grid-2">
        <div class="panel animate-up delay-2">
            <h3>🏆 Top Selling Vegetables</h3>
            <div class="table-wrap">
            <table>
                <thead><tr><th>#</th><th>Vegetable</th><th>Qty Sold</th><th>Revenue</th><th>Profit</th></tr></thead>
                <tbody>
                <?php if ($topRes->num_rows === 0): ?>
                    <tr><td colspan="5" class="muted">No sales in range</td></tr>
                <?php else: $i=1; while($r=$topRes->fetch_assoc()): ?>
                    <tr>
                        <td><span class="badge badge-<?= ['green','blue','orange'][min($i-1,2)] ?? 'green' ?>"><?= $i ?></span></td>
                        <td><b><?= e($r['name']) ?></b></td>
                        <td><?= money($r['qty']) ?> kg</td>
                        <td>Rs. <?= money($r['rev']) ?></td>
                        <td class="ok">Rs. <?= money($r['rev'] - $r['cost']) ?></td>
                    </tr>
                <?php $i++; endwhile; endif; ?>
                </tbody>
            </table>
            </div>
        </div>

        <div class="panel animate-up delay-3">
            <h3>👥 Top Buyers</h3>
            <div class="table-wrap">
            <table>
                <thead><tr><th>#</th><th>Buyer</th><th>Invoices</th><th>Total Spent</th></tr></thead>
                <tbody>
                <?php if ($buyersRes->num_rows === 0): ?>
                    <tr><td colspan="4" class="muted">No buyers in range</td></tr>
                <?php else: $i=1; while($r=$buyersRes->fetch_assoc()): ?>
                    <tr>
                        <td><b><?= $i ?></b></td>
                        <td><?= e($r['buyer_name']) ?></td>
                        <td><span class="badge badge-blue"><?= (int)$r['invs'] ?></span></td>
                        <td><b>Rs. <?= money($r['total']) ?></b></td>
                    </tr>
                <?php $i++; endwhile; endif; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>

    <!-- Full Sales Detail -->
    <div class="panel animate-up delay-4">
        <h3>📋 Sales Detail <span class="badge-count"><?= $listRes->num_rows ?> records</span></h3>
        <div class="table-wrap" style="max-height:500px;overflow-y:auto">
        <table>
            <thead>
                <tr>
                    <th>#</th><th>Date</th><th>Invoice</th><th>Buyer</th>
                    <th>Vegetable</th><th>Qty</th><th>Rate</th><th>Cost</th><th>Total</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($listRes->num_rows === 0): ?>
                <tr><td colspan="9" class="muted">No records</td></tr>
            <?php else: $i=1; while($r=$listRes->fetch_assoc()):
                $lineTotal = $r['quantity_kg'] * $r['selling_price'];
                $lineCost  = $r['quantity_kg'] * $r['cost_price'];
            ?>
                <tr>
                    <td><?= $i ?></td>
                    <td><?= e(date('Y-m-d', strtotime($r['sale_date']))) ?></td>
                    <td><a href="invoice.php?inv=<?= urlencode($r['invoice_no']) ?>" class="no-print"><?= e(substr($r['invoice_no'], -10)) ?></a>
                        <span class="only-print"><?= e($r['invoice_no']) ?></span></td>
                    <td><?= e($r['buyer_name']) ?></td>
                    <td><?= e($r['veg']) ?></td>
                    <td><?= money($r['quantity_kg']) ?> kg</td>
                    <td>Rs. <?= money($r['selling_price']) ?></td>
                    <td>Rs. <?= money($r['cost_price']) ?></td>
                    <td><b>Rs. <?= money($lineTotal) ?></b></td>
                </tr>
            <?php $i++; endwhile; endif; ?>
            </tbody>
            <tfoot>
                <tr style="background:#f1f5f9;font-weight:700">
                    <td colspan="5" style="text-align:right">TOTALS</td>
                    <td><?= money($sum['qty']) ?> kg</td>
                    <td colspan="2"></td>
                    <td>Rs. <?= money($sum['rev']) ?></td>
                </tr>
            </tfoot>
        </table>
        </div>
    </div>

    <!-- Signature section (print only) -->
    <div class="print-signatures">
        <div>
            <div class="sig-line"></div>
            <p>Prepared By: <b><?= e(currentUser()['name']) ?></b></p>
        </div>
        <div>
            <div class="sig-line"></div>
            <p>Checked By</p>
        </div>
        <div>
            <div class="sig-line"></div>
            <p>Approved By (Manager)</p>
        </div>
    </div>

    <!-- Print Footer -->
    <div class="print-footer">
        <p>© <?= date('Y') ?> Vegie Pro - Vegetable Shop Management System | Generated on <?= date('Y-m-d H:i:s') ?></p>
    </div>

</div>
<!-- /printArea -->

<script>
function exportCSV() {
    let csv = 'Date,Invoice,Buyer,Vegetable,Qty,Rate,Cost,Total\n';
    document.querySelectorAll('#printArea table:last-of-type tbody tr').forEach(tr => {
        if (tr.querySelector('td.muted')) return;
        const cells = tr.querySelectorAll('td');
        const row = [];
        cells.forEach((c, i) => { if (i !== 0) row.push('"' + c.textContent.trim().replace(/"/g,'""') + '"'); });
        csv += row.join(',') + '\n';
    });
    const blob = new Blob(['\ufeff' + csv], {type:'text/csv;charset=utf-8'});
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'sales_report_<?= $from ?>_to_<?= $to ?>.csv';
    a.click();
    URL.revokeObjectURL(url);
    if (window.showToast) showToast('CSV exported successfully');
}
</script>

<?php include 'footer.php'; ?>