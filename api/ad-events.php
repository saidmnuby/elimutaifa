<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/includes/ad_networks.php';
header('Cache-Control: no-store'); header('X-Content-Type-Options: nosniff');
if(($_SERVER['REQUEST_METHOD'] ?? '')!=='POST') { http_response_code(405); exit; }
if((int)($_SERVER['CONTENT_LENGTH'] ?? 0)>2048) { http_response_code(413); exit; }
$input=json_decode(file_get_contents('php://input',false,null,0,2049),true) ?: [];
try {
    $db=et_db(); $id=(string)($input['id'] ?? ''); $page=(string)($input['page'] ?? ''); $expires=(int)($input['expires'] ?? 0); $event=(string)($input['event'] ?? '');
    if(!preg_match('/^[a-f0-9]{24}$/D',$id) || !isset(et_placement_pages()[$page]) || !in_array($event,['mounted','loaded','failed'],true) || $expires<time() || $expires>time()+3600 || !hash_equals(et_ad_token($db,$id,$page,$expires),(string)($input['token'] ?? ''))) { http_response_code(400); exit; }
    $unit=et_ad_units($db)[$id] ?? null;
    if(!$unit || $unit['status']!=='published' || !et_placement_matches($unit['targets'],$page)) { http_response_code(400); exit; }
    et_ad_record($db,$id,$event); http_response_code(204);
} catch(Throwable) { http_response_code(503); }
