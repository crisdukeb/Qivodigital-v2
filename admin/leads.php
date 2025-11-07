<?php
require __DIR__.'/config.php';
require_login();

$pdo = pdo();
$per_page = PER_PAGE;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page-1)*$per_page;

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
if ($to !== '')       { $where[] = "created_at <= :to";   $bind[':to']   = $to.' 23:59:59'; }

$sqlWhere = $where ? ('WHERE '.implode(' AND ', $where)) : '';
$total = (int)$pdo->prepare("SELECT COUNT(*) FROM qd_leads {$sqlWhere}")
                  ->execute($bind) || 0;
$stmt = $pdo->prepare("SELECT * FROM qd_leads {$sqlWhere} ORDER BY id DESC LIMIT {$per_page} OFFSET {$offset}");
$stmt->execute($bind);
$rows = $stmt->fetchAll();

$intereses = ['whatsapp','automatizacion','ecommerce','software-medida','integraciones','web-apps','apps','chatbots','crm','otros'];
$csrf = csrf_token();
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Leads — QivoDigital</title>
<link rel="stylesheet" href="/assets/css/style.css">
<style>
  body{background:#0b1220;color:#e6edf6;font-family:Inter,system-ui}
  .wrap{max-width:1200px;margin:24px auto;padding:0 16px}
  .top{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:14px}
  .card{background:#111a2b;border:1px solid #1f2a44;border-radius:14px;padding:16px}
  .btn{background:#3b82f6;border:0;color:#fff;padding:10px 14px;border-radius:10px;text-decoration:none;font-weight:700}
  .btn.ghost{background:#1f2a44}
  input,select{background:#0c1424;border:1px solid #27324a;border-radius:10px;color:#e6edf6;padding:10px}
  table{width:100%;border-collapse:separate;border-spacing:0}
  th,td{padding:10px;border-bottom:1px solid #1f2a44;vertical-align:top}
  th{color:#9fb1d6;text-align:left}
  .muted{color:#9aa8c5}
  .row{display:grid;grid-template-columns:1fr 1fr 1fr 1fr 2fr 1fr;gap:10px}
  .pagination{display:flex;gap:8px;margin-top:14px}
  .tag{background:#1f2a44;padding:2px 8px;border-radius:999px;font-size:12px}
  .actions{display:flex;gap:8px;flex-wrap:wrap}
  .nowrap{white-space:nowrap}
</style>
</head>
<body>
<div class="wrap">
  <div class="top">
    <h1>Leads</h1>
    <div class="actions">
      <a class="btn" href="/admin/">← Volver</a>
      <a class="btn" href="/admin/export.php?<?=http_build_query(array_filter(['q'=>$q,'interes'=>$interes,'from'=>$from,'to'=>$to]))?>">Exportar CSV</a>
    </div>
  </div>

  <form class="card" method="get" action="/admin/leads.php">
    <div class="row">
      <input name="q" value="<?=htmlspecialchars($q)?>" placeholder="Buscar nombre, email, empresa, mensaje…">
      <select name="interes">
        <option value="">Interés (todos)</option>
        <?php foreach($intereses as $i): ?>
          <option value="<?=$i?>" <?=$i===$interes?'selected':''?>><?=$i?></option>
        <?php endforeach; ?>
      </select>
      <input type="date" name="from" value="<?=htmlspecialchars($from)?>">
      <input type="date" name="to"   value="<?=htmlspecialchars($to)?>">
      <button class="btn" type="submit">Filtrar</button>
      <a class="btn ghost" href="/admin/leads.php">Limpiar</a>
    </div>
  </form>

  <div class="card" style="margin-top:12px">
    <div class="muted">Total: <b><?=$total?></b> · Página <?=$page?></div>
    <table>
      <thead>
        <tr>
          <th>ID</th><th>Nombre / Email</th><th>Empresa</th><th>Interés</th><th>Mensaje</th><th class="nowrap">Fecha</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach($rows as $r): ?>
        <tr>
          <td>#<?=$r['id']?></td>
          <td>
            <div><b><?=htmlspecialchars($r['nombre'])?></b></div>
            <div class="muted"><?=htmlspecialchars($r['email'])?></div>
            <div class="muted">Desde: <?=htmlspecialchars($r['source_page'])?></div>
          </td>
          <td><?=htmlspecialchars($r['empresa'] ?? '')?></td>
          <td><span class="tag"><?=htmlspecialchars($r['interes'])?></span></td>
          <td><?=nl2br(htmlspecialchars(mb_strimwidth($r['mensaje'] ?? '',0,300,'…','UTF-8')))?></td>
          <td class="nowrap"><?=htmlspecialchars($r['created_at'])?></td>
        </tr>
      <?php endforeach; if(!$rows): ?>
        <tr><td colspan="6" class="muted">Sin resultados con los filtros actuales.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>

    <?php
      $pages = max(1, (int)ceil($total/$per_page));
      if ($pages>1):
        $qs = $_GET; unset($qs['page']);
    ?>
    <div class="pagination">
      <?php for($p=1;$p<=$pages;$p++): $qs['page']=$p; $link='/admin/leads.php?'.http_build_query($qs); ?>
        <a class="btn <?=$p===$page?'':'ghost'?>" href="<?=$link?>"><?=$p?></a>
      <?php endfor; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
</body>
</html>