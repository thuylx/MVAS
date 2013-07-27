<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Test2 extends MY_Controller {

    protected function sc_default()
    {        
        $this->MT->no_signature = TRUE;
        for ($i=1;$i<=10;$i++)
        {                           
            $this->MT->content = $this->_msg_test2($i);                        
            $this->MT->send();                            
        }                                 
    }    
        
    private function _msg_test2($i)
    {
        $msg = "This is test message 2 #$i";
        return($msg);
    }    
}

/* End of file*/