<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/includes/form_five.php';
function f5c_check(bool $ok,string $label): void { if(!$ok) throw new RuntimeException($label); }
$source='https://selform.tamisemi.go.tz/content/selection-and-allocation/2026/first-selection/index.html';
$data=['cycle_key'=>'2026-first','intake_year'=>2026,'exam_year'=>2025,'round_label'=>'First selection','source_mode'=>'auto','source_url'=>'','status'=>'draft'];
[$valid,$errors]=f5_validate_cycle($data);
f5c_check($errors===[] && $valid['source_url']===$source,'Automatic uses intake year');
f5c_check(!f5_source_valid('https://selection.tamisemi.go.tz/allocations/2025/first-selection/index.html'),'Module host isolation');
f5c_check(count(f5_validate_cycle(array_replace($data,['source_mode'=>'manual','source_url'=>'https://evil.test/index.html']))[1])>0,'Unsafe manual source');
$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec('CREATE TABLE app_settings(setting_key TEXT PRIMARY KEY,setting_value TEXT); CREATE TABLE form_five_cycles(id INTEGER PRIMARY KEY,cycle_key TEXT,intake_year INTEGER,exam_year INTEGER,round_label TEXT,source_url TEXT,status TEXT,verified_at TEXT)');
$q=$db->prepare('INSERT INTO form_five_cycles VALUES(?,?,?,?,?,?,?,?)');
foreach([[1,'2026-first',2026,2025,'First selection',$source,'published','2026-09-16'],[2,'2027-first',2027,2026,'First selection',str_replace('/2026/','/2027/',$source),'published','2026-09-16'],[3,'draft',2028,2027,'First selection',$source,'draft',null],[4,'unverified',2029,2028,'First selection',$source,'published',null]] as $row) $q->execute($row);
$db->exec("INSERT INTO app_settings VALUES('form_one_default_cycle','other'); INSERT INTO app_settings VALUES('form_five_default_cycle','2026-first')");
f5c_check(array_keys(f5_cycles($db))===['2026-first','2027-first'],'Default and publication gates');
$db->exec("UPDATE form_five_cycles SET status='archived' WHERE id=1");
f5c_check(array_keys(f5_cycles($db))===['2027-first'],'Archived default fallback');
$region=dirname($source).'/arusha/index.html';$council=dirname($region).'/arusha%20cc/index.html';$school=dirname($council).'/S3884/index.html';
$pages=[$source=>'<h2>KIDATO CHA TANO NA VYUO VYA KATI, 2026</h2><a href="arusha/index.html">ARUSHA</a>',$region=>'<a href="arusha cc/index.html">ARUSHA CC</a>',$council=>'<a href="S3884/index.html">S3884 SCHOOL</a>',$school=>'<table id="students-table"><tbody><tr><td>1</td><td>S3884.0001.2025</td><td>SINYA</td><td>HGK</td><td>Boarding</td><td>LONGIDO</td><td></td></tr></tbody></table>'];
$fetch=static fn($url)=>['status'=>isset($pages[$url])?200:404,'html'=>$pages[$url]??false];
f5c_check(str_contains(f5_verify_cycle($valid,$fetch),'1 selections'),'Verification hierarchy');
try { f5_verify_cycle(array_replace($valid,['exam_year'=>2024]),$fetch);throw new LogicException('Wrong exam accepted'); } catch(RuntimeException $e) { f5c_check($e->getMessage()==='SOURCE_FORMAT','Exam mismatch blocked'); }
echo "PASS: Form Five cycle URLs, host isolation, defaults, drafts, archive and verification.\n";
