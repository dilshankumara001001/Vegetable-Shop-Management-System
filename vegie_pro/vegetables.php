<?php
require_once 'auth.php';
requireLogin();
$pageTitle = 'Vegetable Master List';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $act = $_POST['action'] ?? '';
    if ($act==='add') {
        $stmt = $conn->prepare("INSERT INTO vegetables (name,category,unit,reorder_level) VALUES (?,?,?,?)");
        $stmt->bind_param('sssd', $_POST['name'], $_POST['category'], $_POST['unit'], $_POST['reorder']);
        if ($stmt->execute()) { $_SESSION['flash_js']='Vegetable added successfully'; flash('Vegetable added successfully'); }
        else { flash('Name already exists.', 'error'); }
        $stmt->close();
    } elseif ($act==='delete' && isAdmin()) {
        $stmt = $conn->prepare("DELETE FROM vegetables WHERE id=?");
        $stmt->bind_param('i', $_POST['id']); $stmt->execute(); $stmt->close();
        flash('Vegetable deleted.', 'error');
    }
    header('Location: vegetables.php'); exit;
}

$list = $conn->query("SELECT v.*, COALESCE(SUM(s.remaining_kg),0) q FROM vegetables v LEFT JOIN stock s ON s.veg_id=v.id GROUP BY v.id ORDER BY v.name");
$cats = $conn->query("SELECT DISTINCT category FROM vegetables WHERE category IS NOT NULL AND category<>'' ORDER BY category");
include 'header.php';
?>
<!-- Feature 1: Animated panel for adding -->
<div class="panel animate-up">
    <h3>➕ Add New Vegetable</h3>
    <form method="POST" class="row-form">
        <input type="hidden" name="action" value="add">
        <input type="text" name="name" placeholder="Vegetable name" required>
        <input type="text" name="category" placeholder="Category">
        <select name="unit">
            <option value="kg">kg</option><option value="g">g</option>
            <option value="bunch">bunch</option><option value="piece">piece</option>
        </select>
        <input type="number" name="reorder" step="0.01" placeholder="Reorder lvl" value="20">
        <button class="btn" type="submit">Add</button>
    </form>
</div>

<div class="panel animate-up delay-1">
    <h3>🥦 All Vegetables <span class="badge-count"><?= $list->num_rows ?> items</span></h3>
    <!-- Feature 2: Live search -->
    <div class="filter-bar">
        <input type="text" id="vegSearch" data-search="vegTable" placeholder="Search vegetables...">
        <select id="catFilter">
            <option value="">All Categories</option>
            <?php while($c=$cats->fetch_assoc()): ?>
                <option value="<?= e($c['category']) ?>"><?= e($c['category']) ?></option>
            <?php endwhile; ?>
        </select>
    </div>
    <div class="table-wrap">
    <!-- Feature 3: Sortable table -->
    <table id="vegTable" data-sortable>
        <thead>
            <tr>
                <th>ID</th><th>Name</th><th>Category</th><th>Unit</th>
                <th>Available</th><th>Reorder</th><th>Status</th>
                <?php if(isAdmin()):?><th>Action</th><?php endif;?>
            </tr>
        </thead>
        <tbody>
        <?php while ($r = $list->fetch_assoc()):
            $low = $r['q'] <= $r['reorder_level'];
            $pct = $r['reorder_level'] > 0 ? min(100, ($r['q'] / ($r['reorder_level']*3)) * 100) : 100;
        ?>
            <tr data-category="<?= e($r['category']) ?>">
                <td>#<?= (int)$r['id'] ?></td>
                <td><b><?= e($r['name']) ?></b></td>
                <td><span class="badge badge-blue"><?= e($r['category'] ?: 'N/A') ?></span></td>
                <td><?= e($r['unit']) ?></td>
                <td>
                    <b class="<?= $low ? 'danger' : 'ok' ?>"><?= money($r['q']) ?></b>
                    <div class="progress">
                        <div class="progress-bar <?= $low ? 'danger' : '' ?>" style="--target:<?= $pct ?>%"></div>
                    </div>
                </td>
                <td><?= money($r['reorder_level']) ?></td>
                <td>
                    <?php if ($low): ?>
                        <span class="badge badge-red badge-pulse">⚠ Low</span>
                    <?php else: ?>
                        <span class="badge badge-green">✓ OK</span>
                    <?php endif; ?>
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

<script>
// Feature: category filter combined with search
document.getElementById('catFilter').addEventListener('change', function(){
    const cat = this.value;
    document.querySelectorAll('#vegTable tbody tr').forEach(tr => {
        tr.style.display = (!cat || tr.dataset.category === cat) ? '' : 'none';
    });
});
</script>
<?php include 'footer.php'; ?>