<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/includes/placements.php';
require_once dirname(__DIR__).'/scripts/setup_placements.php';
function placement_check(bool $condition,string $message): void { if(!$condition){throw new RuntimeException($message);} }
$mysql=in_array('--mysql',$argv,true);
$database=$mysql?et_db():et_open_database(':memory:');
if($mysql){$database->beginTransaction();}else{et_setup_placements($database);}
$now=et_utc_now();
$database->prepare('INSERT INTO admin_users(username,display_name,password_hash,role,created_at,updated_at) VALUES(?,?,?,\'owner\',?,?)')->execute(['placement-test-'.bin2hex(random_bytes(6)),'Fixture','not-a-real-password',$now,$now]);
$admin=(int)$database->lastInsertId();
$input=['title'=>'Tangazo la majaribio','description'=>'Maelezo ya tangazo','kind'=>'sponsor','sponsor_name'=>'Sponsor fixture',
    'format'=>'banner','slot'=>'bottom','targets'=>['home'],'priority'=>10,'image_url'=>'https://example.com/banner.png',
    'destination_type'=>'external','external_url'=>'https://example.com/notice','status'=>'published'];
[$data,$errors]=et_validate_placement($input); placement_check(!$errors,'Valid placement rejected');
foreach ([['external_url'=>'javascript:alert(1)'],['image_url'=>'http://example.com/banner.png'],['targets'=>['admin']],
    ['kind'=>'sponsor','sponsor_name'=>''],['priority'=>101],['format'=>'arbitrary_html'],
    ['display_mode'=>'unlimited'],['popup_style'=>'fullscreen-script'],['skip_delay'=>31],
    ['starts_at'=>'2026-09-16T12:00','ends_at'=>'2026-09-16T11:00']] as $invalid) {
    [, $errors]=et_validate_placement(array_replace($input,$invalid)); placement_check((bool)$errors,'Invalid placement accepted');
}
placement_check(et_placement_matches(['all'],'psle-schools'),'All targeting');
foreach(['form-one','form-five'] as $module) {
    placement_check(et_placement_matches(['levels'],$module),'Selection landing group');
    placement_check(et_placement_matches(['schools'],$module.'-schools'),'Selection schools group');
    placement_check(et_placement_matches(['results'],$module.'-results'),'Selection results group');
    placement_check(et_placement_matches(['errors'],$module.'-error'),'Selection errors group');
    placement_check(et_placement_matches([$module.'-results'],$module.'-results'),'Selection exact target');
    placement_check(!et_placement_matches([$module],$module.'-results'),'Landing does not target results');
}
placement_check(et_placement_matches(['levels'],'sfna'),'Level group');
placement_check(!et_placement_matches(['levels'],'sfna-results'),'Group isolation');
placement_check(!et_placement_matches(['all'],'admin'),'Admin must not be targeted');
$seed=function(array $override) use($database,$input,$admin,$now): void {
    [$row,$errors]=et_validate_placement(array_replace($input,$override));
    if($errors){throw new RuntimeException(implode(';',$errors));}
    $row+=['created_by'=>$admin,'updated_by'=>$admin,'created_at'=>$now,'updated_at'=>$now];
    $keys=array_keys($row);$database->prepare('INSERT INTO placement_items('.implode(',',$keys).') VALUES(:'.implode(',:',$keys).')')->execute($row);
};
$seed(['title'=>'Draft hidden','status'=>'draft','priority'=>100]);
$seed(['title'=>'Archived hidden','status'=>'archived','priority'=>100]);
$seed(['title'=>'Future hidden','starts_at'=>et_utc_datetime_to_local(gmdate('Y-m-d H:i:s',time()+86400)),'priority'=>100]);
$seed(['title'=>'Expired hidden','ends_at'=>et_utc_datetime_to_local(gmdate('Y-m-d H:i:s',time()-86400)),'priority'=>100]);
$seed(['title'=>'Wrong page hidden','targets'=>['about'],'priority'=>100]);
$seed(['title'=>'Lower priority','priority'=>1]);
$seed(['title'=>'First bottom','priority'=>90]);
$seed(['title'=>'Second bottom','priority'=>80,'format'=>'card']);
$seed(['title'=>'Top banner','slot'=>'top','priority'=>70]);
$seed(['title'=>'Popup notice','slot'=>'popup','priority'=>75]);
$seed(['title'=>'Search only','slot'=>'top','targets'=>['levels']]);
$database->prepare('INSERT INTO placement_items(title,description,kind,sponsor_name,format,slot,targets,priority,image_url,destination_type,external_url,content_id,status,created_by,updated_by,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,NULL,?,?,?,?,?)')
    ->execute(['Missing article hidden','','system','','banner','bottom','["home"]',100,'','internal','','published',$admin,$admin,$now,$now]);
$items=et_public_placements($database,'home','/get-results-faster');
placement_check(count($items)===4,'Slot limits / publication filtering');
placement_check(array_column($items,'title')===['First bottom','Second bottom','Popup notice','Top banner'],'Priority / exclusions');
placement_check(!array_key_exists('created_by',$items[0]),'Private fields leaked');
placement_check(count(et_public_placements($database,'csee',''))===1,'Group selection');
placement_check(count(et_public_placements($database,'form-one',''))===1,'Form One public placement feed');
placement_check(count(et_public_placements($database,'form-five',''))===1,'Form Five public placement feed');
$slug='placement-article-'.bin2hex(random_bytes(6));
$database->prepare('INSERT INTO content_items(category,title,slug,excerpt,body,status,published_at,created_by,updated_by,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?)')
    ->execute(['announcement','Placement article',$slug,'Fixture excerpt','Fixture body','published',$now,$admin,$admin,$now,$now]);
$articleId=(int)$database->lastInsertId();
$internal=array_replace($data,['id'=>999,'destination_type'=>'internal','content_id'=>$articleId,'external_url'=>'']);
$entry=et_placement_entry($database,$internal,'/get-results-faster');
placement_check($entry && $entry['href']==='/get-results-faster/announcements/'.$slug.'/','Internal article destination');
$database->prepare('UPDATE content_items SET status=? WHERE id=?')->execute(['draft',$articleId]);
placement_check(et_placement_entry($database,$internal,'')===null,'Unpublished article omitted');
$database->prepare('DELETE FROM content_items WHERE id=?')->execute([$articleId]);
placement_check(et_placement_entry($database,$internal,'')===null,'Deleted article omitted');
echo "PASS: placement validation, unsafe URLs, targets, groups, status, scheduling, priority, limits and public payload.\n";
if($mysql){$database->rollBack();echo "MariaDB diagnostic writes rolled back.\n";}
