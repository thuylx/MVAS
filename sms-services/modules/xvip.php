<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 *Hinh Nen
 */
class Xvip extends MY_Controller
{            
    public $client;
    private $svc_config;
    
    public function __construct()
    {
        parent::__construct(); 
        $this->svc_config = $this->load->config('hn',TRUE);        
        try 
        { 
            $this->client = @new SoapClient('http://tinhlo.vn/ServiceLottery?wsdl');             
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
        $this->reply_string("Sai dau so dich vu.");
    }    
        
    /*****************************************************
     * Default service scenario     
     *****************************************************/     
    public function sc_default()
    {                             
        $this->load->helper('string');
        $random = random_string();
        
        //----------------------------------------------------------------------
        // Change pass        
        $args = explode(' ',$this->MO->argument);
        if (strtolower($args[0]) == 'mk')
        {
            try
            {                       
                $msg = array(
                    'userCal'      => '7x27',
                    'passCall'      => '7x27#@!',
                    'vipPhone'      => trim($this->MO->msisdn,'+'),
                    'newPass'       => $random                    
                ); 
                
                $result = $this->client->__soapCall('changePass',array($msg));
                $result = $result->return;
                $result = explode('|',$result);
                //var_dump($result);
                
                if ($result[0] == 0)
                {
                    $this->reply_string('Mat khau moi: '.$msg['newPass']."\nChuc ban may man!");
                    return;
                }
            }
            catch (SoapFault $E)
            {                           
                write_log('error','SOAP call failed: '.$E->faultstring);
                $this->sc_soap_fail();                    
                return;     
            }            
        }
        
        //--------------------------------------------------------------------
        // Create acount
        $count_days = array(
            '7127'=> 1,
            '7227'=> 2,
            '7327'=> 4,
            '7427'=> 6,
            '7527'=> 10,
            '7627'=> 25,
            '7727'=> 40,
            '7827'=> 2,
            '7927'=> 2
        );                           
        $msg = array(
            'userCalWS'      => '7x27',
            'passCallWS'      => '7x27#@!',
            'phoneVip'      => trim($this->MO->msisdn,'+'),
            'passVip'       => $random,
            'countDay'      => $count_days[$this->MO->short_code]
        ); 
        
      
        
        try
        {
            $result = $this->client->__soapCall('registerVIP',array($msg));
            
            $date = strtotime($msg['countDay'].' days');
            $date = date('d/m/Y',$date);
            
            $this->reply_string("Tai khoan VIP cua ban da duoc kich hoat.\nTen dang nhap: ".$msg['phoneVip']."\nMat khau: ".$msg['passVip']."\nHet han vao ngay $date\nChuc ban may man!");
        } 
        catch (SoapFault $E)
        {                           
            write_log('error','SOAP call failed: '.$E->faultstring);
            $this->sc_soap_fail();                    
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