<?php
declare(strict_types=1);
$html=file_get_contents(dirname(__DIR__).'/index.html');
$dom=new DOMDocument();libxml_use_internal_errors(true);$dom->loadHTML('<?xml encoding="UTF-8">'.$html);libxml_clear_errors();
$xpath=new DOMXPath($dom);
foreach(['exam-levels','selections','announcements'] as $id) {
    if($xpath->query('//*[@id="'.$id.'"]')->length!==1) throw new RuntimeException('Missing/duplicate section '.$id);
}
foreach($xpath->query('//a[contains(concat(" ",normalize-space(@class)," ")," home-scroll-link ")]') as $link) {
    $target=ltrim($link->getAttribute('href'),'#');
    if($xpath->query('//*[@id="'.$target.'"]')->length!==1) throw new RuntimeException('Broken scroll target');
}
foreach(['selection/form-one/','selection/form-five/'] as $href) if($xpath->query('//*[@id="selections"]//a[@href="'.$href.'"]')->length!==1) throw new RuntimeException('Missing selection card');
if(str_contains($html,'Admission 2026') || str_contains($html,'Form Four 2026')) throw new RuntimeException('Misleading availability label');
echo "PASS: home selection cards, scroll targets and availability labels.\n";
