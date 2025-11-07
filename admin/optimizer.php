<?php
// QivoDigital — Admin Optimizer (panel)
// Ruta: /public_html/admin/optimizer.php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_login();

$token = 'QIVO_2025_MODO_BESTIA';
// IMPORTANTE: usamos ruta absoluta al mismo host para evitar CORS y mod_security.
$optimizer_url = 'https://qivodigital.com/admin/optimizer/run.php?token=' . urlencode($token);

$err = null;
$result = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  // Llamada con cURL (más robusto que file_get_contents en Hostinger)
  $ch = curl_init($optimizer_url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_CONNECTTIMEOUT => 8,
    CURLOPT_TIMEOUT        => 25,
    CURLOPT_SSL_VERIFYPEER => false, // shared host a veces no tiene CA actualizada
    CURLOPT_SSL_VERIFYHOST => 0,
    CURLOPT_USERAGENT      => 'QivoDigital-Admin/1.0',
  ]);
  $resp = curl_exec($ch);
  $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $cerr = curl_error($ch);
  curl_close($ch);

  if ($resp === false) {
    $err = 'No se pudo contactar el Optimizer. cURL: ' . ($cerr ?: 'desconocido');
  } elseif ($http < 200 || $http >= 300) {
    $err = "El Optimizer respondió HTTP $http";
  } else {
    $json = json_decode($resp, true);
    if (!is_array($json)) {
      $err = 'Respuesta inválida del Optimizer (no JSON)';
    } else {
      $result = $json;
    }
  }
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Optimizer — <?=h(APP_NAME)?></title>
<link rel="preload" as="font" href="/assets/fonts/Inter-Variable.woff2" type="font/woff2" crossorigin>
<style>
  :root{color-scheme: dark; --bg:#0b1220; --card:#121a2a; --txt:#e8eefc; --mut:#9bb0d1; --pri:#3b82f6; --ok:#22c55e; --err:#ef4444; --bd:#1f2b46;}
  body{margin:0;background:var(--bg);color:var(--txt);font:16px/1.45 system-ui,Segoe UI,Roboto,Ubuntu,Arial}
  .wrap{max-width:960px;margin:32px auto;padding:0 16px}
  .title{font-size:34px;font-weight:800;margin:0 0 6px}
  .mut{color:var(--mut)}
  .card{background:var(--card);border:1px solid var(--bd);border-radius:16px;padding:18px;margin-top:18px}
  .btn{display:inline-block;background:var(--pri);color:#fff;padding:12px 18px;border-radius:12px;
       text-decoration:none;font-weight:700;border:0}
  .btn:active{transform:translateY(1px)}
  .row{display:flex;gap:12px;align-items:center;flex-wrap:wrap}
  .pill{display:inline-block;padding:.35rem .6rem;border-radius:999px;background:#15213a;color:var(--mut);font-size:.85rem}
  .ok{color:var(--ok)} .err{color:var(--err)}
  pre{white-space:pre-wrap;background:#0d1527;border-radius:12px;padding:12px;border:1px solid #1a2440;overflow:auto}
  ul{margin:0;padding-left:18px}
</style>
</head>
<body>
<div class="wrap">
  <h1 class="title">Optimizer</h1>
  <p class="mut">Minifica CSS/JS, valida <code>sitemap.xml</code> y deja todo liviano.</p>

  <div class="card">
    <form method="post" class="row" action="/admin/optimizer.php">
      <button class="btn" type="submit">Ejecutar ahora</button>
      <span class="pill">Endpoint: /admin/optimizer/run.php</span>
      <span class="pill">Token: QIVO_2025_MODO_BESTIA</span>
    </form>
  </div>

  <?php if ($err): ?>
    <div class="card"><strong class="err">Error:</strong> <?=h($err)?></div>
  <?php endif; ?>

  <?php if ($result): ?>
    <div class="card">
      <div><strong>Estado:</strong> <?= $result['ok'] ? '<span class="ok">OK</span>' : '<span class="err">FAIL</span>' ?></div>
      <div class="mut" style="margin-top:6px">Ejecutado: <?=h($result['executed_at'] ?? '-')?></div>

      <?php if (!empty($result['actions'])): ?>
        <h3>Acciones</h3>
        <ul>
          <?php foreach ($result['actions'] as $a): ?>
            <li><code><?=h($a['time'] ?? '')?></code> — <strong><?=h($a['step'] ?? '')?></strong>
              <?php
                $extra = $a; unset($extra['step'],$extra['time']);
                if (!empty($extra)) echo ' <span class="mut">'.h(json_encode($extra, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)).'</span>';
              ?>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>

      <?php if (!empty($result['errors'])): ?>
        <h3>Errores</h3>
        <ul>
          <?php foreach ($result['errors'] as $e): ?>
            <li class="err"><?=h($e)?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>

      <h3>JSON bruto</h3>
      <pre><?=h(json_encode($result, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES))?></pre>
    </div>
  <?php endif; ?>
</div>
</body>
</html>