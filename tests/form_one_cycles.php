<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/includes/form_one.php';
function cycle_check(bool $ok,string $label): void { if(!$ok) throw new RuntimeException($label); }
$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec('CREATE TABLE app_settings(setting_key TEXT PRIMARY KEY,setting_value TEXT); CREATE TABLE form_one_cycles(id INTEGER PRIMARY KEY,cycle_key TEXT,intake_year INTEGER,exam_year INTEGER,round_label TEXT,source_url TEXT,status TEXT,verified_at TEXT)');
$source='https://selection.tamisemi.go.tz/allocations/2025/first-selection/index.html';
$data=['cycle_key'=>'2026-first','intake_year'=>2026,'exam_year'=>2025,'round_label'=>'First selection','source_url'=>$source,'status'=>'draft'];
cycle_check(fo_validate_cycle($data)[1]===[],'Valid draft');
cycle_check(fo_generated_source(2025,'First selection')===$source,'Generated source uses exam year');
cycle_check(fo_validate_cycle(array_replace($data,['source_mode'=>'auto','source_url'=>'wrong']))[0]['source_url']===$source,'Server generates automatic URL');
cycle_check(fo_generated_source('', 'First selection')==='','Incomplete year');
foreach(['http://selection.tamisemi.go.tz/allocations/2025/first-selection/index.html',$source.'?x=1','https://evil.test/index.html'] as $bad) cycle_check(!fo_source_valid($bad),'Unsafe source rejected');
cycle_check(count(fo_validate_cycle(array_replace($data,['exam_year'=>2027]))[1])>0,'Year validation');
$insert=$db->prepare('INSERT INTO form_one_cycles VALUES(?,?,?,?,?,?,?,?)');
foreach([[1,'2026-first',2026,2025,'First',$source,'published','2026-09-16'],[2,'2027-first',2027,2026,'First',str_replace('2025','2026',$source),'published','2026-09-16'],[3,'draft',2028,2027,'First',$source,'draft',null],[4,'unverified',2029,2028,'First',$source,'published',null]] as $row) $insert->execute($row);
$db->exec("INSERT INTO app_settings VALUES('form_one_default_cycle','2026-first')");
cycle_check(array_keys(fo_cycles($db))===['2026-first','2027-first'],'Default priority and publication gating');
$db->exec("UPDATE form_one_cycles SET status='archived' WHERE id=1");
cycle_check(array_keys(fo_cycles($db))===['2027-first'],'Archived default falls back');
cycle_check(!fo_cycle_changed($data,array_replace($data,['status'=>'published'])),'Status retains verification');
cycle_check(fo_cycle_changed($data,array_replace($data,['intake_year'=>2027])),'Metadata change requires verification');
$pages=[$source=>'<h1>KIDATO CHA KWANZA 2026</h1><a href="geita/index.html">GEITA</a>',dirname($source).'/geita/index.html'=>'<a href="chato/index.html">CHATO</a>',dirname($source).'/geita/chato/index.html'=>'<a href="PS2402026/index.html">PS2402026 CHATO</a>',dirname($source).'/geita/chato/PS2402026/index.html'=>'<table><tr><td>1</td><td>PS2402026-0126</td><td>KE</td><td>GEITA GIRLS</td><td>Bweni</td><td>GEITA TC</td></tr></table>'];
$fetch=static fn($url)=>['status'=>isset($pages[$url])?200:404,'html'=>$pages[$url]??false];
cycle_check(str_contains(fo_verify_cycle($data,$fetch),'1 selections'),'Hierarchy verification');
try { fo_verify_cycle(array_replace($data,['intake_year'=>2027]),$fetch);throw new LogicException('Mismatch accepted'); } catch(RuntimeException $e) { cycle_check($e->getMessage()==='INTAKE_YEAR_MISMATCH','Intake mismatch'); }
echo "PASS: cycle validation, default, publication gates, archive fallback and source verification.\n";
$layout=file_get_contents(dirname(__DIR__).'/admin/_layout.php');
$manager=file_get_contents(dirname(__DIR__).'/admin/form-one/index.php');
cycle_check(!str_contains($layout,"'form-one-cycles' =>"),'No extra sidebar entry');
cycle_check(str_contains($manager,'sources.php?section=<?= et_e($cycleModule) ?>&amp;id='),'Cycle edit preserves section');
cycle_check(str_contains($manager,"et_require_admin_at_root()"),'Embedded authentication redirects at admin root');
cycle_check(str_contains($manager,"et_require_owner(\$user)"),'Owner write protection');
echo "PASS: integrated navigation and owner guard regression checks.\n";
