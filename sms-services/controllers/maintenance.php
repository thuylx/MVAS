<?php            
class Maintenance extends MX_Controller 
{    
    function __construct()
    {
        parent::__construct();
        
        //For development;
        //if (ENVIRONMENT == 'development' || ENVIRONMENT == 'testing')
        //{
        $this->output->enable_profiler(TRUE);
        $this->config->set_item('log_print_out',TRUE);
        //    write('<a href="http://appsrv.mvas.vn/'.ENVIRONMENT.'/index.php/injector" target="_parent">SMS Injection Form</a>');            
        //    write('<hr>');            
        //}          
        
        $this->load->model('Dba');              
    }
    
    function flush_dlr()
    {                       
        //$date = date('Y-m-d',strtotime("2 days ago"));
        $date = date('Y-m-d');
        $this->Dba->purge_dlr($date);                                      
    }             
    
    function flush_lottery_cache()
    {
        $date = date('Y-m-d',strtotime('1 week ago'));
        $this->Dba->purge_lottery_cache($date);        
    }
    
    function archive($table)
    {
        switch ($table)
        {
            case 'mo':
                $date = date('Y-m-01',strtotime("last month"));
                if ($this->Dba->archive_mo($date))
                {
                    $this->Dba->purge_mo($date);
                }
                break;
            case 'mt':
                $date = date('Y-m-01',strtotime("last month"));
                if ($this->Dba->archive_mt($date))
                {
                    $this->Dba->purge_mt($date);                    
                }
                break;                
            case 'customer':
                $date = date('Y-m-01',strtotime("3 months ago"));   
                if ($this->Dba->archive_customer($date))
                {
                    $this->Dba->purge_customer($date);
                }                
                break;
            case 'kannel_sent_sms':
                $date = date('Y-m-01',strtotime("last month"));
                if ($this->Dba->archive_kannel_sent_sms($date))
                {
                    $this->Dba->purge_kannel_sent_sms($date);
                }            
                break;
        }
                
    }
}

/* End of file Scheduler.php */
/* Location: ./sms-services/controllers/Scheduler.php */