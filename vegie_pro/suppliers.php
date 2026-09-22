<?php
require_once 'auth.php';
requireLogin();
$pageTitle = 'Supplier Management';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $act = $_POST['action'] ?? '';
    if ($act==='add') {
        $stmt = $conn->prepare("INSERT INTO suppliers (name,phone,address) VALUES (?,?,?)");
        $stmt->bind_param('sss', $_POST['name'], $_POST['phone'], $_POST['address']);
        $stmt->execute(); $stmt->close();
        $_SESSION['flash_js']='Supplier registered'; flash('Supplier registered.');
    } elseif ($act==='delete' && isAdmin()) {
        $stmt = $conn->prepare("DELETE FROM suppliers WHERE id=?");
        $stmt->bind_param('i', $_POST['id']); $stmt->execute(); $stmt->close();
        flash('Supplier deleted.', 'error');
    }
    header('Location: suppliers.php'); exit;
}

$list = $conn->query("SELECT s.*, (SELECT COUNT(*) FROM stock WHERE supplier_id=s.id) batches,
                     (SELECT COALESCE(SUM(quantity_kg*buying_price),0) FROM stock WHERE supplier_id=s.id) total_value
                     FROM suppliers s ORDER BY s.name");
$totalSup = $conn->query("SELECT COUNT(*) c FROM suppliers")->fetch_assoc()['c'];
$totalVal = (float)$conn->query("SELECT COALESCE(SUM(quantity_kg*buying_price),0) v FROM stock")->fetch_assoc()['v'];
include 'header.php';
?>
<!-- Stat cards -->
<div class="cards">
    <div class="card border-green animate-bounce delay-1">
        <div class="icon-badge">👥</div>
        <h4>Total Suppliers</h4>
        <h2 data-counter data-target="<?= $totalSup ?>">0</h2>
    </div>
    <div class="card border-blue animate-bounce delay-2">
        <div class="icon-badge">💵</div>
        <h4>Total Purchase Value</h4>
        <h2 data-counter data-target="<?= $totalVal ?>" data-decimals="2" data-prefix="Rs. ">0</h2>
    </div>
    <div class="card border-orange animate-bounce delay-3">
        <div class="icon-badge">📦</div>
        <h4>Total Batches</h4>
        <h2 data-counter data-target="<?= (int)$conn->query("SELECT COUNT(*) c FROM stock")->fetch_assoc()['c'] ?>">0</h2>
    </div>
</div>

<div class="panel animate-up">
    <h3>➕ Register Supplier</h3>
    <form method="POST" class="row-form">
        <input type="hidden" name="action" value="add">
        <input type="text" name="name" placeholder="Supplier name" required>
        <input type="text" name="phone" placeholder="Phone (07X-XXXXXXX)" pattern="[0-9\-]+">
        <input type="text" name="address" placeholder="Address">
        <button class="btn" type="submit">Register</button>
    </form>
</div>

<div class="panel animate-up delay-1">
    <h3>👨‍🌾 Registered Suppliers <span class="badge-count"><?= $list->num_rows ?> suppliers</span></h3>
    <div class="filter-bar">
        <input type="text" id="supSearch" data-search="supTable" placeholder="Search by name, phone, address...">
    </div>
    <div class="table-wrap">
    <table id="supTable" data-sortable>
        <thead><tr><th>ID</th><th>Name</th><th>Phone</th><th>Address</th><th>Batches</th><th>Value</th><th>Contact</th><?php if(isAdmin()):?><th></th><?php endif;?></tr></thead>
        <tbody>
        <?php while ($r = $list->fetch_assoc()): ?>
            <tr>
                <td>#<?= (int)$r['id'] ?></td>
                <td><b><?= e($r['name']) ?></b></td>
                <td><?= e($r['phone']) ?></td>
                <td><?= e($r['address']) ?></td>
                <td><span class="badge badge-blue"><?= (int)$r['batches'] ?></span></td>
                <td>Rs. <?= money($r['total_value']) ?></td>
                <td>
                    <button class="btn-icon" data-copy="<?= e($r['phone']) ?>" title="Copy phone">📋 Copy</button>
                </td>
                <?php if (isAdmin()): ?>
                <td>
                    <form method="POST" data-confirm="Delete <?= e($r['name']) ?>?" style="display:inline">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                        <button class="btn-x" type="submit">✕</button>
                    </form>
                </td>
                <?php endif; ?>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    </div>
</div>
<?php include 'footer.php'; ?>