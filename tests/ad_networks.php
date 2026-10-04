<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/includes/ad_networks.php';
function ad_check(bool $ok,string $message): void { if(!$ok) throw new RuntimeException($message); }
$db=et_open_database(':memory:');
$input=['name'=>'Google display','provider'=>'adsense','slot'=>'bottom','status'=>'draft','publisher'=>'ca-pub-1234567890123456','unit_code'=>'1234567890','targets'=>['home'],'priority'=>'50'];
$unit=et_ad_validate($input); $unit['id']=str_repeat('a',24);
et_email_save($db,'ad_unit_'.$unit['id'],json_encode($unit));
ad_check(et_ad_public_units($db,'home')===[],'Draft unit exposed publicly');
try {et_ad_validate(array_replace($input,['status'=>'published'])); throw new RuntimeException('Unready provider published');} catch(InvalidArgumentException){}
$unit=et_ad_validate(array_replace($input,['status'=>'published','consent_ready'=>'1'])); $unit['id']=str_repeat('a',24); et_email_save($db,'ad_unit_'.$unit['id'],json_encode($unit));
$entries=et_ad_public_units($db,'home'); ad_check(count($entries)===1 && $entries[0]['provider']==='adsense','Published unit missing');
ad_check(et_ad_public_units($db,'privacy')===[],'Page targeting ignored');
ad_check(hash_equals(et_ad_token($db,$unit['id'],'home',$entries[0]['expires']),$entries[0]['token']),'Event token invalid');
ad_check(!hash_equals(et_ad_token($db,$unit['id'],'privacy',$entries[0]['expires']),$entries[0]['token']),'Event token not page bound');
$_SERVER['HTTP_USER_AGENT']='Fixture browser'; $_SERVER['REMOTE_ADDR']='127.0.0.1';
et_ad_record($db,$unit['id'],'mounted'); et_ad_record($db,$unit['id'],'mounted'); et_ad_record($db,$unit['id'],'failed');
$stats=et_ad_stats($db)[$unit['id']]; ad_check($stats['mounted']===1 && $stats['failed']===1,'Delivery signals not deduplicated');
$_SERVER['HTTP_DNT']='1'; et_ad_record($db,$unit['id'],'loaded'); ad_check(!isset(et_ad_stats($db)[$unit['id']]['loaded']),'Do Not Track ignored'); unset($_SERVER['HTTP_DNT']);
$native=array_replace($input,['provider'=>'adsterra','publisher'=>'','unit_code'=>str_repeat('b',32),'script_url'=>'https://example.com/'.str_repeat('b',32).'/invoke.js']); et_ad_validate($native);
foreach(['javascript:alert(1)','https://localhost/'.str_repeat('b',32).'/invoke.js','https://example.com/other/invoke.js'] as $url) {
    try {et_ad_validate(array_replace($native,['script_url'=>$url])); throw new RuntimeException('Unsafe/mismatched network URL accepted');} catch(InvalidArgumentException){}
}
echo "PASS: provider IDs, ready gate, targeting, signed events, signal deduplication, DNT and unsafe URLs.\n";
