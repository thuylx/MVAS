<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Test extends MY_Controller {

    public function __construct()
    {
        parent::__construct();                
    }
    
    public function sc_default()
    {     
        $client = new SoapClient("http://203.162.71.101:8080/MvasWS/services/MvasWS?wsdl"); //URL do VNN Plus cung cap        
        $arg = array('type' => 1,'code' => 'viyeu', 'note' => 'test'); //Cac tham so ham Vnnplus_get() duoc dinh nghia trong webservice cua VNN Plus
        
        $result = $client->Vnnplus_get($arg); //Goi ham Vnnplus_get        
        $result = $result->return;                
        
        if (is_numeric($result))
        {
            //Xu ly loi
        }
        else
        {
            //Tach phan text va phan link
            $pos = strpos($result,":");
            $link = substr($result,$pos+1);
            $text = substr($result,0,$pos);
            
            //Thuc hien wap-push
            $this->MT->udh = $link;        
            $this->MT->content = $text;        
            $this->MT->wap_push();             
        }
            
    }
 
    protected function __event_timer()
    {
        $this->send_sms('test timer at '.$this->Evr->time["hours"].':'.$this->Evr->time["minutes"],'7927','+841999890003','GSM-Modem');
    }        
}

/* End of file*/