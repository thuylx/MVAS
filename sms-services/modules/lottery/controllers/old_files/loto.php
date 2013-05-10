<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class LOTO extends MY_Controller {
        
    private $sconfig;        
    private $date = NULL;
    private $lottery_code = 'MB';
    
    public function __construct()
    {
        parent::__construct();
        $this->sconfig = $this->load->config('loto',TRUE);         
        $this->load->model("Lottery_model");        
    }
    
    /*****************************************************
     * Pre process MO before processing below scenario
     * Called automatically by core class
     *****************************************************/    
    public function __initialize()
    {
        //----------------------------------------------------------------------------
        //Khong phai new_mo => dang xu ly queue
        //----------------------------------------------------------------------------
        if ($this->trigger != "new_mo") //do this for new mo only
        {
            $this->date = $this->MO->argument;
            
            return;
        }
        
        //----------------------------------------------------------------------------
        //Xu ly tham so truyen vao neu la New MO
        //----------------------------------------------------------------------------        
        
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
                $year = (isset($matches[4]))?$matches[4]:date("Y");
                
                if (checkdate($month,$day,$year))
                {            
                    $this->date = "$year-$month-$day";                    
                    write_log("debug","Detected MO argurment = ".$this->date);
                    
                    //Run scenario for the case that user enter the date
                    $this->scenario = "sc_date_entered";
                                        
                    break;
                }        
            }
        }               
                                    
        $this->MO->argument = $this->date; //Save only this allowed argument
                        
        return;    
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
                $data['date'] = date("d/m/y",strtotime($this->date));
                return $data;
            
            case 'not_found':
                $data['date'] = date("d/m/y",strtotime($this->date));
                return $data;
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
            $this->load->model('Result_model');
            $this->Result_model->load('MB',$this->date);            
            $result = $this->Result_model->generate_loto_string();
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
        elseif ($this->date >= date("Y-m-d"))
        {
            $this->MO->balance = 1;
            $this->reply('confirm_future');
            return;
        }                         
    }
    
    /*
    protected function sc_after_udf()
    {        
        if ($this->date == '' || $this->date == date("Y-m-d"))
        {
            $this->reply_string($this->Evr->lottery['loto']);
            $this->MO->balance -= 1;                             
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
            $result = $this->Lottery_model->get_last_loto($this->lottery_code);
            //Return last full result
            $this->reply_string($result->content);            
            return;
        }
        
        //==============================================================================
        //If MO sent after 19:00:00
        //==============================================================================
        $result = $this->Lottery_model->get_today_loto($this->lottery_code);
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
        //==============================================================================
        //Gui truoc 19:00:00
        //==============================================================================                
        if (date("H:i:s",$this->MO->time) < $this->sconfig['time_to_queue'])
        {                                                                  
            //Last result
            //$result = $this->Lottery_model->get_last_loto($this->lottery_code);
            //$this->reply_string($result->content);
            
            //Confirm
            $this->reply('confirm_01');            
            
            //Update balance
            $this->MO->balance = 1;
            return;
        }
        
        //==============================================================================
        //Sau 19:00:00 va chua co kq cuoi cung
        //==============================================================================
        $result = $this->Lottery_model->get_today_loto($this->lottery_code);        

        //Chua co kq hom nay
        if ( ! $result)
        {                        
            //Confirm
            $this->reply('confirm_02');
            
            $this->MO->balance = 1;
            return;            
        }
        
        //Da co ket qua hom nay
        $this->reply_string($result->content);
    }       
    
    protected function sc_7227()
    {
        //==============================================================================
        //Gui truoc 19:00:00
        //==============================================================================                
        if (date("H:i:s",$this->MO->time) < $this->sconfig['time_to_queue'])
        {                                                                  
            //Last result
            $result = $this->Lottery_model->get_last_loto($this->lottery_code);
            $this->reply_string($result->content);
            
            //Confirm
            $this->reply('confirm_01');            
            
            //Update balance
            $this->MO->balance = 1;
            return;
        }
        
        //==============================================================================
        //Sau 19:00:00 va chua co kq cuoi cung
        //==============================================================================
        $result = $this->Lottery_model->get_today_loto($this->lottery_code);        

        //Chua co kq hom nay
        if ( ! $result)
        {                        
            //Confirm
            $this->reply('confirm_02');
            
            $this->MO->balance = 1;
            return;            
        }
        
        //Da co ket qua hom nay
        $this->reply_string($result->content);     
    }
    
    /*****************************************************
     * Default service scenario
     *****************************************************/     
    protected function sc_default()
    {                                 
        $balance = array(
                    '7327'=>1,
                    '7427'=>3,
                    '7527'=>6,
                    '7627'=>14,
                    '7727'=>20,
                    '7927'=>1
                    );
        $this->MO->balance = $balance[$this->MO->short_code];
        //==============================================================================
        //Truoc 19:00:00
        //==============================================================================    
        $result = $this->Lottery_model->get_last_loto($this->lottery_code);
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
}

/* End of file*/