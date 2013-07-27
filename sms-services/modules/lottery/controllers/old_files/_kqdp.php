<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class _KQDP extends MY_Controller {              
    
    private $excluded_msisdn = array();
    
    public function __construct()
    {
        parent::__construct();              
        $this->load->model("Lottery_model");                        
    }
     
    /*****************************************************
     * Pre process MO before processing below scenario
     * Called automatically by core class
     *****************************************************/    
    public function __initialize()
    {                
        if ($this->MO->argument == '')
        {                        
            return;
        }        
        
        $args = explode(' ',$this->MO->argument);
        
        if ($this->lottery_code != $args[0])
        {
            //Reload lottery code and its relevant info
            $this->lottery_code = $args[0];                
        } 
        
        if (isset($args[1]) && $args[1]<>date('Y-m-d'))
        {
            $this->MO->date = $args[1];           
        }
        
        return;
    }    
    
    
        
    protected function sc_after_udf()
    {
        if (($this->MO->date) && ($this->MO->date != date('Y-m-d')))
        {
            return;
        }  
        
        if (($this->service == 'XSMN') || ($this->service == 'XSMT'))
        {
            $this->reply('result',array('result'=>$this->Evr->lottery['result']));
            $this->MO->balance -= 1;
            $this->excluded_msisdn[] = $this->MO->msisdn;
        }
        elseif ($this->service == 'XSDP')
        {
            $this->reply('result',array('result'=>$this->Evr->lottery['result']));
            $this->MO->balance -= 1;
        }
        elseif ($this->service == 'LOTODP')
        {
            $this->reply('loto',array('loto'=>$this->Evr->lottery['loto']));
            $this->MO->balance -= 1;            
        }
    }           
        
    //----------------------------------------------------
    // Event processing function
    // This function will be called once event_name raised.
    // In this exapmle, lower case of event name is after_lottery_update    
    //----------------------------------------------------    
    protected function __event_after_udf()
    {        
        //tra tin XSMN, XSMT
        $this->excluded_msisdn = array();
        $this->service = 'XS'.$this->Evr->lottery['region'];
        $keywords = $this->get_keywords($this->service);
        $this->process_queue(NULL,$keywords,NULL,array('mo_queue.msisdn'));
        
        //Tra tin XSDP        
        $this->service = 'XSDP';
        //Chi tra ket qua cho nhung MO nhan hom nay. MO con ton tu hom truoc xe de do de gui tin hello
        $this->queue_db->where('date(from_unixtime('.$this->db->protect_identifiers('time').'))',$this->db->escape(date('Y-m-d')),FALSE);                
        $this->queue_db->where('tag',$this->Evr->lottery['code']);  
        if ($this->excluded_msisdn) $this->queue_db->where_not_in('mo_queue.msisdn',$this->excluded_msisdn);
        $keywords = $this->get_keywords($this->service);
        $keywords = array_merge($keywords,$this->get_keywords('SCDP'));
        $keywords = array_merge($keywords,$this->get_keywords('TKDP'));
        $keywords = array_merge($keywords,$this->get_keywords('MMDP'));
        $keywords = array_merge($keywords,$this->get_keywords('VIPDP'));          
        $this->process_queue(NULL,$keywords);
        
        //Tra tin LOTO
        $this->excluded_msisdn = array(); //Ko dung nua
        $this->service = 'LOTODP';
        //Chi tra ket qua cho nhung MO nhan hom nay. MO con ton tu hom truoc xe de do de gui tin hello
        $this->queue_db->where('date(from_unixtime('.$this->db->protect_identifiers('time').'))',$this->db->escape(date('Y-m-d')),FALSE);                
        $this->queue_db->where('tag',$this->Evr->lottery['code']);          
        $keywords = $this->get_keywords('LOTODP');        
        $keywords = array_merge($keywords,$this->get_keywords('SCDP'));
        $keywords = array_merge($keywords,$this->get_keywords('TKDP'));
        $keywords = array_merge($keywords,$this->get_keywords('MMDP'));
        $keywords = array_merge($keywords,$this->get_keywords('VIPDP'));        
        $this->process_queue(NULL,$keywords,NULL,array('mo_queue.msisdn'));        
    }              
}

/* End of file*/