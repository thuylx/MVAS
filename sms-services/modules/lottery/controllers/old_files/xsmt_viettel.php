<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class XSMT_viettel extends MY_Controller
{
    private $region = 'MT';
    private $date = NULL;
    
    //private $results; //Store current result    
    private $sconfig;
    
    public function __construct()
    {
        parent::__construct();
        $this->sconfig = $this->load->config('xsmn',TRUE);
        $this->load->model("Lottery_model");        
    }
    
    public function __parse_new_mo()
    {        
        $args = explode(' ',$this->MO->argument);
        $this->MO->argument = NULL;
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
                        $date = "$year-$month-$day";
                        $this->MO->date = $date;  
                        $this->MO->argument = $date;  
                        if ($date != date('Y-m-d'))
                        {
                            $this->scenario = "sc_date_entered";                            
                        }                                                                                                                
                        write_log("debug","Detected MO argurment = ".$this->MO->date);                        
                        break;                
                    }        
                }                       
            }                     
        }                       
    }
    
    /*****************************************************
     * Pre process MO before processing below scenario
     * Called automatically by core class, suitable for 
     *  standalize argument in MO content before processing
     *****************************************************/    
    public function __initialize()
    {
        //Process parameter
        if ($this->MO->argument == '')
        {            
            return;
        }        
                
        $this->MO->date = $this->MO->argument;                                 
    }   
    
    /*******************************************************
     * Load data for template
     *******************************************************/    
    protected function load_data($template)
    {
        $data = array();        
        $time = ($this->MO->date == "")?time():strtotime($this->MO->date);
        $data['date'] = date('d/m',$time);        
        
        $data['short_code'] = $this->MO->short_code;              
        return $data;        
    }      
    
    protected function sc_date_entered()
    {
        $results = $this->Lottery_model->get_regional_result($this->region,$this->MO->date);
        $pending = FALSE;
        foreach($results as $result)
        {
            if ($result)
            {
                $this->reply_string($result);                
            }
            else
            {
                $this->MO->balance = $this->MO->balance + 1;
                $pending = TRUE; 
            }            
        }        
        
        //Chua du giai
        if ($pending)
        {
            $this->reply('confirm');
        }
        /*
        //Da du giai
        else
        {
            $this->reply('adv');
        } 
        */               
    }   
    
    protected function sc_after_udf()
    {
        if ($this->MO->date == '' || $this->MO->date == date("Y-m-d"))
        {        
            //$this->reply_string($this->Evr->lottery['result']);
            $this->reply('result',array('result'=>$this->Evr->lottery['result']));
            $this->MO->balance = $this->MO->balance - 1;
        }
    }    
    
    protected function sc_7027()
    {
        $this->reply('short_code_incorrect');
    }
    
    protected function sc_7127()
    {
        $this->sc_7027();
    } 
    
    protected function sc_7227()
    {
        $this->sc_7027();
    }     
    
    protected function sc_7327()
    {
        $this->MO->date = date("Y-m-d",strtotime('yesterday'));
        $results = $this->Lottery_model->get_regional_result($this->region,$this->MO->date);
        $pending = FALSE;
        foreach($results as $result)
        {
            if ($result)
            {
                $this->reply('result',array('result'=>$result));                
            }
            else
            {
                $this->MO->balance = $this->MO->balance + 1;
                $pending = TRUE;
            }            
        }        
        
        //Chua du giai
        if ($pending)
        {
            $this->reply('confirm');
        }        
        //Da du giai
        /*
        else
        {
            $this->reply('adv');
        } 
        */                                 
    }      
    
    protected function sc_7427()
    {
        $this->sc_7327();
    }
    
    protected function sc_default()
    {        
        switch ($this->MO->short_code)
        {
            case '7527':
                $balance = 1; //today included
                break;
                
            case '7627':
                $balance = 1; //today included
                break;

            case '7727':
                $balance = 2; //today included
                break;                                    
                
            default:
                $balance = 1; //today only                  
        }                         
        
        $results = $this->Lottery_model->get_regional_result($this->region);
        $lotteries = array_keys($results);
        $pending = array();
        $sieu_dep = array();    
        $mt_count = 0; //ghi so MT gui di, neu con du thi them tin hello hom sau        
        foreach($results as $code=>$result)
        {
            if ($result)
            {
                $this->reply('result', array('result'=>$result));      
                $mt_count++;      
            }
            else
            {
                $this->MO->balance = $this->MO->balance + 1;
                //Tinh toan sieu dep        
                $temp = $this->Lottery_model->predict($code,NULL,$this->MO->msisdn,12);//str_pad(rand(0,99),2,'0',STR_PAD_LEFT);                                    
                $prediction = array_slice($temp,9,3);
                sort($prediction);      
                $sieu_dep[$code] = $code.": ".implode('-',$prediction);
                
                //Tinh toan chi tiet
                $chi_tiet[$code] = "TK$code";                                                    
                $pending[] = $code;
            }            
        }    
        
        //Chua du giai
        if (count($pending)>0)
        {
            $data = array();
            $data['short_code'] = $this->MO->short_code;            
            $data['sieu_dep'] = implode("\n",$sieu_dep);
            $data['chi_tiet'] = implode(",",array_slice($chi_tiet,0,1));                
            $data['code'] = implode(', ',$pending);
            $data['date'] = date('d/m');                        
            
            $this->reply('lucky',$data);
            $mt_count++;
            
            if ($this->MO->short_code >= '7627')
            {
                $data = array();

                foreach($lotteries as $code)
                {
                    $date = $this->Lottery_model->get_open_date($code,14);                
                    $temp = $this->Lottery_model->get_statistic_last_occur_from_date($code,$date);          
                    sort($temp);      
                    $data['chua_ve'][] = $code.": ".implode('-',$temp);                
                }

                $data['chua_ve'] = implode("\n",$data['chua_ve']);
                $data['yesterday'] = date('d/m',strtotime('yesterday'));       

                if (isset($balance))
                {
                    $data['num_days'] = $balance;                
                }                            

                $this->reply('stat',$data);
                $mt_count++;                
            }
        }
        
        if (isset($balance) && $balance > 0)
        {
            for ($i=1;$i<$balance;$i++)
            {
                $last_date = date('Y-m-d',strtotime("+$i day"));
                $codes = $this->Lottery_model->get_open_lotteries($this->region,$last_date);                
                $this->MO->balance = $this->MO->balance + count($codes);            
            }             
        }
                                
        if ($this->MO->short_code == '7427' || ($this->MO->short_code == '7327' && $mt_count < $this->max_mt_num()))
        {
            $this->MO->balance = $this->MO->balance + 1; //Cho tin hello
        }                                
    }
    
    protected function sc_timer()
    {
        $data['short_code'] = $this->MO->short_code;
        $data['lotteries'] = $this->Lottery_model->get_open_lotteries($this->region);
        $data['lottery_nums'] = count($data['lotteries']);
        $data['lotteries'] = implode(', ',$data['lotteries']);        
        $this->reply('hello',$data);
        $this->MO->balance = 0;        
    }
    
    protected function __event_timer()
    {
        //Gui tin hello cho 7327,7427
        $this->MO_Queue->set_filter('balance',1); //Khong du cho tin kq        
        $this->MO_Queue->set_filter('short_code',array('7327','7427'));        
        $this->process_queue();
    }
    
}

/* End of file*/