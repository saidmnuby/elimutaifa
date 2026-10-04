<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/includes/admin_email.php';
function email_check(bool $ok,string $message): void { if (!$ok) throw new RuntimeException($message); }
$db=et_open_database(':memory:');
et_email_save($db,'admin_email_main_owner_id','1');
foreach ([1=>'owner',2=>'admin',3=>'owner'] as $id=>$role) {
    $db->prepare('INSERT INTO admin_users(id,username,display_name,password_hash,role,is_active,created_at,updated_at) VALUES(?,?,?,?,?,1,?,?)')->execute([$id,'user'.$id,'User '.$id,'fixture',$role,et_utc_now(),et_utc_now()]);
}
$subject=['id'=>2,'username'=>'user2','display_name'=>'User 2','role'=>'admin'];
et_admin_email_event('login',$subject,$db);
email_check((int)$db->query("SELECT COUNT(*) FROM app_settings WHERE setting_key LIKE 'admin_email_outbox_%'")->fetchColumn()===0,'Disabled email generated queue');
et_email_save($db,'admin_email_enabled','1');
et_email_save($db,'admin_email_address_1','owner@example.com');
et_email_save($db,'admin_email_address_2','admin@example.com');
et_email_save($db,'admin_email_address_3','OWNER@example.com');
et_admin_email_event('login',$subject,$db);
$sent=[]; $counts=et_email_process($db,static function($item)use(&$sent){$sent[]=$item;});
email_check($counts['sent']===2 && count($sent)===2,'Self/owner routing or deduplication failed');
email_check(et_email_process($db,static function(){throw new RuntimeException('duplicate');})['failed']===0,'Already sent message retried');
et_admin_email_event('created',$subject,$db);
$counts=et_email_process($db,static function(){throw new RuntimeException('SECRET SMTP PASSWORD');});
email_check($counts['failed']===2,'SMTP failures not recorded');
$json=implode(' ',$db->query("SELECT setting_value FROM app_settings WHERE setting_key LIKE 'admin_email_outbox_%'")->fetchAll(PDO::FETCH_COLUMN));
email_check(!str_contains($json,'SECRET SMTP PASSWORD'),'Transport secret leaked to logs');
et_admin_email_event('login',$subject,$db); $db->exec('UPDATE admin_users SET is_active=0 WHERE id=2');
$counts=et_email_process($db,static function($item){email_check($item['to']!=='admin@example.com','Disabled recipient received email');});
email_check($counts['sent']===1,'Disabled account notification not cancelled');
$calls=0;
et_email_setup($db,1,'gmail','sender@gmail.com','abcd efgh ijkl mnop','owner@example.com',static function($item,$config)use(&$calls){$calls++; email_check($config['password']==='abcdefghijklmnop','Google code spaces not normalized'); email_check($item['to']==='owner@example.com','Setup test has wrong recipient');});
$saved=et_email_setting($db,'admin_email_sender');
email_check($calls===1 && !str_contains($saved,'abcdefghijklmnop'),'Setup secret saved in plain text');
email_check(et_email_config($db)['password']==='abcdefghijklmnop','Encrypted secret cannot be read by worker');
try { et_email_setup($db,1,'brevo','verified@example.com','fake-brevo-api-key-123456','owner@example.com',static function(){throw new RuntimeException('SECRET');}); throw new RuntimeException('Failed provider activated'); } catch (RuntimeException $e) { email_check(!str_contains($e->getMessage(),'SECRET'),'Provider error leaked secret'); }
email_check(et_email_setting($db,'admin_email_sender')===$saved,'Failed setup overwrote working connection');
et_email_setup($db,1,'gmail','sender@gmail.com','','owner@example.com',static function($item,$config){email_check($config['password']==='abcdefghijklmnop','Saved credential not reused');});
try { et_email_setup($db,1,'gmail','other@gmail.com','','owner@example.com',static function(){throw new RuntimeException('Should not send');}); throw new RuntimeException('Saved secret used with changed sender'); } catch (InvalidArgumentException) {}
et_email_setup($db,1,'brevo','verified@example.com','fake-brevo-api-key-123456','owner@example.com',static function($item,$config){email_check($config['provider']==='brevo','Brevo selection not passed to transport');});
email_check(et_email_config($db)['provider']==='brevo','Successful Brevo setup not saved');
$senderBefore=et_email_setting($db,'admin_email_sender'); $blocked=false; $testSent=false;
try { et_email_setup($db,3,'gmail','secondsender@gmail.com','qrst uvwx yzab cdef','secondowner@example.com',static function()use(&$testSent){$testSent=true;}); } catch(RuntimeException) { $blocked=true; }
email_check($blocked && !$testSent && et_email_setting($db,'admin_email_sender')===$senderBefore,'Secondary owner changed sender or sent setup email');
email_check(et_email_main_owner($db,1) && !et_email_main_owner($db,3) && !et_email_main_owner($db,2),'Main owner access was not isolated');
et_email_save($db,'admin_email_address_3','secondowner@example.com');
email_check(et_email_setting($db,'admin_email_address_1')==='owner@example.com','Secondary owner recipient changed main owner address');
$db->exec('UPDATE admin_users SET is_active=0 WHERE id=1');
email_check(!et_email_main_owner($db,1) && !et_email_main_owner($db,3),'Disabled main owner transferred sender access');
try {et_email_address("bad@example.com\r\nBcc: other@example.com"); throw new RuntimeException('Unsafe address accepted');} catch(InvalidArgumentException){}
echo "PASS: disabled notifications, owner/self routing, deduplication, failure isolation, secret redaction, no duplicate retries and recipient access checks.\n";
