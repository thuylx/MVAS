<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Huy extends MY_Controller
{
   
    /*****************************************************
     * Default service scenario
     *****************************************************/     
    protected function sc_default()
    {        
        $this->MO_model->cancel($this->MO->msisdn);
        $this->reply('confirm',$this->Evr);
    }
}

/* End of file*/