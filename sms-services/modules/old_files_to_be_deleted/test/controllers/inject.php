<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Inject extends MY_Controller {

    public function __construct()
    {
        parent::__construct();                
    }
 
    protected function sc_default()
    {
        $pattern = "+84900000000";
        for ($i=80001;$i<=100000;$i++)
        {                  
            $msisdn = str_pad($i,strlen($pattern),$pattern,STR_PAD_LEFT);
            $cust[] = array('msisdn'=>$msisdn,'smsc_id'=>'VMS','revenue'=>0,'last_date'=>'2011-05-08','first_date'=>'2011-05-09');            
            $mo[] = array('keyword'=>'TEST','argument'=>'','msisdn'=>$msisdn,'content'=>"test $i",'short_code'=>'7927','time'=>'1304788657','smsc_id'=>'VMS','status'=>'executed','last_provision_time'=>'1304793495','balance'=>0);                                   
        }
        insert_batch($this->db,'customer',$cust);
        insert_batch($this->db,'mo',$mo);
        
        write("<strong>Injected.</strong>");
    }
        
    private function _msg_test($i)
    {
        $msg = "This is test message #$i";
        return($msg);
    }    
}

/* End of file*/