<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 *Webservice Controller
 * 
 */
require_once('Zend/Soap/AutoDiscover.php');
class WSC extends MX_Controller 
{        
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
            $wsdl->setUri('http://ws.mvas.vn:7227/wsc/serve/'.$class);
            $wsdl->setClass($class);
            $wsdl->handle();            
            return;
        }                        
        
        $soap = new SoapServer('http://ws.mvas.vn:7227/wsc/serve/'.$class.'?wsdl');
        $soap->setClass($class);
        $soap->handle();        
    }          
}