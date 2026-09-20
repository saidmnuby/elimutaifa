<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/includes/selection_navigation.php';
$_SERVER['REQUEST_METHOD']='GET';
$_GET=['action'=>'browse','cycle'=>'2026-first','region'=>'arusha','council'=>'arusha cc','school'=>'S3884'];
if(et_selection_request('form-five')!==$_GET) throw new RuntimeException('Browse state lost');
$_GET=['action'=>'candidate','candidate'=>'PS2402026-0126'];
if(et_selection_request('form-one',true)['action']!=='invalid') throw new RuntimeException('Student URL accepted');
$_GET=['action'=>['browse'],'region'=>['arusha']];
if(et_selection_request('form-five')['region']!=='') throw new RuntimeException('Non-scalar input accepted');
echo "PASS: GET browsing state, private candidate guard and scalar inputs.\n";
