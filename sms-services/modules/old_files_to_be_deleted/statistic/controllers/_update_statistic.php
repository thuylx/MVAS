<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class _update_statistic extends MY_Controller {

    public function __construct()
    {
        parent::__construct();          
        $this->load->model('Statistic_model');      
    }
 
    protected function __event_timer()
    {
        
    }        
}

/* End of file*/