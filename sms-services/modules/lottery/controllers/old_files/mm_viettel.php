<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MM_viettel extends MY_Controller {
                    
    private $lottery_code;
    
    //If customer didn't send MO for this amount of day, send him a hello message'
    private $day_to_say_hello_mo = 3;
    //If system didn't send MT for this amount of day, send him a hello message'
    private $day_to_say_hello_mt = 1;  
    
    private $start_timer = '07:00:00';//Thoi gian nhan lenh tu timer de ban tin quang cao
    private $stop_timer = '18:15:00';//Thoi gian thoi nhan lenh tu timer de ban tin quang cao
    private $end_day_time = '20:00:00'; //After this time, sent time will be consider as next day, before $start_time.
    private $time_diff = 15; //minutes. Khoang thoi gian quang cao truoc thoi diem khach hang gui MT toi thieu
    private $timer_interval = 5; //minutes. Khoang thoi gian dinh ky chay timer, thiet lap trong crontab
        
    //Tong so MT se tra, bao gom cac tin lucky, kq, quang cao
    //Dieu chinh so tin quang cao o day
    private $number_of_mt = array(
        '7127' => 1,
        '7227' => 2,
        '7327' => 3,
        '7427' => 4,
        '7527' => 5,
        '7627' => 7,
        '7727' => 10,
        '7827' => 9,
        '7927' => 9
    );      
    
    public function __construct()
    {
        parent::__construct();              
        $this->load->model("Lottery_model");
        $this->lottery_code = 'MB';                        
    }
    
    public function __parse_new_mo()
    {
        if ( ! is_null($this->MO->argument))
        {
            $this->MO->argument = NULL;
        }                
    }
    
    /*****************************************************
     * Pre process MO before processing below scenario
     * Called automatically by core class
     *****************************************************/    
    public function __initialize()
    {                        
    }    
    
    protected function load_data($template)
    {        
        if (date('H:i:s') < '19:30:00')
        {
            $date = date('Y-m-d');
        }           
        else
        {
            $date = date('Y-m-d',strtotime('tomorrow'));
        }
        
        $data['short_code'] = $this->MO->short_code;
        $data['date'] = date('d/m');        
        switch ($template)
        {            
            case 'MM':                                          
                $temp = $this->Lottery_model->predict($this->lottery_code,NULL,$this->MO->msisdn,12,$date);//str_pad(rand(0,99),2,'0',STR_PAD_LEFT);                                          
                $data['mm'] = array_slice($temp,3,3);
                $data['mm'] = implode(',',$data['mm']);                
                break;     
                
            case 'stat_01':                                          
                $date = date('Y-m-d',strtotime('1 year ago'));
                $temp = $this->Lottery_model->get_statistic_last_occur_from_date($this->lottery_code,$date,TRUE);                  
                sort($temp);      
                $data['stat_db'] = implode(',',$temp);
                
                $data['repeat'] = $this->Lottery_model->get_loto_repeated_pair();
                
                /*
                $date = date('Y-m-d',strtotime('10 days ago'));
                $temp = $this->Lottery_model->get_statistic_last_occur_from_date($this->lottery_code,$date);                
                sort($temp);      
                $data['stat_loto'] = implode(',',$temp);
                */                
                break;        
                
            case 'stat_02':                            
                $temp = $this->Lottery_model->get_statistic_last_occur_longest($this->lottery_code,5,TRUE);
                $stat = array();
                foreach($temp as $result=>$date)                
                {
                    $date = date('d/m/y',strtotime($date));
                    $stat[] = "$result($date)";
                }
                $data['stat_db'] = implode("\n",$stat);
                
                $temp = $this->Lottery_model->get_statistic_last_occur_longest($this->lottery_code,3);
                $stat = array();
                foreach($temp as $result=>$date)                
                {
                    $date = date('d/m/y',strtotime($date));
                    $stat[] = "$result($date)";
                }
                $data['stat_loto'] = implode("\n",$stat);                                        
                break;    
                
            case 'stat_03':     
                $date = strtotime('1 month ago');                
                $date = date("Y-m-d",$date);                       
                $temp = $this->Lottery_model->get_statistic_min_max($this->lottery_code,$date);
                $data = array_merge($data,$temp);
                
                $temp = $this->Lottery_model->get_succession($this->lottery_code);                
                $data['succession'] = array();
                foreach($temp as $key=>$value)
                {
                    $data['succession'][]="$key($value)";
                }
                $data['succession'] = implode('-',$data['succession']);
                                
                break;   
                
            case 'stat_04':     
                $date = strtotime('2 years ago');                
                $date = date("Y-m-d",$date);
                $temp = $this->Lottery_model->get_statistic_min_max($this->lottery_code,$date,'DB',5);
                $data = array_merge($data,$temp);
                break;                                                           
                                                                  
        }
        return $data;
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
        //$this->MO_Queue->set_filter('date(from_unixtime('.$this->db->protect_identifiers('time').'))<',$this->db->escape(date('Y-m-d',strtotime($this->day_to_say_hello_mo.' days ago'))),FALSE);
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
     * 
     */
    
    /*
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
    
    protected function sc_7027()
    {
        $this->reply('incorrect');
    }

    protected function sc_7127()
    {
        $this->sc_7027();
        $this->MO->balance += $this->number_of_mt[$this->MO->short_code] - 1;
    }
    
    protected function sc_7227()
    {
        $this->sc_7027();
        $this->MO->balance += $this->number_of_mt[$this->MO->short_code] - 1;
    }            
    
    protected function sc_7327()
    {
        $this->sc_7027();
        $this->MO->balance += $this->number_of_mt[$this->MO->short_code] - 1;
    }        
    
    protected function sc_7427()
    {
        $this->sc_7027();
        $this->MO->balance += $this->number_of_mt[$this->MO->short_code] - 1;
    }            

/*
    protected function sc_7527()
    {
        $this->sc_7027();
    }
*/  
        
    protected function sc_default()
    {             
        $this->MO->balance += $this->number_of_mt[$this->MO->short_code];
        
        $this->reply('MM');
        $this->MO->balance -= 1;

        $begin_time = time() - 15*60; //15' before
        $replied = $this->MO_model->get_replied_mt($this->MO->msisdn,date('Y-m-d H:i:s',$begin_time));        
        for ($i=1;$i<=4;$i++)
        {
            $tpl = "stat_0$i";
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
}

/* End of file*/