<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Tc extends MY_Controller {
    
    public function __construct()
    {
        parent::__construct();        
    }
    
    /*****************************************************
     * Service scenario for short_code 7027     
     *****************************************************/
    /*     
    protected function sc_7027()
    {
        $this->reply('short_code_incorrect');
    }    
    
    protected function sc_7127()
    {
        $this->sc_7027();
    }
    */
    
    /*****************************************************
     * Default service scenario     
     *****************************************************/     
    protected function sc_default()
    {        
        $this->load->model('Rejection_model');
        $this->Rejection_model->add($this->MO->msisdn);
        
        $this->reply('confirm');
    }      
}

/* End of file*/