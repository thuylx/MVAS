<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
 * KHONG DUNG LOP NAY (27/6/2012)
 */
class XSTTDP_viettel extends MY_Controller {
        
    private $sconfig;  
          
    private $lottery_catalog = array();
    private $lottery_code;
    private $date = NULL;
    private $open_days;    
    private $region;
    
    public function __construct()
    {
        parent::__construct();              
        $this->load->model("Lottery_model");                        
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
                        $this->MO->tag = $lottery_code; //Tag MO to speed up queue loading when after_udf raised.
                        write_log("debug","Detected MO argurment = ".$lottery_code);
                        break 1;
                    }
                }                    
            }

            if ( ! isset($this->date))
            {
                //Process for date argument if any in MO content          
                preg_match('/^([0-9]{1,2})[^0-9a-zA-Z]([0-9]{1,2})([^0-9a-zA-Z]([0-9]{1,4}))?$/',$arg,$matches);
                if (count($matches) > 0)
                {
                    $day = str_pad($matches[1],2,'0',STR_PAD_LEFT);
                    $month = str_pad($matches[2],2,'0',STR_PAD_LEFT);
                    $year = (isset($matches[4]))?$matches[4]:date("Y");
                    
                    if (checkdate($month,$day,$year))
                    {            
                        $this->date = "$year-$month-$day";                        
                        write_log("debug","Detected MO argurment = ".$this->date);                                                                 
                    }        
                }                       
            }         
            
            if (isset($this->lottery_code) && isset($this->date))
            {
                break;
            }
        }        
        
        $this->MO->argument = ($this->date)?$this->lottery_code." ".$this->date:$this->lottery_code;                
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
            $this->date = $args[1];
            
            //Run scenario for the case that user enter the date
            $this->scenario = "sc_date_entered";             
        }
        
        if ( ! $this->region)
        {
            $this->region = $this->Lottery_model->get_region($this->lottery_code);            
        }
        if ($this->MO->short_code == '7227' && $this->trigger == 'new_mo' && ($this->region == 'MN' || $this->region == 'MT'))
        {
            $this->MO->keyword = 'LUCKY'.$this->region;
        }
        return;
    }    
    
    protected function load_data($template)
    {
        $data['lottery_code'] = $this->lottery_code;
        $data['short_code'] = $this->MO->short_code;
        $data['date'] = date('d/m');
        $data['region'] = $this->region;
        switch ($template)
        {
            case 'result':
                $data['result'] = $this->Evr->lottery['result'];
                break;
            
            case 'lucky':
                $temp = $this->Lottery_model->predict($this->lottery_code,NULL,$this->MO->msisdn,12);//str_pad(rand(0,99),2,'0',STR_PAD_LEFT);                
                $data['mm'] = $temp[3];                
                $data['prediction'] = array_slice($temp,9,3);
                $date = $this->Lottery_model->get_open_date($this->lottery_code,14);                
                $temp = $this->Lottery_model->get_statistic_last_occur_from_date($this->lottery_code,$date);          
                sort($temp);      
                $data['stat'] = implode('-',$temp);                
                break;
                
            case 'hello':
                $codes = $this->Lottery_model->get_open_lotteries($this->region);
                array_walk($codes,create_function('&$str','$str = "XS".$str;'));
                $data['reg_string'] = implode(' hoac ',$codes);
                break;
            
            case 'stat_01':                             
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
                
            case 'stat_02':          
                $date = strtotime('6 months ago');                
                $date = date("Y-m-d",$date);          
                $temp = $this->Lottery_model->get_statistic_min_max($this->lottery_code,$date);
                $data = array_merge($data,$temp);
                break;         
                
            case 'stat_03':     
                $date = strtotime('1 year ago');                
                $date = date("Y-m-d",$date);                       
                $temp = $this->Lottery_model->get_statistic_min_max($this->lottery_code,$date);
                $data = array_merge($data,$temp);
                break;  
                
            case 'stat_04':     
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
        //==============================================================================
        //User nhap ngay de xem
        //==============================================================================
        //Before today
        if ($this->date < date("Y-m-d"))
        {
            $result = $this->Lottery_model->get_full_result($this->lottery_code,$this->date);
            if ($result)
            {
                $this->MT->content = $result;
                $this->MT->send();                              
            }
            else
            {                
                $this->reply('not_found');
            }                
              
            return;           
        }
        //today or later
        elseif ($this->date >= date("Y-m-d"))
        {
            $this->MO->balance = 1;
            $this->reply('confirm_future');

            return;
        }
    }    
    
    protected function sc_after_udf()
    {
        if ($this->date == '' || $this->date == date("Y-m-d"))
        {        
            //$this->reply_string($this->Evr->lottery['result']);        
            $this->reply('result');
            $this->MO->balance -= 10;
        }                   
    }
    
    public function sc_timer()
    {        
        //Khong du cho 1 ngay - cau keo them.
        $this->reply('hello');
        $this->MO->balance -= 1;
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
        $result = $this->Lottery_model->get_last_result($this->lottery_code,'DB');        
        //******************************************************************************
        //Vao ngay mo thuong & chua co giai
        //******************************************************************************        
        if ($this->_is_open_day() && (( ! $result) || (date('Y-m-d',$result->time) < date('Y-m-d'))))
        {            
            $this->reply('lucky');                                
            $this->MO->balance = 11; //10 cho tin kq, 1 cho tin hello
            return;                      
        }
                
        //******************************************************************************
        //Khong phai ngay mo thuong hoac da co ket qua
        //******************************************************************************        
        //Send last result        
        $this->reply_string($result->content);
        $this->MO->balance = 1; //Cho tin hello
    }
    
    protected function sc_7327()
    {           
        $result = $this->Lottery_model->get_last_result($this->lottery_code,'DB');        
        //******************************************************************************
        //Vao ngay mo thuong & chua co giai
        //******************************************************************************
        if ($this->_is_open_day() && (( ! $result) || (date('Y-m-d',$result->time) < date('Y-m-d'))))
        {            
            $this->reply('lucky');
            $this->reply('stat_01');       
            $this->MO->balance = 11; //10 cho tin kq, 1 cho tin hello
            return;                      
        }
                
        //******************************************************************************
        //Khong phai ngay mo thuong hoac da co ket qua
        //******************************************************************************        
        //Send last result        
        $this->reply_string($result->content);
        $this->reply('stat_01');
        $this->MO->balance = 1; //Cho tin hello
    }    
        
    protected function sc_7427()
    {
        $result = $this->Lottery_model->get_last_result($this->lottery_code,'DB');        
        //******************************************************************************
        //Vao ngay mo thuong & chua co giai
        //******************************************************************************
        if ($this->_is_open_day() && (( ! $result) || (date('Y-m-d',$result->time) < date('Y-m-d'))))
        {            
            $this->reply('lucky');
            $this->reply('stat_01');
            $this->reply('stat_02');       
            $this->MO->balance = 11; //10 cho tin kq, 1 cho tin hello
            return;                      
        }
                
        //******************************************************************************
        //Khong phai ngay mo thuong hoac da co ket qua
        //******************************************************************************        
        //Send last result        
        $this->reply_string($result->content);
        $this->reply('stat_01');
        $this->reply('stat_02');         
        $this->MO->balance = 1; //Cho tin hello
    }
    
    protected function sc_7527()
    {
        $result = $this->Lottery_model->get_last_result($this->lottery_code,'DB');        
        //******************************************************************************
        //Vao ngay mo thuong & chua co giai
        //******************************************************************************
        if ($this->_is_open_day() && (( ! $result) || (date('Y-m-d',$result->time) < date('Y-m-d'))))
        {            
            $this->reply('lucky');
            $this->reply('stat_01');
            $this->reply('stat_02');
            $this->reply('stat_03');       
            $this->MO->balance = 11; //10 cho tin kq, 1 cho tin hello
            return;                      
        }
                
        //******************************************************************************
        //Khong phai ngay mo thuong hoac da co ket qua
        //******************************************************************************        
        //Send last result        
        $this->reply_string($result->content);
        $this->reply('stat_01');
        $this->reply('stat_02');
        $this->reply('stat_03');         
        $this->MO->balance = 1; //Cho tin hello
    }    
    
    protected function sc_default()
    {
        $result = $this->Lottery_model->get_last_result($this->lottery_code,'DB');        
        //******************************************************************************
        //Vao ngay mo thuong & chua co giai
        //******************************************************************************
        if ($this->_is_open_day() && (( ! $result) || (date('Y-m-d',$result->time) < date('Y-m-d'))))
        {            
            $this->reply('lucky');
            $this->reply('stat_01');
            $this->reply('stat_02');
            $this->reply('stat_03');
            $this->reply('stat_04');        
            $this->MO->balance = 11; //10 cho tin kq, 1 cho tin hello
            return;                      
        }
                
        //******************************************************************************
        //Khong phai ngay mo thuong hoac da co ket qua
        //******************************************************************************        
        //Send last result        
        $this->reply_string($result->content);
        $this->reply('stat_01');
        $this->reply('stat_02');
        $this->reply('stat_03');
        $this->reply('stat_04');        
        $this->MO->balance = 1; //Cho tin hello     
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
        $this->MO_Queue->set_filter('`balance`>=',10,FALSE); //Khong du cho tin kq
        $this->MO_Queue->set_filter('tag',$this->Evr->lottery['code']);                    
        $this->process_queue();
    }            
    
    protected function __event_timer()
    {        
        //Gui tin hello chi cho tin ko con du balance nhan kq
        $this->MO_Queue->set_filter('balance',1); //Khong du cho tin kq
        
        //Get open list
        $codes = $this->Lottery_model->get_open_lottery(date("Y-m-d"));
        $this->MO_Queue->set_filter('tag',$codes);               
        
        $this->MO_Queue->set_filter('short_code',array('7127','7227','7327','7427','7527','7627','7727','7927'));
        
        $this->process_queue();
    }    
}

/* End of file*/