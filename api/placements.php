<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/includes/placements.php';
require_once dirname(__DIR__).'/includes/ad_networks.php';
require_once dirname(__DIR__).'/includes/monitoring.php';
header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
if (($_SERVER['REQUEST_METHOD']??'')!=='GET') { header('Allow: GET'); http_response_code(405); echo '{"items":[]}'; exit; }
$page=is_string($_GET['page']??null)?$_GET['page']:'';
if (!isset(et_placement_pages()[$page])) { http_response_code(400); echo '{"items":[]}'; exit; }
$script=str_replace('\\','/',(string)$_SERVER['SCRIPT_NAME']);
$position=strpos($script,'/api/placements.php');
$base=$position===false?'':substr($script,0,$position);
try {
    $items=array_merge(et_public_placements(et_db(),$page,$base),et_ad_public_units(et_db(),$page));
    usort($items,static fn($a,$b)=>(int)($b['priority'] ?? 0)<=>(int)($a['priority'] ?? 0));
    echo json_encode(['items'=>$items],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
}
catch(Throwable $exception) {
    et_record_system_event('placement_api_error','Distributed placements could not be retrieved.','error');
    http_response_code(503); echo '{"items":[]}';
}
