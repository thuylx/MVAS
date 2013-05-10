<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Cancel extends MY_Controller
{
   
    /*****************************************************
     * Default service scenario
     *****************************************************/     
    protected function do_cancel()
    {        
        $this->MO_model->cancel($this->MO->msisdn);        
    }
}

/* End of file*/