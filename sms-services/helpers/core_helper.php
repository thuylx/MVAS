<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Get MO auto correction rules from database (table m_mo_auto_corect_rule)
 * which belong to given code.
 * */
function get_mo_correction_rules($code)
{
    $CI =& get_instance();
    return $CI->MO_model->get_auto_correct_rules($code);    
}

/**
 * Function to send sms
 * @param   $content: content to be sent, discard if NULL
 * @param   $from: sender, set to mo.short_code if NULL
 * @param   $to: receiver, set to mo.msisdn if NULL
 * @param   $smsc_id: SMSC to send, set to mo.smsc_id if NULL
 * @param   $no_signature: set to TRUE if dont want to sign the message
 * */
function send_sms($content = NULL, $from = NULL, $to = NULL, $smsc_id = NULL, $no_signature = NULL)
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
    if($no_signature)
    {
        $CI->MT->no_signature = $no_signature;
    }
    
    $CI->MT->send();
    
}

    
/**
 * Reply to sender of MO using template
 * @param   $template: template code to be used
 * @param   $no_signature: set to two to disable signature of message.
 * @data    array/object of data to parse or function name to load data.
 * */
function reply($template, $data = 'load_data',$no_signature = FALSE)
{
    $CI =& get_instance();
    $mt_template = $CI->get_mt_template($template);
    $content = $mt_template['content'];
    if ( ! $content)
    {
        write_log("debug","Template '$template' not found, discarded", 'core');
        return FALSE;
    }   
            
    if (is_string($data))
    {
        if (method_exists($CI,$data))
        {
            $data = $CI->$data($template);
        }            
    }
    
    if ($data && (is_array($data) || is_object($data)))
    {
        $content = $CI->parser->parse($content,$data);
    }
            
    $CI->MT->reply_to($CI->MO);   
    $CI->MT->service_action_id = $mt_template['id'];
    $CI->MT->content = $content;
    if ($no_signature)
    {
        $CI->MT->no_signature = $no_signature;
    }
    $CI->MT->send();
    
    $CI->MO->responded = TRUE; //Mark as responded to update last_provision_time field
}


/**
 * Reply string to sender
 * @param   $string: string of MT
 * @param   $no_signature: set to two to disable signature of message.
 * @data    data to parse to string
 * */
function reply_string($string, $data = NULL,$no_signature = FALSE)
{
    $CI =& get_instance();
    $string = trim($string);
    if ( ! $string)
    {
        write_log('debug',"Content string is blank, discarded.",'mt');
    }
    if ($data)
    {
        $string = $CI->parser->parse($string,$data);
    }
    
    $CI->MT->reply_to($CI->MO);        
    $CI->MT->content = $string;
    if ($no_signature)
    {
        $CI->MT->no_signature = $no_signature;
    }        
    $CI->MT->send();
    
    $CI->MO->responded = TRUE; //Mark as responded to update last_provision_time field  
}

/**
 * Get total balance
 * @param   $msisdn: phone number to caculate total balance 
 * @param   $short_code: NULL mean ALL
 * @param   $keyword: MO keyword to count, if not set, 
 * @param   $argument: argument of MO
 *          count all of MO messages belong to this service   
 * */
function get_total_balance($msisdn = NULL,$short_code = NULL,$keyword = NULL,$argument = NULL)
{
    $CI =& get_instance();
    if ( ! $keyword)
    {
        //Get alias of this service
        $keyword = $CI->keywords;
    }
    
    if (! isset($CI->MO_Queue))
    {
        $CI->load->model('Mo_queue_model','MO_Queue');
    }
    
    return $CI->MO_Queue->get_total_balance($msisdn,$short_code,$keyword);
}


/*End of file*/