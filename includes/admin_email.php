<?php
declare(strict_types=1);
require_once __DIR__ . '/admin_db.php';
require_once __DIR__ . '/two_factor.php';

function et_email_setting(PDO $db, string $key): ?string {
    $q = $db->prepare('SELECT setting_value FROM app_settings WHERE setting_key=?'); $q->execute([$key]);
    $value = $q->fetchColumn(); return $value === false ? null : (string)$value;
}
function et_email_save(PDO $db, string $key, string $value): void {
    $db->prepare(et_conflict_sql($db,'INSERT INTO app_settings(setting_key,setting_value,updated_at) VALUES(?,?,?) ON CONFLICT(setting_key) DO UPDATE SET setting_value=excluded.setting_value,updated_at=excluded.updated_at'))
        ->execute([$key,$value,et_utc_now()]);
}
function et_email_address(string $email): string {
    $email = trim($email);
    if ($email !== '' && (strlen($email)>254 || !filter_var($email,FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/',$email))) throw new InvalidArgumentException('Enter a valid email address.');
    return $email;
}
/** Pinned main owner: never transfer sender access when an account is disabled. */
function et_email_main_owner(PDO $db,int $userId): bool {
    $mainId=(int)(et_email_setting($db,'admin_email_main_owner_id') ?? '0');
    if ($mainId<=0 || $mainId!==$userId) return false;
    $q=$db->prepare("SELECT COUNT(*) FROM admin_users WHERE id=? AND role='owner' AND is_active=1 AND deleted_at IS NULL");
    $q->execute([$userId]); return (int)$q->fetchColumn()===1;
}
function et_email_require_main_owner(PDO $db,int $userId): void {
    if (!et_email_main_owner($db,$userId)) throw new RuntimeException('Only the main owner can change system email settings.');
}
function et_email_config(?PDO $db = null): array {
    $saved = et_email_setting($db ?? et_db(),'admin_email_sender');
    if ($saved !== null) {
        $config = json_decode($saved,true,512,JSON_THROW_ON_ERROR);
        $bytes = base64_decode($config['secret'],true);
        if ($bytes === false || strlen($bytes)<29) throw new RuntimeException('Email settings could not be read.');
        $secret = openssl_decrypt(substr($bytes,28),'aes-256-gcm',et_mfa_key(),OPENSSL_RAW_DATA,substr($bytes,0,12),substr($bytes,12,16),'email:sender');
        if ($secret === false) throw new RuntimeException('Email settings could not be read.');
        $config['password'] = $secret; unset($config['secret']);
        return $config;
    }
    $path = dirname(__DIR__) . '/storage/email.php';
    $config = is_file($path) ? require $path : [];
    if (!is_array($config)) throw new RuntimeException('Invalid email configuration.');
    return $config + ['provider'=>'gmail','host'=>'smtp.gmail.com','port'=>587,'username'=>'','password'=>'','from_email'=>'','from_name'=>'ElimuTaifa','encryption'=>'tls'];
}
function et_email_ready(): bool {
    $config = et_email_config();
    $autoload = dirname(__DIR__).'/vendor/autoload.php';
    if (is_file($autoload)) require_once $autoload;
    return $config['password'] !== '' && et_email_address($config['from_email']) !== '' && (($config['provider'] ?? 'gmail')==='brevo' ? function_exists('curl_init') : class_exists(\PHPMailer\PHPMailer\PHPMailer::class) && $config['username'] !== '');
}
/** Only replace a working sender after the provider accepts a test message. */
function et_email_setup(PDO $db, int $ownerId, string $provider, string $sender, string $secret, string $recipient, ?callable $send = null, ?string $expected = null): void {
    et_email_require_main_owner($db,$ownerId);
    if (!in_array($provider,['gmail','brevo'],true)) throw new InvalidArgumentException('Choose Gmail or Brevo.');
    $sender=et_email_address($sender); $recipient=et_email_address($recipient);
    if ($sender==='' || $recipient==='') throw new InvalidArgumentException('Enter the sender email and your account email.');
    $oldRaw=et_email_setting($db,'admin_email_sender');
    if ($expected!==null && !hash_equals(hash('sha256',$oldRaw ?? ''),$expected)) throw new RuntimeException('Email settings changed. Reload the page and try again.');
    if (trim($secret)==='') {
        $current=et_email_config($db);
        if (($current['provider'] ?? 'gmail')!==$provider || strcasecmp($current['from_email'],$sender)!==0) throw new InvalidArgumentException('Enter the code or API key for this sender.');
        $secret=$current['password'];
    }
    if ($provider==='gmail') {
        $secret=preg_replace('/\s+/','',$secret);
        if (!preg_match('/^[a-zA-Z0-9]{16}$/D',$secret)) throw new InvalidArgumentException('Enter the 16-character Google App Password, not your normal Gmail password.');
    } elseif (strlen(trim($secret))<20 || strlen($secret)>512 || preg_match('/[\r\n]/',$secret)) throw new InvalidArgumentException('Enter a valid Brevo API key.');
    $config=['provider'=>$provider,'from_email'=>$sender,'from_name'=>'ElimuTaifa','username'=>$sender,'password'=>$secret,'host'=>'smtp.gmail.com','port'=>587,'encryption'=>'tls'];
    $item=['to'=>$recipient,'subject'=>'ElimuTaifa: email setup test','body'=>'Your ElimuTaifa email connection test was accepted. You can now receive admin notifications.'];
    try { ($send ?? 'et_email_transport_send')($item,$config); }
    catch (Throwable) { throw new RuntimeException($provider==='gmail' ? 'Gmail test failed. Check the email and App Password, then try again.' : 'Brevo test failed. Check the API key and verified sender email, then try again.'); }
    $iv=random_bytes(12); $encrypted=openssl_encrypt($secret,'aes-256-gcm',et_mfa_key(),OPENSSL_RAW_DATA,$iv,$tag,'email:sender');
    if ($encrypted===false) throw new RuntimeException('Email settings could not be saved.');
    unset($config['password']); $config['secret']=base64_encode($iv.$tag.$encrypted); $config['verified_at']=et_utc_now();
    $db->beginTransaction();
    try {
        $new=json_encode($config,JSON_THROW_ON_ERROR);
        if ($oldRaw===null) $db->prepare('INSERT INTO app_settings(setting_key,setting_value,updated_at) VALUES(?,?,?)')->execute(['admin_email_sender',$new,et_utc_now()]);
        else {
            $q=$db->prepare('UPDATE app_settings SET setting_value=?,updated_at=? WHERE setting_key=? AND setting_value=?'); $q->execute([$new,et_utc_now(),'admin_email_sender',$oldRaw]);
            if ($q->rowCount()!==1) throw new RuntimeException('Email settings changed. Reload the page and try again.');
        }
        et_email_save($db,'admin_email_address_'.$ownerId,$recipient);
        et_email_save($db,'admin_email_enabled','1'); $db->commit();
    } catch (Throwable $e) { if ($db->inTransaction()) $db->rollBack(); throw $e; }
}
/** Queue after the completed sign-in, including 2FA. Never block account access. */
function et_admin_email_event(string $event, array $subject, ?PDO $db = null): void {
    try {
        $db ??= et_db();
        if (et_email_setting($db,'admin_email_enabled') !== '1') return;
        if (!in_array($event,['login','created'],true)) throw new InvalidArgumentException('Invalid notification event.');
        $preferences=json_decode(et_email_setting($db,'notification_preferences') ?? '{}',true) ?: [];
        if (isset($preferences[$event]) && !$preferences[$event]) return;
        $q = $db->prepare("SELECT id FROM admin_users WHERE is_active=1 AND deleted_at IS NULL AND (role='owner' OR id=?)"); $q->execute([(int)$subject['id']]);
        $recipients = [];
        foreach ($q->fetchAll() as $user) {
            $email = et_email_address(et_email_setting($db,'admin_email_address_' . $user['id']) ?? '');
            if ($email !== '') $recipients[strtolower($email)] = $email;
        }
        $time = (new DateTimeImmutable('now',new DateTimeZone('Africa/Dar_es_Salaam')))->format('Y-m-d H:i:s') . ' EAT';
        $title = $event === 'login' ? 'Admin signed in' : 'Admin account created';
        $body = "ElimuTaifa notification\n\n" . $title . "\nName: " . $subject['display_name'] . "\nUsername: " . $subject['username'] . "\nRole: " . $subject['role'] . "\nTime: " . $time . "\n\nIf this was unexpected, contact the owner and review the admin audit log.\nPasswords and sign-in codes are never included in these messages.";
        foreach ($recipients as $recipient) {
            et_email_save($db,'admin_email_outbox_' . bin2hex(random_bytes(16)),json_encode(['status'=>'pending','event'=>$event,'to'=>$recipient,'subject'=>'ElimuTaifa: '.$title,'body'=>$body,'created_at'=>et_utc_now(),'attempts'=>0],JSON_THROW_ON_ERROR));
        }
    } catch (Throwable) {
        error_log('ElimuTaifa: could not queue an admin email notification.');
        if (function_exists('et_record_system_event')) et_record_system_event('admin_email_queue_failed','An admin notification could not be queued.','warning');
    }
}
function et_email_smtp_send(array $item): void {
    if (!et_email_ready()) throw new RuntimeException('Email sender is not configured.');
    et_email_transport_send($item,et_email_config());
}
function et_email_transport_send(array $item, array $config): void {
    if (($config['provider'] ?? 'gmail')==='brevo') {
        $curl=curl_init('https://api.brevo.com/v3/smtp/email');
        curl_setopt_array($curl,[CURLOPT_POST=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>20,CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_HTTPHEADER=>['Content-Type: application/json','Accept: application/json','api-key: '.$config['password']],CURLOPT_POSTFIELDS=>json_encode(['sender'=>['email'=>et_email_address($config['from_email']),'name'=>$config['from_name']],'to'=>[['email'=>et_email_address($item['to'])]],'subject'=>$item['subject'],'textContent'=>$item['body']],JSON_THROW_ON_ERROR)]);
        $response=curl_exec($curl); $status=curl_getinfo($curl,CURLINFO_HTTP_CODE); curl_close($curl);
        if ($status!==201 || !is_string($response) || empty(json_decode($response,true)['messageId'])) throw new RuntimeException('The email provider did not accept the message.');
        return;
    }
    if (!in_array($config['encryption'],['tls','ssl'],true)) throw new RuntimeException('Encrypted SMTP is required.');
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP(); $mail->Host = $config['host']; $mail->Port = (int)$config['port'];
    $mail->SMTPAuth = true; $mail->Username = $config['username']; $mail->Password = $config['password'];
    $mail->SMTPSecure = $config['encryption']; $mail->Timeout = 15; $mail->SMTPDebug = 0; $mail->CharSet = 'UTF-8';
    $mail->setFrom(et_email_address($config['from_email']),$config['from_name']);
    $mail->addAddress(et_email_address($item['to'])); $mail->Subject = $item['subject']; $mail->Body = $item['body'];
    $mail->send();
}
function et_email_queue_test(PDO $db, int $userId): void {
    if (et_email_setting($db,'admin_email_enabled') !== '1' || !et_email_ready()) throw new RuntimeException('Connect the sender and enable notifications first.');
    $address = et_email_address(et_email_setting($db,'admin_email_address_'.$userId) ?? '');
    if ($address === '') throw new RuntimeException('Save your recipient email first.');
    et_email_save($db,'admin_email_outbox_'.bin2hex(random_bytes(16)),json_encode(['status'=>'pending','event'=>'test','to'=>$address,'subject'=>'ElimuTaifa: email test','body'=>'This is a test of ElimuTaifa admin email notifications.','created_at'=>et_utc_now(),'attempts'=>0],JSON_THROW_ON_ERROR));
}
/** Worker-only transport. Atomic claiming stops two workers sending the same item. */
function et_email_process(PDO $db, ?callable $send = null): array {
    if (et_email_setting($db,'admin_email_enabled') !== '1') return ['sent'=>0,'failed'=>0];
    if (!$send && !et_email_ready()) throw new RuntimeException('Configure the sender before running the email worker.');
    $send ??= 'et_email_smtp_send'; $counts = ['sent'=>0,'failed'=>0];
    $cutoff = gmdate('Y-m-d H:i:s',time()-14*86400);
    $db->prepare("DELETE FROM app_settings WHERE setting_key LIKE 'admin_email_outbox_%' AND updated_at < ? AND (setting_value LIKE '{\"status\":\"sent\",%' OR setting_value LIKE '{\"status\":\"failed\",%' OR setting_value LIKE '{\"status\":\"cancelled\",%')")->execute([$cutoff]);
    $rows = $db->query("SELECT setting_key,setting_value FROM app_settings WHERE setting_key LIKE 'admin_email_outbox_%' AND setting_value LIKE '{\"status\":\"pending\",%' ORDER BY updated_at,setting_key LIMIT 20")->fetchAll();
    $processed = 0;
    foreach ($rows as $row) {
        if (et_email_setting($db,'admin_email_enabled') !== '1') break;
        $item = json_decode($row['setting_value'],true,512,JSON_THROW_ON_ERROR);
        if ($item['status'] !== 'pending' || ++$processed > 20) continue;
        $item['status'] = 'processing'; $item['attempts']++; $item['last_attempt_at'] = et_utc_now();
        $claimed = json_encode($item,JSON_THROW_ON_ERROR);
        $q = $db->prepare('UPDATE app_settings SET setting_value=?,updated_at=? WHERE setting_key=? AND setting_value=?');
        $q->execute([$claimed,et_utc_now(),$row['setting_key'],$row['setting_value']]);
        if ($q->rowCount() !== 1) continue;
        // Re-check recipients after account access or address changes.
        $active = [];
        foreach ($db->query('SELECT id FROM admin_users WHERE is_active=1 AND deleted_at IS NULL')->fetchAll() as $user) {
            $active[] = strtolower(et_email_setting($db,'admin_email_address_' . $user['id']) ?? '');
        }
        if (!in_array(strtolower($item['to']),$active,true)) { $item['status']='cancelled'; }
        else {
            try { $send($item); $item['status']='sent'; $item['sent_at']=et_utc_now(); $counts['sent']++; }
            catch (Throwable) { $item['status']='failed'; $item['error']='Delivery failed. Check email settings and provider status.'; $counts['failed']++; }
        }
        et_email_save($db,$row['setting_key'],json_encode($item,JSON_THROW_ON_ERROR));
    }
    return $counts;
}
