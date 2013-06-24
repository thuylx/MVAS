<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class XSDP extends MY_Controller {
        
    private $sconfig;  
          
    private $lottery_catalog = array();
    private $lottery_code;
    //private $date = NULL;
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
    
    public function __parse_new_mo()
    {
        
        $this->lottery_catalog = $this->Lottery_model->get_lottery_catalog();
        $args = explode(' ',$this->MO->argument);
        
        //Detect lottery_code
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
                        $this->MO->tag = $lottery_code; //Tag MO to speed up queue loading when after_udf raised.
                        write_log("debug","Detected MO argurment = ".$lottery_code);
                        break 1;
                    }
                }                    
            }
        }
        
        //Detect target date
        if ($this->_is_open_day())
        {
            $target_date = date('Y-m-d');
        }
        else
        {
            if (! $this->open_days)
            {
                //Load information
                $this->open_days = $this->Lottery_model->get_open_days($this->lottery_code);                                
            }                 
            $target_date = $this->Lottery_model->get_next_day($this->open_days);
        }
                
        foreach($args as $arg)
        {        
            if ( ! isset($this->MO->date))
            {
                //Process for date argument if any in MO content          
                preg_match('/^([0-9]{1,2})[^0-9a-zA-Z]([0-9]{1,2})([^0-9a-zA-Z]([0-9]{1,4}))?$/',$arg,$matches);
                if (count($matches) > 0)
                {
                    $day = str_pad($matches[1],2,'0',STR_PAD_LEFT);
                    $month = str_pad($matches[2],2,'0',STR_PAD_LEFT);
                    $year = date("Y");                    
                    $year = (isset($matches[4]))?str_pad($matches[4],4,$year,STR_PAD_LEFT):$year;
                    
                    if (checkdate($month,$day,$year))
                    {            
                        $this->MO->date = "$year-$month-$day";  
                        if ($this->MO->date != $target_date)
                        {
                            //Run scenario for the case that user enter the date
                            $this->scenario = "sc_date_entered";                             
                        }
                        else
                        {
                            //Marked as date entered to by pass last result
                            $this->MO->date_specified = TRUE;
                        }
                        write_log("debug","Detected MO argurment = ".$this->MO->date);                                                                 
                    }        
                }                       
            }         
            
            if (isset($this->lottery_code) && isset($this->MO->date))
            {
                break;
            }
        }               
        
        $this->MO->argument = ($this->MO->date)?$this->lottery_code." ".$this->MO->date:$this->lottery_code;     
        
        $this->MO->date = ($this->MO->date)?$this->MO->date:$target_date;       
        if ( ! $this->region)
        {
            $this->region = $this->Lottery_model->get_region($this->lottery_code);            
        }
        if ($this->MO->short_code == '7227' && ($this->region == 'MN' || $this->region == 'MT'))
        {
            $this->MO->keyword = 'LUCKY'.$this->region;
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
        
        if (isset($args[1]) && $args[1]<>date('Y-m-d'))
        {
            $this->MO->date = $args[1];           
        }
        
        return;
    }    
    
    protected function load_data($template)
    {        
        $data['lottery_code'] = $this->lottery_code;
        $data['short_code'] = $this->MO->short_code;
        $date = ($this->MO->date)?strtotime($this->MO->date):time();
        $date = date('d/m',$date);   
        $data['date'] = $date;
        $data['region'] = $this->region;
        switch ($template)
        {
            case 'result':
                $data['result'] = $this->Evr->lottery['result'];
                break;
            
            case 'lucky':
                $temp = $this->Lottery_model->predict($this->lottery_code,NULL,$this->MO->msisdn,12,$this->MO->date);//str_pad(rand(0,99),2,'0',STR_PAD_LEFT);                
                $data['mm'] = $temp[3];                
                $data['prediction'] = array_slice($temp,9,3);           
                if ($data['date'] == date('d/m'))
                {
                    $data['confirmation'] = "\nKqxs ".$data['lottery_code']." ".$data['date']." se gui ban som nhat\n";
                }
                else
                {
                    $data['confirmation'] = "";
                }
                break;
                
            case 'lucky2':
                $date = $this->Lottery_model->get_open_date($this->lottery_code,14);                
                $temp = $this->Lottery_model->get_statistic_last_occur_from_date($this->lottery_code,$date);          
                sort($temp);      
                $data['stat'] = implode('-',$temp);
                            
                $temp = $this->Lottery_model->get_succession($this->lottery_code);                
                $data['succession'] = array();
                foreach($temp as $key=>$value)
                {
                    $data['succession'][]="$key($value)";
                }
                $data['succession'] = implode('-',$data['succession']);
                
                if ( ! $this->open_lotteries)
                {
                    $this->open_lotteries = $this->Lottery_model->get_open_lotteries($this->region);
                }
                $data['lotteries_count'] = count($this->open_lotteries);                            
                
            case 'hello':
                if ( ! $this->open_lotteries)
                {
                    $this->open_lotteries = $this->Lottery_model->get_open_lotteries($this->region);
                }                
                
                $codes = $this->open_lotteries;
                
                array_walk($codes,create_function('&$str','$str = "XS".$str;'));
                $data['reg_string'] = implode(' hoac ',$codes);
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
    
    protected function sc_date_entered()
    {
        $this->MO->balance = $this->number_of_mt[$this->MO->short_code];
        
        //==============================================================================
        //User nhap ngay de xem
        //==============================================================================
        //Before today
        if ($this->MO->date < date("Y-m-d"))
        {
            $result = $this->Lottery_model->get_full_result($this->lottery_code,$this->MO->date);
            if ($result)
            {
                $this->MT->content = $result;
                $this->MT->send();                              
                $this->MO->balance -= 1;
            }
            else
            {                
                $this->reply('not_found');
                $this->MO->balance -= 1;
            }                
              
            return;           
        }
        //today or later
        elseif ($this->MO->date >= date("Y-m-d"))
        {
            //$this->MO->balance = 1;
            $this->reply('confirm_future');
            $this->MO->balance -= 1;
            return;
        }
    }    
    
    protected function sc_after_udf()
    {
        if ($this->MO->date == '' || $this->MO->date == date("Y-m-d"))
        {        
            //$this->reply_string($this->Evr->lottery['result']);        
            $this->reply('result');
            $this->MO->balance -= 1; 
        }                   
    }
    
    public function sc_timer()
    {        
        //chi tra tin hello cho nhung tin gui tu nhung hom truoc, co tin hom nay roi thi thoi
        if (date('Y-m-d',$this->MO->time) < date('Y-m-d'))
        {
            $this->reply('hello');
            $this->MO->balance -= 1;            
        }
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
    
    
    protected function sc_7027()
    {        
        $result = $this->Lottery_model->get_last_result($this->lottery_code,'DB');
        if ($result)
        {
            $this->reply_string($result->content);            
        }
        else
        {            
            $this->reply('not_found');
        }
    }    
        
    protected function sc_7127()
    {    
        $this->sc_7027();        
        $this->MO->balance = 1; //Cho tin hello                
    }       
        
    protected function sc_7227()
    {          
        $this->MO->balance = $this->number_of_mt['7227'];
        
        $result = $this->Lottery_model->get_last_result($this->lottery_code,'DB');        
        //******************************************************************************
        //Vao ngay mo thuong
        //******************************************************************************        
        if ($this->_is_open_day())
        {            
            //Chua co giai
            if (( ! $result) || (date('Y-m-d',$result->time) < date('Y-m-d')))
            {
                $this->reply('lucky');      
                $this->MO->balance -= 1;
                
                $this->reply('lucky2');                          
                $this->MO->balance -= 1;
                return;                 
            }
            //Da co giai
            $this->reply_string($result->content);         
            $this->MO->balance -= 1;
            return;
        }
                
        //******************************************************************************
        //Khong phai ngay mo thuong
        //******************************************************************************        
        //Send last result              
        if (! $this->MO->date_specified)
        {
            //$this->reply_string($result->content);
            $data = array(
                'result'=>$result->content,
                'region'=>$this->region
            );
            
            $this->reply('result',$data);
            $this->MO->balance -= 1;
        }       
        $this->reply('lucky');      
        $this->MO->balance -= 1;
        $this->reply('lucky2');
        $this->MO->balance -= 1;
    }
    
    protected function sc_7327()
    {           
        $this->sc_7227();           
        $this->MO->balance = $this->MO->balance + $this->number_of_mt[$this->MO->short_code] - $this->number_of_mt['7227'];

        //******************************************************************************
        //Gui tin thong ke
        //******************************************************************************         
        $begin_time = time() - 15*60; //15' before
        $replied = $this->MO_model->get_replied_mt($this->MO->msisdn,date('Y-m-d H:i:s',$begin_time));                
        for ($i=1;$i<=1;$i++)
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
    }    
        
    protected function sc_7427()
    {
        $this->sc_7227();              
        $this->MO->balance = $this->MO->balance + $this->number_of_mt[$this->MO->short_code] - $this->number_of_mt['7227'];
                
        //******************************************************************************
        //Gui tin thong ke
        //******************************************************************************         
        $begin_time = time() - 15*60; //15' before
        $replied = $this->MO_model->get_replied_mt($this->MO->msisdn,date('Y-m-d H:i:s',$begin_time));                
        for ($i=1;$i<=2;$i++)
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
    }
    
    protected function sc_7527()
    {
        $this->sc_7227();
        $this->MO->balance = $this->MO->balance + $this->number_of_mt[$this->MO->short_code] - $this->number_of_mt['7227'];
        
        //******************************************************************************
        //Gui tin thong ke
        //******************************************************************************         
        $begin_time = time() - 15*60; //15' before
        $replied = $this->MO_model->get_replied_mt($this->MO->msisdn,date('Y-m-d H:i:s',$begin_time));                
        for ($i=1;$i<=3;$i++)
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
        */  
    }    
    
    protected function sc_default()
    {
        $this->sc_7227();
        $this->MO->balance = $this->MO->balance + $this->number_of_mt[$this->MO->short_code] - $this->number_of_mt['7227'];

        //******************************************************************************
        //Gui tin thong ke
        //******************************************************************************         
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
        //$this->MO_Queue->set_filter('`balance`>=',10,FALSE); //Du cho tin kq
        
        //Chi tra ket qua cho nhung MO nhan hom nay. MO con ton tu hom truoc xe de do de gui tin hello
        $this->MO_Queue->set_filter('date(from_unixtime('.$this->db->protect_identifiers('time').'))',$this->db->escape(date('Y-m-d')),FALSE);        
        
        $this->MO_Queue->set_filter('tag',$this->Evr->lottery['code']);                    
        $this->process_queue();
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
                        
            $this->process_queue();           
        }      
    }    
}

/* End of file*/