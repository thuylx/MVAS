<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Description of _ads
 * Use this to control advertising by timer
 * @author ThuyLe
 */
class _ADS extends MY_Controller {
    
    private $start_timer;//Thoi gian nhan lenh tu timer de ban tin quang cao
    private $stop_timer;//Thoi gian thoi nhan lenh tu timer de ban tin quang cao
    private $end_day_time; //After this time, sent time will be consider as next day, before $start_time.
    private $time_diff = 15; //minutes. Khoang thoi gian quang cao truoc thoi diem khach hang gui MT toi thieu
    private $timer_interval = 5; //minutes. Khoang thoi gian dinh ky chay timer, thiet lap trong crontab
    //If customer didn't send MO for this amount of day, send him a hello message'
    private $day_to_say_hello_mo = 3;
    //If system didn't send MT for this amount of day, send him a hello message'
    private $day_to_say_hello_mt = 1;
    
    private $open_lotteries;
    private $msg_data; //To be pased to messate template
    
    public function __construct() {
        parent::__construct();
        $this->load->model('Lottery_model');
    }
    
    public function _load_data()
    {        
        if (isset($this->msg_data[$this->Evr->service]))
        {
            $this->msg_data[$this->Evr->service]['mo'] = $this->MO->get_all_properties();
            return;
        }
        
        $this->msg_data[$this->Evr->service] = array();
        $this->msg_data[$this->Evr->service]['mo'] = $this->MO->get_all_properties();                
        
        switch ($this->Evr->service)
        {
            case 'XSTT':
                break;
            case 'VIP':
                break;
            case 'KQ':
                break;
            case 'XSDP':
                $codes = $this->open_lotteries;                
                array_walk($codes,create_function('&$str','$str = "XS".$str;'));
                $this->msg_data[$this->Evr->service]['reg_string'] = implode(' hoac ',$codes);
                break;
            case 'VIPDP':                
            case 'MMDP':
                $this->msg_data[$this->Evr->service]['lotteries'] = implode(', ',$this->open_lotteries);
                break;           
        }
        return;        
    }
    
    public function sc_timer()
    {        
        //Gui tin quang cao.     
        $this->_load_data();
        $this->reply('timer',$this->msg_data[$this->Evr->service]);
        $this->MO->balance -= 1;                         
    }
    
    /**
     * SET TIME CONDITION FOR QUEUE FILTER
     * @return boolean: TRUE if time set OK, FALSE if out of time to send ads
     */
    public function _set_queue_timer_condition() {
        
        //Lay tin de ban quang cao
        $now = date('H:i:00');    
        if ($now == $this->start_timer)
        {            
            $this->queue_db->having('(time(from_unixtime('.$this->db->protect_identifiers('mo_queue.time').'))<'.$this->db->escape(date('H:i:00',strtotime(($this->time_diff+$this->timer_interval).' minutes'))) .' OR time(from_unixtime('.$this->db->protect_identifiers('mo_queue.time').'))>='.$this->db->escape($this->end_day_time).')');
        }
        elseif ($now == $this->stop_timer)
        {
            //$this->queue_db->having('time(from_unixtime('.$this->db->protect_identifiers('mo_queue.time').'))>='.$this->db->escape(date('H:i:00',strtotime($this->time_diff.' minutes'))));
            $this->queue_db->having('time(from_unixtime('.$this->db->protect_identifiers('mo_queue.time').'))<'.$this->db->escape($this->end_day_time));
        }
        elseif(($now > $this->start_timer) && ($now < $this->stop_timer))
        {
            //$this->queue_db->having('time(from_unixtime('.$this->db->protect_identifiers('mo_queue.time').'))>='.$this->db->escape(date('H:i:00',strtotime($this->time_diff.' minutes'))));         
            $this->queue_db->having('time(from_unixtime('.$this->db->protect_identifiers('mo_queue.time').'))<'.$this->db->escape(date('H:i:00',strtotime(($this->time_diff + $this->timer_interval).' minutes'))));                   
        }
        else
        {
            return FALSE; //Do nothing
        }     
        
        $this->queue_db->join('customer','mo_queue.msisdn = customer.msisdn','left');     
        $this->queue_db->select('date(from_unixtime('.$this->db->protect_identifiers('customer.last_mo_time').')) AS last_mo_date');
        $this->queue_db->select('date(from_unixtime('.$this->db->protect_identifiers('customer.last_mt_time').')) AS last_mt_date');
        //Loc lay nhung thang ma gan day khong gui mo
        $this->queue_db->having('last_mo_date <'.$this->db->escape(date('Y-m-d',strtotime($this->day_to_say_hello_mo.' days ago'))));
        //va gan day khong co mt
        $this->queue_db->having('last_mt_date <'.$this->db->escape(date('Y-m-d',strtotime($this->day_to_say_hello_mt.' days ago'))));                          
        
        return TRUE;
    }        
    
    public function __event_timer() 
    {         
        $regions = array('MB','MN','MT');
        $now = date('H:i:00');  
        foreach ($regions as $region)
        {                                    
            //Lay tin de ban quang cao                     
            if ($region == 'MB')
            {
                $this->start_timer = '07:00:00';
                $this->stop_timer = '18:30:00';
                $this->end_day_time = '20:00:00'; 
            }
            elseif ($region == 'MN')
            {
                $this->start_timer = '07:00:00';
                $this->stop_timer = '14:30:00';
                $this->end_day_time = '17:00:00';                
            }
            elseif ($region == 'MT')
            {
                $this->start_timer = '07:00:00';
                $this->stop_timer = '15:30:00';
                $this->end_day_time = '19:00:00';                
            }                        
                                                          
            if ( $now >= $this->start_timer && $now <= $this->stop_timer)
            {
                if ($region == 'MB')
                {
                    $this->Evr->region = $region;
                    $this->_set_queue_timer_condition();
                    $keywords = $this->get_keywords('SC');
                    $keywords = array_merge($keywords,$this->get_keywords('TK'));
                    $keywords = array_merge($keywords,$this->get_keywords('MM'));
                    $keywords = array_merge($keywords,$this->get_keywords('VIP'));
                    $keywords = array_merge($keywords,$this->get_keywords('XSTT'));
                    //$keywords = array_merge($keywords,$this->get_keywords('KQ'));
                    $this->process_queue(NULL,$keywords,NULL,array('mo_queue.msisdn'));                                        
                }               
                else
                {
                    //Get open list for MT,MN lottery         
                    $open_lotteries = $this->Lottery_model->get_open_lotteries($region);
                    $this->open_lotteries = $open_lotteries;
                    
                    $this->Evr->region = $region;
                    $this->Evr->service = 'VIPDP';
                    $this->_set_queue_timer_condition(); //Re filter for new queue
                    $this->queue_db->where_in('tag',$open_lotteries);             
                    $keywords = $this->get_keywords('VIPDP');
                    $keywords = array_merge($keywords,$this->get_keywords('TKDP'));
                    $keywords = array_merge($keywords,$this->get_keywords('XSDP'));
                    $keywords = array_merge($keywords,$this->get_keywords('MMDP'));
                    $keywords = array_merge($keywords,$this->get_keywords('SCDP'));                    
                    $this->process_queue(NULL,$keywords,NULL,array('mo_queue.msisdn'));                    
                }                                
            }                            
                         
        }      
        
        //TODO: XSMN XSMT        
    }
}


/* End of file*/