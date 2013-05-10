<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Event extends MX_Controller 
{    
    public function __construct()
    {
        parent::__construct();          
        
        //Configure to print out re-run result
        //$this->config->set_item('log_print_out',TRUE);   
        
        //For development;
        if (ENVIRONMENT == 'development' || ENVIRONMENT == 'testing')
        {
            $this->output->enable_profiler(TRUE);
            write('<a href="http://appsrv.mvas.vn/'.ENVIRONMENT.'/index.php/injector" target="_parent">SMS Injection Form</a>');            
            write('<hr>');            
        }                
                
        //Load libraries
        $this->load->library('Mo',NULL,'MO');
        $this->load->library('Mt',NULL,'MT');
        $this->load->library('Mt_box',NULL,'MT_Box');
        $this->load->library('Mo_box',NULL,'MO_Box');        
        $this->load->library('Event_handler',NULL,'Event');
        $this->load->library('Environment_params',NULL,'Evr'); //For environment parameters
        $this->load->library('Scp');
        $this->load->library('Parser');                
        
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

    public function raise($event_name)
    {
        $this->Event->raise($event_name);
        
        //Provision content
        $this->scp->provision();                                       
        write_log("debug","<strong>Total processed MO: ".$this->Statistic_model->total_processed_mo."</strong>",'service');
        write_log("debug","<strong>Total sent MT: ".$this->Statistic_model->total_sent_mt."</strong>",'service');        
    }    
    
    public function send_sms($from, $to, $smsc, $content, $mo_id = NULL)
    {        
        $this->MT->short_code = $from;
        $this->MT->msisdn = $to;
        $this->MT->smsc_id = $smsc;
        $this->MT->content = $content;
        $this->MT->no_signature = TRUE;
        if ($mo_id) $this->MT->mo_id = $mo_id;
        $this->MT->send();
        
        //Provision content
        $this->scp->provision();
    }
        
    public function _remap($method)
    {        
        $args = array_slice($this->uri->rsegments,2);     
        array_walk($args,create_function('&$str','$str = urldecode($str);'));
                
        if (method_exists($this,$method))
        {
            call_user_func_array(array(&$this,$method),$args);            
        }
        else
        {
            $this->raise($method);
        }
    }
}

/* End of file*/