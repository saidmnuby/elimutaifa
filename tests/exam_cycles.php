<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/exam_cycles.php';
require_once dirname(__DIR__) . '/includes/exam_landing.php';
function exam_check(bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); }
function exam_reject(callable $operation, string $message): void {
    try { $operation(); } catch (Throwable) { return; }
    throw new RuntimeException($message);
}
$db = et_open_database(':memory:');
$db->exec("INSERT INTO admin_users(id,username,display_name,password_hash,role,is_active,created_at,updated_at) VALUES(1,'owner','Owner','fixture','owner',1,'2026-01-01','2026-01-01')");
$cycle = et_exam_legacy_cycle('csee', 2023);
exam_check(et_exam_validate($cycle) === [], 'Legacy source validation');
exam_check(et_exam_url($cycle,'school','S0101') === 'https://maktaba.tetea.org/exam-results/CSEE2023/s0101.htm', 'Shared 2023 archive school URL');
foreach (['https://evil.test/exam-results/CSEE2023/','http://maktaba.tetea.org/exam-results/CSEE2023/','https://maktaba.tetea.org/exam-results/CSEE2022/','https://user@maktaba.tetea.org/exam-results/CSEE2023/','https://maktaba.tetea.org:443/exam-results/CSEE2023/'] as $base) {
    exam_check(et_exam_validate(array_replace($cycle,['base_url'=>$base])) !== [], 'Unsafe or incorrect URL accepted');
}
foreach (['../{school}.htm','/{school}.htm','{school}.htm?x=1','https://evil.test/{school}.htm','{school}/{school}.htm'] as $path) exam_check(et_exam_validate(array_replace($cycle,['school_path'=>$path])) !== [], 'Unsafe template accepted');
$draft = et_exam_prepare_change(null, $cycle);
exam_check($draft['status'] === 'draft' && !$draft['inherited'], 'New cycle must be Draft');
et_exam_store($db, $draft, null, 1, 'exam_cycle_saved');
exam_check(et_exam_cycles('csee',$db) === [], 'Draft leaked to public');
exam_reject(fn()=>et_exam_cycle('csee',2023,$db),'Unavailable cycle accepted');
$raw = $db->query("SELECT setting_value FROM app_settings WHERE setting_key='exam_cycle_csee_2023'")->fetchColumn();
exam_reject(fn()=>et_exam_store($db,array_replace($draft,['status'=>'published']),$raw,1,'exam_cycle_saved'),'Unverified publication accepted');
$draft['sample_school'] = 'S0101';
$fetch = static fn(string $url): array => ['status'=>200,'html'=>str_ends_with($url,'index.htm')
    ? '<h1>CSEE 2023</h1><a href="s0101.htm">School</a>'
    : '<h1>CSEE 2023</h1><table><tr><td>S0101/0001</td><td>M</td><td>I</td><td>MATH - A</td></tr></table>'];
$report = et_exam_verify($draft,$fetch);
exam_check(str_contains($report,'passed'), 'Live fixture rejected');
exam_check(!et_exam_link_matches('../CSEE2022/s0101.htm',et_exam_url($draft,'directory'),et_exam_url($draft,'school','S0101')), 'Wrong-year school link accepted');
exam_check(!et_exam_link_matches('//evil.test/s0101.htm',et_exam_url($draft,'directory'),et_exam_url($draft,'school','S0101')), 'External relative school link accepted');
exam_reject(fn()=>et_exam_verify($draft,static fn($u)=>['status'=>200,'html'=>'<h1>CSEE 2022</h1>']), 'Wrong examination year accepted');
exam_reject(fn()=>et_exam_verify($draft,static fn($u)=>['status'=>200,'html'=>'<h1>CSEE 2023</h1>']), 'Missing directory/candidate rows accepted');
exam_reject(fn()=>et_exam_verify($draft,static fn($u)=>['status'=>503,'html'=>false]), 'Source outage accepted');
exam_reject(fn()=>et_exam_verify($draft,static fn($u)=>$fetch($u)+['cache_state'=>'stale_fallback']), 'Stale response accepted');
$published = array_replace($draft,['status'=>'published','verified_at'=>et_utc_now(),'verification_report'=>$report]);
et_exam_store($db,$published,$raw,1,'exam_cycle_saved');
exam_check(count(et_exam_cycles('csee',$db)) === 1,'Published cycle unavailable');
exam_reject(fn()=>et_exam_store($db,$published,$raw,1,'exam_cycle_saved'),'Stale owner edit overwrote newer data');
$changed = et_exam_prepare_change($published,array_replace($published,['base_url'=>'https://onlinesys.necta.go.tz/results/2023/csee/','school_path'=>'results/{school}.htm']));
exam_check($changed['status']==='draft' && $changed['verified_at']===null && !$changed['inherited'],'Source change retained publication/verification');
exam_check(et_exam_cache_revision($published)!==et_exam_cache_revision($changed),'Cache revision did not change');
$raw = $db->query("SELECT setting_value FROM app_settings WHERE setting_key='exam_cycle_csee_2023'")->fetchColumn();
et_exam_store($db,array_replace($published,['status'=>'suspended']),$raw,1,'exam_cycle_saved');
exam_check(et_exam_cycles('csee',$db)===[],'Suspended cycle leaked');
ob_start(); et_exam_landing('csee',dirname(__DIR__).'/results/csee/index.html',$db); $html=ob_get_clean();
$dom=new DOMDocument(); libxml_use_internal_errors(true); $dom->loadHTML($html); libxml_clear_errors(); $xpath=new DOMXPath($dom);
exam_check($xpath->query('//select[@name="examYear" or @name="year"]//option[@value!=""]')->length===0,'Unavailable years remained in public selectors');
foreach(['psle','sfna'] as $level) {
    $primary=et_exam_legacy_cycle($level,2023);
    exam_check(str_contains(et_exam_url($primary,'directory','1101'),'1101.htm'),'Primary directory routing');
    exam_check(str_contains(et_exam_url($primary,'school','PS0101010'),'ps0101010.htm'),'Primary school routing');
}
exam_check(str_contains(et_exam_url(et_exam_legacy_cycle('psle',2015),'school','PS0101010'),'shl_p0101010.htm'),'Legacy PSLE filename');
exam_check((int)$db->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn()===3,'Audit history missing or failed save audited');
exam_check((int)$db->query("SELECT COUNT(*) FROM app_settings WHERE setting_key LIKE 'exam_history_%'")->fetchColumn()===2,'Previous source configurations missing');
echo "PASS: exam cycle publication, suspension, safe URLs, live verification, year choices, source changes, cache revisions, concurrency and audit history.\n";
