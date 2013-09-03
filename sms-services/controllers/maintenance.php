<?php            
class Maintenance extends MX_Controller 
{    
    function __construct()
    {
        parent::__construct();
        $this->benchmark->mark('begin');
        
        define('SID', 'MAINTENANCE'); //For logging
        
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
    
    function flush_kannel()
    {
        $date = date('Y-m-d',strtotime("1 month ago"));
        $this->Dba->purge_kannel_sent_sms($date);  
        
        $date = date('Y-m-d');
        $this->Dba->purge_dlr($date);         
    }
    
    function archive($table)
    {
        switch ($table)
        {
            case 'mo':
                $date = date('Y-m-d',strtotime("1 month ago"));
                if ($this->Dba->archive_mo($date))
                    $this->Dba->purge_mo($date);
                break;
            case 'mt':
//                $date = date('Y-m-d',strtotime("1 month ago"));
//                if ($this->Dba->archive_mt($date))
//                {
//                    $this->Dba->purge_mt($date);                    
//                }
                if ($this->Dba->archive_mt_without_mo())
                    $this->Dba->purge_mt_without_mo();
                break;                
            case 'customer':
                $date = date('Y-m-d',strtotime("3 months ago"));   
                if ($this->Dba->archive_customer($date))
                    $this->Dba->purge_customer($date);
                break;
            case 'kannel_sent_sms':
                $date = date('Y-m-d',strtotime("last month"));
                if ($this->Dba->archive_kannel_sent_sms($date))
                    $this->Dba->purge_kannel_sent_sms($date);
                break;
        }
                
    }
    
    public function process()
    {
//        # PURGE kannel database
        $this->flush_kannel();

//        # PURGE LOTTERY CACHE
        $this->flush_lottery_cache();        

//        # ARCHIVE KANNEL DATABSE
        $this->archive('kannel_sent_sms');

//        # ARCHIVE MVAS DATABSE
        $this->archive('mo');
        $this->archive('mt');
        $this->archive('customer');
        
        //Warning if execution time is to long
        $this->benchmark->mark('end');
        $exec_time = $this->benchmark->elapsed_time('begin','end');
        if ($exec_time > $this->config->item('exec_time_threshold'))
        {
            write_log('error','WARNING: DB Maintenance time ('.$exec_time.') exceeds threshold of '.$this->config->item('exec_time_threshold').' second(s)','maintenance');
        }        
    }
}

/* End of file Scheduler.php */
/* Location: ./sms-services/controllers/Scheduler.php */