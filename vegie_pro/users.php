<?php
require_once 'auth.php';
requireLogin();
if (!isAdmin()) { flash('Admin only.', 'error'); header('Location: index.php'); exit; }
$pageTitle = 'User Management';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $act = $_POST['action'] ?? '';
    if ($act==='add') {
        $u = trim($_POST['username']); $p = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $n = trim($_POST['full_name']); $r = $_POST['role']==='admin'?'admin':'staff';
        $stmt = $conn->prepare("INSERT INTO users (username,password,full_name,role) VALUES (?,?,?,?)");
        $stmt->bind_param('ssss', $u, $p, $n, $r);
        if ($stmt->execute()) { $_SESSION['flash_js']='User created'; flash('User created.'); }
        else flash('Username already exists.', 'error');
        $stmt->close();
    } elseif ($act==='delete') {
        $id = (int)$_POST['id'];
        if ($id !== (int)$_SESSION['user_id']) {
            $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
            $stmt->bind_param('i',$id); $stmt->execute(); $stmt->close();
            flash('User deleted.', 'error');
        } else flash('Cannot delete yourself.', 'error');
    } elseif ($act==='reset') {
        $id = (int)$_POST['id']; $p = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
        $stmt->bind_param('si',$p,$id); $stmt->execute(); $stmt->close();
        flash('Password reset.');
    }
    header('Location: users.php'); exit;
}

$list = $conn->query("SELECT id,username,full_name,role,created_at FROM users ORDER BY id");
$adminCount = (int)$conn->query("SELECT COUNT(*) c FROM users WHERE role='admin'")->fetch_assoc()['c'];
$staffCount = (int)$conn->query("SELECT COUNT(*) c FROM users WHERE role='staff'")->fetch_assoc()['c'];
include 'header.php';
?>
<div class="cards">
    <div class="card border-purple animate-bounce delay-1">
        <div class="icon-badge">👥</div>
        <h4>Total Users</h4>
        <h2 data-counter data-target="<?= $list->num_rows ?>">0</h2>
    </div>
    <div class="card border-orange animate-bounce delay-2">
        <div class="icon-badge">👑</div>
        <h4>Admins</h4>
        <h2 data-counter data-target="<?= $adminCount ?>">0</h2>
    </div>
    <div class="card border-green animate-bounce delay-3">
        <div class="icon-badge">🧑‍💼</div>
        <h4>Staff</h4>
        <h2 data-counter data-target="<?= $staffCount ?>">0</h2>
    </div>
</div>

<div class="panel animate-up">
    <h3>➕ Add New User</h3>
    <form method="POST" class="row-form">
        <input type="hidden" name="action" value="add">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <input type="text" name="full_name" placeholder="Full name">
        <select name="role">
            <option value="staff">Staff</option>
            <option value="admin">Admin</option>
        </select>
        <button class="btn" type="submit">Create</button>
    </form>
</div>

<div class="panel animate-up delay-1">
    <h3>👤 Users <span class="badge-count"><?= $list->num_rows ?> users</span></h3>
    <div class="filter-bar">
        <input type="text" id="userSearch" data-search="userTable" placeholder="Search username, name...">
    </div>
    <div class="table-wrap">
    <table id="userTable" data-sortable>
        <thead><tr><th>ID</th><th>Username</th><th>Name</th><th>Role</th><th>Created</th><th>Actions</th></tr></thead>
        <tbody>
        <?php while ($r = $list->fetch_assoc()): ?>
            <tr>
                <td>#<?= (int)$r['id'] ?></td>
                <td><b><?= e($r['username']) ?></b></td>
                <td><?= e($r['full_name']) ?></td>
                <td><span class="badge badge-<?= $r['role']==='admin'?'orange':'green' ?>"><?= $r['role']==='admin'?'👑 Admin':'🧑‍💼 Staff' ?></span></td>
                <td><?= e(date('Y-m-d', strtotime($r['created_at']))) ?></td>
                <td style="display:flex;gap:6px">
                    <button class="btn-icon" onclick="resetPwd(<?= (int)$r['id'] ?>)">🔑 Reset</button>
                    <?php if ($r['id'] != $_SESSION['user_id']): ?>
                    <form method="POST" data-confirm="Delete <?= e($r['username']) ?>?" style="display:inline">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                        <button class="btn-x" type="submit">✕</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    </div>
</div>

<!-- Reset password modal -->
<div class="modal-backdrop" id="resetModal">
    <form method="POST" class="modal">
        <h3>🔑 Reset Password</h3>
        <input type="hidden" name="action" value="reset">
        <input type="hidden" name="id" id="resetId">
        <label>New Password</label>
        <input type="text" name="password" id="resetPwd" required minlength="4">
        <div style="display:flex;gap:10px;margin-top:16px">
            <button class="btn" type="submit">Save</button>
            <button class="btn-ghost" type="button" onclick="closeReset()">Cancel</button>
        </div>
    </form>
</div>

<script>
function resetPwd(id){
    document.getElementById('resetId').value = id;
    document.getElementById('resetPwd').value = '';
    document.getElementById('resetModal').classList.add('show');
    setTimeout(()=>document.getElementById('resetPwd').focus(), 200);
}
function closeReset(){ document.getElementById('resetModal').classList.remove('show'); }
document.getElementById('resetModal').addEventListener('click', e => {
    if (e.target.id === 'resetModal') closeReset();
});
</script>
<?php include 'footer.php'; ?>