<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Echo a message to screen when running in a specific environment but not write to log file
 * @param $message: message to be echo
 * @param $level: String. Add this level at the begining of printed out string
 * Note: change environment setting by static ENVIRONMENT in index.php of application
 * */
function write($message, $level = NULL)
{    
    $CI =& get_instance();        
    
    //================================================================
    //Format content
    //================================================================
    if (strtoupper($level) == 'ERROR')
    {
        $message = '<span  style="color: #FF0000;">'.$message.'</span>';
    }  
    
        
    //================================================================
    //Prepare output
    //================================================================
    //Add log level        
    if ($CI->config->item('log_print_log_level'))
    {
        $message = (is_null($level))?$message:"$level : $message";            
    }                  
    
    //Convert newline to <br>
    $message = nl2br($message);
    //Make newline
    $message = "<br />".$message;        

    //================================================================
    //Append output
    //================================================================                         
    //echo $message;        
    $CI->output->append_output($message);                      
}

/**
 * Write log to file and show to html page for debuging and testing purpose.
 * @param   $level = debug, error or info
 * @param   $message: content of the log. Can conain html and php tag, they will be removed before writing to file.
 * @param   $item: item name of log message, can be configured tobe recorded or not by $config[log_debug_items][itemname]
 * */
function write_log($level = 'error', $message, $item = 'service')
{                  
    $CI =& get_instance();
    
    $log_items = $CI->config->item('log_debug_items');
    $level = strtoupper($level);
    
    if ( ! ($level == 'ERROR' || $log_items[$item])) //Alway log error
    {
        return FALSE;
    }    

    //Class which write log
    if ($CI->config->item('log_show_class'))
    {
        $temp = debug_backtrace();
        $temp = (isset($temp[1]['class']))?$temp[1]['class']:FALSE;
                
        if ($temp)
        {
            $message = "[$temp] $message";
        }            
    }
    else
    {
        $item = strtoupper($item);
        $message = "[$item] $message";        
    }
    
    if (config_item('log_show_benchmark'))
    {        
        $CI->benchmark->counter = (isset($CI->benchmark->counter))?$CI->benchmark->counter+1:0;
        $CI->benchmark->mark($CI->benchmark->counter);
        $time = $CI->benchmark->elapsed_time('0',$CI->benchmark->counter);
        $message = "$time - $message";            
    }                   
    
//    if (isset($CI->ORI_MO))
//    {
//        $log = "[MVAS] [".$CI->ORI_MO->id."]".strip_tags($message); 
//    }
//    else
//    {
        $log = "[MVAS] ".strip_tags($message); 
//    }
    
    log_message($level, $log);                        
    if ($CI->config->item('log_print_out'))
    {
        write($message,$level);
    }
}

// END OF Log Helper