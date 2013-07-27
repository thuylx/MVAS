<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 *Class: SCP (Service control point) 
 */
class Scp
{        
    private $real_time_statistic = FALSE;
    
    public $service;
    public $module;
    public $controller;
    public $method = "index";
    public $root_service_action;
    public $category;
    
    public $trigger = 'new_mo';
    
    public function __construct()
    {
        write_log('debug',"Service Control Point constructing",'core');        
    }
    
    /**
     * Function to load service parameter to store locally
     * @param   String $service: Service keyword
     * @return  boolean
     * */
    public function load_service($service = NULL)
    {
        if ( ! $service)
        {
            write_log('error','Service keyword was not specified, cancelled loading service','service');
            return;
        }
        
        $CI =& get_instance();
        $svc = $CI->Service_model->get_service($service);
        if (! $svc)
        {
            write_log('info',  highlight_warning("<strong>Service $service not found, loading service failed.</strong>"),'core');                
            return FALSE;                                           
        }
        else
        {            
            $this->service = $svc->service;
            
            if ($svc->module)
            {
                $module = explode('/',$svc->module);        
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
            
//            $module = $svc->module;            
//            
//            $this->service = $svc->service;
//            //Parse new module parameter            
//            $module = explode('/',$module);
//            $this->module = $module[0];
//            $this->controller = (isset($module[1]))?$module[1]:strtolower($this->module); //For controller class
//            $this->method = (isset($module[2]))?$module[2]:'index'; //For method to be called
//            
            $this->root_service_action = $svc->root_service_action;
            if ($this->root_service_action == '') $this->root_service_action = 0;
            $this->category = $svc->category;
                                   
            write_log('debug',"<strong>Loaded service = $svc->service, module = $this->module, controller = $this->controller, method = $this->method</strong>",'service');
        }  
        
        return TRUE;
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
        
        write_log('debug','Redirecting to service'.$service);
        
        $this->load_service($service);
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
     * */
    public function run_service()
    {                
        $CI =& get_instance(); 
        $ori_service = $this->service; //for logging only        
        
        write_log('debug',highlight_info("Start running service $ori_service using module ".strtoupper($this->module)." by trigger ".strtoupper($this->trigger)),'core');
                       
//        //Parse $module
//        if ($this->module)
//        {
//            $module = explode('/',$this->module);        
//            $this->module = $module[0];
//            $this->controller = (isset($module[1]))?$module[1]:strtolower($this->module); //For controller class
//            $this->method = (isset($module[2]))?$module[2]:'index'; //For method to be called                            
//        }
//        else
//        {            
//            $this->module = $CI->config->item('default_module');
//            $this->controller = strtolower($this->module); //For controller class
//            $this->method = 'index'; //For method to be called                            
//        }
        $CI->config->load("$this->module/config",FALSE,TRUE);                
        
        //-----------------------------------------------------------------------------------------------
        // Try to run pre_execution and pre_run hook if enabled
        //-----------------------------------------------------------------------------------------------               
        $CI->config->load("$this->module/hooks",TRUE,TRUE);                
        $hooks = $CI->config->item('hooks');              
        if ($hooks)
        {                    
            //$old_keyword = (isset($CI->ORI_MO))?$CI->ORI_MO->keyword:NULL; //store old keyword
            
            if (($this->trigger == "new_mo") && (isset($hooks['pre_execution'])))
            {                                
                $controller = $hooks['pre_execution']['controller'];
                $method = (isset($hooks['pre_execution']['method']))?$hooks['pre_execution']['method']:"pre_execution";                                
                write_log('debug',"Hook pre_execution enabled, run $this->module/$controller/$method");
                try
                {
                    $CI->load->module($this->module.'/'.$controller);
                    if (method_exists($CI->$controller, $method))
                    {
                        $CI->$controller->$method();
                    }
                    else 
                    {
                        write_log('error',"pre_execution hook error: method <b>".$controller."->".$method."</b> does not exist");
                    }
                } catch (Exception $e) {
                    write_log('error','pre_execution hook error: '.$e->getMessage());
                }
            }
            /*
            if (isset($hooks['pre_run']))
            {
                $controller = $hooks['pre_run']['controller'];
                $method = (isset($hooks['pre_run']['method']))?$hooks['pre_run']['method']:"pre_run";
                write_log('debug',"Hook pre_run enabled, run $this->module/$controller/$method");
                $CI->load->module($this->module.'/'.$controller);
                $CI->$controller->$method();                
                //$output = modules::run($service_controller,$this->service,$trigger);    
                //write($output);                
            } 
             * 
             */                       
        }
        
        //-----------------------------------------------------------------------------------------------
        // Run main function
        //-----------------------------------------------------------------------------------------------
        //Determine service controller to be called                 
        write_log("debug","<p><strong>Run $this->module/$this->controller/$this->method, service = $this->service, trigger = $this->trigger</strong>",'core');        
        $controller = $this->controller;
        $method = $this->method;        
        try
        {
            $CI->load->module($this->module.'/'.$controller);        
            if (method_exists($CI->$controller, $method))
            {
                $CI->$controller->$method();
            }
            else 
            {
                write_log('error',"SCP error: method <b>".$controller."->".$method."</b> does not exist");
            }                        
        } catch (Exception $e) {
            write_log('error','SCP main function error: '.$e->getMessage());
        }                
        
        //-----------------------------------------------------------------------------------------------
        // Try to run post_execution and post_run hooks if enabled
        //----------------------------------------------------------------------------------------------- 
        if ($hooks)
        {
            if (($this->trigger == "new_mo") && (isset($hooks['post_execution'])))
            {
                $controller = $hooks['post_execution']['controller'];
                $method = (isset($hooks['post_execution']['method']))?$hooks['post_execution']['method']:"post_execution";
                write_log('debug',"Hook post_execution enabled, run $this->module/$controller/$method");
                try
                {
                    $CI->load->module($this->module.'/'.$controller);
                    if (method_exists($CI->$controller, $method))
                    {
                        $CI->$controller->$method();
                    }
                    else 
                    {
                        write_log('error',"post_execution hook error: method <b>".$controller."->".$method."</b> does not exist");
                    }                    
                } catch (Exception $e) {
                    write_log('error','post_execution hook error: '.$e->getMessage());
                }                
            }
            
            /*
            if (isset($hooks['post_run']))
            {
                $controller = $hooks['post_run']['controller'];
                $method = (isset($hooks['post_run']['method']))?$hooks['post_run']['method']:"post_run";
                write_log('debug',"Hook post_run enabled, run $this->module/$controller/$method");
                $CI->load->module($this->module.'/'.$controller);
                $CI->$controller->$method();                                
                //$output = modules::run($service_controller,$this->service,$this->trigger);    
                //write($output);
            } 
             * 
             */           
        }
        write_log('debug',highlight_info("Finished running service $ori_service ($this->service) using module ".strtoupper($this->module)." by trigger ".strtoupper($this->trigger)),'core');
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