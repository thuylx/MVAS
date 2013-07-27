<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Xstt extends MY_Controller {
        
    private $lottery_code = 'MB';
    
    /*
    private $after_udf_msg = array(); //store parsed msg to send in event of _after_udf
    
    private $start_timer = '07:00:00';//Thoi gian nhan lenh tu timer de ban tin quang cao
    private $stop_timer = '23:15:00';//Thoi gian thoi nhan lenh tu timer de ban tin quang cao
    private $end_day_time = '20:00:00'; //After this time, sent time will be consider as next day, before $start_time.
    private $time_diff = 15; //minutes. Khoang thoi gian quang cao truoc thoi diem khach hang gui MT toi thieu
    private $timer_interval = 5; //minutes. Khoang thoi gian dinh ky chay timer, thiet lap trong crontab
    
    //If customer didn't send MO for this amount of day, send him a hello message'
    private $day_to_say_hello_mo = 3;
    //If system didn't send MT for this amount of day, send him a hello message'
    private $day_to_say_hello_mt = 1;
     * 
     */
    
    //Cau hinh dau so nao tra giai nao
    private $return_prizes = array(
        '7027' => array('DB'),
        '7127' => array('DB'),
        '7227' => array('DB'),
        '7327' => array('4','DB'),
        '7427' => array('1','4','DB'),
        '7527' => array('1','2','3','4','5','6','7','DB'),
        '7627' => array('1','2','3','4','5','6','7','DB'),
        '7727' => array('1','2','3','4','5','6','7','DB'),
        '7827' => array('1','2','3','4','5','6','7','DB'),
        '7927' => array('1','2','3','4','5','6','7','DB')
    );
    
    //Tong so MT se tra, bao gom cac tin lucky, kq, quang cao
    //Dieu chinh so tin quang cao o day
    private $number_of_mt = array(
        '7227' => 4,
        '7327' => 6,
        '7427' => 8,
        '7527' => 12,
        '7627' => 20,
        '7727' => 25,
        '7827' => 10,
        '7927' => 10
    );   
     
    public function __construct()
    {
        parent::__construct();
        $this->load->model("Lottery_model");        
    }
    
    public function __parse_new_mo()
    {
        //We accept only parameter of Date
        if ($this->MO->argument == '')
        {
            return;
        }
        
        $args = explode(' ',$this->MO->argument);
        foreach($args as $arg)
        {           
            //Process for date argument if any in MO argument
            //Only date argument accepted, discard another ones        
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
                                        
                    if ($this->MO->date != date('Y-m-d'))
                    {
                        //Run scenario for the case that user enter the date
                        $this->scenario = "sc_date_entered";                             
                    }
                                                 
                    write_log("debug","Detected MO argurment = ".$this->MO->date);                         
                              
                    break;
                }        
            }
        }                                                              
        $this->MO->argument = $this->MO->date; //Save only this allowed argument        
    }
    
    protected function __initialize()
    {
        if ($this->MO->argument)
        {
            $this->MO->date = $this->MO->argument;
        }
    }    
    
    /*
    private function _get_after_udf_msg($short_code = NULL,$prize = NULL)
    {
        $short_code = (is_null($short_code))?$this->MO->short_code:$short_code;
        $prize = (is_null($prize))?$this->Evr->lottery['prize']:$prize;        
        if ( ! isset($this->after_udf_msg[$short_code][$prize]))
        {
            $this->after_udf_msg[$short_code][$prize] = $this->generate_mt_message('prize'.$prize,array('result'=>$this->Evr->lottery['result']));
        }
        
        return $this->after_udf_msg[$short_code][$prize];
    }
     * 
     */
    
    protected function load_data($template)
    {
        $data = array();
        $data['short_code'] = $this->MO->short_code;
        $date = ($this->MO->date)?strtotime($this->MO->date):time();
        $date = date('d/m',$date);   
        $data['date'] = $date;        
          
        switch ($template)
        {
            case 'timer':
                break;

            case 'loto':
                $loto = $this->Lottery_model->get_today_loto($this->lottery_code);
                $data['result'] = $loto->content;
                break;
                
            case 'lucky':
                $temp = $this->Lottery_model->predict($this->lottery_code,NULL,$this->MO->msisdn,12,$this->MO->date);//str_pad(rand(0,99),2,'0',STR_PAD_LEFT);                
                $data['mm'] = $temp[3];                
                $data['prediction'] = array_slice($temp,9,3);                                             
                //$count=array('7227'=>'2 ','7327'=>'3 ','7427'=>'4 ','7527'=>'','7627'=>'','7727'=>'','7827'=>'','7927'=>''); //So tin xstt se nhan dc
                //$data['tt'] = $count[$this->MO->short_code];
                $count = count($this->return_prizes[$this->MO->short_code]);                           
                $data['tt'] = ($count != 8)?$count.' ':''; //8 mean all
                
//                break;
                
//            case 'stat':
                $temp = $this->Lottery_model->get_statistic_last_occur_num_days($this->lottery_code,10);                          
                sort($temp);      
                $data['stat'] = implode('-',$temp);
                $data['stat'] = ($data['stat'])?$data['stat']:'khong co so nao';
                $temp = $this->Lottery_model->get_succession($this->lottery_code);                
                $data['succession'] = array();
                foreach($temp as $key=>$value)
                {
                    $data['succession'][]="$key($value)";
                }
                $data['succession'] = implode('-',$data['succession']);
                $data['succession'] = ($data['succession'])?$data['succession']:'khong co so nao';
                                
                break;                                                                     
        }
        
        return $data;
        
    }
    
    protected function sc_date_entered()
    {
        $this->MO->balance = $this->number_of_mt[$this->MO->short_code];
        
        if ($this->MO->date < date('Y-m-d'))
        {
            //Return result
            $result = $this->Lottery_model->get_full_result($this->lottery_code,$this->MO->date);
            if ($result)
            {
                $this->reply_string($result);
                $this->MO->balance -= 1;
            }
            else
            {
                $this->reply('not_found');
                $this->MO->balance -= 1; 
            }                
            return;            
        }
        elseif ($this->MO->date > date("Y-m-d"))
        {            
            //$this->MO->balance = count($this->return_prizes[$this->MO->short_code]); //For results in the future
            
            if ($this->MO->short_code >= '7227')
            {
                $this->reply('lucky');
                $this->MO->balance -= 1;
                $this->reply('inform'); 
                $this->MO->balance -= 1;               
            }
        }         

    }
     
    /*
    //Kich ban khi cap nhat ket qua
    protected function sc_after_udf()
    {               
        if (($this->MO->date) && ($this->MO->date != date('Y-m-d')))
        {
            return;
        }
        
        $prizes = $this->return_prizes[$this->MO->short_code];
                      
        if ((count($prizes) == 8) || (in_array($this->Evr->lottery['prize'],$prizes))) //count = 8 mean all
        {
            $this->reply_message($this->_get_after_udf_msg($this->MO->short_code,$this->Evr->lottery['prize']));              
            $this->MO->balance -= 1;
        }                            
    }        
     * 
     */
    
    protected function sc_7027()
    {                                      
        $result = $this->Lottery_model->get_last_result($this->lottery_code);          
        
        if( ! $result)
        {
            write_log("debug","Lottery result not cached");
            return;
        }
        
        //Neu chua quay giai, tra ket qua moi nhat
        if (date('Y-m-d',$result->time)<date('Y-m-d'))
        {
            $this->reply_string($result->content);
            return;
        }
        
        //Neu da co giai dac biet, tra giai dac biet hom nay
        if ($result->code == 'DB')
        {
            $this->reply_string($result->content);            
            return;
        }
        
        //Dang quay giai, chua co giai dac biet, dua tin nhan vao queue
        $this->MO->balance = 1;
    }
        
    protected function sc_7127()
    {
        $this->sc_7027();
        $this->MO->balance =  $this->MO->balance + 1; //Cho tin hello
    }    
    
    public function sc_default()        
    {
        $this->MO->balance = $this->number_of_mt[$this->MO->short_code];
        
        $prizes = $this->return_prizes[$this->MO->short_code];
        $count = count($prizes);
        
        $result = $this->Lottery_model->get_last_today_result($this->lottery_code);
        
        //Chua quay giai
        if ( ! $result)
        {
            $this->reply('lucky');
            $this->MO->balance -= 1;
            /*             
            $this->reply('stat');
            $this->MO->balance -= 1;                                   
            * 
            */
            //$this->MO->balance = $count; 
            return;
        }
                        
        $i = $count-1;
        while ($i >= 0 && $prizes[$i] > $result->code )
        {            
            $i--;
        }
        
        //$this->MO->balance = $count - $i - 1; // Cho cac giai chua co                     
        
        //Giai vua quay xong khong nam trong danh sach tra ve cua dau so nay thi tra ve kq gan nhat
        if ($i >= 0 && $prizes[$i] < $result->code)
        {
            $result = $this->Lottery_model->get_today_result($this->lottery_code,$prizes[$i]);
        }
            
        $data = array(
            'result'=>$result->content,
            'short_code'=>$this->MO->short_code
        );        
        
        $this->reply('prize'.$result->code,$data);
        $this->MO->balance -= 1;
        
        //**************************************************************************
        // KHUYEN MAI
        //**************************************************************************        
        if ($result->code == 'DB')
        {            
            //$this->MO->balance = $this->MO->balance + 1; //hello
            
            //Tra tin loto
            $loto = $this->Lottery_model->get_today_loto($this->lottery_code);
            $this->reply_string($loto->content);
            $this->MO->balance -= 1;
        }                        
    }

    /*
    public function __event_after_udf()
    {                                
        //Chi tra ket qua cho nhung MO nhan hom nay. MO con ton tu hom truoc xe de do de gui tin hello
        $this->MO_Queue->set_filter('date(from_unixtime('.$this->db->protect_identifiers('time').'))',$this->db->escape(date('Y-m-d')),FALSE);
        
        $keywords = $this->get_keywords('XSTT');
        //Neu la giai dac biet tra luon cho VIP, MM, SC, TK
        if ($this->Evr->lottery['prize'] == 'DB')
        {
            $keywords = array_merge($keywords,$this->get_keywords('VIP'));
            $keywords = array_merge($keywords,$this->get_keywords('MM'));
            $keywords = array_merge($keywords,$this->get_keywords('SC'));
            $keywords = array_merge($keywords,$this->get_keywords('TK'));
        }
        $this->process_queue(NULL,$keywords,NULL,array('mo_queue.msisdn'));                        
    }
    
    public function sc_timer()
    {        
        //$condition = date('Y-m-d',$this->MO->time) < date('Y-m-d',strtotime($this->day_to_say_hello_mo.' days ago'));        
        //$condition = $condition && (date('Y-m-d',$this->MO->last_provision_time) < date('Y-m-d',strtotime($this->day_to_say_hello_mt.' days ago')));
        //if ($condition)
        //{

        //Gui tin hello.
        $this->reply('timer');
        $this->MO->balance -= 1;                         

        //}
    }
   
    public function __event_timer()
    {    
        //$short_codes = array('7127','7227','7327','7427','7527','7627','7727');
        
        //foreach ($short_codes as $short_code)
        //{
            //$count = count($this->return_prizes[$short_code]);
            //$this->MO_Queue->set_filter('balance<',$count,FALSE); //Khong du cho ca ngay
            
            //$this->process_queue($short_code);
        //}
        
        //Loc lay nhung thang ma gan day khong gui mo
        $this->queue_db->having('date(from_unixtime('.$this->db->protect_identifiers('time').'))<'.$this->db->escape(date('Y-m-d',strtotime($this->day_to_say_hello_mo.' days ago'))));
        //va gan day khong co mt
        $this->queue_db->having('date(from_unixtime('.$this->db->protect_identifiers('last_provision_time').'))<'.$this->db->escape(date('Y-m-d',strtotime($this->day_to_say_hello_mt.' days ago'))));
        
        //$this->MO_Queue->set_filter('short_code',$short_codes);
        
        //Lay tin de ban quang cao
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
            return; //Do nothing
        }                     
        
        $this->process_queue();
        
    }
     * 
     */
}

/* End of file*/