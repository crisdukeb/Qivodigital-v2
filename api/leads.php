<?php
/**
 * QivoDigital — Receptor de Leads
 * Guarda en MySQL (VPS) y envía correos por SMTP Hostinger.
 * - Notificación interna: notificaciones@qivodigital.com, cristhianvelazques@live.com
 * - Autorespuesta al cliente: HTML corporativo.
 *
 * Requiere (opcional): PHPMailer (vendor/autoload.php). Si no está, usa mail() como fallback.
 */

/* =========================
   CONFIGURACIÓN GENERAL
   ========================= */
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok'=>false,'error'=>'Método no permitido']);
  exit;
}

// ---- DB remota (VPS) ----
$DB_HOST = '72.60.127.195';
$DB_NAME = 'qivosend_db';
$DB_USER = 'qivo_user';
$DB_PASS = 'Qiv0Send$2025!';

// ---- SMTP Hostinger ----
$SMTP_HOST = getenv('SMTP_HOST') ?: 'smtp.hostinger.com';
$SMTP_PORT = getenv('SMTP_PORT') ?: 465;
$SMTP_SECURE = filter_var(getenv('SMTP_SECURE') ?: 'true', FILTER_VALIDATE_BOOLEAN); // true => SMTPS
$SMTP_USER = getenv('SMTP_USER') ?: 'notificaciones@qivodigital.com';
$SMTP_PASS = getenv('SMTP_PASS') ?: 'Empresa1+cr';
$FROM_EMAIL = 'notificaciones@qivodigital.com';
$FROM_NAME  = 'QivoDigital';

// Destinatarios internos
$OPS_TO = [
  ['email' => 'notificaciones@qivodigital.com', 'name' => 'Qivo Ops'],
  ['email' => 'cristhianvelazques@live.com',    'name' => 'Cristhian Velazques'],
];

/* =========================
   UTILIDADES
   ========================= */
function clean_text($v, $max = 500) {
  if (!isset($v)) return null;
  $v = (string)$v;
  $v = strip_tags($v);
  $v = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $v);
  $v = trim(preg_replace('/\s+/u', ' ', $v));
  if ($max && mb_strlen($v,'UTF-8') > $max) {
    $v = mb_substr($v, 0, $max, 'UTF-8');
  }
  return $v === '' ? null : $v;
}
function esc($s){ return htmlspecialchars((string)$s ?? '', ENT_QUOTES, 'UTF-8'); }

function now_co(){
  // Hora local “CO” para el email (no afecta DB)
  $dt = new DateTime('now', new DateTimeZone('America/Bogota'));
  return $dt->format('Y-m-d H:i:s');
}

/* =========================
   INPUT
   ========================= */
$brand       = clean_text($_POST['brand']        ?? null, 40)  ?: 'qivodigital';
$source_page = clean_text($_POST['source_page']  ?? null, 160) ?: ($_SERVER['HTTP_REFERER'] ?? '/');
$nombre      = clean_text($_POST['nombre']       ?? null, 160);
$email       = clean_text($_POST['email']        ?? null, 160);
$interes     = clean_text($_POST['interes']      ?? null, 40);
$mensaje_raw = clean_text($_POST['mensaje']      ?? null, 3000);
$empresa     = clean_text($_POST['empresa']      ?? null, 160);

$permitidos = [
  'whatsapp','automatizacion','ecommerce','software-medida',
  'integraciones','web-apps','apps','chatbots','crm','otros'
];
if (!in_array($interes, $permitidos, true)) $interes = 'otros';

if (!$nombre || !$email || !$interes) {
  http_response_code(422);
  echo json_encode(['ok'=>false,'error'=>'Faltan campos obligatorios']);
  exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  http_response_code(422);
  echo json_encode(['ok'=>false,'error'=>'Email inválido']);
  exit;
}

$prefijoEmpresa = $empresa ? "Empresa: {$empresa}\n" : '';
$mensaje = $mensaje_raw ? ($prefijoEmpresa.$mensaje_raw) : ($prefijoEmpresa."Solicitud inicial sin mensaje.");

// Metadatos
$ip         = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

// UTM
$utm_source   = clean_text($_POST['utm_source']   ?? ($_GET['utm_source']   ?? null), 80);
$utm_medium   = clean_text($_POST['utm_medium']   ?? ($_GET['utm_medium']   ?? null), 80);
$utm_campaign = clean_text($_POST['utm_campaign'] ?? ($_GET['utm_campaign'] ?? null),120);

/* =========================
   DB INSERT (PDO)
   ========================= */
$dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4";
$lead_id = null;
try {
  $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
  ]);
  $sql = "INSERT INTO qd_leads
    (brand, source_page, nombre, email, interes, mensaje, ip, user_agent, utm_source, utm_medium, utm_campaign)
    VALUES
    (:brand,:source_page,:nombre,:email,:interes,:mensaje,:ip,:user_agent,:utm_source,:utm_medium,:utm_campaign)";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([
    ':brand'        => $brand,
    ':source_page'  => $source_page,
    ':nombre'       => $nombre,
    ':email'        => $email,
    ':interes'      => $interes,
    ':mensaje'      => $mensaje,
    ':ip'           => $ip,
    ':user_agent'   => $user_agent,
    ':utm_source'   => $utm_source,
    ':utm_medium'   => $utm_medium,
    ':utm_campaign' => $utm_campaign,
  ]);
  $lead_id = $pdo->lastInsertId();
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'DB_ERROR']);
  // Log discreto
  error_log('[QD_LEADS][DB] '.$e->getMessage());
  exit;
}

/* =========================
   EMAIL TEMPLATES (HTML)
   ========================= */
$now_str = now_co();

function tpl_client_html($data) {
  $e = fn($s)=>esc($s);
  return '
  <div style="font-family:Inter,system-ui,Segoe UI,Roboto,Arial,sans-serif;background:#0b1020;padding:24px;color:#e9eefb">
    <div style="max-width:660px;margin:0 auto;background:#0f1630;border:1px solid #223055;border-radius:12px;overflow:hidden">
      <div style="padding:18px 20px;background:linear-gradient(90deg,#0e1a3a,#1b2a5b);color:#fff">
        <div style="font-weight:700;font-size:16px;letter-spacing:.5px">Solicitud recibida — QivoDigital</div>
        <div style="opacity:.85;font-size:12px">'.$e($data['now']).' · Colombia</div>
      </div>
      <div style="padding:20px;color:#dce6ff;font-size:14px;line-height:1.6">
        <p>Hola '.$e($data['nombre']).',</p>
        <p>Gracias por tu interés en <strong>QivoDigital</strong>. Hemos recibido tu solicitud correctamente y nuestro equipo especializado se comunicará contigo en las próximas <strong>24 horas hábiles</strong>.</p>
        <p style="margin:14px 0 4px"><strong>Detalles de tu solicitud:</strong></p>
        <ul style="margin:8px 0 16px;padding-left:18px">
          <li><strong>Interés:</strong> '.$e($data['interes']).'</li>
          '.($data['empresa']?'<li><strong>Empresa:</strong> '.$e($data['empresa']).'</li>':'').'
          <li><strong>Origen de registro:</strong> '.$e($data['source']).'</li>
        </ul>
        <p>Estamos preparando la información o demo necesaria según tu requerimiento.</p>
        <p style="margin-top:18px">— <strong>Equipo QivoDigital</strong><br/><span style="opacity:.8">Tecnología que transforma negocios.</span></p>
      </div>
      <div style="padding:14px 20px;background:#0e1533;color:#9fb3ff;font-size:12px;text-align:center">
        <a href="https://qivodigital.com" target="_blank" style="color:#9fb3ff;text-decoration:none">www.qivodigital.com</a>
      </div>
    </div>
  </div>';
}

function tpl_ops_html($data){
  $e = fn($s)=>esc($s);
  return '
  <div style="font-family:Inter,system-ui,Segoe UI,Roboto,Arial,sans-serif">
    <h2 style="margin:0 0 8px">Nuevo Lead — QivoDigital</h2>
    <p style="margin:0 0 12px;color:#444">ID '.$e($data['id']).' · '.$e($data['now']).' (CO)</p>
    <table cellpadding="6" cellspacing="0" style="border-collapse:collapse;border:1px solid #ddd">
      <tr><td><strong>Nombre</strong></td><td>'.$e($data['nombre']).'</td></tr>
      <tr><td><strong>Email</strong></td><td>'.$e($data['email']).'</td></tr>
      '.($data['empresa']?'<tr><td><strong>Empresa</strong></td><td>'.$e($data['empresa']).'</td></tr>':'').'
      <tr><td><strong>Interés</strong></td><td>'.$e($data['interes']).'</td></tr>
      <tr><td><strong>Source Page</strong></td><td>'.$e($data['source']).'</td></tr>
      <tr><td><strong>UTM</strong></td><td>source='.$e($data['utm_source']).' · medium='.$e($data['utm_medium']).' · campaign='.$e($data['utm_campaign']).'</td></tr>
      <tr><td><strong>IP</strong></td><td>'.$e($data['ip']).'</td></tr>
      <tr><td><strong>UA</strong></td><td>'.$e($data['ua']).'</td></tr>
    </table>
    <p style="margin:14px 0 6px"><strong>Mensaje:</strong></p>
    <pre style="white-space:pre-wrap;background:#0f1630;color:#dce6ff;padding:12px;border-radius:8px">'.$e($data['mensaje']).'</pre>
  </div>';
}

/* =========================
   ENVÍO DE CORREO
   ========================= */
$mailer_ok_client = false;
$mailer_ok_ops    = false;

// Intentar usar PHPMailer si existe; si no, usar mail().
$has_phpmailer = false;
try {
  if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    $has_phpmailer = true;
  }
} catch (Throwable $e) { $has_phpmailer = false; }

$data = [
  'id'            => $lead_id,
  'now'           => $now_str,
  'nombre'        => $nombre,
  'email'         => $email,
  'empresa'       => $empresa,
  'interes'       => $interes,
  'source'        => $source_page,
  'utm_source'    => $utm_source,
  'utm_medium'    => $utm_medium,
  'utm_campaign'  => $utm_campaign,
  'ip'            => $ip,
  'ua'            => $user_agent,
  'mensaje'       => $mensaje,
];

$subject_client = 'Solicitud recibida — QivoDigital';
$subject_ops    = 'Nuevo lead ('.$interes.') — #'.$lead_id;

/* ---- con PHPMailer ---- */
if ($has_phpmailer) {
  try {
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = $SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = $SMTP_USER;
    $mail->Password   = $SMTP_PASS;
    if ($SMTP_SECURE) {
      $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
      $mail->Port       = (int)$SMTP_PORT; // 465
    } else {
      $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
      $mail->Port       = (int)$SMTP_PORT; // 587 típico
    }
    $mail->CharSet = 'UTF-8';
    $mail->setFrom($FROM_EMAIL, $FROM_NAME);
    $mail->addAddress($email, $nombre);
    $mail->Subject = $subject_client;
    $mail->isHTML(true);
    $mail->Body = tpl_client_html($data);
    $mail->AltBody = "Hola {$nombre},\n\nHemos recibido tu solicitud. Nuestro equipo se comunicará contigo en menos de 24 horas hábiles.\n\nQivoDigital — www.qivodigital.com";
    $mailer_ok_client = $mail->send();
  } catch (Throwable $e) {
    error_log('[QD_LEADS][MAIL_CLIENT] '.$e->getMessage());
    $mailer_ok_client = false;
  }

  try {
    $mail2 = new PHPMailer\PHPMailer\PHPMailer(true);
    $mail2->isSMTP();
    $mail2->Host       = $SMTP_HOST;
    $mail2->SMTPAuth   = true;
    $mail2->Username   = $SMTP_USER;
    $mail2->Password   = $SMTP_PASS;
    if ($SMTP_SECURE) {
      $mail2->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
      $mail2->Port       = (int)$SMTP_PORT;
    } else {
      $mail2->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
      $mail2->Port       = (int)$SMTP_PORT;
    }
    $mail2->CharSet = 'UTF-8';
    $mail2->setFrom($FROM_EMAIL, $FROM_NAME);
    foreach ($OPS_TO as $r){
      $mail2->addAddress($r['email'], $r['name']);
    }
    $mail2->Subject = $subject_ops;
    $mail2->isHTML(true);
    $mail2->Body = tpl_ops_html($data);
    $mail2->AltBody = "Nuevo lead #{$lead_id}\nNombre: {$nombre}\nEmail: {$email}\nInterés: {$interes}\nEmpresa: {$empresa}\nSource: {$source_page}\n\nMensaje:\n{$mensaje}";
    $mailer_ok_ops = $mail2->send();
  } catch (Throwable $e) {
    error_log('[QD_LEADS][MAIL_OPS] '.$e->getMessage());
    $mailer_ok_ops = false;
  }
}
/* ---- fallback: mail() ---- */
else {
  // Cliente
  $headers_html  = "MIME-Version: 1.0\r\n";
  $headers_html .= "Content-type: text/html; charset=UTF-8\r\n";
  $headers_html .= "From: {$FROM_NAME} <{$FROM_EMAIL}>\r\n";
  $mailer_ok_client = @mail($email, '=?UTF-8?B?'.base64_encode($subject_client).'?=', tpl_client_html($data), $headers_html);

  // Ops
  $headers_html2  = "MIME-Version: 1.0\r\n";
  $headers_html2 .= "Content-type: text/html; charset=UTF-8\r\n";
  $headers_html2 .= "From: {$FROM_NAME} <{$FROM_EMAIL}>\r\n";
  $to_list = implode(',', array_map(fn($r)=>$r['email'], $OPS_TO));
  $mailer_ok_ops = @mail($to_list, '=?UTF-8?B?'.base64_encode($subject_ops).'?=', tpl_ops_html($data), $headers_html2);
}

/* =========================
   RESPUESTA
   ========================= */
echo json_encode([
  'ok' => true,
  'id' => $lead_id,
  'mail_client' => $mailer_ok_client,
  'mail_ops'    => $mailer_ok_ops,
]);