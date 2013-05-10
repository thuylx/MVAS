<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/* Event handler class */
class Event_handler
{
    private $CI;

    //constructor
    public function __construct()
    {        
        write_log('debug',"Event handler constructing",'core');
        $this->CI =& get_instance();
 
        //Load models to interactive with database
        if ( ! isset($this->CI->Service_model))
        {
            $this->CI->load->model('Service_model');
        } 
    }    
    
    /**
     * rasie event
     * call process function to process raised event
     * */
    public function raise($event_name)
    {
        if (! $event_name)
        {
            write_log('error',"Event name has not specified to raised",'core');
            return FALSE;
        }
        write_log('debug',"Event $event_name raised",'core');
        
        $method = "process_".strtolower($event_name);
        if (method_exists($this,$method))
        {
            $this->$method();            
        }
        else
        {
            //Default event processing function
            $this->process($event_name);            
        }
    }
    
    /**
     * Process event which have been raised
     * Called in raise function
     * */
    public function process($event_name)
    {                                 
        //****************************************************************
        //PROCESS TRIGGED SERVICES
        //****************************************************************
        //Get trigged list
        write_log('debug',"Load servives which trigged by event $event_name",'core');
        $svcs = $this->CI->Service_model->get_trigged_services($event_name); 
        //Process one by one  
        if ( ! $svcs)
        {
            write_log('debug',"No service found.",'core');
            return FALSE;
        }                
          
        foreach($svcs as $svc)
        {                        
            write_log("debug","Trig service $svc by event $event_name.",'core');        
            $this->CI->scp->load_service($svc);
            $this->CI->scp->trigger = $event_name;
            $this->CI->scp->run_service();         
        }
        
        return TRUE;
    }
    
    /**
     * Function to process timer
     * - load time information in to environmental parameters
     * - call process(timer) to run trigged services
     * */
    public function process_timer()
    {
        $this->CI->Evr->time = getdate();
        $this->process('timer');
    }
}
//End of Service class