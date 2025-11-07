<?php
require __DIR__ . '/config.php';
require_login();
$me = current_admin();
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?=APP_NAME?></title>
<link rel="stylesheet" href="/assets/css/style.css">
<style>
  body{background:#0b1220;color:#e6edf6;font-family:Inter,system-ui,Segoe UI,Roboto,Helvetica,Arial,sans-serif}
  .wrap{max-width:1100px;margin:4vh auto;padding:0 16px}
  .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:14px}
  .card{background:#111a2b;border-radius:16px;padding:18px;border:1px solid #1f2a44}
  .k{color:#98a2b3;margin:0 0 10px}
  .btn{display:inline-block;padding:10px 14px;border-radius:10px;background:#3b82f6;color:#fff;text-decoration:none;font-weight:700}
  .top{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px}
  a.link{color:#9ec1ff}
</style>
</head>
<body>
<div class="wrap">
  <div class="top">
    <h1><?=APP_NAME?></h1>
    <div>
      <span class="k">Sesión:</span> <strong><?=htmlspecialchars($me['user'])?></strong>
      &nbsp;·&nbsp; <a class="link" href="/admin/logout.php">Salir</a>
    </div>
  </div>

  <div class="grid">
    <div class="card">
      <h3>Leads</h3>
      <p class="k">Ver formularios guardados y exportar CSV.</p>
      <a class="btn" href="/admin/leads.php">Abrir</a>
    </div>
    <div class="card">
      <h3>Optimizer</h3>
      <p class="k">Ejecutar optimizaciones (minificar, generar sitemap, etc.).</p>
      <a class="btn" href="/admin/optimizer.php">Ejecutar</a>
    </div>
  </div>
</div