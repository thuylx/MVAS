<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Service extends MX_Controller 
{        
    public function __construct()
    {
        parent::__construct();                          
                
        //For development;
        if (ENVIRONMENT == 'development' || ENVIRONMENT == 'testing')
        {
            $this->output->enable_profiler(TRUE);
            write('<a href="http://appsrv.mvas.vn/'.ENVIRONMENT.'/sms-services/injector" target="_parent">SMS Injection Form</a>'); 
            write('<a href="http://appsrv.mvas.vn/'.ENVIRONMENT.'/sms-services/injector/lottery" target="_parent">Lottery Result Injection Form (KQXS)</a>');                        
            write('<hr>');            
        }
        
        ini_set('memory_limit', '-1'); // Increase allowed memory size since the list might be huge
                        
        //Load libraries
        $this->load->library('Mo',NULL,'ORI_MO');
        $this->load->library('Mt',NULL,'MT');
        $this->load->library('Mt_box',NULL,'MT_Box');
        $this->load->library('Mo_box',NULL,'MO_Box');        
        $this->load->library('Event_handler',NULL,'Event');
        $this->load->library('Environment_params',NULL,'Evr'); //For environment parameters
        $this->load->library('Scp'); //Service control point
        $this->load->library('Parser');                
        
        $this->MT_Box->set_max_cache_size($this->config->item('max_mt_box_cache_size'));
        $this->MO_Box->set_max_cache_size($this->config->item('max_mo_box_cache_size'));
        $this->scp->set_real_time_statistic($this->config->item('real_time_statistic'));        

        //Load models
        $this->load->model('Mo_model','MO_model');
        $this->load->model('Mt_model','MT_model');
        $this->load->model('Service_model');            
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
     * Run service
     * - Parse URI for imcoming MO                 
     * - Process MO for service information (module, corrected keyword)
     * - Insert MO into database           
     * - Run service     
     * - Update MO
     * */
    public function exec()
    {
        //Parse URI for infoming MO
        $this->ORI_MO->parse_uri();      
        
        //Check if this MO exist already (caused by an error of Telco somehow)
        write_log('debug','Checking if ORI_MO is duplicated somehow...');
        if ($this->MO_model->is_inserted($this->ORI_MO))
        {
            write_log('error', 'Duplicate MO found, discarded!');
            return FALSE;
        }        
        
        $this->Evr->ori_mo = $this->ORI_MO;
        
        //Stop message which got from other CP/SP like 8502.
        $msisdn_pattern = $this->config->item('accepted_msisdn_pattern');
        $msisdn_pattern = (isset($msisdn_pattern[$this->ORI_MO->smsc_id]))?$msisdn_pattern[$this->ORI_MO->smsc_id]:$msisdn_pattern['default'];
        if ( ! preg_match('/'.$msisdn_pattern.'/',$this->ORI_MO->msisdn)) //Sent from a CP or Operator
        {            
            $this->ORI_MO->status = "error_msisdn_invalid";            
            $this->scp->load_service($this->config->item('error_handle_service'));
        }
        else
        {            
            //Auto correct MO
            if (trim($this->ORI_MO->content) == '')
            {                
                $this->ORI_MO->keyword = $this->config->item('default_service');
                write_log('debug',"Converted empty MO to default service of ".highlight_content($this->ORI_MO->keyword));
            }
            $this->ORI_MO->auto_correct($this->MO_model->get_auto_correct_rules('_core'));
            $this->ORI_MO->auto_correct($this->MO_model->get_auto_correct_rules('_service'));                            
                        
            //Process MO for service information (module, corrected keyword)     
            //$svc = $this->Service_model->get_service($this->ORI_MO->keyword);            
            if (!$this->scp->load_service($this->ORI_MO->keyword)) //service not found
            {
                write_log('debug',"<strong>Service <ins>".$this->ORI_MO->keyword."</ins> not found.</strong>",'core');                
                $this->ORI_MO->status = "error_mo_incorrect";                
                $this->scp->load_service($this->config->item('error_handle_service'));
            }             
            else
            {                
                write_log('debug',"<strong>Detected service = ".$this->scp->service."</strong>",'service');                                               
                $this->ORI_MO->status = "executable";
            }                                      
        }
        
        //Insert MO into database        
        $this->MO_model->insert($this->ORI_MO);                
                
        //Black list and White list processing             
        if ($this->_process_black_white_list($this->scp->category,$this->ORI_MO->msisdn))
        {
            //Service Execution 
            $method = '_service_'.$this->scp->category;
            $this->$method();
        }
        else
        {
            write_log('error','Black or White list has blocked MO message from '.$this->ORI_MO->msisdn.' to '.$this->ORI_MO->short_code.' via '.$this->ORI_MO->smsc_id.'. MO id = '.$this->ORI_MO->id);
            $this->ORI_MO->status = 'error_black_white_list';
            $this->scp->load_service($this->config->item('error_handle_service'));            
            $this->_service_system();
        }        
        
        if ($this->config->item('real_time_statistic'))
        {
            if (! $this->ORI_MO->is_set('revenue'))
            {
                $fee = $this->Statistic_model->cal_revenue($this->ORI_MO->short_code,$this->ORI_MO->smsc_id);        
                $this->ORI_MO->price = (float)$fee->price;
                $this->ORI_MO->revenue = (float)$fee->revenue;                
            }
                          
            $this->Statistic_model->save_mo($this->ORI_MO);
            $this->Statistic_model->update();
        }           
                              
        write_log("error",highlight_info("<strong>Total processed MO: ".$this->Statistic_model->total_processed_mo."</strong>"),'service');
        write_log("error",highlight_info("<strong>Total sent MT: ".$this->Statistic_model->total_sent_mt."</strong>"),'service');            
    }
    
    private function _process_black_white_list($service_category,$msisdn)
    {
        write_log('debug','Black list and white list processing','core');
        
        $black_list = $this->config->item($service_category,'black_list');
        $white_list = $this->config->item($service_category,'white_list');
        
        $allowed = TRUE;
         
        if ($black_list)
        {
            $allowed = ! preg_match("/$black_list/i",$msisdn);
        }
        
        if ($white_list)
        {
            $allowed = $allowed && preg_match("/$white_list/i",$msisdn);
        }
        
        return $allowed;
    }
        
    /**
     * Execute the service scenario for customer
     * - Parse URI for imcoming MO                 
     * - Process MO for service information (module, corrected keyword)
     * - Insert MO into database           
     * - Run service     
     * - Update MO
     * */
    private function _service_customer()
    {                
        $this->load->model('Customer_model');
        
        //**********************************************************************
        // QUOTA CHECKING
        //**********************************************************************        
        $quota = $this->config->item('customer_quota');
        if (array_key_exists($this->ORI_MO->smsc_id,$quota))
        {
            write_log('debug',"Quota was set to ".$quota[$this->ORI_MO->smsc_id].". Checking quota...",'core');
            $cost = $this->Customer_model->cal_day_cost($this->ORI_MO->msisdn);
            if ($cost > $quota[$this->ORI_MO->smsc_id])
            {
                write_log('debug',"Denied. Total today cost of ".$this->ORI_MO->msisdn." is $cost",'core');
                $this->ORI_MO->status = "error_over_quota";                   
                $this->scp->load_service($this->config->item('error_handle_service'));                
                $this->scp->run_service();
            }
            else
            {
                write_log('debug',"Allowed. Total today cost of ".$this->ORI_MO->msisdn." is $cost",'core');
            }         
        }
        
        
        $cust = $this->Customer_model->load($this->ORI_MO->msisdn);
        if ($this->ORI_MO->status != "error_over_quota")
        {
            //Check customer if he/she is disabled                        
            if ($cust && ( ! $cust->enable))
            {                        
                $this->ORI_MO->status = "error_cust_disabled";            
                $this->scp->load_service($this->config->item('error_handle_service'));                
                $this->scp->run_service();                                                       
            }
            else
            {
                $this->ORI_MO->status = 'executed';                
                $this->scp->run_service();            
            }            
        }
        
        //Provision content
        $sent_msg = $this->scp->provision();
        
        //Update customer database
        if (! $this->ORI_MO->is_set('price'))
        {
            $fee = $this->Statistic_model->cal_revenue($this->ORI_MO->short_code,$this->ORI_MO->smsc_id);        
            $this->ORI_MO->price = (float)$fee->price;
            $this->ORI_MO->revenue = (float)$fee->revenue;                           
        }
        if ($cust) //Customer exist.
        {            
            $cust->revenue += $this->ORI_MO->price;            
            //$cust->last_date = date("Y-m-d", time());           
            $cust->last_mo_time = time();
            if ($sent_msg) $cust->last_mt_time = $cust->last_mo_time;
            $this->Customer_model->update($cust);                
        }
        else
        {                
            $cust = array(
                'msisdn'        =>$this->ORI_MO->msisdn,
                'smsc_id'       =>$this->ORI_MO->smsc_id,
                'revenue'       =>$this->ORI_MO->price,
                'last_mo_time'  => time()                
            );
            if ($sent_msg) $cust['last_mt_time'] = $cust['last_mo_time'];               
            $this->Customer_model->insert($cust);
        }                          
    }    
    
    /**
     * MO is processed to update database
     * */
    private function _service_agent()
    {
        //Load model        
        $this->load->model('Agent_model');
                
        //Check sms_agent if he/she is disabled or not allowed to update this service          
        $agent = $this->Agent_model->load($this->ORI_MO->msisdn,$this->scp->service);                    
        if ($agent && ( ! $agent->enable))
        {                                    
            $this->ORI_MO->status = 'error_agent_disabled';                        
            $this->scp->load_service($this->config->item('error_handle_service'));
            $this->scp->run_service();
        }
        elseif($agent === FALSE)
        {
            $this->ORI_MO->status = 'error_agent_denied';                    
            $this->scp->load_service($this->config->item('error_handle_service'));
            $this->scp->run_service();            
        }
        else
        {            
            $this->ORI_MO->status = 'updated';                                
            $this->scp->run_service();                                                                       
        }              
        
        //Provision content
        $this->scp->provision();                            
            
        //Update agent
        if ($this->ORI_MO->status == 'updated' && $agent)
        {
            $agent->count = $agent->count + 1;
            $agent->last_date = date("Y-m-d", time());
            $this->Agent_model->update($agent);              
        }                
    }
    
    private function _service_system()
    {           
        $this->scp->run_service();      
        //Provision content
        $this->scp->provision();          
    }
    
    /**
     * re-run service
     * - Load MO from database     
     * - Run service
     * - Update MO in database
     * 
     * @param $mo_id: id of MO which will be re-executed
     * */
    public function re_run($mo_id)
    {                
        //Configure to print out re-run result
        $this->config->set_item('log_print_out',TRUE);        
        
        //Load MO from database
        $MO = $this->MO_model->get_mo($mo_id);
        if ( ! $MO)
        {
            write("<strong>The MO message is not found to re-run. ID = $mo_id</strong>");
            return;
        }
        $this->ORI_MO->load($MO);
                                        
        if ($this->ORI_MO->status == 're_run') //Re-run already, bypass this
        {            
            write(highlight_info("<strong>Tin da gui lai, vui long kiem tra lai.</strong>"));
            return;
        }
        
        //Auto correct MO                
        //$this->ORI_MO->auto_correct($this->MO_model->get_auto_correct_rules('core'));
        $this->ORI_MO->auto_correct($this->MO_model->get_auto_correct_rules('_core'));
        $this->ORI_MO->auto_correct($this->MO_model->get_auto_correct_rules('_service'));                    
        
        //Load service to be run        
        if (!$this->scp->load_service($this->ORI_MO->keyword)) //service not found
        {
            write_log('debug',"<strong>Service <ins>".$this->ORI_MO->keyword."</ins> not found.</strong>",'core');            
            $this->ORI_MO->status = "error_mo_incorrect";
            $this->scp->load_service($this->config->item('error_handle_service'));            
            write(highlight_info("<strong>Sua lai van sai !!!!</strong>"));
        }             
        else
        {                     
            write_log('debug',"<strong>Detected service = $svc->service</strong>",'service');  
            write(highlight_info("<strong>Gui lai tin theo noi dung moi sua:\n</strong>")."keyword = ".highlight_content($this->ORI_MO->keyword)."\nargument = ".highlight_content($this->ORI_MO->argument));                  
            $this->ORI_MO->status = "re_run";                          
        }   
        
        //Run service
        $this->scp->run_service();
                         
        //Provision content and update queue
        $this->scp->provision();          
                        
        //Update customer last_time
        $cust = (object)array('msisdn'=>$this->ORI_MO->msisdn);
        $cust->last_mo_time = time();
        
        $this->load->model('Customer_model');
        $this->Customer_model->update($cust);     
        
        //Update statistic
        if ($this->config->item('real_time_statistic'))
        {
            $this->Statistic_model->update();
        }
    }        
    
    public function timer()
    {
        $this->scp->trigger = 'timer';
        if ($this->scp->load_service('timer'))
        {                        
            $this->scp->run_service();
        }
        //Provision content and update queue
        $this->scp->provision();          
    }
 
}

/* End of file*/