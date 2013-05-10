<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Ads_reject extends MY_Controller {
    
    public function __construct()
    {
        parent::__construct();        
    }
    
    /*****************************************************
     * Default service scenario     
     *****************************************************/     
    protected function do_reject()
    {        
        $this->load->model('Rejection_model');
        $this->Rejection_model->add($this->MO->msisdn);                
    }      
}

/* End of file*/