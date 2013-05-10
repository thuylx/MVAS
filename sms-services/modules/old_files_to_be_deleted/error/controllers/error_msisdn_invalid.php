<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Error_msisdn_invalid extends MY_Controller 
{
    
    public function __parse_new_mo()
    {
        $this->MO->keyword = 'error_msisdn_invalid';
        $this->MO->argument = NULL;
    }         

    /*****************************************************
     * Default error processing scenario     
     *****************************************************/     
    protected function sc_default()
    {        
        $msg = $this->generate_mt_message('error_message',$this->Evr);                

        foreach ($this->config->item('admin_mobile') as $admin)
        {            
            $this->MT->load($msg);           
            $this->MT->msisdn = $admin;
            //Detect smsc if not specified
            $smsc = $this->MT_model->detect_smsc($this->MT->msisdn,'modem');                              
            $this->MT->smsc_id = ($smsc)?$smsc:$this->config->item('default_smsc_id');            
            $this->MT->no_signature = TRUE;
            $this->MT->send();
        }
    }    
}

/* End of file*/