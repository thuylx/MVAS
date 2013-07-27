<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Tra extends MY_Controller
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
        if ($this->MO->argument == 'HELP' || $this->MO->argument == '?')
        {
            $message = "De tra thong tin thoi gian thi\nSOAN TIN :\nTRA MaGV\nHoac\nTRA TenGV\nGui 7027";
        }
        else
        {
            $this->db->select("thong_tin")
                     ->from("hung_danh_sach")
                     ->where('ma_sinh_vien',$this->MO->argument)
                     ->or_where('ho_ten',$this->MO->argument);
            $query = $this->db->get();
            
            if ($query->num_rows == 0)
            {
                $message = "Khong tim thay thong tin, vui long kiem tra lai tin nhan.";                
            }
            else
            {
                $row = $query->row();
                $message = $row->thong_tin;
            }
                        
        }
        $this->reply_string($message);
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