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
            $this->client = @new SoapClient('http://203.162.71.101:8080/MvasWS/services/MvasWS?wsdl');             
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
            //TODO: SOAP init failure announcement
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
    public function sc_7227()
    {
    }    
    
    /*****************************************************
     * Default service scenario     
     *****************************************************/     
    public function sc_default()
    {                                     
        try
        {                        
            $msg = array(
                'type'      => 5,
                'code'      => '1234',
                'request_id'    => 2871650,
                'user_id'       => 84983098334,
                'service_id'    => 7227,
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
            write(highlight_info($result->return));                
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