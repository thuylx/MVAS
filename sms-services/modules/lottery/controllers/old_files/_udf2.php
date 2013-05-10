<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class _udf2 extends MY_Controller {

    public function __construct()
    {
        parent::__construct();           
        $this->load->model("Lottery_model");     
    }
 
    protected function __event_timer()
    {
        $codes = $this->Lottery_model->get_open_lotteries();
        foreach ($codes as $code)
        {
            $this->send_sms('XS'.$code,'7827','8102','GSM-Modem-2',TRUE);
        }        
    }        
}

/* End of file*/