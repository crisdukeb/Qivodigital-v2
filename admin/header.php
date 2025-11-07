<?php require __DIR__.'/config.php'; require_login(); $me = current_admin(); ?>
<header style="display:flex;gap:12px;align-items:center;justify-content:space-between;background:#0b1220;color:#e5e7eb;padding:12px 16px;border-bottom:1px solid #1f2937">
  <div style="display:flex;gap:10px;align-items:center">
    <strong>QivoDigital Admin</strong>
    <nav style="display:flex;gap:10px">
      <a href="/admin/index.php" style="color:#93c5fd">Dashboard</a>
      <a href="/admin/leads.php" style="color:#93c5fd">Leads</a>
      <a href="/admin/optimizer.php" style="color:#93c5fd">Optimizar Web 🚀</a>
    </nav>
  </div>
  <div>
    <span style="opacity:.8;margin-right:8px">👤 <?=$me['user']?></span>
    <a href="/admin/logout.php" style="color:#fca5a5">Salir</a>
  </div>
</header>