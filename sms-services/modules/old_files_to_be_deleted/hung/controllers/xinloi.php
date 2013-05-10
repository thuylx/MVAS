<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Xinloi extends MY_Controller
{
    var $customers;
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
        $param = explode(' ',$this->MO->argument);     
        $this->MO->argument = strtoupper($param[0]);
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
        $xinloi = 'Do viec nang cap he thong, tu 17/3 dich vu se tam thoi gian doan voi thue bao Mobiphone. Tong dai se thong bao khi nang cap hoan tat. Thanh that xin loi ban!';
        
        $sql = "SELECT t1 . * FROM ( SELECT msisdn, short_code, smsc_id FROM `m_mo` WHERE smsc_id = 'VMS' and date(from_unixtime(time))>='2012-03-11' ORDER BY short_code DESC )t1 GROUP BY msisdn";        
        $query = $this->db->query($sql);
        
        foreach ($query->result() as $customer)
        {
            $this->send_sms($xinloi,$customer->short_code,$customer->msisdn,$customer->smsc_id);
        }
        
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