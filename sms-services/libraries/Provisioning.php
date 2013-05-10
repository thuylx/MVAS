<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Provisioning
{        
    private $real_time_statistic = FALSE;
    
    public $service;
    public $module;
    public $controller;
    public $method = "index";
    
    public function __construct()
    {
        write_log('debug',"Provisioning constructing",'core');        
    }
    
    /**
     * Function to load service parameter to store locally
     * @param   String $service: Service keyword
     * @param   String $module: Module to process service
     * @param   String $controller: Controler class
     * @param   String $method: Method of controller will be called
     * @return  Void
     * */
    public function load_service($service = NULL, $module = NULL, $controller = NULL, $method = NULL)
    {
        if ($service) $this->service = $service;
        if ($module) $this->module = $$module;
        if ($controller) $this->controller = $controller;
        if ($method) $this->method = $method;
    }    

    /**
     * Function to load new service parameter to store locally. New service will be call for provisioning
     * @param   String $service: Service keyword which will be use to detect service to redirect to.
     * @return  TRUE if re-direct successfully, FALSE if specified service not found.
     * */
    public function re_direct($service)
    {        
        if ( ! $service)
        {
            write_log('error','Service keyword was not specified, cancelled redirection','service');
            return;
        }
        
        $CI =& get_instance();
        $svc = $CI->Service_model->get_service($service);
        if (! $svc)
        {
            write_log('error',"<strong>Service $service not found, redirect failed.</strong> MO id = ".$CI->ORI_MO->id,'service');                
            return FALSE;                                              
        }
        else
        {            
            $module = $svc->module;            
            
            $this->service = $svc->service;
            //Parse new module parameter
            $module = explode('/',$module);        
            $this->controller = (isset($module[1]))?$module[1]:strtolower($this->service); //For controller class
            $this->method = (isset($module[2]))?$module[2]:'index'; //For method to be called
            $this->module = $module[0];            
                                   
            write_log('debug',"<strong>Redirected to service = $svc->service</strong>",'service');
        }  
    }   
    
    public function set_real_time_statistic($true_false = TRUE)
    {
        $this->real_time_statistic = $true_false;
    }
    
    public function get_real_time_statistic()
    {
        return $this->real_time_statistic;
    }
    
    /**
     * Raise given service controller to run scenario
     * @param   $module: module to process service, could be module_name or module_name/controller_name
     *          if controller_name not specified, it will be set to $keyword
     * @param   $service: keyword of the service which will be run.
     * */
    public function run_service($module,$service,$trigger = "new_mo")
    {        
        $CI =& get_instance(); 
        $ori_service = $service; //for logging only        
        
        write_log('error',highlight_info("[INFO] Start running service $ori_service using module ".strtoupper($module)." by trigger ".strtoupper($trigger)),'core');
        if ( ! ($service && $trigger))
        {
            write_log('error',"Missing parameters (keyword or trigger), cannot run service",'core');
            return FALSE;
        }                
               
        //Parse $module
        if ($module)
        {
            $module = explode('/',$module);        
            $this->module = $module[0];
            $this->controller = (isset($module[1]))?$module[1]:strtolower($this->module); //For controller class
            $this->method = (isset($module[2]))?$module[2]:'index'; //For method to be called                            
        }
        else
        {
            $this->module = $CI->config->item('default_module');
            $this->controller = strtolower($this->module); //For controller class
            $this->method = 'index'; //For method to be called                            
        }
        
        $this->service = $service;
        $this->trigger = $trigger;
        //-----------------------------------------------------------------------------------------------
        // Try to run pre_execution and pre_run hook if enabled
        //-----------------------------------------------------------------------------------------------               
        $CI->config->load("$this->module/hooks",TRUE,TRUE);                
        $hooks = $CI->config->item('hooks');      
        if ($hooks)
        {                    
            //$old_keyword = (isset($CI->ORI_MO))?$CI->ORI_MO->keyword:NULL; //store old keyword
            
            if (($trigger == "new_mo") && (isset($hooks['pre_execution'])))
            {                                
                $controller = $hooks['pre_execution']['controller'];
                $method = (isset($hooks['pre_execution']['method']))?$hooks['pre_execution']['method']:"pre_execution";                                
                write_log('debug',"Hook pre_execution enabled, run $this->module/$controller/$method");
                $CI->load->module($this->module.'/'.$controller);
                $CI->$controller->$method($this->service,$trigger);
                //$output = modules::run($service_controller,$this->service,$trigger);    
                //write($output);                    
            }
            
            if (isset($hooks['pre_run']))
            {
                $controller = $hooks['pre_run']['controller'];
                $method = (isset($hooks['pre_run']['method']))?$hooks['pre_run']['method']:"pre_run";
                write_log('debug',"Hook pre_run enabled, run $this->module/$controller/$method");
                $CI->load->module($this->module.'/'.$controller);
                $CI->$controller->$method($this->service,$trigger);                
                //$output = modules::run($service_controller,$this->service,$trigger);    
                //write($output);                
            }                        
        }
        
        /*
        //-----------------------------------------------------------------------------------------------
        // Do mapping service and short_code to controller
        //-----------------------------------------------------------------------------------------------
        if ($trigger == "new_mo")
        {      
            $CI->config->load("$this->module/maps",TRUE,TRUE);                
            $maps = $CI->config->item('maps');             
            
            if ($maps)
            {                                 
                if (isset($maps[$this->service][$CI->ORI_MO->short_code]))
                {
                    $map = $maps[$this->service][$CI->ORI_MO->short_code];                
                }elseif (isset($maps[$this->service]['default']))
                {
                    $map = $maps[$this->service]['default'];            
                }
                
                if (isset($map))
                {
                    if (isset($map['module']))
                    {
                        $this->module = $map['module'];                    
                    }
                    
                    if (isset($map['controller']))
                    {
                        $this->controller = $map['controller'];                    
                    }
                    
                    if (isset($map['method']))
                    {
                        $this->method = $map['method'];
                    }
                                        
                    write_log('debug',highlight_info("<strong>Map to $this->module/$this->controller/$this->method</strong>"),'core');                
                }            
            }            
        }
         * 
         */
        
        //-----------------------------------------------------------------------------------------------
        // Run main function
        //-----------------------------------------------------------------------------------------------
        //Determine service controller to be called                 
        write_log("debug","<p><strong>Run $this->module/$this->controller/$this->method, service = $this->service, trigger = $this->trigger</strong>",'core');        
        $controller = $this->controller;
        $method = $this->method;        
        $CI->load->module($this->module.'/'.$controller);        
        $CI->$controller->$method($this->service,$this->trigger);        
        //$output = modules::run($service_controller,$this->service,$this->trigger);        
        //write($output);
        
        
        //-----------------------------------------------------------------------------------------------
        // Try to run post_execution and post_run hooks if enabled
        //----------------------------------------------------------------------------------------------- 
        if ($hooks)
        {
            if (($trigger == "new_mo") && (isset($hooks['post_execution'])))
            {
                $controller = $hooks['post_execution']['controller'];
                $method = (isset($hooks['post_execution']['method']))?$hooks['post_execution']['method']:"post_execution";
                write_log('debug',"Hook post_execution enabled, run $this->module/$controller/$method");
                $CI->load->module($this->module.'/'.$controller);
                $CI->$controller->$method($this->service,$this->trigger);
                //$output = modules::run($service_controller,$this->service,$this->trigger);                        
                //write($output);                
            }
            
            if (isset($hooks['post_run']))
            {
                $controller = $hooks['post_run']['controller'];
                $method = (isset($hooks['post_run']['method']))?$hooks['post_run']['method']:"post_run";
                write_log('debug',"Hook post_run enabled, run $this->module/$controller/$method");
                $CI->load->module($this->module.'/'.$controller);
                $CI->$controller->$method($this->service,$this->trigger);                                
                //$output = modules::run($service_controller,$this->service,$this->trigger);    
                //write($output);
            }            
        }
        write_log('error',highlight_info("[INFO] Finished running service $ori_service ($this->service) using module ".strtoupper($this->module)." by trigger ".strtoupper($this->trigger)),'core');
    }
    
    public function process_mt_box()
    {
        $CI =& get_instance();                
        
        //Insert MT messages into database        
        $CI->MT_model->insert_batch($CI->MT_Box->get_cache(FALSE));                
        
        //Send saved MT message to gateway
        $sent_msg = $CI->MT_Box->get_cache(TRUE,FALSE);        
        $sent_msg = $CI->MT_model->send_batch($sent_msg);   
        
        //Update customer
        if ($sent_msg)
        {
            $msisdns = array();
            $CI->load->model('Customer_model');
            foreach($sent_msg as $MT)
            {
                $ok = (isset($CI->ORI_MO))?($MT->msisdn != $CI->ORI_MO->msisdn):TRUE;
                $ok = ($ok && ( ! in_array($MT->msisdn, $msisdns)));
                
                if ($ok) $msisdns[] = $MT->msisdn;                
            }            
            if ($msisdns) $CI->Customer_model->update_batch($msisdns,'last_mt_time',time());
        }
        
        //Update statistic
        if ($this->real_time_statistic)
        {
            $CI->Statistic_model->save_mt($sent_msg);                        
        }        
        $CI->Statistic_model->total_sent_mt += count($sent_msg);
                
        
        $CI->MT_Box->reset();               
        
        return $sent_msg;        
    }
    
    public function process_mo_box()
    {
        $CI =& get_instance();
        
        //Update changed MO     
        $processed_msg = $CI->MO_Box->get_cache(FALSE);
        $CI->MO_model->update_batch($processed_msg);
        
        //Update statistic
        $CI->Statistic_model->total_processed_mo += count($processed_msg);
        
        $CI->MO_Box->reset();
        
        return $processed_msg;        
    }
    
    public function provision()
    {                
        //Process MT Box
        $sent_msg = $this->process_mt_box();
        
        //Process MO Box
        $this->process_mo_box();
        
        return $sent_msg;
    }            
}
/*End of file*/