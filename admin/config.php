<?php
// ===========================================
// QivoDigital — Admin Config (FINAL)
// ===========================================

// ---- DB (mismos de leads.php) ----
define('DB_HOST', '72.60.127.195');
define('DB_NAME', 'qivosend_db');
define('DB_USER', 'qivo_user');
define('DB_PASS', 'Qiv0Send$2025!');

// ---- App ----
define('APP_NAME', 'QivoDigital Admin');
define('PER_PAGE', 25);
date_default_timezone_set('America/Bogota');

// ---- Sesión ----
session_name('QivoAdminSess');
session_start();

// ---- PDO helper ----
function pdo() {
  static $pdo;
  if (!$pdo) {
    $pdo = new PDO(
      'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
      DB_USER, DB_PASS,
      [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
      ]
    );
  }
  return $pdo;
}

// ---- CSRF helpers ----
function csrf_token() {
  if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }
  return $_SESSION['csrf'];
}
function csrf_validate($t) {
  return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$t);
}

// ---- Auth (modo PLANO por ahora para entrar ya) ----
define('ADMIN_USER', 'qivo');

// Modo de autenticación: 'plain' (rápido) o 'hash' (seguro)
define('ADMIN_AUTH_MODE', 'plain');

// Clave en texto plano (solo mientras terminas instalación)
define('ADMIN_PASS_PLAIN', 'QivoAdmin#2025');

// Si cambias a 'hash', pega aquí el hash de password_hash('tuClave', PASSWORD_DEFAULT)
define('ADMIN_PASS_HASH', '$2y$10$placeholderplaceholderplaceholderplacexxxxxxxx');

// Helpers de sesión
function is_logged() { return !empty($_SESSION['admin_id']); }
function require_login() { if (!is_logged()) { header('Location: /admin/login.php'); exit; } }
function current_admin() {
  return is_logged() ? ['id'=>$_SESSION['admin_id'], 'user'=>$_SESSION['admin_user']] : null;
}

// Verificador de contraseña universal (acepta plain o hash según config)
function check_admin_password(string $input): bool {
  if (ADMIN_AUTH_MODE === 'plain') {
    return hash_equals(ADMIN_PASS_PLAIN, $input);
  }
  if (ADMIN_AUTH_MODE === 'hash') {
    return password_verify($input, ADMIN_PASS_HASH);
  }
  return false;
}

// ---- Optimizer (botón en el admin) ----
// Ruta correcta dentro de /admin:
define('OPTIMIZER_URL', 'https://qivodigital.com/admin/optimizer/run.php?token=QIVO_2025_MODO_BESTIA');