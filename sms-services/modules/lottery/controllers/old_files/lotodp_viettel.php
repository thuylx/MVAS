<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class LOTODP_viettel extends MY_Controller {
        
    private $sconfig;  
          
    private $lottery_catalog = array();
    private $lottery_code;
    private $date = NULL;
    private $open_days;    
    
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
        write_log('debug',"Service XS initializing");
        
        if ($this->MO->argument == '')
        {
            return;
        }        
        
        $args = explode(' ',$this->MO->argument);
        if ($this->trigger != 'new_mo')
        {
            if ($this->lottery_code != $args[0])
            {
                //Reload lottery code and its relevant info
                $this->lottery_code = $args[0];                
            } 
            if (isset($args[1]))
            {
                $this->date = $args[1];
            }
            return;
        }
                
        //**************************************************************************************
        //New MO comming
        //**************************************************************************************
        $this->lottery_catalog = $this->Lottery_model->get_lottery_catalog();
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
                        
                        //Run scenario for the case that user enter the date
                        $this->scenario = "sc_date_entered";                                          
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
    
    protected function load_data($template)
    {
        switch ($template)
        {
            case 'not_found':
            case 'confirm_01':
                $data['date'] = date("d/m");
                $data['lottery_code'] = $this->lottery_code;
                return $data;         
                   
            case 'confirm_future':
                $data['date'] = date("d/m/y",strtotime($this->date));
                return $data;
                
            case 'prediction':
                $data['predict'] = $this->Lottery_model->predict($this->lottery_code,NULL,$this->MO->msisdn);
                $data['predict_DB'] = $this->Lottery_model->predict($this->lottery_code,'DB',$this->MO->msisdn);
                return $data;      
                      
            case 'sched_02':                
                $data['next_open_date'] = date('d/m',$this->Lottery_model->get_next_day($this->open_days));                
            case 'sched_01':
                $data['lottery_code'] = $this->lottery_code;
                foreach($this->open_days as $day)
                {
                    $day = $day + 1;
                    $weekday[] = ($day==1)?"chu nhat":" thu $day";            
                }
                $weekday = implode(', ',$weekday);
                $data['weekday'] = $weekday;
                return $data;
                
            case "statistic_min_max":
                $data = $this->Lottery_model->get_statistic_min_max($this->lottery_code,30);
                $data['lottery_code'] = $this->lottery_code;
                return $data;

/*                
            case "statistic_sum":
                $data['sum'] = $this->Lottery_model->get_statistic_sum($this->lottery_code,30);    
                return $data;
*/                                                                                                            
        }
    }
    
    protected function sc_date_entered()
    {
        //==============================================================================
        //User nhap ngay de xem
        //==============================================================================
        //Before today
        if ($this->date < date("Y-m-d"))
        {
            $result = $this->Lottery_model->get_loto_result($this->lottery_code,$this->date);
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
            $this->reply_string($this->Evr->lottery['loto']);        
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
            
    /*****************************************************
     * Service scenario for short_code 7027     
     *****************************************************/
    protected function sc_7027()
    {
        $result = $this->Lottery_model->get_last_loto($this->lottery_code);
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
    }
    
    /*****************************************************
     * Service scenario for short_code 7227     
     *****************************************************/
    protected function sc_7227()
    {    
        //******************************************************************************
        //Khong phai ngay mo thuong
        //******************************************************************************        
        if ( ! $this->_is_open_day())
        {
            //send last result
            $result = $this->Lottery_model->get_last_loto($this->lottery_code);
            if ($result)
            {
                $this->reply_string($result->content);            
            }
            else
            {            
                $this->reply('not_found');
            }             
            
            $this->reply('sched_01');
            
            return;
        }                       

        //******************************************************************************
        //Vao ngay mo thuong
        //******************************************************************************                        
        $today_result = $this->Lottery_model->get_today_loto($this->lottery_code);
        //Chua co giai cuoi cung
        if ($today_result === FALSE)
        {
            $this->reply('confirm_01');
            
            $this->MO->balance = 1;
            return;
        }
        
        //Da co giai cuoi cung
        $this->reply_string($today_result->content);
        $this->reply('ads_01');              
    }       
    
    protected function sc_7327()
    {           
        //******************************************************************************
        //Khong phai ngay mo thuong
        //******************************************************************************        
        if ( ! $this->_is_open_day())
        {
            //Send last result
            $result = $this->Lottery_model->get_last_loto($this->lottery_code);
            if ($result)
            {
                $this->reply_string($result->content);            
            }
            else
            {            
                $this->reply('not_found');
            }
            
            //Send statistic                         
            $this->reply('statistic_min_max');
            $this->reply('sched_01');
            
            return;
        }         
        
        //******************************************************************************
        //Vao ngay mo thuong
        //******************************************************************************
        $result = $this->Lottery_model->get_today_loto($this->lottery_code);
        //Chua co ket qua
        if (! $result)
        {
            $this->reply('confirm_01');
            $this->reply('statistic_min_max');
            
            $this->MO->balance = 1;
            return;
        }             
        
        //Da co ket qua
        $this->reply_string($result->content);
        $this->reply('ads_01');
    }
    
    protected function sc_7427()
    {           
        //******************************************************************************
        //Khong phai ngay mo thuong
        //******************************************************************************        
        if ( ! $this->_is_open_day())
        {
            //Send last result
            $result = $this->Lottery_model->get_last_loto($this->lottery_code,'DB');
            if ($result)
            {
                $this->reply_string($result->content);            
            }
            else
            {            
                $this->reply('not_found');
            }
            
            //Send statistic             
            $this->reply('statistic_min_max');
            
            //Send schedule
            $this->reply('sched_02');
            
            $this->MO->balance = 1;
            
            return;
        }         
        
        //******************************************************************************
        //Vao ngay mo thuong
        //******************************************************************************
        $result = $this->Lottery_model->get_today_loto($this->lottery_code);
        //Chua co ket qua
        if (! $result)
        {
            //Send last result
            $last_result = $this->Lottery_model->get_last_loto($this->lottery_code);
            if ($result)
            {
                $this->reply_string($last_result->content);            
            }
            else
            {            
                $this->reply('not_found');
            }       
                  
            //Send confirm
            $this->reply('confirm_01');
            $this->reply('statistic_min_max');
            
            $this->MO->balance = 1;
            return;
        }             
        
        //Da co ket qua
        $this->reply_string($result->content);
        $this->reply('ads_01');
    }    
    
    protected function sc_7527()
    {
        $this->sc_7327();
    }
    
    /*****************************************************
     * Default service scenario     
     *****************************************************/     
    protected function sc_default()
    {
        //******************************************************************************
        //Khong phai ngay mo thuong
        //******************************************************************************        
        if ( ! $this->_is_open_day())
        {
            //Send last result
            $result = $this->Lottery_model->get_last_loto($this->lottery_code);
            if ($result)
            {
                $this->reply_string($result->content);            
            }
            else
            {            
                $this->reply('not_found');
            }                        

            $this->reply('statistic_min_max');
            $this->reply('prediction');
            $this->reply('sched_02');
            
            $this->MO->balance = 1;
            
            return;
        }         
        
        //******************************************************************************
        //Vao ngay mo thuong
        //******************************************************************************
        $result = $this->Lottery_model->get_today_loto($this->lottery_code);        
        //Chua co ket qua
        if (! $result)
        {
            //Send last result
            $last_result = $this->Lottery_model->get_last_loto($this->lottery_code);
            if ($last_result)
            {
                $this->reply_string($last_result->content);            
            }
            else
            {            
                $this->reply('not_found');
            }                        

            $this->reply('confirm_01');
            $this->reply('statistic_min_max');
            $this->reply('prediction');
            
            $this->MO->balance = 1;
            return;
        }             
        
        //Da co ket qua
        $this->reply_string($result->content);
        $this->reply('ads_01');        
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
        $this->process_queue(NULL,NULL,$this->Evr->lottery['code']);
    }            
}

/* End of file*/