<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Sample extends MY_Controller
{
    
    public function __construct()
    {
        parent::__construct();        
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
    protected function load_data($template)
    {
        switch ($template)
        {
            case 'template_01':             
                $data = $this->Lottery_model->get_statistic_min_max('MB',30,NULL,3);                                                             
            case 'template_02':
                $data['today'] = date("d/m");                
                return $data;        
                        
            case 'template_03':
                $data = $this->Lottery_model->get_statistic_min_max('MB',30,NULL,3);
                $data['balance'] = $this->get_total_balance($this->MO->msisdn,$this->MO->short_code) + $this->MO->balance;
                return $data;
        }
    }         
        
    /*****************************************************
     * Service scenario for short_code 7227     
     *****************************************************/     
    protected function sc_7227()
    {
    }    
    
    /*****************************************************
     * Default service scenario     
     *****************************************************/     
    protected function sc_default()
    {     
    }  
          
    //----------------------------------------------------
    // Event processing function
    // This function will be called once event_name raised automatically.
    // In this exapmle, lower case of event name is after_lottery_update    
    //----------------------------------------------------
    protected function __event_after_udf()
    {
        
    } 
}

/* End of file*/