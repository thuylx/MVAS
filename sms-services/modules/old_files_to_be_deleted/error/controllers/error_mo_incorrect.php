<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Error_mo_incorrect extends MY_Controller 
{
    
    public function __parse_new_mo()
    {
        //$this->MO->keyword = 'error_mo_incorrect';
        $this->MO->argument = NULL;
    }    

    /*****************************************************
     * Default error processing scenario     
     *****************************************************/     
    protected function sc_default()
    {        
        if ($this->MO->keyword == 'ER' || date("H:i")>'20:00' || date("H:i")<'08:00')
        {
            $this->reply("error_message",$this->Evr);
            $this->MO->keyword = 'error_mo_incorrect';
            $this->MO->status = 're_run';            
        }               
    }
}

/* End of file*/