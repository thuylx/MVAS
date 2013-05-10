<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class _qa extends MY_Controller {

    public function __construct()
    {
        parent::__construct();      
        $this->load->model('Balance_model');
    }                                       
 
    protected function __event_after_udf()
    {
        $qas = $this->load->config('qa',TRUE);
        $qas = $qas['qa_mobile'];

        //$msg = $this->_get_after_udf_msg('7527');                
        
        foreach ($qas as $qa)
        {
            $this->MT->mo_id = 0;
            $this->MT->content = $this->Evr->lottery['result'];            
            $this->MT->short_code = '7927';
            $this->MT->msisdn = $qa;
            $smsc = $this->MT_model->detect_smsc($this->MT->msisdn,'modem');
            $this->MT->smsc_id = $smsc;            
            //$this->MT->no_signature = TRUE;            
            $this->MT->send();
        }                                    
    }        
}

/* End of file*/