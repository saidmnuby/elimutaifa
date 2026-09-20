<?php
declare(strict_types=1);
require_once __DIR__.'/monitoring.php';

function et_selection_placement_page(string $module,string $stage,bool $failed): string {
    if(!in_array($module,['form-one','form-five'],true)) throw new InvalidArgumentException('Invalid module');
    return $module.($failed?'-error':($stage==='records'?'-results':($stage!==''?'-schools':'')));
}
function et_selection_report_failure(string $module,string $code,string $source=''): void {
    if(in_array($code,['NO_CYCLES','INVALID_CANDIDATE','INVALID_SELECTION','SEARCH_EXPIRED','RATE_LIMIT'],true)) return;
    $known=in_array($code,['SOURCE_FORMAT','SOURCE_UNAVAILABLE','DIRECTORY_LOOKUP'],true);
    et_record_system_event('selection_processing_error','Selection processing could not complete.','error',[
        'exam_type'=>$module==='form-five'?'FORM FIVE':'FORM ONE','target_url'=>$source,
        'error_code'=>$known?$code:'PROCESSING_FAILURE','http_status'=>503
    ]);
}
