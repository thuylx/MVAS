<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class XS_viettel extends MY_Controller {
        
    private $sconfig;        
    //private $date = NULL;
    private $lottery_code = 'MB';
    
    public function __construct()
    {
        parent::__construct();
        $this->sconfig = $this->load->config('xs',TRUE);
        $this->load->model("Lottery_model");        
    }
    
    public function __parse_new_mo()
    {
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
    
    /*****************************************************
     * Pre process MO before processing below scenario
     * Called automatically by core class
     *****************************************************/    
    public function __initialize()
    {
        $this->MO->date = $this->MO->argument;
        /*
        if ($this->MO->date && $this->trigger == 'new_mo')
        {
            //Will run scenario for the case that user enter the date
            $this->scenario = "sc_date_entered";
        } 
        */                                               
    }
    
    protected function load_data($template)
    {
        switch ($template)
        {
            case 'confirm_01':             
                $data = $this->Lottery_model->get_statistic_min_max('MB',30,NULL,3);                                                             
            case 'confirm_02':
                $data['today'] = date("d/m");                
                return $data;        
                        
            case 'confirm_03':
                $data = $this->Lottery_model->get_statistic_min_max('MB',30,NULL,3);
                $data['balance'] = $this->get_total_balance($this->MO->msisdn,$this->MO->short_code) + $this->MO->balance;
                return $data;
                
            case 'confirm_future':
                $data['date'] = date("d/m/y",strtotime($this->MO->date));
                return $data;
            
            case 'not_found':
                $data['date'] = date("d/m/y",strtotime($this->MO->date));
                return $data;                                  
        }
    }    
    
    protected function sc_date_entered()
    {
        //==============================================================================
        //User nhap ngay de xem
        //==============================================================================
        //Before today
        if ($this->MO->date < date("Y-m-d"))
        {
            $result = $this->Lottery_model->get_full_result($this->lottery_code,$this->MO->date);
            if ($result)
            {
                $this->reply_string($result);                                    
            }
            else
            {
                $this->reply('not_found'); 
            }                
            return;           
        }
        //today or latter than today
        elseif ($this->MO->date >= date("Y-m-d"))
        {
            $this->MO->balance = 1;
            $this->reply('confirm_future');
            
            return;
        }                       
    }
    
    /*
    protected function sc_after_udf()
    {
        if ($this->Evr->lottery['prize'] == 'DB')
        {
            if ($this->MO->date == '' || $this->MO->date == date("Y-m-d"))
            {
                $this->reply_string($this->Evr->lottery['result']);
                $this->MO->balance -= 1;
            }
            return;            
        }
    }    
     * 
     */
            
    /*****************************************************
     * Service scenario for short_code 7027     
     *****************************************************/
    protected function sc_7027()
    {                        
        //==============================================================================
        //If MO sent before 19:00:00
        //==============================================================================        
        if (date("H:i:s",$this->MO->time) < $this->sconfig['time_to_queue'])
        {
            $result = $this->Lottery_model->get_last_result($this->lottery_code,'DB');            
            //Return last full result
            $this->reply_string($result->content);            
            return;
        }
        
        //==============================================================================
        //If MO sent after 19:00:00
        //==============================================================================
        $result = $this->Lottery_model->get_today_result($this->lottery_code,'DB');
        //Da co ket qua
        if ($result)
        {
            $this->reply_string($result->content);
            return;            
        }
        //Chua co ket qua
        $this->MO->balance = 1;
    }    
    
    /*****************************************************
     * Service scenario for short_code 7127     
     *****************************************************/
    protected function sc_7127()
    {            
        $this->sc_7027();
    }       
    
    protected function sc_7227()
    {
        //Do nothing since it will be redirect to LUCKY
    }         
    
    protected function sc_7527()    
    {
        //Do nothing since it will be redirect to LUCKY
    }
    
    /*****************************************************
     * Default service scenario
     *****************************************************/     
    protected function sc_default()
    {                                 
        $balance['default'] = array(
                    '7327'=>1,
                    '7427'=>2,
                    //'7527'=>5,
                    '7627'=>5,
                    '7727'=>7,
                    '7927'=>1
                    );
        /*
        $balance['VMS'] = array(
                    '7327'=>3,
                    '7427'=>4,
                    '7527'=>10,
                    '7627'=>14,
                    '7727'=>21,
                    '7927'=>1
                    );
        */        
        if ( isset($balance[$this->MO->smsc_id]))
        {
            $this->MO->balance = $balance[$this->MO->smsc_id][$this->MO->short_code];
            
        }         
        else
        {
            $this->MO->balance = $balance['default'][$this->MO->short_code];
        }   
        
        //==============================================================================
        //Truoc 19:00:00
        //==============================================================================    
        $result = $this->Lottery_model->get_last_result($this->lottery_code,'DB');
        if (date("H:i:s",$this->MO->time)<$this->sconfig['time_to_queue'])
        {
            //Gui ket qua moi nhat
            $this->reply_string($result->content);            
            $this->reply('confirm_03');            
            return;
        }
        //------------------------------------------------------------------------------
        
        //==============================================================================
        //Sau 19:00:00
        //==============================================================================             
        //Chua co kq hom nay
        if ( (! $result) || (date('Y-m-d',$result->time) < date('Y-m-d',time())))
        {            
            //Confirm
            $this->reply('confirm_02');                 
            return;            
        }
        //------------------------------------------------------------------------------
        //Da co ket qua hom nay
        $this->reply_string($result->content);           
    }
    
    /*
    public function __event_after_udf()
    {        
        if ($this->Evr->lottery['prize'] == 'DB')
        {
            $this->process_queue();
            return;
        }
    }
     * 
     */
}

/* End of file*/