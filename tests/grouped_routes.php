<?php
declare(strict_types=1);
$root = dirname(__DIR__);
$checked = 0;
foreach (['results','selection'] as $group) {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/'.$group, FilesystemIterator::SKIP_DOTS));
    foreach ($files as $file) {
        if (!in_array($file->getExtension(), ['php','html','css'], true)) continue;
        $source = file_get_contents($file->getPathname());
        preg_match_all('~(?:href|src|action)=["\']([^"\']+)["\']|url\(["\']?([^"\')]+)~i', $source, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $url = $match[1] !== '' ? $match[1] : ($match[2] ?? '');
            if ($url === '' || preg_match('~^(?:[a-z]+:|//|/|#|\?)~i', $url) || str_contains($url,'<') || str_contains($url,'$')) continue;
            $path = parse_url(html_entity_decode($url), PHP_URL_PATH);
            if (!$path) continue;
            if (!file_exists($file->getPath().'/'.rawurldecode($path))) throw new RuntimeException('Broken local URL: '.$file->getPathname().' -> '.$url);
            $checked++;
        }
    }
}
echo "PASS: $checked grouped-module local asset, form and navigation references.\n";
if (!in_array('--http', $argv, true)) exit;
$base = 'http://localhost/get-results-faster/';
function route_request(string $url, ?string $post = null): array {
    $ch=curl_init($url); curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_HEADER=>true,CURLOPT_TIMEOUT=>10,CURLOPT_FOLLOWLOCATION=>false]);
    if ($post!==null) curl_setopt($ch,CURLOPT_POSTFIELDS,$post);
    $response=curl_exec($ch); $status=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE); curl_close($ch);
    return [$status,(string)$response];
}
foreach (['acsee','csee','ftna','psle','sfna','form-one','form-five'] as $module) {
    $group=str_starts_with($module,'form-')?'selection':'results';
    [$status,$headers]=route_request($base.$module.'/?example=1');
    if($status!==307 || !str_contains($headers,'Location: '.$base.$group.'/'.$module.'/?example=1')) throw new RuntimeException('Legacy redirect: '.$module);
    [$status,$html]=route_request($base.$group.'/'.$module.'/');
    if($status!==200 || !str_contains($html,'ElimuTaifa')) throw new RuntimeException('Grouped landing: '.$module);
}
[$status,$headers]=route_request($base.'form-one/','action=browse');
if($status!==307) throw new RuntimeException('Legacy POST must preserve its method');
[$status,$headers]=route_request($base.'selection/form-one/','action=browse');
if($status!==303 || !str_contains($headers,'Location: ./?action=browse')) throw new RuntimeException('Selection POST/redirect/GET');
