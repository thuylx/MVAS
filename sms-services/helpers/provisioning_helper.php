<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Provisioning
 * */
function provision()
{
    $CI =& get_instance();
    
    //Insert MT messages into database
    $CI->MT_model->insert_batch($CI->MT_Box->get_cache(FALSE));        
 
    //Send saved MT message to gateway
    $mt_to_send = $CI->MT_Box->get_cache(TRUE,FALSE);
    $CI->MT_model->send($mt_to_send);

    //Update changed MO
    $CI->MO_model->update_batch($CI->MO_Box->get_cache(FALSE));
    
    //Update statistic
    $CI->Statistic_model->update_batch_mt($mt_to_send);        
            
    //Free memory
    $CI->MT_Box->reset();
    $CI->MO_Box->reset();                        
} 
/*End of file*/