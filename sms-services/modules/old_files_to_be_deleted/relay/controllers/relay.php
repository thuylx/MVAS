<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Relay extends MY_Controller
{
    private $partner_name = 'TUAN'; 
    private $wsdl_url = "http://112.213.94.165:8386/sms/SendMO2Mss?wsdl"; 
    
    public function __construct()
    { 
        parent::__construct();                
    }
    
    /*****************************************************
     * Default service scenario     
     *****************************************************/    
    protected function sc_default()
    {        
        try { 
            $client = @new SoapClient("http://112.213.94.165:8386/sms/SendMO2Mss?wsdl",array('uri'=>'http://ws.msc/',"exceptions" => 1)); 
        } catch (SoapFault $E){
            write_log('error','Failed: '.$E->faultstring);
            $this->MO->status = 'relay_failed';
            return;                        
        }
        
        $param = array(
            'mo_id' => $this->MO->id,
            'msisdn' => $this->MO->msisdn,
            'short_code' => $this->MO->short_code,
            'content' => $this->MO->content
        );
        
        //$header = new SoapHeader('http://ws.msc/','');echo "here";         
        //$client->__setSoapHeaders($header);        
        //$result = $client->__soapCall('getMOFromPartNer',$param);
        $result = $client->getMOFromPartNer($param);

        if (is_soap_fault($result)) {
            write_log('error','Failed: '.$result->faultstring);
            $this->MO->status = 'relay_error';
            return;
        }        
        
        switch ($result->return)
        {
            case '-1':
                $this->MO->status = 'relay_error_-1';
                break;
            case '1':
                $this->MO->status = 'relayed';
                break;                    
        }
        
        write_log('debug',highlight_info('<strong>MO forwarded with status of '.$this->MO->status.'</strong>'));
        
    }
    
    /*****************************************************
     * Default service scenario     
     *****************************************************/
    /*     
    protected function sc_default()
    {
        require_once(APPPATH.'libraries/nusoap/nusoap'.EXT); //includes nusoap
        
        $this->load->library('nusoap');
        $this->nusoap_client = new soapclient($this->wsdl_url);
                        
        $this->MO->tag = $this->partner_name;
        if($this->nusoap_client->fault)
        {
            write_log('error','Failed: '.$this->nusoap_client->fault);
            $this->MO->status = 'relay_failed';
            return;            
        }
        else
        {
            if ($this->nusoap_client->getError())
            {                
                $this->MO->status = 'relay_error';
                write_log('error','Failed: '.$this->nusoap_client->getError());
                return;
            }
            else
            {
                $param = array(
                    'mo_id' => $this->MO->id,
                    'msisdn' => $this->MO->msisdn,
                    'short_code' => $this->MO->short_code,
                    'content' => $this->MO->content
                );
                $this->MO->tag = 'WSDL';  
                $status = $this->nusoap_client->call(
                    'getMOFromPartNer',
                    $param,
                    'http://ws.msc/',
                    ''
                );
                
                if ($status === FALSE)
                {
                    $this->MO->status = 'relay_failed';
                    write_log('error','Failed: '.$this->nusoap_client->getError());
                    return;                    
                }
                                
                switch ($status)
                {
                    case '-1':
                        $this->MO->status = 'relay_error';
                        break;
                    case '1':
                        $this->MO->status = 'relayed';
                        break;                    
                }
                
                write_log('debug',highlight_info('<strong>MO forwarded with status of '.$this->MO->status.'</strong>'));
                
                /*
                // Display the request and response
                write('<h2>Request</h2>');
                write('<pre>' . htmlspecialchars($this->nusoap_client->request, ENT_QUOTES) . '</pre>');
                write('<h2>Response</h2>');
                write('<pre>' . htmlspecialchars($this->nusoap_client->response, ENT_QUOTES) . '</pre>');
                write('<h2>Debug</h2>');                    
                write(htmlspecialchars($this->nusoap_client->debug_str, ENT_QUOTES));
                                                  
            }
        }                                
    }  
    */
           
}

/* End of file*/