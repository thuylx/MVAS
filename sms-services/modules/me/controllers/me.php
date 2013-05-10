<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Me extends MY_Controller
{        
    
    /*****************************************************
     * Default service scenario     
     *****************************************************/     
    public function iwin()
    {     
        $user = 'mvas';
        $password = 'o5324pobfx35n5U';
        $this->load->library('sms_iwin');        
        try 
        { 
            $return = $this->sms_iwin->Call($user, $password, $this->MO->id, $this->MO->msisdn, $this->MO->short_code, $this->MO->content, $this->MO->smsc_id, date('Y-m-d H:i:s', $this->MO->time));
        } catch (SoapFault $E){            
            write_log('error','Call sms IWIN failed: '.$E->faultstring);            
            return;                        
        }         
        
        if ( ! $return)
        {
            write_log('error','MECorp returned nothing!');
            return;            
        }
        
        return $return;
    }  

}

/* End of file*/