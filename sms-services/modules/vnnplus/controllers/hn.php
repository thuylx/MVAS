<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 *Hinh Nen
 */
class HN extends MY_Controller
{            
    public $client;
    private $svc_config;
    
    public function __construct()
    {
        parent::__construct(); 
        $this->svc_config = $this->load->config('hn',TRUE);        
        try 
        { 
            $this->client = @new SoapClient('http://210.211.99.34:8080/MvasWS/services/MvasWS?wsdl'); //http://203.162.71.101:8080/MvasWS/services/MvasWS?wsdl             
        } catch (SoapFault $E){            
            write_log('error','SOAPClient Failed: '.$E->faultstring);
            $this->scenario = 'sc_soap_fail'; //Stop further processing            
            return;                        
        }        
    }
    
    /*****************************************************
     * Pre process new coming MO before processing scenario
     * Called automatically by core class, suitable for 
     *  standalize argument in coming MO content before processing
     *  processed argument should be stored in database.
     *****************************************************/      
    public function __parse_new_mo()
    {
        $args = explode(' ',$this->MO->argument);
        $this->MO->code = $args[0];
        
        if (isset($args[1]))
        {
            preg_match('|(+84|0)?([0-9]{9,10})|', $args[1], $matches);
            if ($matches)
            {
                $this->MO->destination = '+84'.$matches[2];
            }
        }        
    }
    
    /*****************************************************
     * Pre process MO before processing below scenario
     * Called automatically by core class.
     *****************************************************/    
    public function __initialize()
    {
        
    }   
    
    /*******************************************************
     * Load data for template
     *******************************************************/    
    public function load_data($template)
    {
        switch ($template)
        {
        }
    }         
    
    /**
     *Scenario for case of soap client load failed 
     */
    public function sc_soap_fail()
    {
        if ($this->trigger == 'new_mo')
        {
            $this->MO->balance = $this->svc_config['ws_max_retry'];
            $this->reply_string("May chu dang qua tai, chung toi se gui hinh nen den ban som nhat.\nMong quy khach thong cam!");
            return;
        }
        
        $this->MO->balance -= 1;
        
        if ($this->MO->balance == 0)
        {
            //TODO: Webservice max retry time announcement
        }
    }
        
    /*****************************************************
     * Service scenario for short_code 7227     
     *****************************************************/     
    public function sc_7027()
    {
        $this->reply_string("Sai dau so dich vu.\nSoan HN <ma hinh> gui 7527 de tai ve.");
    }    
    
    public function sc_7127()
    {
        $this->sc_7027();
    }
    public function sc_7227()
    {
        $this->sc_7027();
    }
    public function sc_7327()
    {
        $this->sc_7027();
    }
    public function sc_7427()
    {
        $this->sc_7027();
    }
    
    /*****************************************************
     * Default service scenario     
     *****************************************************/     
    public function sc_default()
    {                                     
        try
        {                        
            $msg = array(
                'type'          => 5,
                'code'          => $this->MO->code,
                'request_id'    => $this->MO->id,
                'user_id'       => $this->MO->msisdn,
                'service_id'    => $this->MO->short_code,
                'cmd'           => 'HN'
            );        
            //$msg = (object)$msg;
            $result = $this->client->__soapCall('Vnnplus_get',array($msg));                        
        } 
        catch (SoapFault $E)
        {                           
            write_log('error','SOAP call failed: '.$E->faultstring);
            $this->sc_soap_fail();                    
            return;     
        }                      

        if (! is_soap_fault($result)) {            
            //write(highlight_info($result->return));                
            $result = $result->return;
            preg_match('|(.*):(http\:\/\/.*)|', $result,$matches);            
            if ( ! $matches)
            {
                switch ($result)
                {
                    case '2':
                        write_log('error','Wapush type khong nam trong khoang 1-7. MO ID = '.$this->MO->id);
                        break;
                    case '3':
                        write_log('error','Wapush type phai la kieu so. MO ID = '.$this->MO->id);
                        break;
                    case '4':
                        write_log('error','Khong co noi dung. MO ID = '.$this->MO->id);
                        $this->reply_string('Khong tim thay noi dung nay, vui long kiem tra lai ma.');
                        break;
                    default:
                }
                
                return;
            }                        
            $this->MT->udh = $matches[2];
            $this->MT->content = $matches[1];
            if ($this->MO->destination) $this->MT->msisdn = $this->MO->destination;
            $this->MT->wap_push();                        
            return;
        }                          
    }          
    
    public function __event_timer()
    {
        if ($this->client)
        {
            $this->process_queue();
        }
    }    
}

/* End of file*/