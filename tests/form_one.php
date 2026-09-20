<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/includes/form_one.php';
function fo_check(bool $condition,string $message): void { if(!$condition) throw new RuntimeException($message); }
$base='https://selection.tamisemi.go.tz/allocations/2025/first-selection/index.html';
$regions=fo_links('<a href="geita/index.html">GEITA</a><a href="placements/Index.html">All schools</a><a href="https://evil.test/index.html">Bad</a>',$base);
fo_check(count($regions)===1 && $regions[0]['id']==='geita','Region parsing/URL allowlist');
$councilUrl='https://selection.tamisemi.go.tz/allocations/2025/first-selection/geita/chato%20dc/index.html';
$schools=fo_links('<a href="PS2402026/index.html">PS2402026 - CHATO</a>',$councilUrl,true);
fo_check($schools[0]['id']==='PS2402026','School parsing');
[$region,$council]=fo_candidate_location('PS2402026-0126');
fo_check($region==='geita' && $council==='Chato','Candidate district lookup');
fo_check(fo_normalize('Dar es Salaam City')===fo_normalize('DAR ES SALAAM CC'),'Council alias');
fo_check(fo_normalize('Geita Town')!==fo_normalize('Geita'),'Council ambiguity');
fo_check(fo_url('https://selection.tamisemi.go.tz.evil.test/file.pdf',$base)===null,'Malicious host');
fo_check(fo_url('https://selection.tamisemi.go.tz/allocations/2025/first-selection/%2e%2e/private',$base)===null,'Encoded traversal');
$html='<table><tr><td>1</td><td>PS2402026-0126</td><td>KE</td><td><a href="../../../../jis/GEITA GIRLS.pdf">GEITA GIRLS</a></td><td>Bweni Kitaifa</td><td>GEITA TC</td></tr><tr><td>2</td><td>PS9999999-0126</td><td>KE</td><td>OTHER</td><td>Kutwa</td><td>DC</td></tr></table>';
$rows=fo_records($html,$schools[0]['url'],'PS2402026');
fo_check(count($rows)===1 && $rows[0]['destination']==='GEITA GIRLS' && $rows[0]['joining_url']!==null,'Exact school parsing / joining link');
foreach ([['status'=>404,'html'=>'missing'],['status'=>200,'html'=>false]] as $response) {
    try { fo_fetch($base,static fn()=> $response); throw new LogicException('Failure accepted'); } catch(RuntimeException $error) { fo_check($error->getMessage()==='SOURCE_UNAVAILABLE','Failure classification'); }
}
echo "PASS: Form One parsers, candidate mapping, aliases, link safety and source failures.\n";
if (in_array('--live',$argv,true)) {
    $response=fo_fetch($base); $regions=fo_links($response['html'],$base); $region=fo_entry($regions,'geita');
    $response=fo_fetch($region['url']); $councils=fo_links($response['html'],$region['url']); $council=fo_entry($councils,'chato dc');
    $response=fo_fetch($council['url']); $schools=fo_links($response['html'],$council['url'],true); $school=fo_entry($schools,'PS2402026');
    $response=fo_fetch($school['url']); $rows=fo_records($response['html'],$school['url'],$school['id']);
    fo_check(count(array_filter($rows,static fn($row)=>$row['candidate']==='PS2402026-0126'))===1,'Live exact candidate');
    echo 'PASS: Live hierarchy; '.count($regions).' regions, '.count($schools).' council schools, '.count($rows)." school selections.\n";
}
