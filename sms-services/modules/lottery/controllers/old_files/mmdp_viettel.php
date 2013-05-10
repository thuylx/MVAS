<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MMDP_viettel extends MY_Controller {
                    
    private $lottery_code;
    private $open_days;
    private $region;
    private $open_lotteries = array();
    
    private $start_timer;//Thoi gian nhan lenh tu timer de ban tin quang cao
    private $stop_timer;//Thoi gian thoi nhan lenh tu timer de ban tin quang cao
    private $end_day_time; //After this time, sent time will be consider as next day, before $start_time.
    private $time_diff = 15; //minutes. Khoang thoi gian quang cao truoc thoi diem khach hang gui MT toi thieu
    private $timer_interval = 5; //minutes. Khoang thoi gian dinh ky chay timer, thiet lap trong crontab
    
    //Tong so MT se tra, bao gom cac tin lucky, kq, quang cao
    //Dieu chinh so tin quang cao o day
    private $number_of_mt = array(
        '7027' => 1,
        '7127' => 2,
        '7227' => 4,
        '7327' => 6,
        '7427' => 8,
        '7527' => 12,
        '7627' => 20,
        '7727' => 25,
        '7827' => 9,
        '7927' => 9
    );     
    
    public function __construct()
    {
        parent::__construct();              
        $this->load->model("Lottery_model");                          
    }
    
    private function _is_open_day()
    {
        if (! $this->open_days)
        {
            //Load information
            $this->open_days = $this->Lottery_model->get_open_days($this->lottery_code);                                
        }        
        return in_array(date('w'),$this->open_days);
    }    
    
    public function __parse_new_mo()
    {
        $this->lottery_catalog = $this->Lottery_model->get_lottery_catalog();
        $args = explode(' ',$this->MO->argument);
        foreach($args as $arg)
        {                                                                
            if ( ! isset($this->lottery_code))
            {
                //Process for lottery code parameter
                foreach($this->lottery_catalog as $lottery_code)
                {                        
                    if ($lottery_code==$arg) //found
                    {
                        $this->lottery_code = $lottery_code;     
                        $this->MO->argument = $lottery_code;              
                        $this->MO->tag = $lottery_code; //Tag MO to speed up queue loading when after_udf raised.
                        $this->region = $this->Lottery_model->get_region($this->lottery_code);                        
                        write_log("debug","Detected MO argurment = ".$lottery_code);
                        break 1;
                    }
                }                    
            }
        }                
        
        //Detect target date
        $open_time = ($this->region == 'MN')?"16:30:00":"17:30:00";
        if ($this->_is_open_day() && date('H:i:s') <= $open_time)
        {
            $this->MO->date = date('Y-m-d');
        }
        else
        {
            if (! $this->open_days)
            {
                //Load information
                $this->open_days = $this->Lottery_model->get_open_days($this->lottery_code);                                
            }                 
            $this->MO->date = $this->Lottery_model->get_next_day($this->open_days);
        }        
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
        
        if ( ! $this->region)
        {
            $this->region = $this->Lottery_model->get_region($this->lottery_code);            
        }
    }    
    
    protected function load_data($template)
    {        
        $data['short_code'] = $this->MO->short_code;        
        $data['lottery_code'] = $this->lottery_code;      
        $data['date'] = date('d/m');
        $data['region'] = $this->region;
        switch ($template)
        {            
            case 'MM':             
                $data['date'] = date('d/m',strtotime($this->MO->date));                             
                $temp = $this->Lottery_model->predict($this->lottery_code,NULL,$this->MO->msisdn,12,$this->MO->date);//str_pad(rand(0,99),2,'0',STR_PAD_LEFT);                                          
                $data['mm'] = array_slice($temp,3,3);
                $data['mm'] = implode(',',$data['mm']);                
                break;     
                
            case 'hello':                
                $data['lotteries'] = $this->Lottery_model->get_open_lotteries($data['region']);
                $data['lottery_nums'] = count($data['lotteries']);
                $data['lotteries'] = implode(', ',$data['lotteries']);                
                break;                
                
            case 'stat_dp_01':                             
                $temp = $this->Lottery_model->get_statistic_min_max($this->lottery_code,15);
                $data['max'] = $temp['max'];
                $temp = $this->Lottery_model->get_statistic_last_occur_longest($this->lottery_code,3);
                $stat = array();
                foreach($temp as $result=>$date)                
                {
                    $date = date('d/m/y',strtotime($date));
                    $stat[] = "$result($date)";
                }
                $data['last_occur'] = implode("\n",$stat);            
                break;        
                
            case 'stat_dp_02':          
                $date = strtotime('6 months ago');                
                $date = date("Y-m-d",$date);          
                $temp = $this->Lottery_model->get_statistic_min_max($this->lottery_code,$date);
                $data = array_merge($data,$temp);
                break;         
                
            case 'stat_dp_03':     
                $date = strtotime('6 months ago');                
                $date = date("Y-m-d",$date);            
                $data['repeat'] = $this->Lottery_model->get_loto_repeated_pair($this->lottery_code,$date);
                
                /*            
                $date = strtotime('1 year ago');                
                $date = date("Y-m-d",$date);                       
                $temp = $this->Lottery_model->get_statistic_min_max($this->lottery_code,$date);
                $data = array_merge($data,$temp);
                */
                break;  
                
            case 'stat_dp_04':     
                $date = strtotime('1 year ago');                
                $date = date("Y-m-d",$date);                       
                
                $temp = $this->Lottery_model->get_statistic_min_max($this->lottery_code,$date,'DB');
                $data['maxdb'] = $temp['max'];
                
                $temp = $this->Lottery_model->get_statistic_min_max($this->lottery_code,$date,'8');
                $data['max8'] = $temp['max'];
                                
                break;                                                           
                                                                  
        }
        return $data;
    }            
    
    protected function sc_timer()
    {
        if (date('Y-m-d',$this->MO->time) < date('Y-m-d'))
        {
            $this->reply('hello');
            $this->MO->balance -= 1;            
        }
    }         
    
    protected function sc_after_udf()
    {
        $this->reply_string($this->Evr->lottery["loto"]);
        $this->MO->balance -= 1;
    }    
    
    protected function sc_7027()
    {
        $this->reply('incorrect');
    }

    protected function sc_7127()
    {
        $this->sc_7027();
        $this->MO->balance = $this->number_of_mt[$this->MO->short_code] - 1;
    }
    
    protected function sc_7227()
    {
        $this->sc_7027();
        $this->MO->balance = $this->number_of_mt[$this->MO->short_code] - 1;
    }            
    
    protected function sc_7327()
    {
        $this->sc_7027();
        $this->MO->balance = $this->number_of_mt[$this->MO->short_code] - 1;
    }        
    
    protected function sc_7427()
    {
        $this->sc_7027();
        $this->MO->balance = $this->number_of_mt[$this->MO->short_code] - 1;
    }            

/*
    protected function sc_7527()
    {
        $this->sc_7027();
    }
*/  
        
    protected function sc_default()
    {             
        $this->MO->balance = $this->number_of_mt[$this->MO->short_code];
        
        $this->reply('MM');
        $this->MO->balance -= 1;

        $begin_time = time() - 15*60; //15' before
        $replied = $this->MO_model->get_replied_mt($this->MO->msisdn,date('Y-m-d H:i:s',$begin_time));        
        for ($i=1;$i<=4;$i++)
        {
            $tpl = "stat_dp_0$i";
            $found = FALSE;
            foreach($replied as $mt)
            {
                if ($mt['code'] == $tpl && $mt['keyword'] != $this->MO->keyword)
                {
                    $found = TRUE;
                    break;
                }            
            }
            
            if (!$found)
            {
                $this->reply($tpl);
                $this->MO->balance -= 1;
            }
        }        
        /*
        $this->reply('stat_01');
        $this->reply('stat_02');
        $this->reply('stat_03');
        $this->reply('stat_04');
        */                
        
        if (date('H:i:s') > '19:30:00')
        {
            $this->reply('inform',array('tomorrow'=>date('d/m',strtotime('tomorrow'))));
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
        //Pocess for only MO taged by lottery code which have just been updated
        // udf controller will save lottery code in Evironmental param, named lottery.
        // see udf controller for more detail
        
        //Chi tra ket qua cho nhung MO nhan hom nay. MO con ton tu hom truoc xe de do de gui tin hello
        $this->MO_Queue->set_filter('date(from_unixtime('.$this->db->protect_identifiers('time').'))',$this->db->escape(date('Y-m-d')),FALSE);        
        
        $this->MO_Queue->set_filter('tag',$this->Evr->lottery['code']);
        
        //Goi cai mot lan thoi nha
        $keywords = $this->get_keywords('SCDP');
        $keywords = array_merge($keywords,$this->get_keywords('TKDP'));
        $keywords = array_merge($keywords,$this->get_keywords('MMDP'));
        $keywords = array_merge($keywords,$this->get_keywords('VIPDP'));                           
        $this->process_queue(NULL,$keywords);
    }    
    
    protected function __event_timer()
    {        
        $regions = array('MN','MT');
        
        foreach ($regions as $region)
        {
            $this->region = $region;
            
            //Lay tin de ban quang cao
            $now = date('H:i:00');                
            if ($this->region == 'MN')
            {
                $this->start_timer = '07:00:00';
                $this->stop_timer = '15:30:00';
                $this->end_day_time = '17:00:00';                
            }
            elseif ($this->region == 'MT')
            {
                $this->start_timer = '07:00:00';
                $this->stop_timer = '16:30:00';
                $this->end_day_time = '19:00:00';                
            }
                        
            $now = date('H:i:00');    
            if ($now == $this->start_timer)
            {            
                $this->queue_db->having('(time(from_unixtime('.$this->db->protect_identifiers('time').'))<'.$this->db->escape(date('H:i:00',strtotime(($this->time_diff+$this->timer_interval).' minutes'))) .' OR time(from_unixtime('.$this->db->protect_identifiers('time').'))>='.$this->db->escape($this->end_day_time).')');
            }
            elseif ($now == $this->stop_timer)
            {
                $this->queue_db->having('time(from_unixtime('.$this->db->protect_identifiers('time').'))>='.$this->db->escape(date('H:i:00',strtotime($this->time_diff.' minutes'))));
                $this->queue_db->having('time(from_unixtime('.$this->db->protect_identifiers('time').'))<'.$this->db->escape($this->end_day_time));
            }
            elseif(($now > $this->start_timer) && ($now < $this->stop_timer))
            {
                $this->queue_db->having('time(from_unixtime('.$this->db->protect_identifiers('time').'))>='.$this->db->escape(date('H:i:00',strtotime($this->time_diff.' minutes'))));         
                $this->queue_db->having('time(from_unixtime('.$this->db->protect_identifiers('time').'))<'.$this->db->escape(date('H:i:00',strtotime(($this->time_diff + +$this->timer_interval).' minutes'))));                   
            }     
            else
            {
                continue; //Do nothing
            }    
            
            //Get open list
            $this->open_lotteries = $this->Lottery_model->get_open_lotteries($region);            
            $this->MO_Queue->set_filter('tag',$this->open_lotteries);             
            
            $keywords = $this->get_keywords('MMDP');
            $keywords = array_merge($keywords,$this->get_keywords('SCDP'));
            $this->process_queue(NULL,$keywords);            
        }
    }     
}

/* End of file*/