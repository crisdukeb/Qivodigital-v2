<?php
require __DIR__.'/config.php';
require_login();

$pdo = pdo();
$q        = trim($_GET['q'] ?? '');
$interes  = trim($_GET['interes'] ?? '');
$from     = trim($_GET['from'] ?? '');
$to       = trim($_GET['to'] ?? '');

$where = []; $bind = [];
if ($q !== '') {
  $where[] = "(nombre LIKE :q OR email LIKE :q OR empresa LIKE :q OR mensaje LIKE :q OR source_page LIKE :q)";
  $bind[':q'] = "%{$q}%";
}
if ($interes !== '') { $where[] = "interes = :interes"; $bind[':interes'] = $interes; }
if ($from !== '')     { $where[] = "created_at >= :from"; $bind[':from'] = $from.' 00:00:00'; }
if ($to   !== '')     { $where[] = "created_at <= :to";   $bind[':to']   = $to.' 23:59:59'; }
$sqlWhere = $where ? ('WHERE '.implode(' AND ', $where)) : '';

$stmt = $pdo->prepare("SELECT id,brand,source_page,nombre,email,empresa,interes,mensaje,ip,user_agent,utm_source,utm_medium,utm_campaign,created_at FROM qd_leads {$sqlWhere} ORDER BY id DESC");
$stmt->execute($bind);

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="leads_'.date('Ymd_His').'.csv"');
$out = fopen('php://output', 'w');
fputcsv($out, ['id','brand','source_page','nombre','email','empresa','interes','mensaje','ip','user_agent','utm_source','utm_medium','utm_campaign','created_at']);
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
  fputcsv($out, $r);
}
fclose($out);