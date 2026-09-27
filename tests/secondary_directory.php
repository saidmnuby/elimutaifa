<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/includes/secondary_directory.php';
function school_check(bool $pass,string $message): void { if (!$pass) throw new RuntimeException($message); }
$base=et_secondary_base('csee',2025);
$html='<a href="results/s0101.htm">AZANIA</a><a href="https://evil.test/results/s0102.htm">Bad</a><a href="../results/s0103.htm">Wrong year</a><a href="results/s0101.htm">AZANIA</a>';
$schools=et_secondary_schools($html,$base);
school_check(count($schools)===1 && isset($schools['S0101']),'Safe unique school links');
school_check(str_contains(et_secondary_base('acsee',2026),'matokeo.necta.go.tz'),'ACSEE host');
school_check(str_contains(et_secondary_base('ftna',2022),'FTNA2022'),'Archive mapping');
school_check(et_secondary_base('csee',2023)==='https://maktaba.tetea.org/exam-results/CSEE2023/','2023 archive mapping');
school_check(et_secondary_directory_source('ftna',2023)==='https://maktaba.tetea.org/exam-results/FTNA2023/ftna.htm','2023 FTNA archive directory');
school_check(et_secondary_directory_source('ftna',2022)==='https://maktaba.tetea.org/exam-results/FTNA2022/ftna.htm','Historical FTNA archive directory');
foreach ([['unknown',2025],['csee',2099]] as [$level,$year]) {
    try { et_secondary_base($level,$year); throw new RuntimeException('Invalid input accepted'); }
    catch (InvalidArgumentException $expected) {}
}
$row='<table><tr><td>S0101/0001</td><td>M</td><td>12</td><td>I</td><td>MATH - A</td></tr></table>';
school_check(count(et_secondary_rows($row,'S0101'))===1,'Candidate row');
school_check(et_secondary_rows($row,'S0102')===[],'Wrong school excluded');
$ftna='<table><tr><td>S0101/0001</td><td>Student</td><td>F</td><td>12</td><td>I</td><td>MATH - A</td></table>';
$rows=et_secondary_rows($ftna,'S0101','ftna');
school_check(count($rows)===1 && count($rows[0])===5 && $rows[0][1]==='F','FTNA columns');
$ftna2023='<table><tr><td>S0101/0001</td><td>20153935386</td><td>Student</td><td>M</td><td>21</td><td>II</td><td>CIV - C</td></tr></table>';
$rows=et_secondary_rows($ftna2023,'S0101','ftna');
school_check(count($rows)===1 && $rows[0]===['S0101/0001','M','21','II','CIV - C'],'FTNA 2023 archive columns');
$ftna2023Malformed='<table><tr><td>S0101/0001</td><td>20153935386</td><td>Student</td><td>M</td><td>21</td><td>II</td><td>CIV - C</td></tr><td>S0101/0002</td><td>20193338315</td><td>Student Two</td><td>F</td><td>12</td><td>I</td><td>MATH - A</td></table>';
$rows=et_secondary_rows($ftna2023Malformed,'S0101','ftna');
school_check(count($rows)===2 && $rows[1]===['S0101/0002','F','12','I','MATH - A'],'Malformed FTNA archive rows');
echo "PASS: secondary school directory, URL boundaries, years and candidate parsing\n";
