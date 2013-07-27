<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 *Webservice Controller
 * 
 */
require_once('Zend/Soap/AutoDiscover.php');
class WSC extends MX_Controller 
{       
    private $base_uri = "http://ws.mvas.vn:7227/sms-services/wsc/serve/";
    
    public function __construct() {
        parent::__construct();
        //Load config
        $this->load->config('core');
        $this->load->config(ENVIRONMENT);
    }

    public function serve($class = 'mgw')
    {              
        
        $path = APPPATH.'controllers/soap/'.$class.'.php';        
        
        if ( ! file_exists($path))
        {
            $path = "modules/$class";
            
            if ( !file_exists($path))
            {
                show_404();
                return;                
            }
        }        
         
        require($path);
            
        if (ENVIRONMENT == 'development')
        {                                   
            ini_set("soap.wsdl_cache_enabled", "0"); // disabling WSDL cache
        }        
        $this->config->set_item('log_print_out',FALSE);           

        if (isset($_GET['wsdl']))
        {
            $wsdl = new Zend_Soap_AutoDiscover();            
            $wsdl->setUri($this->base_uri.$class);
            $wsdl->setClass($class);
            $wsdl->handle();            
            return;
        }                        
        
        $soap = new SoapServer($this->base_uri.$class.'?wsdl');
        $soap->setClass($class);
        $soap->handle();        
    }          
    
    public function test($class = 'mgw')
    {
        write_log('debug','Initializing a new soap client...');
        try {
            $soap_client = new SoapClient($this->base_uri.$class."?wsdl",array('trace' => 1));
        }catch (Exception $E){
            write_log('error','ERROR: '.$E->faultstring);
            return;
        }        
        
        write_log('debug','New soap client created. WSDL uri = '.$this->base_uri.$class."?wsdl");
        
        $MT = array(
            'mo_id' => 0,
            'short_code' => '7927',
            'msisdn' => '+84996664444',
            'content' => 'This is for test',
            'type' => 1,
            'link' => ''
        );
        
        $params = array(
            'username' => 'gtelict',
            'password' => 'gtelict@123',
            'MT' => $MT
        );
        
        write_log('debug',"\nCalling function send_sms, parameter = ".print_r($params,TRUE));
        try {
            $return = $soap_client->__soapCall('send_sms',$params);
        }  catch (Exception $E){
            write_log('error','ERROR: '.$E->faultstring);
        }        
        
//        $return = $soap_client->send_sms('gtelict','gtelict@123',$MT);

        write_log('debug',"\n\nXML request: \n".$soap_client->__getLastRequest());
        write_log('debug',"\n\nXML response: \n".$soap_client->__getLastResponse());
    }
}