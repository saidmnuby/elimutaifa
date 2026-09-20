<?php
declare(strict_types=1);
require_once __DIR__.'/session.php';

/** Read-only browsing uses GET; candidate POST redirects to a session-bound opaque key. */
function et_selection_request(string $module,bool $allowCandidate=false): array
{
    $fields=['action','cycle','region','council','school'];
    $scalar=static fn(array $input,string $key): string => is_string($input[$key]??null)?$input[$key]:'';
    if(($_SERVER['REQUEST_METHOD']??'GET')==='POST') {
        $data=[];foreach($fields as $field) $data[$field]=$scalar($_POST,$field);
        if($allowCandidate && $data['action']==='candidate') {
            grf_start_session();
            $history=$_SESSION['selection_searches'][$module]??[];
            $history=array_filter($history,static fn($item)=>($item['expires']??0)>time());
            $token=bin2hex(random_bytes(16));
            $data['candidate']=$scalar($_POST,'candidate');
            $history[$token]=['expires'=>time()+1800,'request'=>$data];
            $_SESSION['selection_searches'][$module]=array_slice($history,-5,null,true);
            session_write_close();
            header('Location: ./?search='.$token,true,303);exit;
        }
        header('Location: ./?'.http_build_query(array_filter($data,static fn($value)=>$value!==''),'','&',PHP_QUERY_RFC3986),true,303);exit;
    }
    if($allowCandidate && isset($_GET['search'])) {
        grf_start_session();
        $token=$scalar($_GET,'search');
        $saved=preg_match('/^[a-f0-9]{32}$/D',$token)?($_SESSION['selection_searches'][$module][$token]??null):null;
        session_write_close();
        return $saved && $saved['expires']>time()?$saved['request']:['action'=>'expired'];
    }
    $data=[];foreach($fields as $field) $data[$field]=$scalar($_GET,$field);
    if($data['action']==='candidate') $data['action']='invalid'; // never accept student IDs in a URL
    return $data;
}
