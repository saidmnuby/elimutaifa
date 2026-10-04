<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/exam_monitoring.php';
function monitor_check(bool $ok,string $message): void { if (!$ok) throw new RuntimeException($message); }
$cycle = et_exam_provider_cycle('csee',2025,'necta');
$cycle['sample_school'] = 'P0104';
$calls = [];
$fetch = static function(string $url) use (&$calls): array {
    $calls[] = $url;
    return ['status'=>200,'html'=>str_ends_with($url,'index.htm')
        ? '<h1>CSEE 2025</h1><a href="https://evil.test/s0001.htm">Bad</a><a href="results\\s0101.htm">School</a>'
        : '<h1>CSEE 2025</h1><table><tr><td>S0101/0001</td><td>M</td><td>I</td><td>MATH - A</td></tr></table>'];
};
$result = et_exam_auto_check($cycle,$fetch);
monitor_check($result['sample_school']==='S0101','Automatic sample still depends on manually entered school');
monitor_check(count($calls)===2,'Verification duplicated directory/sample requests');
monitor_check(!str_contains(implode(' ',$calls),'evil.test'),'Discovery fetched an unapproved source');
$checked = et_exam_monitor_check($cycle,$cycle,1,$fetch);
monitor_check($checked['last_check_ok'] && !$checked['inherited'],'Successful automatic check did not clear inherited review');
monitor_check(et_exam_health($checked)['key']==='ready','Draft not marked ready');
$published = array_replace($checked,['status'=>'published']);
monitor_check(et_exam_health($published)['key']==='healthy','Published verified cycle not healthy');
$outage = et_exam_monitor_check($published,et_exam_provider_cycle('csee',2025,'tetea'),1,static fn($u)=>['status'=>0,'html'=>false]);
monitor_check($outage['status']==='published' && $outage['base_url']===$published['base_url'],'Failed provider check removed working public mapping');
monitor_check($outage['verified_at']===$published['verified_at'] && !$outage['last_check_ok'],'Failure lost verification history or showed success');
monitor_check(et_exam_health($outage)['key']==='issue','Outage not highlighted');
monitor_check(et_exam_health(array_replace($published,['last_check_at'=>'2020-01-01 00:00:00']))['label']==='Check due','Old checks shown as fresh');
monitor_check(et_exam_health($published,3)['key']==='issue','Repeated source failures ignored');
$primary = et_exam_provider_cycle('psle',2025,'necta'); $primary['sample_district'] = '1101';
$primaryFetch = static fn(string $url): array => ['status'=>200,'html'=>str_contains($url,'distr_')
    ? '<h1>PSLE 2025</h1><a href="shl_ps1101001.htm">Primary school</a>'
    : '<h1>PSLE 2025</h1><table><tr><td>PS1101001-001</td><td>123</td><td>F</td><td>MATH - A</td></tr></table>'];
$result = et_exam_auto_check($primary,$primaryFetch);
monitor_check($result['sample_school']==='PS1101001' && $result['sample_district']==='1101','Primary auto-discovery failed');
monitor_check($result['school_path']==='results/shl_{school}.htm','Primary school filename not inferred');
$attempts = 0;
foreach ([2020,2021,2022] as $sfnaYear) {
    $sfna = et_exam_provider_cycle('sfna',$sfnaYear,'tetea');
    $sfna['sample_district'] = '0102';
    $sfnaFetch = static fn(string $url): array => ['status'=>200,'html'=>str_contains($url,'distr_')
        ? '<h1>SFNA ' . $sfnaYear . '</h1><a href="ps0102047.htm">School</a>'
        : '<h1>SFNA ' . $sfnaYear . '</h1><table><tr><td>PS0102047-001</td><td>M</td><td>Sample</td><td>Kiswahili - A Hisabati - B</td>' . ($sfnaYear <= 2021 ? '<td>A</td>' : '') . '</tr></table>'];
    monitor_check(et_exam_auto_check($sfna,$sfnaFetch)['sample_school']==='PS0102047','SFNA legacy/current subject column rejected');
    $sfnaEmpty = et_exam_monitor_check(null,$sfna,1,static fn($url)=>array_replace($sfnaFetch($url),['html'=>str_replace('Kiswahili - A Hisabati - B','Pending',$sfnaFetch($url)['html'])]));
    monitor_check(!$sfnaEmpty['last_check_ok'],'SFNA average-only row verified without subjects');
}
$ftna = et_exam_provider_cycle('ftna',2023,'tetea');
$ftnaFetch = static fn(string $url): array => ['status'=>200,'html'=>str_ends_with($url,'ftna.htm')
    ? '<h1>FTNA 2023</h1><a href="s0101.htm">School</a>'
    : '<h1>FTNA 2023</h1><table><tr><td>CNO</td></tr><td>S0101/0001</td><td>123456</td><td>Sample</td><td>M</td><td>21</td><td>II</td><td>CIV-\'C\' MATH-\'A\'</td></table>'];
monitor_check(et_exam_auto_check($ftna,$ftnaFetch)['sample_school']==='S0101','Malformed FTNA candidate cells rejected');
$wrongSchool = et_exam_monitor_check(null,$ftna,1,static fn($url)=>array_replace($ftnaFetch($url),['html'=>str_replace('S0101/0001','S0102/0001',$ftnaFetch($url)['html'])]));
monitor_check(!$wrongSchool['last_check_ok'],'Fallback accepted a different school candidate');
$noGrades = et_exam_monitor_check(null,$ftna,1,static fn($url)=>array_replace($ftnaFetch($url),['html'=>str_replace("CIV-'C' MATH-'A'",'Results pending',$ftnaFetch($url)['html'])]));
monitor_check(!$noGrades['last_check_ok'],'Fallback accepted candidate without subject grades');
$missing = et_exam_monitor_check(null,$cycle,1,static function($u) use (&$attempts): array { $attempts++; return ['status'=>404,'html'=>false]; });
monitor_check(!$missing['last_check_ok'] && $missing['status']==='draft' && $attempts<=8,'Missing result created public cycle or exceeded request budget');
monitor_check(et_exam_discovery_link('../../2024/csee/index.htm',$cycle['base_url'].'index.htm',$cycle['base_url'])===null,'Discovery crossed examination/year boundary');
monitor_check(et_exam_discovery_link('\\\\evil.test/s0101.htm',$cycle['base_url'].'index.htm',$cycle['base_url'])===null,'Backslash network path escaped approved provider');
echo "PASS: automatic school and district discovery, bounded live checks, provider safety, publication readiness, health states and availability during outages.\n";
