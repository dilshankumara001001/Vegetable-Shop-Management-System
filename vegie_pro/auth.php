<?php
if (session_status()===PHP_SESSION_NONE) session_start();
require_once __DIR__.'/db.php';

function requireLogin(){ if(empty($_SESSION['user_id'])){ header('Location: login.php'); exit; } }
function currentUser(){ return ['id'=>$_SESSION['user_id']??null,'name'=>$_SESSION['full_name']??'','role'=>$_SESSION['role']??'staff']; }
function isAdmin(){ return ($_SESSION['role']??'')==='admin'; }
function e($s){ return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8'); }
function money($n){ return number_format((float)$n,2); }
function flash($m,$t='success'){ $_SESSION['flash']=['msg'=>$m,'type'=>$t]; }
function getFlash(){ if(!empty($_SESSION['flash'])){ $f=$_SESSION['flash']; unset($_SESSION['flash']); return $f; } return null; }

function getAvailableStock($conn,$veg_id){
    $s=$conn->prepare("SELECT COALESCE(SUM(remaining_kg),0) s FROM stock WHERE veg_id=?");
    $s->bind_param('i',$veg_id); $s->execute();
    $r=$s->get_result()->fetch_assoc(); $s->close();
    return (float)$r['s'];
}
function deductStock($conn,$veg_id,$qty,$ref=null){
    $s=$conn->prepare("SELECT id,remaining_kg,buying_price FROM stock WHERE veg_id=? AND remaining_kg>0 ORDER BY date_added ASC,id ASC");
    $s->bind_param('i',$veg_id); $s->execute();
    $batches=$s->get_result()->fetch_all(MYSQLI_ASSOC); $s->close();
    $need=(float)$qty; $total=0.0;
    $upd=$conn->prepare("UPDATE stock SET remaining_kg=remaining_kg-? WHERE id=?");
    foreach($batches as $b){ if($need<=0.0001) break;
        $take=min((float)$b['remaining_kg'],$need);
        $upd->bind_param('di',$take,$b['id']); $upd->execute();
        $total += $take*(float)$b['buying_price']; $need -= $take;
    }
    $upd->close();
    $mv=$conn->prepare("INSERT INTO stock_movements (veg_id,type,quantity_kg,ref,note) VALUES (?, 'out', ?, ?, 'Sale')");
    $mv->bind_param('ids',$veg_id,$qty,$ref); $mv->execute(); $mv->close();
    return $total;
}
function nextInvoiceNo($conn){
    $prefix='INV-'.date('Ymd').'-';
    $s=$conn->prepare("SELECT COUNT(DISTINCT invoice_no) c FROM sales WHERE invoice_no LIKE CONCAT(?, '%')");
    $s->bind_param('s',$prefix); $s->execute();
    $c=(int)$s->get_result()->fetch_assoc()['c']; $s->close();
    return $prefix.str_pad($c+1,4,'0',STR_PAD_LEFT);
}