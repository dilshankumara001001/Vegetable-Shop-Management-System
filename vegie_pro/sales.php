<?php
require_once 'auth.php';
requireLogin();
$pageTitle = 'Sales & Billing';

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $act = $_POST['action'] ?? '';
    if ($act==='add') {
        $vid = (int)$_POST['veg_id']; $qty = (float)$_POST['qty']; $price = (float)$_POST['price'];
        $avail = getAvailableStock($conn, $vid);
        if ($vid<=0 || $qty<=0 || $price<=0) flash('Invalid input.', 'error');
        elseif ($qty > $avail) flash("Insufficient stock! Available: {$avail} kg", 'error');
        else { $_SESSION['cart'][$vid] = ['qty'=>$qty, 'price'=>$price]; flash('Item added to cart.'); }
    } elseif ($act==='remove') unset($_SESSION['cart'][(int)$_POST['veg_id']]);
    elseif ($act==='clear') $_SESSION['cart'] = [];
    elseif ($act==='complete') {
        $buyer = trim($_POST['buyer_name'] ?? ''); $phone = trim($_POST['buyer_phone'] ?? '');
        $cart = $_SESSION['cart'];
        if (!$cart) { flash('Cart is empty.', 'error'); header('Location: sales.php'); exit; }
        foreach ($cart as $vid=>$it) if ($it['qty'] > getAvailableStock($conn,$vid)) { flash('Stock changed.','error'); header('Location: sales.php'); exit; }
        $conn->begin_transaction();
        try {
            $inv = nextInvoiceNo($conn); $uid = (int)$_SESSION['user_id'];
            $stmt = $conn->prepare("INSERT INTO sales (invoice_no,veg_id,buyer_name,buyer_phone,quantity_kg,selling_price,cost_price,user_id) VALUES (?,?,?,?,?,?,?,?)");
            foreach ($cart as $vid=>$it) {
                $cost = deductStock($conn, $vid, $it['qty'], $inv);
                $stmt->bind_param('sissdddi', $inv, $vid, $buyer, $phone, $it['qty'], $it['price'], $cost, $uid);
                $stmt->execute();
            }
            $stmt->close(); $conn->commit();
            $_SESSION['cart'] = []; $_SESSION['flash_js']='Sale completed!';
            header('Location: invoice.php?inv='.urlencode($inv)); exit;
        } catch (Throwable $ex) {
            $conn->rollback(); flash('Sale failed.', 'error'); header('Location: sales.php'); exit;
        }
    }
    header('Location: sales.php'); exit;
}

$vegs = $conn->query("SELECT v.id,v.name,COALESCE(SUM(s.remaining_kg),0) q FROM vegetables v LEFT JOIN stock s ON s.veg_id=v.id GROUP BY v.id HAVING q>0 ORDER BY v.name");
$cartRows = []; $total = 0;
foreach ($_SESSION['cart'] as $vid=>$it) {
    $s = $conn->prepare("SELECT name FROM vegetables WHERE id=?");
    $s->bind_param('i',$vid); $s->execute();
    $name = $s->get_result()->fetch_assoc()['name'] ?? '?';
    $s->close();
    $line = $it['qty']*$it['price']; $total += $line;
    $cartRows[] = ['id'=>$vid,'name'=>$name,'qty'=>$it['qty'],'price'=>$it['price'],'line'=>$line];
}
$todaySales = (float)$conn->query("SELECT COALESCE(SUM(quantity_kg*selling_price),0) v FROM sales WHERE DATE(sale_date)=CURDATE()")->fetch_assoc()['v'];
$todayCount = (int)$conn->query("SELECT COUNT(DISTINCT invoice_no) c FROM sales WHERE DATE(sale_date)=CURDATE()")->fetch_assoc()['c'];
include 'header.php';
?>
<div class="cards">
    <div class="card border-green animate-bounce delay-1">
        <div class="icon-badge">💰</div>
        <h4>Today's Sales</h4>
        <h2 data-counter data-target="<?= $todaySales ?>" data-decimals="2" data-prefix="Rs. ">0</h2>
    </div>
    <div class="card border-blue animate-bounce delay-2">
        <div class="icon-badge">🧾</div>
        <h4>Today's Invoices</h4>
        <h2 data-counter data-target="<?= $todayCount ?>">0</h2>
    </div>
    <div class="card border-orange animate-bounce delay-3">
        <div class="icon-badge">🛒</div>
        <h4>Cart Items</h4>
        <h2 data-counter data-target="<?= count($cartRows) ?>">0</h2>
    </div>
    <div class="card border-purple animate-bounce delay-4">
        <div class="icon-badge">💵</div>
        <h4>Cart Total</h4>
        <h2 data-counter data-target="<?= $total ?>" data-decimals="2" data-prefix="Rs. ">0</h2>
    </div>
</div>

<div class="grid-2">
    <div class="panel animate-up">
        <h3>➕ Add Item to Cart</h3>
        <form method="POST" class="stack-form">
            <input type="hidden" name="action" value="add">
            <label>Vegetable</label>
            <select name="veg_id" required>
                <option value="">-- Select --</option>
                <?php while($v=$vegs->fetch_assoc()): ?>
                    <option value="<?= (int)$v['id'] ?>"><?= e($v['name']) ?> (<?= money($v['q']) ?> kg available)</option>
                <?php endwhile; ?>
            </select>
            <label>Quantity (kg)</label>
            <input type="number" name="qty" step="0.01" required>
            <label>Selling Price /kg</label>
            <input type="number" name="price" step="0.01" required>
            <button class="btn" type="submit">🛒 Add to Cart</button>
        </form>
    </div>

    <div class="panel animate-up delay-1">
        <h3>🛒 Cart <span class="badge-count"><?= count($cartRows) ?> items</span></h3>
        <div class="table-wrap">
        <table>
            <thead><tr><th>Item</th><th>Qty</th><th>Price</th><th>Total</th><th></th></tr></thead>
            <tbody>
            <?php if (!$cartRows): ?>
                <tr><td colspan="5" class="muted">🛒 Cart is empty. Add some items!</td></tr>
            <?php else: foreach ($cartRows as $r): ?>
                <tr>
                    <td><b><?= e($r['name']) ?></b></td>
                    <td><?= money($r['qty']) ?> kg</td>
                    <td>Rs. <?= money($r['price']) ?></td>
                    <td><b>Rs. <?= money($r['line']) ?></b></td>
                    <td>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="veg_id" value="<?= (int)$r['id'] ?>">
                            <button class="btn-x" type="submit">✕</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
            <tfoot><tr><th colspan="3" style="text-align:right">GRAND TOTAL</th><th colspan="2" style="color:var(--green);font-size:16px">Rs. <?= money($total) ?></th></tr></tfoot>
        </table>
        </div>
    </div>
</div>

<div class="panel animate-up delay-2">
    <h3>💳 Complete Sale</h3>
    <form method="POST" class="row-form">
        <input type="hidden" name="action" value="complete">
        <input type="text" name="buyer_name" placeholder="Buyer name" required>
        <input type="text" name="buyer_phone" placeholder="Phone (optional)">
        <button class="btn btn-accent" type="submit" <?= $cartRows ? '' : 'disabled' ?>>✅ Complete Sale & Generate Invoice</button>
        <?php if ($cartRows): ?>
        <button class="btn-ghost" type="button" onclick="if(confirm('Clear cart?')){this.form.action.value='clear';this.form.submit();}">🗑 Clear Cart</button>
        <?php endif; ?>
    </form>
</div>
<?php include 'footer.php'; ?>