<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
//KICH BAN TIN NHAN HO TRO CAC DICH VU CUA MIC

class MIC0 extends MY_Controller 
{
    public function __construct()
    {
        parent::__construct();        
    }
    
    /*****************************************************
     * Default service scenario     
     *****************************************************/     
    protected function sc_default()
    {     
        $this->auto_reply();
    }  
}

/* End of file*/