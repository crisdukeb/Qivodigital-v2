<?php
$host = "72.60.127.195";
$port = 3306;
$db   = "qivosend_db";
$user = "qivo_user";
$pass = "Qiv0Send$2025!";

mysqli_report(MYSQLI_REPORT_OFF);
$t0 = microtime(true);
$cn = @mysqli_connect($host, $user, $pass, $db, $port);

header("Content-Type: text/plain; charset=utf-8");

if (!$cn) {
  echo "ERROR CONEXIÓN: " . mysqli_connect_errno() . " - " . mysqli_connect_error() . "\n";
  exit;
}

$dt = number_format((microtime(true)-$t0)*1000,1);
echo "OK conexión MySQL (" . $dt . " ms)\n\n";

$res = $cn->query("SELECT USER() AS user_str, CURRENT_USER() AS current_user, SUBSTRING_INDEX(USER(),'@',-1) AS seen_host, @@hostname AS mysql_server");
$row = $res ? $res->fetch_assoc() : null;

echo "USER():         " . ($row['user_str'] ?? 'n/a') . "\n";
echo "CURRENT_USER(): " . ($row['current_user'] ?? 'n/a') . "\n";
echo "IP vista por MySQL (seen_host): " . ($row['seen_host'] ?? 'n/a') . "\n";
echo "Servidor MySQL: " . ($row['mysql_server'] ?? 'n/a') . "\n";

$cn->close();