<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/monitoring.php';
et_register_fatal_error_monitoring();
http_response_code(404);
header('X-Robots-Tag: noindex, follow');
et_record_system_event('route_not_found', 'A visitor requested a URL that does not exist.', 'warning');
$scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/errors/404.php'));
$position = strpos($scriptName, '/errors/404.php');
$basePath = $position === false ? '' : substr($scriptName, 0, $position);
?>
<!doctype html><html lang="sw"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex, follow"><title>Ukurasa haujapatikana | ElimuTaifa</title><style>body{margin:0;display:grid;min-height:100vh;padding:24px;place-items:center;color:#18304d;background:#f4f7fa;font-family:Inter,system-ui,sans-serif}.box{width:min(560px,100%);padding:42px;border:1px solid #dce5ed;border-radius:18px;background:#fff;box-shadow:0 18px 55px rgba(3,27,78,.09);text-align:center}.code{color:#058249;font-size:64px;font-weight:900}.box h1{margin:4px 0 10px;color:#031b4e}.box p{color:#607188;line-height:1.65}.box a{display:inline-block;margin-top:14px;padding:11px 17px;border-radius:9px;color:#fff;background:#058249;font-weight:800;text-decoration:none}</style></head><body><main class="box"><div class="code">404</div><h1>Ukurasa haujapatikana</h1><p>Link inaweza kuwa imebadilishwa, imeondolewa au haijaandikwa kwa usahihi.</p><a href="<?= htmlspecialchars(($basePath === '' ? '' : $basePath) . '/', ENT_QUOTES, 'UTF-8') ?>">Rudi ElimuTaifa</a></main></body></html>
