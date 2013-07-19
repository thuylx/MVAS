<?php

class Partner extends MX_Controller 
{        
    public function __construct()
    {
        parent::__construct();           
        
        //For development;
        if (ENVIRONMENT == 'development' || ENVIRONMENT == 'testing')
        {
            $this->output->enable_profiler(TRUE);
            write('<a href="http://appsrv.mvas.vn/'.ENVIRONMENT.'/index.php/injector" target="_parent">SMS Injection Form</a>');            
            write('<hr>');
        }
        
        //Load config
        $this->load->config('core');
        $this->load->config(ENVIRONMENT);        
                
        //Load libraries
        $this->load->library('Mo',NULL,'MO');
        $this->load->library('Mt',NULL,'MT');
        $this->load->library('Mt_box',NULL,'MT_Box');
        $this->load->library('Mo_box',NULL,'MO_Box');        
        $this->load->library('Event_handler',NULL,'Event');
        $this->load->library('Environment_params',NULL,'Evr'); //For environment parameters
        $this->load->library('Scp');
        $this->load->library('Parser');               
        $this->load->library('Simplelogin'); 
        
        $this->MT_Box->set_max_cache_size($this->config->item('max_mt_box_cache_size'));
        $this->MO_Box->set_max_cache_size($this->config->item('max_mo_box_cache_size'));
        $this->scp->set_real_time_statistic($this->config->item('real_time_statistic'));        

        //Load models
        $this->load->model('Mo_model','MO_model');
        $this->load->model('Mt_model','MT_model');
        $this->load->model('Service_model');
        $this->load->model('Customer_model');    
        $this->load->model('Statistic_model');
        $this->load->model('Mo_queue_model','MO_Queue');                                  
        
        //$this->MO_Queue->set_distinct_mode($this->config->item('mo_queue_distinct_mode'));
        //$this->MO_Queue->set_distinct($this->config->item('queue_distinct_fields'));
        $this->MO_Queue->set_queue_size($this->config->item('mo_queue_cache_size'));
                
        //Load helpers
        $this->load->helper('database');
        $this->load->helper('text');                                   
    }                
    
    /**
     * Create partner
     * */
    public function create($username, $password)
    {
        $return = $this->simplelogin->create($username,$password);
        if ($return)
        {
            write_log('debug',"Partner created: $username / $password");
        }
    }
    
    /**
     * Delete a partner
     * */
    public function delete($username)
    {
        $return = $this->simplelogin->delete($username);
        if ($return)
        {
            write_log('debug',"Partner deleted: $username");
        }
    }            
    
    public function test() {
        try 
        { 
            $client = @new SoapClient('http://ws.mvas.vn:7227/wsc/serve/mgw?wsdl');             
        } catch (SoapFault $E){            
            write_log('error','SOAPClient Failed: '.$E->faultstring);
            return;                        
        }        
     
        try
        {                        
            $msg[] = array(
                'mo_id'         => 2871567,
                'short_code'    => '7227',                
                'msisdn'        => '+84983098334',
                'content'       => 'Noi dung',                
                'type'          => 1,
                'link'          => ''
            );                                    
            $msg[] = array(
                'mo_id'         => 2871567,
                'short_code'    => '7227',                
                'msisdn'        => '+84996660003',
                'content'       => 'Noi dung',                
                'type'          => 1,
                'link'          => ''
            );                                    

            $result = $client->send_bulk_sms('ha','ha',$msg);
            
            /*
            $param = array(
                'username'     => 'test',
                'password'     => 'test',
                'MT'           => $msg
            );            
            $result = $client->__soapCall('send_sms',$param);                                   
             */
        } 
        catch (SoapFault $E)
        {                           
            write_log('error','SOAP call failed: '.$E->faultstring);
            return;     
        }                      

        if (! is_soap_fault($result)) {            
            write(highlight_info($result));    
            return;
        }                
    }          
}
