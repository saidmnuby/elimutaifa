<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/includes/selection_activity.php';
function activity_check(bool $ok,string $message): void { if(!$ok) throw new RuntimeException($message); }
foreach(['form-one','form-five'] as $module) {
    activity_check(et_selection_placement_page($module,'',false)===$module,'Landing placement');
    activity_check(et_selection_placement_page($module,'school',false)===$module.'-schools','Browsing placement');
    activity_check(et_selection_placement_page($module,'records',false)===$module.'-results','Results placement');
    activity_check(et_selection_placement_page($module,'records',true)===$module.'-error','Error placement');
    activity_check(et_detect_exam_type('/'.$module.'/')===($module==='form-one'?'FORM ONE':'FORM FIVE'),'Monitoring label');
    $html=file_get_contents(dirname(__DIR__).'/selection/'.$module.'/index.php');
    activity_check(substr_count($html,'data-et-placement-slot="')===2,'Top/bottom slots');
    activity_check(str_contains($html,'../assets/js/monitoring.js'),'Traffic script');
}
activity_check(et_normalize_public_path('/form-one/?search=abcdef&candidate=PS2402026-0126')==='/form-one/','Traffic omits query data');
echo "PASS: selection placements, monitoring labels, scripts and traffic privacy.\n";
