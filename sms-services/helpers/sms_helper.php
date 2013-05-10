<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');    
//Save SMS to mt table
function save_sms($content = NULL, $from = NULL, $to = NULL, $smsc_id = NULL, $mo_id = NULL)
{
    
}

//Send SMS to Gateway, do wap-push if $link is available
function send_sms($content = NULL, $from = NULL, $to = NULL, $smsc_id = NULL, $mo_id = NULL)
{        
    $CI =& get_instance();
    
    if (is_null($content))
    {            
        return FALSE;
    }
    
    $CI->MT->content = $content;
    if($from)
    {
        $CI->MT->short_code = $from;
    }
    if($to)
    {
        $CI->MT->msisdn = $to;
    }
    if($smsc_id)
    {
        $CI->MT->smsc_id = $smsc_id;
    }
    
    $CI->MT->send();
    
}

/* End of file */