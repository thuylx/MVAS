<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class _remind_balance extends MY_Controller {

    public function __construct()
    {
        parent::__construct();      
        $this->load->model('Balance_model');
    }                                       
 
    protected function __event_timer()
    {
        $short_codes = array('7427','7527','7627','7727');
        $keywords = $this->Service_model->get_aliases('XS');
        $keywords[] = 'XS';
        $list = $this->Balance_model->get_list(0,$short_codes,$keywords,NULL,date("Y-m-d",time()-24*60*60));            
        foreach($list as $item)
        {
            $this->Evr->mo = $item;
            $msg = $this->generate_mt_message('balance_remind',$this->Evr);            
            if ( ! $msg)
            {
                //Cannot generate mt message
                break;
            }            
            
            $this->MT->load($msg);
            $this->MT->msisdn = $item->msisdn;
            $this->MT->smsc_id = $item->smsc_id;
            $this->MT->short_code = $item->short_code;                
            $this->MT->mo_id = $item->last_mo_id;                             
            $this->MT->send();                                
        }
                            
        //***********************************************************************
        // REMIND XSMN, XSMT
        //***********************************************************************
        $short_codes = array('7527','7627','7727');
        $keywords = $this->Service_model->get_aliases('XSMN');
        $keywords[] = 'XSMN';
        $list = $this->Balance_model->get_list(0,$short_codes,$keywords,NULL,date("Y-m-d",strtotime('yesterday')));            
        foreach($list as $item)
        {
            $this->Evr->mo = $item;
            $msg = $this->generate_mt_message('balance_remind_xsmn',$this->Evr);            
            if ( ! $msg)
            {
                //Cannot generate mt message
                break;
            }            
            
            $this->MT->load($msg);
            $this->MT->msisdn = $item->msisdn;
            $this->MT->smsc_id = $item->smsc_id;
            $this->MT->short_code = $item->short_code;                
            $this->MT->mo_id = $item->last_mo_id;                             
            $this->MT->send();                               
        }

        
        $short_codes = array('7527','7627','7727');
        $keywords = $this->Service_model->get_aliases('XSMT');
        $keywords[] = 'XSMT';
        $list = $this->Balance_model->get_list(0,$short_codes,$keywords,NULL,date("Y-m-d",strtotime('yesterday')));            
        foreach($list as $item)
        {
            $this->Evr->mo = $item;
            $msg = $this->generate_mt_message('balance_remind_xsmn',$this->Evr);            
            if ( ! $msg)
            {
                //Cannot generate mt message
                break;
            }            
            
            $this->MT->load($msg);
            $this->MT->msisdn = $item->msisdn;
            $this->MT->smsc_id = $item->smsc_id;
            $this->MT->short_code = $item->short_code;                
            $this->MT->mo_id = $item->last_mo_id;                             
            $this->MT->send();                               
        }                                                                      
    }        
}

/* End of file*/