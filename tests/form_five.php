<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/includes/form_five.php';
function f5_check(bool $ok,string $label): void { if(!$ok) throw new RuntimeException($label); }
$base='https://selform.tamisemi.go.tz/content/selection-and-allocation/2026/first-selection/index.html';
$regions=f5_links('<a href="arusha/index.html">ARUSHA</a><a href="placements/Index.html">Destinations</a><a href="https://evil.test/index.html">Bad</a>',$base);
f5_check(count($regions)===1,'Region isolation');
$council=dirname($base).'/arusha/arusha%20cc/index.html';
$schools=f5_links('<a href="S3884/index.html">S3884 OLOIRIEN</a><a href="P0781/index.html">P0781 CENTRE</a>',$council,true);
f5_check(count($schools)===2,'S and P school codes');
$html='<table id="students-table"><tbody><tr><td>1</td><td>S3884.0001.2025</td><td><a href="../../../../jis/S7049.pdf">SINYA</a></td><td>HGK</td><td>Boarding School</td><td>LONGIDO DC</td><td></td></tr><tr><td>2</td><td>S3884.0006.2025</td><td><a href="https://www.nacte.go.tz/index.php/admission">IAA</a></td><td>ACCOUNTANCY</td><td>College</td><td>ARUSHA DC</td><td>Ada: 750,000</td></tr><tr><td>3</td><td>S9999.0001.2025</td><td>OTHER</td><td>PCM</td><td>Day</td><td>DC</td><td></td></tr></tbody></table>';
$rows=f5_records($html,$schools[0]['url'],'S3884',2025);
f5_check(count($rows)===2 && $rows[0]['course']==='HGK' && $rows[0]['joining_url']!==null,'School records and PDF');
f5_check($rows[1]['joining_url']===null && $rows[1]['info']==='Ada: 750,000','College link is not joining PDF');
foreach(['https://evil.test/index.html','https://selform.tamisemi.go.tz.evil.test/index.html','https://selform.tamisemi.go.tz/content/selection-and-allocation/2026/first-selection/%2e%2e/index.html'] as $bad) f5_check(f5_url($bad,$base)===null,'Unsafe URL');
try { f5_records($html,$schools[0]['url'],'S3884',2024);throw new LogicException('Wrong year accepted'); } catch(RuntimeException $e) { f5_check($e->getMessage()==='SOURCE_FORMAT','Exact year'); }
f5_check(grf_fetch_result('https://selform.tamisemi.go.tz/Account/Login')['status']===400,'Fetcher path guard');
echo "PASS: Form Five hierarchy, school codes, exact school/year, college metadata and safe joining links.\n";
if(in_array('--live',$argv,true)) {
    $r=f5_fetch($base);$regions=f5_links($r['html'],$base);$region=f5_entry($regions,'arusha');
    $r=f5_fetch($region['url']);$councils=f5_links($r['html'],$region['url']);$council=f5_entry($councils,'arusha cc');
    $r=f5_fetch($council['url']);$schools=f5_links($r['html'],$council['url'],true);$school=f5_entry($schools,'S3884');
    $r=f5_fetch($school['url']);$records=f5_records($r['html'],$school['url'],'S3884',2025);
    f5_check(count($regions)===26 && count($records)>0,'Live hierarchy');
    echo 'PASS: live '.count($regions).' regions, '.count($schools).' council schools/centres, '.count($records)." school selections.\n";
}
