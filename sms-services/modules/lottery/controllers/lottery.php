<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Lottery extends MY_Controller 
{
    public $cache = array();        
    
    public function __construct() {
        parent::__construct();
        $this->load->model("Lottery_model"); 
        //if ($this->scp->service == 'XSTT') $this->lottery_code = 'MB';
    }
    
    public function preprocess_XS()
    {
        $this->MO->args = array('date'=>NULL);
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
                    $this->MO->argument = "$year-$month-$day";
                    $this->MO->args['date'] = "$year-$month-$day";                    
                             
                    write_log("debug","Detected MO argurment = ".$this->MO->argument);                              
                    break;
                }        
            }
        }                
    }   
    
    /*
    public function load_min_max($lottery_code,$date,$prize,$count)
    {
        $date = strtotime($date);                
        $date = date("Y-m-d",$date);                       
        if (strtolower($prize) == 'all') $prize = NULL;
        $temp = $this->Lottery_model->get_statistic_min_max($lottery_code,$date,$prize,$count);            
        return $temp;        
    }
     * 
     */
    
    /**
     * Thong ke loto MB 1 thang: ve nhieu, ve it
     * @return array (bao gom 2 mang max va min) 
     */
    public function load_tk_loto_1_thang()
    {
        $date = strtotime('1 month ago');                
        $date = date("Y-m-d",$date);                       
        $temp = $this->Lottery_model->get_statistic_min_max('MB',$date,NULL,3);            
        return $temp;         
    }
    
    public function load_tk_ve_lien_tiep()
    {
        $temp = $this->Lottery_model->get_succession('MB');                
        $succession  = array();
        foreach($temp as $key=>$value)
        {
            $succession[]="$key($value)";
        }
        return $succession;
    }
    
    /**
     * Thong ke giai dac biet XSMB trong 2 nam: ve nhieu, ve it
     * @return array (bao gom 2 mang max va min) 
     */
    public function load_tk_db_2_nam()
    {
        $date = strtotime('2 years ago');                
        $date = date("Y-m-d",$date);                       
        $temp = $this->Lottery_model->get_statistic_min_max('MB',$date,'DB',3);            
        return $temp;          
    }
        
    /**
     * 
     * @return type 
     */
    public function load_balance()
    {
        return $this->get_total_balance($this->MO->msisdn,$this->MO->short_code) + $this->MO->balance -1;
    }   
    
    public function load_today_result($prize = 'DB')
    {
        if ( ! key_exists('today_result.'.$prize, $this->cache))
        {
            $temp = $this->Lottery_model->get_today_result('MB',$prize);
            $this->cache['today_result.'.$prize] = $temp->content;            
        }
        return $this->cache['today_result.'.$prize];
    }
    
    public function load_last_today_result()
    {        
        if ( ! key_exists('last_today_result', $this->cache))
        {
            $this->cache['last_today_result'] = $this->Lottery_model->get_last_today_result('MB');
        }        
        return $this->cache['last_today_result'];
    }
    
    public function load_today_loto()
    {
        if ( ! key_exists('today_loto', $this->cache))
        {
            $this->cache['today_loto'] = $this->Lottery_model->get_today_loto('MB');
        }        
        return $this->cache['today_loto'];        
    }
    
    public function load_last_result()
    {
        if ( ! key_exists('last_result', $this->cache))
        {
            $this->cache['last_result'] = $this->Lottery_model->get_last_result('MB');
        }        
        return $this->cache['last_result'];        
    }
    
    public function load_last_loto_result()
    {
        if ( ! key_exists('last_loto_result', $this->cache))
        {
            $this->cache['last_loto_result'] = $this->Lottery_model->get_last_loto('MB');
        }        
        return $this->cache['last_loto_result'];            
    }
    
    public function _load_nice_numbers()
    {
        if (date('H:i:s') < '19:30:00')
        {
            $date = date('Y-m-d');
        }           
        else
        {
            $date = date('Y-m-d',strtotime('tomorrow'));
        }        
        
        if ( ! key_exists('nice_numbers', $this->cache))
        {
            $this->cache['nice_numbers'] = $this->Lottery_model->predict('MB',NULL,$this->MO->msisdn,12,$date);//str_pad(rand(0,99),2,'0',STR_PAD_LEFT);
        }        
    }    
    
    public function load_lucky_number()
    {
        $this->_load_nice_numbers();
        return $this->cache['nice_numbers'][3];         
    }
    
    public function load_lucky_numbers()
    {
        $this->_load_nice_numbers();        
        return array_slice($this->cache['nice_numbers'],3,3);        
    }
    
    public function load_prediction()
    {        
        $this->_load_nice_numbers();        
        return array_slice($this->cache['nice_numbers'],6,3);
    }
    
    public function load_prediction_DB()
    {
        return $this->Lottery_model->predict('MB','DB',$this->MO->msisdn,10);                
    }
    
    public function load_vip_numbers()
    {
        $this->_load_nice_numbers();
        $vips = array_slice($this->cache['nice_numbers'],0,2);        
        return $vips;         
    }
    
    public function load_bach_thu()
    {
        $this->_load_nice_numbers();
        $vip = array_slice($this->cache['nice_numbers'],2,1);        
        return $vip[0];          
    }
    
    public function load_nice_numbers()
    {
        $this->_load_nice_numbers();
        return array_slice($this->cache['nice_numbers'],9,3);
    }
    
    public function load_tk_10_ngay_chua_ve()
    {
        $temp = $this->Lottery_model->get_statistic_last_occur_num_days('MB',10);                          
        sort($temp);      
        return $temp;           
    }                
    
    public function load_cal_days_in_month()
    {        
        return cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y'));
    }   
    
    public function load_tk_db_1_nam_chua_ve()
    {
        $date = date('Y-m-d',strtotime('1 year ago'));
        $temp = $this->Lottery_model->get_statistic_last_occur_from_date('MB',$date,TRUE);                  
        sort($temp);
        return $temp;        
    }
    
    public function load_nhi_hop()
    {                
        return $this->Lottery_model->get_loto_repeated_pair('MB', 30, 3);
    }
    
    public function load_lau_nhat_chua_ve_db()
    {        
        $temp = $this->Lottery_model->get_statistic_last_occur_longest('MB',5,TRUE);        
        $stat = array();
        foreach($temp as $result=>$date)                
        {
            $stat[] = array(
                'result' => $result,
                'date' => date('d/m/y',strtotime($date))
            );
        }          
        return $stat;
    }
    
    public function load_lau_nhat_chua_ve()
    {
        $temp = $this->Lottery_model->get_statistic_last_occur_longest('MB',3,FALSE);        
        $stat = array();
        foreach($temp as $result=>$date)                
        {
            $stat[] = array(
                'result' => $result,
                'date' => date('d/m/y',strtotime($date))
            );
        }        
        return $stat;
        
    }
    
    public function load_longest_first_db()
    {
        $temp = $this->Lottery_model->get_last_occur_db_first('MB');
        $temp = array_slice($temp, 0, 3);
        $return = array();
        foreach ($temp as $item)
        {
            $item['date'] = date('d/m',strtotime($item['date']));
            $return[] = $item;
        }         
        
        return $return;
    }
    
    public function load_longest_last_db()
    {
        $temp = $this->Lottery_model->get_last_occur_db_last('MB');
        $temp = array_slice($temp, 0, 3);
        $return = array();
        foreach ($temp as $item)
        {
            $item['date'] = date('d/m',strtotime($item['date']));
            $return[] = $item;
        }         
        
        return $return;
    }    
    
    public function load_full_result($date = NULL)
    {
        if ($date) $date = date('Y-m-d');
        $this->load->model('Result_model');
        $this->Result_model->load('MB',$date);            
        $result = $this->Result_model->generate_result_string();                 
    }
    
    public function load_loto_result($date = NULL)
    {
        if ($date) $date = date('Y-m-d');
        $this->load->model('Result_model');
        $this->Result_model->load('MB',$date);            
        $result = $this->Result_model->generate_loto_string();                 
    }    
    
    public function update_lottery_result()
    {
        $this->load->model("Result_model");                
        
        //***************************************************************************
        // CHUAN HOA DU LIEU DAU VAO
        //***************************************************************************
        // Tin sau chuan hoa phai co dinh dang nhu vi du sau, tuy theo nhan kq ve nhu the nao phai
        // chuan hoa cho phu hop:
        // [A-Z]{2,3} +[0-9]{1,2}[\/\-][0-9]{1,2} + (= <ma tinh> <ngay thang>)
        // ( +([1-9]|DB) *: *([0-9]+( *- *[0-9]+)*))+ (= lien tiep cac cap <giai>:<kq1>-<kq2> ... co the co dau cach)
        // .* (=doan quang cao bat ky them vao cuoi ko care)
        
        $this->MO->argument = strtoupper($this->MO->argument);
        
        $searches = array(
                            '/DB:\.\.\./', //Loai bo phan 'DB:...' tin giai 7 XSMB cua 8502
                            '/([A-Z]{2,3} [0-9]{1,2}\/[0-9]{1,2}):\s/', //Loai bo dau : sau ngay thang tin XSMN tu 6289
                            '/\sDB6:([0-9]+)/' //Thay the DB6 bang DB o tin kq XSMN tu 6289
                            );
        $replacements = array(
                            '',
                            '$1 ',
                            ' DB:$1'
                            );
                                    
        do
        {
            $this->MO->argument = preg_replace($searches,$replacements,$this->MO->argument,-1,$count);                       
        }while ($count);        
        
        //*************************************************************************************
        // CAP NHAT KET QUA
        //*************************************************************************************
        if ( ! $this->Result_model->parse_result_string($this->MO->argument))
        {
            write_log('error',"Parse lottery result string failed. MO id = ".$this->MO->id);
            $this->MO->status = 'failed';
            //$evr['updated'] = FALSE;
            //$this->Evr->lottery = $evr;
            return FALSE;
        }
        $evr = array();
        $evr['code'] = $this->Result_model->code;
        $prize = $this->Result_model->get_max_prize();              
        $evr['prize'] = $prize;
        
        $updated = $this->Lottery_model->is_cached($this->Result_model->code,$prize,$this->Result_model->date);                
        if ($updated)
        {
            write_log('error',"Lottery Prize updated already, discarded MO id=".$this->MO->id);
            $this->MO->status = 'discarded';
            //$evr['updated'] = FALSE;
            //$this->Evr->lottery = $evr;
            return FALSE;
        }        
        
        $result = $this->Result_model->generate_result_string();
        $this->Lottery_model->cache($this->Result_model->code,$prize,$result);        
        $evr['result'] = $result;      
        
        if ($prize=='DB')
        {
            //Cache loto result
            $loto = $this->Result_model->generate_loto_string();            
            $this->Lottery_model->cache($this->Result_model->code,'loto',$loto);
            
            //Update lottery result
            $this->Result_model->insert();
            
            //Store environment param for other services
            $evr['loto'] = $loto;
        }
        
        //$evr['updated'] = TRUE;        
        
        //Get region information for service XSMN, XSMT
        $evr['region'] = $this->Lottery_model->get_region($this->Result_model->code);
        
        $this->Evr->lottery = $evr;
        
        //$this->Event->raise("after_udf");                
    }   
}

/* End of file*/