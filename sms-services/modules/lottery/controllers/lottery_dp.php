<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Lottery_dp extends MY_Controller 
{
    public $cache = array();        
    
    public function __construct() {
        parent::__construct();
        $this->load->model("Lottery_model"); 
        //if ($this->scp->service == 'XSTT') $this->lottery_code = 'MB';
    }
    
    public function preprocess_XSDP()
    {
        $this->MO->args = array();        
        $args = explode(' ',$this->MO->argument);
                
        $this->MO->args['lottery_code'] = $args[0];
        $this->MO->tag = $args[0];                      
     
        if (isset($args[1]))
        {
            preg_match('/^([0-9]{1,2})[^0-9a-zA-Z]([0-9]{1,2})([^0-9a-zA-Z]([0-9]{1,4}))?$/',$args[1],$matches);
            if (count($matches) > 0)
            {
                $day = str_pad($matches[1],2,'0',STR_PAD_LEFT);
                $month = str_pad($matches[2],2,'0',STR_PAD_LEFT);
                $year = date("Y");                    
                $year = (isset($matches[4]))?str_pad($matches[4],4,$year,STR_PAD_LEFT):$year;

                if (checkdate($month,$day,$year))
                {            
                    $this->MO->args['date'] = "$year-$month-$day";  
                    write_log("debug","Detected MO argurment = ".$this->MO->args['date']);                                                                 
                }        
            }                       
        }               
        
        $this->MO->argument = implode(' ',  $this->MO->args);     
        if (! isset($this->MO->args['date'])) $this->MO->args['date'] = NULL;                
    }   
    
    public function preprocess_LOTODP()
    {
        $this->preprocess_XSDP();
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
                    
    public function load_today_result($prize = 'DB')
    {
        if ( ! key_exists('today_result.'.$prize, $this->cache))
        {
            $temp = $this->Lottery_model->get_today_result($this->MO->args['lottery_code'],$prize);
            $this->cache['today_result.'.$prize] = $temp->content;            
        }
        return $this->cache['today_result.'.$prize];
    }
    
    public function load_last_today_result()
    {
        if ( ! key_exists('last_today_result', $this->cache))
        {
            $this->cache['last_today_result'] = $this->Lottery_model->get_last_today_result($this->MO->args['lottery_code']);
        }           
        
        if ($this->cache['last_today_result'])
        {
            return $this->cache['last_today_result'];
        }
        else
        {
            return array('code'=>FALSE, 'content'=>'chua co', 'time' => 'chua co');
        }
    }
    
    public function load_today_loto()
    {
        if ( ! key_exists('today_loto', $this->cache))
        {
            $this->cache['today_loto'] = $this->Lottery_model->get_today_loto($this->MO->args['lottery_code']);
        }        
        return $this->cache['today_loto'];        
    }
    
    public function load_last_result()
    {
        if ( ! key_exists('last_result', $this->cache))
        {
            $this->cache['last_result'] = $this->Lottery_model->get_last_result($this->MO->args['lottery_code']);
        }        
        return $this->cache['last_result'];        
    }
    
    public function load_last_loto()
    {
        if ( ! key_exists('last_loto_result', $this->cache))
        {
            $this->cache['last_loto_result'] = $this->Lottery_model->get_last_loto($this->MO->args['lottery_code']);
        }        
        return $this->cache['last_loto_result'];            
    }
    
    public function preprocess_VIPDP()
    {
        $this->MO->args = array();
        
        //$lottery_catalog = $this->Lottery_model->get_lottery_catalog();
        $args = explode(' ',$this->MO->argument);
        
        //Detect lottery_code
        $this->MO->args['lottery_code'] = $args[0];
        $this->MO->tag = $args[0];                      
   
        if (isset($args[1]))
        {
            preg_match('/^([0-9]{1,2})[^0-9a-zA-Z]([0-9]{1,2})([^0-9a-zA-Z]([0-9]{1,4}))?$/',$args[1],$matches);
            if (count($matches) > 0)
            {
                $day = str_pad($matches[1],2,'0',STR_PAD_LEFT);
                $month = str_pad($matches[2],2,'0',STR_PAD_LEFT);
                $year = date("Y");                    
                $year = (isset($matches[4]))?str_pad($matches[4],4,$year,STR_PAD_LEFT):$year;

                if (checkdate($month,$day,$year))
                {            
                    $this->MO->args['date'] = "$year-$month-$day";  
                    write_log("debug","Detected MO argurment = ".$this->MO->args['date']);                                                                 
                }        
            }                       
        }           
        $this->MO->argument = implode(' ',  $this->MO->args);                
    }
    
    public function preprocess_MMDP()
    {
        $this->preprocess_VIPDP();
    }
    
    public function preprocess_SCDP()
    {
        $this->preprocess_VIPDP();
    }
    
    public function preprocess_TKDP()
    {
        $this->preprocess_VIPDP();
    }    
    
    public function _load_nice_numbers()
    {    
        //Load arguments into $this->MO->args[]
        if ( ! isset($this->MO->args['lottery_code'])) $this->preprocess_VIPDP();
        if ( ! key_exists('nice_numbers', $this->cache))
        {
            if (! isset($this->MO->args['date']))
            {
                $this->load_target_date();
            }
            $date = $this->MO->args['date'];
            
            $this->cache['nice_numbers'] = $this->Lottery_model->predict($this->MO->args['lottery_code'],NULL,$this->MO->msisdn,12,$date);//str_pad(rand(0,99),2,'0',STR_PAD_LEFT);
        }        
    }
    
    public function load_target_date()
    {
        if (! isset($this->MO->args['date']))
        {
            $region = $this->load_region();
            //Detect target date
            $open_time = ($region == 'MN')?"16:30:00":"17:30:00";
            if ($this->load_is_open_day() && date('H:i:s') <= $open_time)
            {
                $this->MO->args['date'] = date('Y-m-d');
            }
            else
            {
                //Load information
                $open_days = $this->Lottery_model->get_open_days($this->MO->args['lottery_code']);                    
                $this->MO->args['date'] = $this->Lottery_model->get_next_day($open_days);
            }            
        }    
        
        return date('d/m',  strtotime($this->MO->args['date']));
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
        if ( ! isset($this->MO->args['lottery_code'])) $this->preprocess_VIPDP();
        return $this->Lottery_model->predict($this->MO->args['lottery_code'],'DB',$this->MO->msisdn,10);                
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
    
    public function load_tk_15_lan_chua_ve()
    {
        $date = $this->Lottery_model->get_open_date($this->MO->args['lottery_code'],14);
        $temp = $this->Lottery_model->get_statistic_last_occur_from_date($this->MO->args['lottery_code'],$date);        
        sort($temp);      
        return $temp;           
    }                
        
    public function load_tk_ve_lien_tiep()
    {
        $temp = $this->Lottery_model->get_succession($this->MO->args['lottery_code']);                
        $data = array();
        foreach($temp as $key=>$value)
        {
            $data[]=array(
                'loto'=>$key,
                'count'=>$value
            );
        }
        return $data;
    }
    
    public function load_tk_db_1_nam_chua_ve()
    {
        $date = date('Y-m-d',strtotime('1 year ago'));
        $temp = $this->Lottery_model->get_statistic_last_occur_from_date($this->MO->args['lottery_code'],$date,TRUE);                  
        sort($temp);
        return $temp;        
    }
    
    public function load_tk_nhi_hop()
    {                
        $date = strtotime('6 months ago');                
        $date = date("Y-m-d",$date);            
        return $this->Lottery_model->get_loto_repeated_pair($this->MO->args['lottery_code'],$date);        
    }
    
    public function load_lau_nhat_chua_ve_db()
    {        
        $temp = $this->Lottery_model->get_statistic_last_occur_longest($this->MO->args['lottery_code'],5,TRUE);        
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
        $temp = $this->Lottery_model->get_statistic_last_occur_longest($this->MO->args['lottery_code'],3,FALSE);        
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
        $temp = $this->Lottery_model->get_last_occur_db_first($this->MO->args['lottery_code']);
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
        $temp = $this->Lottery_model->get_last_occur_db_last($this->MO->args['lottery_code']);
        $temp = array_slice($temp, 0, 3);
        $return = array();
        foreach ($temp as $item)
        {
            $item['date'] = date('d/m',strtotime($item['date']));
            $return[] = $item;
        }         
        
        return $return;
    }    
    
    public function load_result_by_date()
    {
        $date = $this->MO->args['date'];
        $this->load->model('Result_model');
        $this->Result_model->load($this->MO->args['lottery_code'],$date);            
        return $this->Result_model->generate_result_string();                 
    }
    
    public function load_loto_by_date()
    {
        $date = $this->MO->args['date'];
        $this->load->model('Result_model');
        $this->Result_model->load($this->MO->args['lottery_code'],$date);            
        return $this->Result_model->generate_loto_string();                 
    }    
    
    public function load_open_lotteries()
    {
        if ( ! key_exists('open_lotteries', $this->cache))
        {
            $this->cache['open_lotteries'] = $this->Lottery_model->get_open_lotteries($this->load_region());
        }
        return $this->cache['open_lotteries'];
    }
    
    public function load_open_lotteries_MN()
    {
        return $this->Lottery_model->get_open_lotteries('MN');
    }
    
    public function load_open_lotteries_MT()
    {
        return $this->Lottery_model->get_open_lotteries('MT');
    }
    
    public function load_open_lotteries_count()
    {
        return count($this->load_open_lotteries());
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
            write_log('error',"Parse lottery result string failed.");
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

    /**
    public function set_queue_filter_kqdb($prize)
    {                        
        if ($prize != 'DB')
        {
            //------------------------------------------------------------------------------------
            // Tra tin truc tiep cho dich vu XSTT
            // - Chi tra nhung giai phu hop cho dau so phu hop 
            // - Chi tra cho tin XSTT gui hom nay            
            //------------------------------------------------------------------------------------
            $where = array();
            $config = $this->config->item('return_prizes');            
            foreach ($config as $smsc_id => $return_prizes)
            {
                //Viettel            
                $short_codes = array();
                foreach ($return_prizes as $short_code => $prizes)
                {
                    if (in_array($prize,$prizes))
                    {
                        $short_codes[] = "'".$short_code."'";
                    }
                }            
                if ($short_codes)
                {
                    $short_codes = implode(',', $short_codes);
                    if (strtolower($smsc_id) != 'default')
                    {
                        $where[] = "(`smsc_id` = '$smsc_id' AND `short_code` IN ($short_codes))";                        
                    }                  
                    else
                    {
                        $temp = array();
                        foreach (array_keys($config) as $item)
                        {
                            if ($item != 'Default')
                            {
                                $item = "'$item'";
                                $temp[] = $item;
                            }
                        }                        
                        $temp = ($temp)?"`smsc_id` NOT IN (".implode(',',$temp).") AND ":'';
                        $where[] = "($temp `short_code` IN ($short_codes))";
                    }
                }                
            }    
            if ($where) $this->queue_db->where("(". implode(' OR ', $where) .")");
            
            //------------------------------------------------------------------------------------
            //Tin hom nay
            $this->queue_db->where('date(from_unixtime(`time`))',date('Y-m-d'));
            
            //------------------------------------------------------------------------------------
            //Dich vu XSTT
            $keywords = $this->get_keywords('XSTT');
            $this->queue_db->where_in('keyword',$keywords);
            
            //------------------------------------------------------------------------------------
            // Gop nhom theo so thue bao
            $this->queue_db->group_by('msisdn');
        }
        
        elseif ($prize == 'DB')
        {            
            //------------------------------------------------------------------------------------
            // Tra tin KQ cho dich vu XS,KQ va cac dich vu XSTT,VIP,MM,TK,SC hom nay
            //------------------------------------------------------------------------------------            
            
            //Dich vu dau cao
            $keywords = $this->get_keywords('XSTT');
            $keywords = array_merge($keywords,$this->get_keywords('VIP'));
            $keywords = array_merge($keywords,$this->get_keywords('MM'));
            $keywords = array_merge($keywords,$this->get_keywords('SC'));
            $keywords = array_merge($keywords,$this->get_keywords('TK'));            
            
            array_walk($keywords, create_function('&$str', '$str="\'".$str."\'";'));
            $keywords = implode(',', $keywords);
            $this->queue_db->where("(`keyword` IN ($keywords) AND date(from_unixtime(`time`)) = '".date('Y-m-d')."')");
            
            //Dich vu XS, KQ
            $keywords = $this->get_keywords('XS');
            $keywords = array_merge($keywords,$this->get_keywords('KQ'));
            array_walk($keywords, create_function('&$str', '$str="\'".$str."\'";'));
            $keywords = implode(',', $keywords);
            $this->queue_db->or_where("(`keyword` IN ($keywords))");
        }        
    }
    
    public function set_queue_filter_loto()
    {
        //------------------------------------------------------------------------------------
        // Tra tin Loto cho dich vu XS,KQ va cac dich vu XSTT,VIP,MM,TK,SC hom nay
        //------------------------------------------------------------------------------------            

        //Dich vu dau cao
        $keywords = $this->get_keywords('VIP');        
        $keywords = array_merge($keywords,$this->get_keywords('MM'));
        $keywords = array_merge($keywords,$this->get_keywords('SC'));
        $keywords = array_merge($keywords,$this->get_keywords('TK'));            

        array_walk($keywords, create_function('&$str', '$str="\'".$str."\'";'));
        $keywords = implode(',', $keywords);
        $this->queue_db->where("(`keyword` IN ($keywords) AND date(from_unixtime(`time`)) = '".date('Y-m-d')."')");

        //Dich vu LOTO
        $keywords = $this->get_keywords('LOTO');        
        array_walk($keywords, create_function('&$str', '$str="\'".$str."\'";'));
        $keywords = implode(',', $keywords);
        $this->queue_db->or_where("(`keyword` IN ($keywords))");
    }
     * 
     */
       
    //Check if today is openday of lottery_code
    public function load_is_open_day()
    {
        if ( ! isset($this->MO->args['lottery_code'])) $this->preprocess_VIPDP ();
        //Load information
        $open_days = $this->Lottery_model->get_open_days($this->MO->args['lottery_code']);        
        return in_array(date('w'),$open_days);
    }          
    
    public function load_region()
    {
        if ( ! key_exists('region', $this->cache))
        {
            $this->cache['region'] = $this->Lottery_model->get_region($this->MO->args['lottery_code']);
        }
        return $this->cache['region'];
    }
    
    public function load_tk_loto_6_thang()
    {
        $date = strtotime('6 month ago');                
        $date = date("Y-m-d",$date);                       
        $temp = $this->Lottery_model->get_statistic_min_max($this->MO->args['lottery_code'],$date,NULL,3);            
        return $temp;         
    }    
    
    public function load_tk_lau_nhat_chua_ve()
    {
        $temp = $this->Lottery_model->get_statistic_last_occur_longest($this->MO->args['lottery_code'],3);
        $stat = array();
        foreach($temp as $result=>$date)                
        {
            $date = date('d/m/y',strtotime($date));
            $stat[] = "$result($date)";
        }
        return $stat;        
    }
    
    public function load_tk_loto_15_lan()
    {
        $temp = $this->Lottery_model->get_statistic_min_max($this->MO->args['lottery_code'],15);        
        return $temp;
    }
    
    public function load_tk_ve_nhieu_1_nam_8()
    {
        $date = strtotime('1 year ago');                
        $date = date("Y-m-d",$date);                       

        return $this->Lottery_model->get_statistic_min_max($this->MO->args['lottery_code'],$date,'8');
    }
    
    public function load_tk_ve_nhieu_1_nam_db()
    {
        $date = strtotime('1 year ago');                
        $date = date("Y-m-d",$date);                       

        return $this->Lottery_model->get_statistic_min_max($this->MO->args['lottery_code'],$date,'DB');
    }
    
    public function preprocess_XSMN()
    {        
        if ($this->MO->argument)
        {            
            preg_match('/^([0-9]{1,2})[^0-9a-zA-Z]([0-9]{1,2})([^0-9a-zA-Z]([0-9]{1,4}))?$/',$this->MO->argument,$matches);
            $this->MO->argument = NULL;
            
            if (count($matches) > 0)
            {
                $day = str_pad($matches[1],2,'0',STR_PAD_LEFT);
                $month = str_pad($matches[2],2,'0',STR_PAD_LEFT);
                $year = date("Y");                    
                $year = (isset($matches[4]))?str_pad($matches[4],4,$year,STR_PAD_LEFT):$year;

                if (checkdate($month,$day,$year))
                {   
                    $this->MO->args = array();
                    $this->MO->args['date'] = "$year-$month-$day";  
                    write_log("debug","Detected MO argurment = ".$this->MO->args['date']);     
                    $this->MO->argument = $this->MO->args['date'];
                }        
            }                       
        }                 
    }
    
    public function load_XSMN()
    {
        $date = (isset($this->MO->args['date']))?$this->MO->args['date']:date('Y-m-d');
        return $this->Lottery_model->get_regional_result('MN',$date);
    }
    
    public function load_XSMN_yesterday()
    {
        $date = date('Y-m-d',strtotime('yesterday'));        
        return $this->Lottery_model->get_regional_result('MN',$date);        
    }
    
    public function preprocess_XSMT()
    {
        $this->preprocess_XSMN();
    }
    
    public function load_XSMT()
    {
        $date = (isset($this->MO->args['date']))?$this->MO->args['date']:date('Y-m-d');
        return $this->Lottery_model->get_regional_result('MT',$date);
    }
    
    public function load_XSMT_yesterday()
    {
        $date = date('Y-m-d',strtotime('yesterday'));
        return $this->Lottery_model->get_regional_result('MT',$date);        
    }   
    
    public function get_mo_list()
    {                
        $short_codes = array('7427','7527','7627','7727');
        $keywords = $this->get_keywords('XS');
        $keywords = array_merge($keywords,$this->get_keywords('KQ'));
        $keywords = array_merge($keywords,$this->get_keywords('LOTO'));
        $this->load->model('Balance_model');
        $list = $this->Balance_model->get_list(0,$short_codes,$keywords,NULL,date("Y-m-d",time()-24*60*60));                            
        return $list;
    }
}

/* End of file*/