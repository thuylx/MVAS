<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Delete extends MY_Controller {
        
    private $code;
    //private $sconfig;
    public function __construct()
    {
        parent::__construct();     
        //$this->sconfig = $this->load->config('udf',TRUE);        
        $this->load->model("Result_model");
        $this->load->model("Lottery_model");
    }
    
    /*****************************************************
     * Pre process MO before processing below scenario
     * Called automatically by core class, suitable for 
     *  standalize argument in MO content before processing
     *****************************************************/    
    public function __initialize()
    {
             
    }
    
    /*****************************************************
     * Default service scenario     
     *****************************************************/     
    protected function sc_default()
    {        
        echo "OK";
    }
}

/* End of file*/