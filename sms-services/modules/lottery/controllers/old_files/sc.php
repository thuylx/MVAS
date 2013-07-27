<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class SC extends MY_Controller
{
    private $sconfig;
    
    private $lottery_code;
    
    //Tong so MT se tra, bao gom cac tin lucky, kq, quang cao
    //Dieu chinh so tin quang cao o day
    private $number_of_mt = array(
        '7227' => 4,
        '7327' => 6,
        '7427' => 8,
        '7527' => 12,
        '7627' => 20,
        '7727' => 25,
        '7827' => 9,
        '7927' => 9
    );  
    
    //If customer didn't send MO for this amount of day, send him a hello message'
    private $day_to_say_hello_mo = 3;
    //If system didn't send MT for this amount of day, send him a hello message'
    private $day_to_say_hello_mt = 1;    
    
    private $start_timer = '07:00:00';//Thoi gian nhan lenh tu timer de ban tin quang cao
    private $stop_timer = '18:15:00';//Thoi gian thoi nhan lenh tu timer de ban tin quang cao
    private $end_day_time = '20:00:00'; //After this time, sent time will be consider as next day, before $start_time.
    private $time_diff = 15; //minutes. Khoang thoi gian quang cao truoc thoi diem khach hang gui MT toi thieu
    private $timer_interval = 5; //minutes. Khoang thoi gian dinh ky chay timer, thiet lap trong crontab    

    public function __construct()
    {
        parent::__construct();
        $this->sconfig = $this->load->config('sc',TRUE);       
        $this->lottery_code = 'MB';
        $this->load->model("Lottery_model"); 
    }
    
    /*****************************************************
     * Pre process MO before processing below scenario
     * Called automatically by core class, suitable for 
     *  standalize argument in MO content before processing
     *****************************************************/    
    public function __initialize()
    {

    }   
    
    /*******************************************************
     * Load data for template
     *******************************************************/    
    protected function load_data($template)
    {
        $tomorrow = time() + 24*60*60;
        switch ($template)
        {
            case 'prediction':     
                $data['date'] = date("d/m");        
                $data['prediction'] = $this->Lottery_model->predict($this->lottery_code,NULL,$this->MO->msisdn,12);
                $data['prediction'] = array_slice($data['prediction'],6,3);
                $data['prediction_DB'] = $this->Lottery_model->predict($this->lottery_code,'DB',$this->MO->msisdn,10);
                return $data;      
                                                                             
            case 'prediction2': //Du doan ngay hom sau
                $data['date'] = date("d/m",$tomorrow);                
                $data['prediction'] = $this->Lottery_model->predict($this->lottery_code,NULL,$this->MO->msisdn,12,$tomorrow);
                $data['prediction'] = array_slice($data['prediction'],6,3);
                $data['prediction_DB'] = $this->Lottery_model->predict($this->lottery_code,'DB',$this->MO->msisdn,10,$tomorrow); 
                return $data;                 
                
            case 'statistic_min_max';
                $data = $this->Lottery_model->get_statistic_min_max($this->lottery_code,30,NULL,3);
                return $data;
                
            case 'statistic_min_max_DB':
                $six_month_ago = date("Y-m-d",strtotime("2 year ago")); 
                $data = $this->Lottery_model->get_statistic_min_max($this->lottery_code,$six_month_ago,'DB',3);
                return $data;       
        }
    }         
    
    /*
    public function __event_timer()
    { 
        //----------------------------------------------------------------------
        // IMPORTANT!!!
        //----------------------------------------------------------------------/
        // This is the same queue for timer event with services of SC, TK, VIP, MM
        // Schedule to trig only one of them.
                
        //$short_codes = array('7127','7227','7327','7427','7527','7627','7727');        
        
        //Loc lay nhung thang ma gan day khong gui mo
       // $this->MO_Queue->set_filter('date(from_unixtime('.$this->db->protect_identifiers('time').'))<',$this->db->escape(date('Y-m-d',strtotime($this->day_to_say_hello_mo.' days ago'))),FALSE);
        //va gan day khong co mt
        //$this->MO_Queue->set_filter('date(from_unixtime('.$this->db->protect_identifiers('last_provision_time').'))<',$this->db->escape(date('Y-m-d',strtotime($this->day_to_say_hello_mt.' days ago'))),FALSE);
        
        //$this->MO_Queue->set_filter('short_code',$short_codes);
        
        
        //Lay tin de ban quang cao
        $now = date('H:i:00');    
        if ($now == $this->start_timer)
        {            
            $this->MO_Queue->set_filter('(time(from_unixtime('.$this->db->protect_identifiers('time').'))<'.$this->db->escape(date('H:i:00',strtotime(($this->time_diff+$this->timer_interval).' minutes'))) .' OR time(from_unixtime('.$this->db->protect_identifiers('time').'))>='.$this->db->escape($this->end_day_time).')',NULL,FALSE);
        }
        elseif ($now == $this->stop_timer)
        {
            $this->MO_Queue->set_filter('time(from_unixtime('.$this->db->protect_identifiers('time').'))>=',$this->db->escape(date('H:i:00',strtotime($this->time_diff.' minutes'))),FALSE);
            $this->MO_Queue->set_filter('time(from_unixtime('.$this->db->protect_identifiers('time').'))<',$this->db->escape($this->end_day_time),FALSE);
        }
        elseif(($now > $this->start_timer) && ($now < $this->stop_timer))
        {
            $this->MO_Queue->set_filter('time(from_unixtime('.$this->db->protect_identifiers('time').'))>=',$this->db->escape(date('H:i:00',strtotime($this->time_diff.' minutes'))),FALSE);
            $this->MO_Queue->set_filter('time(from_unixtime('.$this->db->protect_identifiers('time').'))<',$this->db->escape(date('H:i:00',strtotime(($this->time_diff + +$this->timer_interval).' minutes'))),FALSE);                
        }
        else
        {
            return; //Do nothing
        } 
        
        $keywords = $this->get_keywords('SC');
        $keywords = array_merge($keywords,$this->get_keywords('TK'));
        $keywords = array_merge($keywords,$this->get_keywords('MM'));
        $keywords = array_merge($keywords,$this->get_keywords('VIP'));
        $this->process_queue(NULL,$keywords);     
    }    
    
    protected function sc_timer()
    {
        $condition = date('Y-m-d',$this->MO->time) < date('Y-m-d',strtotime($this->day_to_say_hello_mo.' days ago'));
        $condition = $condition && (date('Y-m-d',$this->MO->last_provision_time) < date('Y-m-d',strtotime($this->day_to_say_hello_mt.' days ago')));
        if ($condition)
        {
            $this->reply('hello');
            $this->MO->balance -= 1;            
        }  
    }    
    
    protected function __event_after_udf()
    {
        //Chi tra ket qua cho nhung MO nhan hom nay. MO con ton tu hom truoc xe de do de gui tin hello
        $this->MO_Queue->set_filter('date(from_unixtime('.$this->db->protect_identifiers('time').'))',$this->db->escape(date('Y-m-d')),FALSE);        
                                    
        $keywords = $this->get_keywords('SC');
        $keywords = array_merge($keywords,$this->get_keywords('TK'));
        $keywords = array_merge($keywords,$this->get_keywords('MM'));
        $keywords = array_merge($keywords,$this->get_keywords('VIP'));                                    
        $this->process_queue(NULL,$keywords);
    }
    
    protected function sc_after_udf()
    {
        $this->reply_string($this->Evr->lottery["loto"]);
        $this->MO->balance -= 1;
    }
     * 
     */
        
    /*****************************************************
     * Service scenario for short_code 7027     
     *****************************************************/     
    protected function sc_7027()
    {  
        //==============================================================================
        //MO truoc 19h
        //==============================================================================
        if (date("H:i:s",$this->MO->time) < $this->sconfig['time_to_queue'])
        {
            $this->reply('prediction');
            return;            
        }        
        
        //==============================================================================
        //MO sau 19h
        //==============================================================================  
        //$this->MO->balance = 1;       
        $this->reply('inform');
    }
    
    protected function sc_7127()
    {        
        $this->sc_7027();
        $this->MO->balance = 1; //Hello
    }    
    
    protected function sc_7227()
    {
        $this->MO->balance = $this->number_of_mt[$this->MO->short_code];
        
        //==============================================================================
        //MO truoc 19h
        //==============================================================================
        if (date("H:i:s",$this->MO->time) < $this->sconfig['time_to_queue'])
        {
            $this->reply('prediction');
            $this->MO->balance -= 1;
            return;            
        }        
        
        //==============================================================================
        //MO sau 19h
        //==============================================================================  
        //$this->MO->balance = 1; 
        $this->reply("prediction2");
        $this->MO->balance -= 1;
        $this->reply('inform');
        $this->MO->balance -= 1;        
    }
    
    protected function sc_7327()
    {
        $this->sc_7227();
        $this->MO->balance = $this->MO->balance + $this->number_of_mt[$this->MO->short_code] - $this->number_of_mt['7227'];
    }
    
    protected function sc_7427()
    {
        $this->MO->balance = $this->number_of_mt[$this->MO->short_code];
        
        //==============================================================================
        //MO truoc 19h
        //==============================================================================
        if (date("H:i:s",$this->MO->time) < $this->sconfig['time_to_queue'])
        {
            $this->reply('prediction');
            $this->MO->balance -= 1;
            $this->reply('statistic_min_max');
            $this->MO->balance -= 1;            
            return;            
        }        
        
        //==============================================================================
        //MO sau 19h
        //==============================================================================  
        $this->MO->balance = 1; 
        $this->reply("prediction2");      
        $this->MO->balance -= 1; 
        $this->reply('inform'); 
        $this->MO->balance -= 1;
    }
    
    /*****************************************************
     * Default service scenario     
     *****************************************************/     
    protected function sc_default()
    {        
        $this->MO->balance = $this->number_of_mt[$this->MO->short_code];
        
        //==============================================================================
        //MO truoc 19h
        //==============================================================================
        if (date("H:i:s",$this->MO->time) < $this->sconfig['time_to_queue'])
        {
            $this->reply('prediction');
            $this->MO->balance -= 1;
            $this->reply('statistic_min_max');
            $this->MO->balance -= 1;
            $this->reply('statistic_min_max_DB');
            $this->MO->balance -= 1;            
            return;            
        }        
        
        //==============================================================================
        //MO sau 19h
        //==============================================================================          
        $this->reply("prediction2");
        $this->MO->balance -= 1;
        $this->reply('inform'); 
        $this->MO->balance -= 1;         
    }          
}

/* End of file*/