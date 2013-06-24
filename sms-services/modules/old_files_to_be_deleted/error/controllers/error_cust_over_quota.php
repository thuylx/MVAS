<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Error_cust_over_quota extends MY_Controller 
{
    
    public function __parse_new_mo()
    {
        $this->MO->keyword = 'error_cust_over_quota';
        $this->MO->argument = NULL;
    }      

    /*****************************************************
     * Default error processing scenario     
     *****************************************************/     
    protected function sc_default()
    {
        //Do nothing
        //$this->reply("error_message",$this->Evr,TRUE);        
    }
    
}

/* End of file*/