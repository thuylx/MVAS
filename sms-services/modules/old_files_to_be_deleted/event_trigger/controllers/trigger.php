<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Trigger extends MY_Controller
{
    private $agent = NULL;
    private $event_name=NULL;
    
    public function __construct()
    {
        parent::__construct();
        
        //Load model        
        $this->load->model('Agent_model');
    }
    
    /*****************************************************
     * Pre process MO before processing below scenario
     * Called automatically by core class, suitable for 
     *  standalize argument in MO content before processing
     *****************************************************/    
    public function __initialize()
    {
                                          
        $event = $this->MO->argument;
        if ( ! $event)
        {
            $this->MO->status = "error_event_not_entered";                
        }
        else
        {
            $this->MO->status = "executed";
            $event = explode(" ",$event);
            $event = $event[0];   
            $this->event_name = $event;                             
        }                           
        
    }   
    
    /*****************************************************
     * Default service scenario     
     *****************************************************/     
    protected function sc_default()
    {     
        if ($this->event_name)
        {
            $this->Event->raise($this->event_name); 
        }
        
        $this->reply_string($this->MO->status);
    }
    
    protected function __finalize()
    {        
        if ($this->agent)
        {            
            $this->agent->count = $this->agent->count + 1;
            $this->agent->last_date = date("Y-m-d", time());
            $this->Agent_model->update($this->agent);
        }              
    }
}

/* End of file*/