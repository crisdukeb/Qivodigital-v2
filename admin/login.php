<?php
require __DIR__ . '/config.php';

// Si ya está logueado → dashboard
if (is_logged()) {
  header('Location: /admin/index.php'); exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $user = trim($_POST['user'] ?? '');
  $pass = (string)($_POST['pass'] ?? '');

  // Ojo: generamos token si aún no existe y validamos si vino en el form
  $has_csrf = isset($_POST['csrf']);
  $csrf_ok  = !$has_csrf || csrf_validate($_POST['csrf']);

  if (!$csrf_ok) {
    $error = 'Sesión expirada. Intenta de nuevo.';
  } elseif ($user === ADMIN_USER && check_admin_password($pass)) {
    $_SESSION['admin_id']   = 1;
    $_SESSION['admin_user'] = ADMIN_USER;
    // rotar CSRF
    unset($_SESSION['csrf']);
    header('Location: /admin/index.php'); exit;
  } else {
    $error = 'Usuario/clave inválidos';
  }
}
$csrf = csrf_token();
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin QivoDigital — Login</title>
<link rel="stylesheet" href="/assets/css/style.css">
<style>
  body{background:#0b1220;color:#e6edf6;font-family:Inter,system-ui,Segoe UI,Roboto,Helvetica,Arial,sans-serif}
  .card{max-width:520px;margin:6vh auto;padding:28px;background:#111a2b;border-radius:16px;box-shadow:0 10px 30px rgba(0,0,0,.3)}
  .title{font-size:28px;margin:0 0 6px;font-weight:800}
  .muted{color:#98a2b3;margin:0 0 18px}
  label{display:block;margin:12px 0 6px}
  input{width:100%;padding:12px;border-radius:10px;border:1px solid #253049;background:#0c1424;color:#e6edf6}
  .btn{margin-top:16px;width:100%;padding:12px;border:0;border-radius:12px;background:#3b82f6;color:#fff;font-weight:700}
  .err{color:#fca5a5;margin:10px 0 0}
</style>
</head>
<body>
  <div class="card">
    <h1 class="title">Admin QivoDigital</h1>
    <p class="muted">Ingresa con tus credenciales.</p>

    <?php if ($error): ?><p class="err"><?=htmlspecialchars($error)?></p><?php endif; ?>

    <form method="post" action="/admin/login.php" autocomplete="off">
      <input type="hidden" name="csrf" value="<?=$csrf?>">
      <label>Usuario</label>
      <input name="user" placeholder="qivo" required>
      <label>Contraseña</label>
      <input name="pass" type="password" placeholder="••••••••" required>
      <button class="btn" type="submit">Ingresar</button>
    </form>
  </div>
</body>
</html>