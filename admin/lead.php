<?php
require __DIR__.'/config.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$st = pdo()->prepare("SELECT * FROM qd_leads WHERE id=:id");
$st->execute([':id'=>$id]);
$r = $st->fetch();
if (!$r) { header('Location: /admin/leads.php'); exit; }
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Lead #<?=$r['id']?> — QivoDigital</title>
<link rel="stylesheet" href="/assets/css/style.css">
<style>
  body{background:#0b1220;color:#e6edf6;font-family:Inter,system-ui}
  .wrap{max-width:900px;margin:24px auto;padding:0 16px}
  .card{background:#111a2b;border:1px solid #1f2a44;border-radius:14px;padding:16px}
  .row{display:grid;grid-template-columns:180px 1fr;gap:10px;margin:6px 0}
  .muted{color:#9aa8c5}
  .btn{background:#1f2a44;border:0;color:#fff;padding:10px 14px;border-radius:10px;text-decoration:none;font-weight:700}
</style>
</head>
<body>
<div class="wrap">
  <a class="btn" href="/admin/leads.php">← Volver</a>
  <div class="card" style="margin-top:12px">
    <h2>Lead #<?=$r['id']?></h2>
    <?php foreach($r as $k=>$v): ?>
      <div class="row"><div class="muted"><?=$k?></div><div><?=nl2br(htmlspecialchars((string)$v))?></div></div>
    <?php endforeach; ?>
  </div>
</div>
</body>
</html>