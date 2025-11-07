<?php
/**
 * QivoDigital — Optimizer Runner (Hostinger)
 * Ruta: /admin/optimizer/run.php?token=QIVO_2025_MODO_BESTIA
 */
declare(strict_types=1);
date_default_timezone_set('America/Bogota');

/* ===================== CONFIG ===================== */
const TOKEN       = 'QIVO_2025_MODO_BESTIA';
const WEB_ROOT    = __DIR__ . '/../../';                // /public_html
const CSS_DIR     = WEB_ROOT . 'assets/css';
const JS_DIR      = WEB_ROOT . 'assets/js';
const SITEMAP     = WEB_ROOT . 'sitemap.xml';
const LOG_DIR     = __DIR__ . '/logs';
/* ================================================== */

header('Content-Type: application/json; charset=UTF-8');
@ini_set('display_errors', '0');
@error_reporting(E_ALL);

@mkdir(LOG_DIR, 0755, true);

$resp = [
  'ok'          => false,
  'executed_at' => date('Y-m-d H:i:s'),
  'actions'     => [],
  'errors'      => [],
];

function logline(string $m): void {
  @file_put_contents(LOG_DIR.'/optimizer-'.date('Ymd').'.log',
    '['.date('H:i:s')."] $m\n", FILE_APPEND);
}
function add_action(array &$r, string $step, array $extra=[]): void {
  $r['actions'][] = ['step'=>$step, 'time'=>date('H:i:s')] + $extra;
  logline("$step " . ($extra ? json_encode($extra, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) : ''));
}
function add_error(array &$r, string $err): void {
  $r['errors'][] = $err;
  logline("ERROR: $err");
}

function minify_css(string $css): string {
  $css = preg_replace('#/\*.*?\*/#s', '', $css);                 // /* comments */
  $css = preg_replace('/\s+/', ' ', $css);
  $css = preg_replace('/\s*([{};:,>])\s*/', '$1', $css);
  $css = str_replace(';}', '}', $css);
  return trim((string)$css);
}
function minify_js(string $js): string {
  $js = preg_replace('#/\*.*?\*/#s', '', $js);
  $js = preg_replace('#(^|[^:])//.*$#m', '$1', $js);             // // comments
  $js = preg_replace('/\s+/', ' ', $js);
  $js = preg_replace('/\s*([{};:,=()+\-<>])\s*/', '$1', $js);
  return trim((string)$js);
}

function process_dir(array &$resp, string $dir, string $ext, callable $minify): void {
  if (!is_dir($dir)) { add_error($resp, "No existe carpeta: $dir"); return; }
  $it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
  );
  $files = 0; $saved = 0;
  foreach ($it as $f) {
    /** @var SplFileInfo $f */
    if ($f->isDir()) continue;
    if (strtolower($f->getExtension()) !== $ext) continue;
    $path = $f->getPathname();
    $src  = @file_get_contents($path);
    if ($src === false) { add_error($resp, "No se pudo leer: $path"); continue; }
    $mini = $minify($src);
    if ($mini !== '' && strlen($mini) < strlen($src)) {
      if (@file_put_contents($path, $mini) === false) {
        add_error($resp, "No se pudo escribir: $path"); continue;
      }
      $saved += strlen($src) - strlen($mini);
    }
    $files++;
  }
  add_action($resp, 'minify_done', ['dir'=>$dir, 'ext'=>$ext, 'files'=>$files, 'bytes_saved'=>$saved]);
}

function ensure_sitemap(array &$resp): void {
  if (is_file(SITEMAP)) { add_action($resp, 'sitemap_ok', ['file'=>'sitemap.xml']); return; }
  $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url><loc>https://qivodigital.com/</loc><priority>1.0</priority><changefreq>weekly</changefreq></url>
  <url><loc>https://qivodigital.com/servicios/</loc><priority>0.95</priority><changefreq>weekly</changefreq></url>
</urlset>
XML;
  if (@file_put_contents(SITEMAP, $xml) !== false) {
    add_action($resp, 'sitemap_created', ['file'=>'sitemap.xml']);
  } else {
    add_error($resp, 'No se pudo crear sitemap.xml');
  }
}

/* --------- TOKEN --------- */
$token = (string)($_GET['token'] ?? '');
if (!hash_equals(TOKEN, $token)) {
  http_response_code(403);
  $resp['errors'][] = 'TOKEN_INVALID';
  echo json_encode($resp, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  exit;
}

/* --------- RUN SAFE --------- */
try {
  add_action($resp, 'start', ['root'=>WEB_ROOT, 'css'=>CSS_DIR, 'js'=>JS_DIR]);
  process_dir($resp, CSS_DIR, 'css', 'minify_css');
  process_dir($resp, JS_DIR,  'js',  'minify_js');
  ensure_sitemap($resp);
  $resp['ok'] = true;
} catch (Throwable $e) {
  add_error($resp, 'EXCEPTION: '.$e->getMessage());
  http_response_code(500);
}

echo json_encode($resp, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);