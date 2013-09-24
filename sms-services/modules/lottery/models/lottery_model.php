<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Lottery_model extends CI_Model 
{       
    
    private $_cache=array(); //Store loaded cache
    
    public function __construct()
    {
        parent::__construct();
    }  
    
    public function get_region($lottery_code)
    {
        $this->db->select('region')
                 ->from('lottery')
                 ->where('code',$lottery_code);
                 
        $query = $this->db->get();
        if ($query->num_rows() == 0)
        {
            return FALSE;
        }
        $row = $query->row();
        return $row->region;
    }      
    
    /**
     * Update cache item
     * */
    public function cache($lottery_code,$code,$text,$time = NULL)
    {        
        write_log("debug","Cache $lottery_code, $code");
        $time = (is_null($time))?time():$time;
        
        $fields['lottery_code'] = $this->db->protect_identifiers('lottery_code');
        $fields['code']         = $this->db->protect_identifiers('code');
        $fields['content']      = $this->db->protect_identifiers('content');
        $fields['time']         = $this->db->protect_identifiers('time');
        
        $values['lottery_code'] = $this->db->escape($lottery_code);
        $values['code']         = $this->db->escape($code);
        $values['text']         = $this->db->escape($text);
        $values['time']         = $this->db->escape($time);
        
        $updates = array();
        $updates[] =  $fields['content']." = ".$values['text'];
        $updates[] =  $fields['time']." = ".$values['time'];
        
        $table = $this->db->protect_identifiers('lottery_cache',TRUE);
        
        $sql =  "INSERT INTO $table (".implode(', ',$fields).")".
                " VALUES (".implode(', ',$values).")".
                " ON DUPLICATE KEY UPDATE ".implode(', ',$updates).";"; 
                
        $this->db->query($sql);
        
        if ($this->db->affected_rows() == 0)
        {
            write_log('error',"Cache item failed. lottery_code = $lottery_code, code = $code");
            return FALSE;
        }
        
        //Save to memory for later use
        $this->_cache[$lottery_code][$code] = array('content'=>$text,'time'=>$time);               
        write_log('debug',"Cached");
        return TRUE;
    }
    
    /**
     * Get cache item
     * @return  database row object with 3 files: code, content and time
     * */
    public function get_cache($lottery_code, $code)
    {
        //Check if it saved into memory already
        if (isset($this->_cache[$lottery_code][$code]))
        {
            $array = $this->_cache[$lottery_code][$code];
            $array['code'] = $code;
            return (object)$array;
        }
        
        $this->db->select('code,content,time')
                 ->from('lottery_cache')
                 ->where('lottery_code',$lottery_code)
                 ->where('code',$code);
        $query = $this->db->get();
        
        if ($query->num_rows())
        {
            $row = $query->row();
            
            //Save to memory for later use
            $this->_cache[$lottery_code][$code] = array('content'=>$row->content, 'time'=>$row->time);
            
            return $row;    
        }
        
        return FALSE;
    }
    
    /**
     * Get area base on given date (for lottery MB only)
     * @param   date, NULL mean to day
     * @return  string
     * */
    public function get_area($date = NULL)
    {
        if (is_null($date))
        {
            $date = time();
        }
        else
        {
            $date = strtotime($date);
        }
        $weekday = date('w',$date);

        $this->db->select('code')
                 ->from('lottery_area')
                 ->where('weekday',$weekday);
        $query = $this->db->get();
        
        if ($query->num_rows() == 0)
        {
            return FALSE;
        }
        
        $row = $query->row();
        return $row->code;                
    }
    
    /**
     * Get last lottery result
     * @param   $lottery_code
     * @param   $prize_code: example: 1,2,3,DB
     * @return  database row object which consist of 3 field: code, content and time
     * */
    public function get_last_result($lottery_code,$prize_code = NULL)
    {                
        write_log('debug',"Get last result of $lottery_code, prize $prize_code");                
        
        $prize_codes = (is_null($prize_code))?array('1','2','3','4','5','6','7','8','DB'):array($prize_code);
        
        //Try cache 
        $this->db->select('code,content,time')
                 ->from('lottery_cache')
                 ->where('lottery_code',$lottery_code)
                 ->where_in('code',$prize_codes)
                 ->order_by('DATE(FROM_UNIXTIME(`time`))','DESC')
                 ->order_by('code','DESC')
                 ->limit(1);
        $query = $this->db->get();
        
        if ($query->num_rows != 0) return $query->row();        
        
        //No cached found, load from database
        if ($prize_code == 'DB' || is_null($prize_code))
        {
            write_log('debug',"No prize cached");                    
            $date = $this->get_open_date($lottery_code);
            $time = strtotime($date.' 00:00:00');
            $result = $this->_get_result_no_cache($lottery_code, $date);
            if ($result)
            {
                //Write cache the last result
                $this->cache($lottery_code, 'DB', $result,$time);
                
                $result = array(
                    'code' => 'DB',
                    'content' => $result,
                    'time' => $time
                );
                $result = (object)$result;               
            }            
            return $result;            
        }

        return FALSE;                
    }        
    
    /**
     * @return  Database row object of cached value which consist of 3 fields: code, content, time
     *          return FALSE if not found
     * */
    public function get_last_today_result($lottery_code)
    {
        write_log('debug',"Get last today result of $lottery_code");
        $this->db->select('code,content,time')
                 ->from('lottery_cache')
                 ->where('lottery_code',$lottery_code)
                 ->where_in('code',array('1','2','3','4','5','6','7','8','DB'))
                 ->where('time >=',strtotime(date('Y-m-d'). " 00:00:00"))             
                 ->order_by('code','DESC')
                 ->limit(1);
        $query = $this->db->get();
        
        if ($query->num_rows != 0) return $query->row();

        //No cache found, see database
        $result = $this->_get_result_no_cache($lottery_code,date('Y-m-d'));
        return $result;       
    }
    
    /**
     * @return  Database row object of cached value which consist of 3 fields: code, content, time
     *          return FALSE if not found
     * */
    public function get_today_result($lottery_code, $prize = 'DB')
    {
        write_log('debug',"Get today result of $lottery_code, prize $prize");
        
        //Try cache first
        $result = $this->get_cache($lottery_code, $prize);
        if ($result && date('Y-m-d',$result->time) == date('Y-m-d')) return $result;
        
        //Try to search in database when no cache found
        if ($prize == 'DB')
        {
            return $this->_get_result_no_cache($lottery_code,date('Y-m-d'));
        }
        return FALSE;
    }
    
    public function is_cached($lottery_code,$code,$date)
    {        
        $this->db->where('lottery_code',$lottery_code)
                 ->where('code',$code)
                 ->where('date(from_unixtime(`time`))',$date);                                           
        $count = $this->db->count_all_results('lottery_cache');
                                      
        return ($count>0);
    }   
    
    public function is_updated($lottery_code,$prize,$date)
    {
        //Check cache first
        if ($this->is_cached($lottery_code, $prize, $date)) return TRUE;
        
        //Not cached
        if ($prize == 'DB')
        {
            $last_date = $this->get_open_date($lottery_code);
            return ($last_date >= $date);
        }
        
        return FALSE;
    }
    
    /**
     * Get last loto result
     * @return  database row object with 3 files: code, content and time
     * */
    public function get_last_loto($lottery_code)
    {
        write_log('debug',"Get last loto result of $lottery_code");
        $temp =  $this->get_cache($lottery_code,'loto');
        
        if ($temp)
        {
            unset($temp->code);
            return $temp;
        }
        
        $date = $this->get_open_date($lottery_code);
        $time = strtotime($date.' 18:25:00');
        $result = $this->_get_loto_no_cache($lottery_code, $date);
        if ($result)
        {
            //Write cache the last loto result
            $this->cache($lottery_code, 'loto', $result, $time);

            $result = array(
                'content' => $result,
                'time' => $time
            );
            
            $result = (object)$result;
        }
        
        return $result;                    
    }
    
    /**
     * Get today loto
     * @return  database row object with 3 files: code, content and time
     * */
    public function get_today_loto($lottery_code)
    {
        write_log('debug',"Get today loto result of $lottery_code");
        
        $temp = $this->get_cache($lottery_code,'loto');
        if (date('Y-m-d',$temp->time) == date('Y-m-d'))
        {
            return $temp;
        }
        
        //from database
        return $this->_get_loto_no_cache($lottery_code,date('Y-m-d'));
    }
    /**
     * make lottery statistic
     * @param   $num_days: number of recently open day base on which do statistic. NULL mean all.
     *          $num_days can be a date in the format of 'yyyy-mm-dd', statistic will be calculate based on
     *          all of lottery result from this day to this moment.
     * @param   $lottery_code: a or an array of lottery code, NULL mean all
     * @param   $prize: a or an array of prize code (1,2,3,..,DB), NULL mean all
     * @return  return query result with two fields: result and amount (count of result)
     * */
    private function _statistic($lottery_code = NULL,$num_days = NULL,$prize = NULL)
    {
        //Detect start date base on num_days
        $min_date = NULL;
        if ($lottery_code == 'MB' && $prize != 'DB')
        {
            $min_date = '2010-07-07'; //We have only data for full prize from this day, for prize of 'DB', data available since 2005           
        }
        
        if ($num_days)
        {
            if (is_integer($num_days))
            {
                /*
                $this->db->select('lottery_date')
                         ->from('lottery_prize')
                         ->where('lottery_code',$lottery_code)
                         ->order_by('lottery_date','DESC')
                         ->group_by('lottery_date')
                         ->limit($num_days);
                $query = $this->db->get();                
                if ($query->num_rows() == 0)
                {
                    return FALSE;
                }       
                $row = $query->last_row();
                
                $min_date = $row->lottery_date;
                */
                $min_date = $this->get_open_date($lottery_code,$num_days-1);                
            }          
            else //$num_days is a date
            {
                $min_date = $num_days;
            }
        }
                
        //Get prize_id list
        $prize_ids = array();
        if ($lottery_code || $prize ||$min_date)        
        {                                                
            $this->db->select('id')
                     ->from('lottery_prize');
            if ($lottery_code)
            {
                $lottery_code = (is_array($lottery_code))?$lottery_code:array($lottery_code);
                $this->db->where_in('lottery_code',$lottery_code);
            }
            
            if ($prize)
            {
                $prize = (is_array($prize))?$prize:array($prize);
                $this->db->where_in('prize_code',$prize);
            }
            
            if ($min_date)
            {
                $this->db->where('lottery_date >= ',$min_date);
            }
            
            $query = $this->db->get();
            
            if ($query->num_rows() == 0)
            {
                return array();
            }
            
            foreach($query->result() as $row)
            {
                $prize_ids[] = $row->id;
            }
        }
        
        $this->db->select('`result`,COUNT(`result`) AS `amount`',FALSE)                            
                 ->from('lottery_loto')
                 ->group_by('result');
                 //->order_by('amount');
        if ($prize_ids)
        {
            $this->db->where_in('prize_id',$prize_ids);
        }                 
        
        $query = $this->db->get();        

        return $query->result();
    }
    
    /**
     * make lottery statistic
     * @param   $num_days: number of recently open days base on which do statistic. NULL mean all.
     *          $num_days can be a date in the format of 'yyyy-mm-dd', statistic will be calculate based on
     *          all of lottery result from this day to this moment.     
     * @param   $lottery_code: a or an array of lottery code, NULL mean all
     * @param   $prize: a or an array of prize code (1,2,3,..,DB), NULL mean all
     * @return  return an array with key is result and value is its amount, sort ASC
     * */
    public function get_statistic($lottery_code = NULL,$num_days = NULL,$prize = NULL)
    {
        /*
        if (isset($this->_cache["stat($num_days,$prize)"]))
        {
            return $this->_cache["stat($num_days,$prize)"];
        }
        */
        //Check if it was cached before
        $statistic = $this->get_cache($lottery_code,"stat($num_days,$prize)");
        if ($statistic && date('Y-m-d',$statistic->time)==date('Y-m-d')) //Available and up-to-date
        {
            $statistic = unserialize($statistic->content);
        }
        else
        {
            //not cached, make it and cache for later use
            $temp = $this->_statistic($lottery_code,$num_days,$prize);
            $statistic = array();
            foreach ($temp as $item)
            {
                $statistic[$item->result] = $item->amount;
            }
            $this->cache($lottery_code,"stat($num_days,$prize)",serialize($statistic));           
        }
        
        for($i=1;$i<100;$i++)
        {
            $return[str_pad($i,2,"0",STR_PAD_LEFT)] = 0;
        }
        
        foreach($statistic as $key => $value)
        {
            $return[$key] = $value;
        }
        
        asort($return);
                
        return $return;
    }
    
    /**
     * make lottery statistic on sum of 2 digit basis
     * @param   $num_days: number of recently open day base on which do statistic. NULL mean all.
     *          $num_days can be a date in the format of 'yyyy-mm-dd', statistic will be calculate based on
     *          all of lottery result from this day to this moment.     
     * @param   $lottery_code: a or an array of lottery code, NULL mean all
     * @param   $prize: a or an array of prize code (1,2,3,..,DB), NULL mean all
     * @return  return query object which containt 2 collumns: 
     *          result (lot result) and amount; shorted by amount ASC
     * */
    public function get_statistic_sum($lottery_code = NULL,$num_days = NULL,$prize = NULL)
    {        
        //Get statistic
        $statistic = $this->get_statistic($lottery_code,$num_days);
                
        //Make sum statistic
        $stat_sum = array('01'=>0,'02'=>0,'03'=>0,'04'=>0,'05'=>0,'06'=>0,'07'=>0,'08'=>0,'09'=>0,'10'=>0);
        foreach($statistic as $key=>$value)
        {
            $index = ($key%10 + floor($key/10))%10;
            $index = ($index == 0)?10:$index;
            $index = str_pad($index,2,'0',STR_PAD_LEFT);
            $stat_sum[$index] = $stat_sum[$index]+$value;
        }
        
        asort($stat_sum);
        $statistic = array();
        foreach($stat_sum as $key=>$amount)
        {
            $item = array('result'=>$key,'amount'=>$amount);
            $statistic[] = $item;
        }
        return $statistic;
    }    
    
    /**
     * make lottery statistic on sum of 2 digit basis
     * @param   $num_days: number of recently open day base on which do statistic. NULL mean all.
     *          $num_days can be a date in the format of 'yyyy-mm-dd', statistic will be calculate based on
     *          all of lottery result from this day to this moment.      
     * @param   $lottery_code: a or an array of lottery code, NULL mean all
     * @param   $prize: a or an array of prize code (1,2,3,..,DB), NULL mean all
     * @param   $element: number of min/max element will be return
     * @return  a array with 2 elements:
     *              min: array of min value, each element is array with 2 fied: result and its ammount, sort ASC
     *              max: array of max value, each element is array with 2 fied: result and its ammount, sort DSC
     * */    
    public function get_statistic_min_max($lottery_code = NULL,$num_days = NULL,$prize = NULL,$element = 3)
    {
        $stat = $this->get_statistic($lottery_code,$num_days,$prize);
        $data = array('min'=>array(),'max'=>array());
        
        $temp = array_slice($stat,0,$element,TRUE);
        foreach($temp as $key=>$value)
        {
            $data['min'][] = array('result'=>$key,'amount'=>$value);
        }
         
        $temp = array_slice($stat,-3,$element,TRUE);
        arsort($temp);
        foreach($temp as $key=>$value)
        {
            $data['max'][] = array('result'=>$key,'amount'=>$value);                
        }        
        
        return $data;
    }
    
    /**
     * Lottery prediction
     * @param   $msisdn: MO msisdn. Give each msisdn a set of prediction, not the same to all customer
     * @param   $lottery_code
     * @param   $prize: give predict to which prize. NULL mean all for Loto service
     * @param   $element: number of prediction number.
     * @param   $date parameter, based on which calculate prediction
     * @return  array consist of $element prediction number.
     * */
    public function predict($lottery_code = NULL,$prize = NULL, $msisdn = 0 ,$element = 3, $date = NULL)
    {        
        $stat = $this->get_statistic($lottery_code,NULL,$prize);
        $stat = array_keys($stat);
        $stat = array_slice($stat,0,50);
        
        $date = (is_null($date))?time():strtotime($date);
        $start_point = (substr($msisdn,strlen($msisdn)-1,1) + date('d',$date))%50;        
        for($i=0;$i<$element;$i++)
        {            
            $predict[$i] = $stat[($start_point + $i*4)%50];
        }
        //sort($predict);        
        return $predict;        
    }        
    
    /**
     * Get list (array) of available lottery code
     * */
    public function get_lottery_catalog($region = NULL)
    {
        if ($region && ( ! is_array($region)))
        {
            $region = array($region);
        }
        
        $this->db->select('code')
                 ->from('lottery');
        if ($region)
        {
            $this->db->where_in('region',$region);
        }                             
        $query = $this->db->get();
        
        $codes = array();
        foreach($query->result() as $row)
        {
            $codes[] = $row->code;
        }
        
        return $codes;        
    }        
    
    /**
     * Get date of recent open day
     * @param   $lottery_code
     * @param   $ordinal: 0 for last open day, n for nth open day from last one.
     * @return  open date, FALSE if not found
     * */
    public function get_open_date($lottery_code,$ordinal = 0)
    {
        $this->db->select('date')
                 ->from('lottery_open_date')
                 ->where('lottery_code',$lottery_code)
                 ->limit($ordinal+1);
        $query = $this->db->get();
        if ($query->num_rows() == 0)
        {
            return FALSE;
        }
        
        $row = $query->last_row();        
        return $row->date;
    }
    
    /**
     * Get array of open weekday.
     * Format: 0 to 6. 0 is Sunday and 6 is Staturday
     * */
    public function get_open_days($lottery_code)
    {
        $this->db->select('weekday')
                 ->from('lottery_open_day')
                 ->where('lottery_code',$lottery_code)
                 ->order_by('weekday');
        $query = $this->db->get();
        
        $open_days = array();
        foreach($query->result() as $row)
        {
            $open_days[] = $row->weekday;
        }
        
        return $open_days;
    }
    
    /**
     * Get open lottery list for specific date
     * If specified date is in the past, get it from database for actual open lottery
     * If spefified date is today or in the future, build the list upon open day information.
     * @return  array of lottery code
     * */
    public function get_open_lottery($date)
    {
        if ($date<date("Y-m-d"))
        {
            $this->db->select('lottery_code')
                     ->from('lottery_open_date')
                     ->where('date',$date);            
            $list = array();

        }
        else
        {
            $weekday = date('w',strtotime($date));
            $this->db->select('lottery_code')
                     ->from('lottery_open_day')
                     ->where('weekday',$weekday);            
        }
        
        $query = $this->db->get();
                        
        foreach($query->result() as $row)
        {
            $list[] = $row->lottery_code;
        }
        
        return $list;
    }    
    
    
    /**
     * Get the timestamp of next open day
     * @param   $open_days: array of weekday in the format of 0-6, 0 for Sunday and 6 for Saturday
     *          $open_days can be a lottery_code, it will be generated from given lottery code automatically
     * @param   $date: starting date. NULL mean today.
     * @return  next open date in 'Y-m-d' format (today will be not considered as next open day)     
     * */
    public function get_next_day($open_days,$date = NULL)
    {
        $date = (is_null($date))?time():strtotime($date);
        if ( ! is_array($open_days)) //lottery_code
        {
            if ($open_days == 'MB')
            {
                return date('Y-m-d',strtotime('+1 day',$date));
            }
            $open_days = $this->get_open_days($open_days);
        }                
        
        $today = date('w',$date);
        $sun = strtotime('last sunday');
        
        asort($open_days);
        if ($today >= $open_days[count($open_days)-1])
        {
            $next_day = $open_days[0];
            $sun = $sun + 7*24*60*60; //next week
        }
        else
        {         
            foreach($open_days as $day)
            {
                if ($day>$today)
                {
                    $next_day = $day;
                    break;
                }
            }            
        }
                        
        return date('Y-m-d',$sun + $next_day*24*60*60);        
    }
    
    /**
     * Get the timestamp of previous open day
     * @param   $open_days: array of weekday in the format of 0-6, 0 for Sunday and 6 for Saturday
     *          $open_days can be a lottery_code, it will be generated from given lottery code automatically
     * @param   $date: starting date. NULL mean today.
     * @return  previous open date in 'Y-m-d' format (today is not considered as previous)     
     * */
    public function get_previous_day($open_days,$date = NULL)
    {                
        $date = (is_null($date))?time():strtotime($date);                
        
        if ( ! is_array($open_days)) //lottery_code
        {
            if ($open_days == 'MB')
            {
                return date('Y-m-d',strtotime('-1 day',$date));
            }
            $open_days = $this->get_open_days($open_days);
        }                
        
        $today = date('w',$date);
        $sun = strtotime('last sunday',$date);  
                
        rsort($open_days);        
        if ($today <= $open_days[count($open_days)-1])
        {            
            $previous_day = $open_days[0];            
            $sun = $sun - 7*24*60*60; //last week
        }        
        else
        {         
            foreach($open_days as $day)
            {                
                if ($day<$today)
                {
                    $previous_day = $day;                                        
                    break;
                }                
            }            
        }                                                               
        return date('Y-m-d',$sun + $previous_day*24*60*60);               
    }
    
    /**
     * Get array of lottery_code which will open in given date
     * @param   $region: NULL mean all (MT and MN)
     * @param   $date: NULL mean today
     * @return  array of string
     * */
    public function get_open_lotteries($region = NULL, $date = NULL)
    {
        $date = (is_null($date))?date("Y-m-d"):$date;
        $this->db->select('lottery_code')
                 ->from('lottery_open_day')
                 ->where('lottery_open_day.enable',TRUE)
                 ->where('weekday',date("w",strtotime($date)));
        if ($region)
        {
            $this->db->join('lottery','lottery.code = lottery_open_day.lottery_code')
                     ->where('lottery.region',$region);                                 
        }
        
        $query = $this->db->get();
        
        $codes = array();
        foreach($query->result() as $row)
        {
            $codes[] = $row->lottery_code;
        }
        
        return $codes;
    }
    
    /**
     * Get result string from database (skip reading cache)
     * @param string $lottery_code
     * @param string $date
     * @return string
     */
    public function _get_result_no_cache($lottery_code, $date)
    {
        $this->load->model('Result_model');         
        $this->Result_model->load($lottery_code,$date);

        return $this->Result_model->generate_result_string();         
    }
    
    public function _get_loto_no_cache($lottery_code, $date)
    {
        $this->load->model('Result_model');                 
        $this->Result_model->load($lottery_code,$date);    
        
        return $this->Result_model->generate_loto_string();        
    }
    
    /**
     * Get DB lottery result for an open date     
     * @param   $lottery_code
     * @param   $date: date to get the result. NULL mean today
     * @return  string of DB result
     * */    
    public function get_full_result($lottery_code, $date = NULL)
    {        
        $date = (is_null($date))?date("Y-m-d"):$date;
        write_log('debug',"Get full result of $lottery_code on $date");
        
        if ($date == date("Y-m-d"))
        {
            return $this->get_today_result($lottery_code);
        }
        
        //Previous day
        return $this->_get_result_no_cache($lottery_code, $date);
    }
    
    /**
     * Get loto result for an open date     
     * @param   $lottery_code
     * @param   $date: date to get the result. NULL mean today
     * @return  string of loto result
     * */    
    public function get_loto_result($lottery_code, $date = NULL)
    {
        $date = (is_null($date))?date("Y-m-d"):$date;
        write_log('debug',"Get loto result of $lottery_code on $date");
                
        if ($date == date("Y-m-d"))
        {
            return $this->get_today_loto($lottery_code);
        }

        return $this->_get_loto_no_cache($lottery_code,$date);
    }
           
    
    /**
     * Get regional lottery result for an open date     
     * @param   $region: MN or MT, NULL mean all
     * @param   $date: date to get the result. NULL mean today
     * @return  array of lottery results
     * */
    public function get_regional_result($region = NULL,$date = NULL)
    {
        $codes = $this->get_open_lotteries($region,$date);
        $results = array();
        foreach ($codes as $code)
        {
            $results[$code] = $this->get_full_result($code, $date);
        }        
        
        return $results;
    }
    
    /**
     * make lottery statistic
     * @param   $lottery_code: a or an array of lottery code, NULL mean all  
     * @return  return query result with two fields: result and amount (count of result)
     * 
     * */
    private function _statistic_last_occur($lottery_code = 'MB',$prize_DB_only = FALSE)
    {
        $this->db->select('RIGHT(`value`,2) AS `result`, MAX(`lottery_date`) AS `date`, (TO_DAYS(CAST(NOW() AS DATE)) - TO_DAYS(MAX(`lottery_date`))) AS `num_days`',FALSE)
                ->from('lottery_result')
                ->join('lottery_prize','lottery_result.prize_id = lottery_prize.id')
                ->where('lottery_code',$lottery_code)
                ->group_by('RIGHT(`value`,2)')
                ->order_by('MAX(`lottery_date`)');
//        $this->db->select('result, date, num_days');
        if ($prize_DB_only)
        {
            $this->db->where('prize_code','DB');
//            $this->db->from('lottery_last_occur_db');
        }
//        else
//        {            
//            $this->db->from('lottery_last_occur');
//        }
        
//        $this->db->where('lottery_code',$lottery_code);
            
        $query = $this->db->get();
        return $query->result();
    }
    
    /**
     * Get lottery statistic base on last occur time from cache if available. Make and cache for later use otherwise
     * @param   $lottery_code: a or an array of lottery code, NULL mean all  
     * @return  return query result with 3 fields: result, date (mean last occur date) and num_days(date diff from last occur to now)
     *          return array is shorted by date ASC
     * */    
    private function get_statistic_last_occur($lottery_code = 'MB',$prize_DB_only = FALSE)
    {
        /*
        if (isset($this->_cache["stat_last_occur($prize_DB_only)"]))
        {
            return $this->_cache["stat_last_occur($prize_DB_only)"];
        }
        */
        
        //Check if it cached before
        $statistic = $this->get_cache($lottery_code,"stat_last_occur($prize_DB_only)");
        if ($statistic && date('Y-m-d',$statistic->time)==date('Y-m-d')) //Available and up-to-date
        {
            $statistic = unserialize($statistic->content);
        }
        else
        {
            //not cached, make it and cache for later use
            $statistic = $this->_statistic_last_occur($lottery_code,$prize_DB_only);     
            $this->cache($lottery_code,"stat_last_occur($prize_DB_only)",serialize($statistic));                                
        }    
        
        return $statistic;          
    }
    
    
    /**
     * Return an array of loto result
     * */
    public function get_statistic_last_occur_from_date($lottery_code = 'MB',$from_date,$prize_DB_only = FALSE)
    {
        //Check if it cached before
        $temp = $this->get_statistic_last_occur($lottery_code,$prize_DB_only);                  
        $statistic = array();
        foreach ($temp as $item)
        {
            if ($item->date <= $from_date)
            {
                $statistic[] = $item->result;
            }
            else
            {
                break;
            }
        }        

        return $statistic;    
    }
    
    /**
     * Return an array of loto result
     * */
    public function get_statistic_last_occur_num_days($lottery_code = 'MB',$num_days,$prize_DB_only = FALSE)
    {
        $temp = $this->get_statistic_last_occur($lottery_code,$prize_DB_only);
                   
        $statistic = array();
        foreach ($temp as $item)
        {
            if ($item->num_days >= $num_days)
            {
                $statistic[] = $item->result;
            }
            else
            {
                break;
            }
        }
    
        return $statistic;    
    }    
    
    /**
     * Return an array of array with 2 dimention: result and num_days
     * */
    public function get_statistic_last_occur_longest($lottery_code = 'MB',$num_elements = 3,$prize_DB_only = FALSE)
    {
        $temp = $this->get_statistic_last_occur($lottery_code,$prize_DB_only);
                   
        $statistic = array();
        reset($temp);
        $item = current($temp);
        for ($i=0;$i<$num_elements;$i++)
        {            
            $statistic[$item->result] = $item->date;
            $item = next($temp);
        }
        return $statistic;    
    }                
    
    /**
     * @param   lottery_code
     * @param   date: NULL mean today
     * @return  array of distinct loto result     
     * */
    private function _get_loto_result_array($lottery_code = 'MB', $date = NULL)
    {
        $date = (is_null($date))?date('Y-m-d'):$date;
        
        //Check if it was cached before
        $return = $this->get_cache($lottery_code,"_get_loto_result_array($lottery_code,$date)");   
        if ($return && date('Y-m-d',$return->time)==date('Y-m-d')) //Available and up-to-date
        {
            $return = unserialize($return->content);
        }
        else        
        {            
            $this->db->select('`lottery_date` AS `lottery_date`, RIGHT(`value`,2) AS `loto`, COUNT(0) AS `count`',FALSE)
                    ->from('lottery_prize')
                    ->join('lottery_result','lottery_prize.id = lottery_result.prize_id')
                    ->group_by('`lottery_date`,RIGHT(`value`,2)')
                    ->where('lottery_code',$lottery_code)
                    ->where('lottery_date',$date);
//            $this->db->select('loto')
//                     ->from('lottery_loto_result')
//                     ->where('lottery_code',$lottery_code)
//                     ->where('lottery_date',$date);

            $query = $this->db->get();
            $return = array();
            foreach ($query->result() as $row)
            {
                $return[] = $row->loto;
            }
            
            //Cache for later use
            $this->cache($lottery_code,"_get_loto_result_array($lottery_code,$date)",serialize($return));             
        }
        
        return $return;
    }
    
    /**
     * Get loto result which in succession
     * @param   $lottery_code: MB by default
     * @param   $days: deep of search. 0 or NULL mean no limit
     * @return  an array with key is loto result and value is number of recent successive days
     * */
    public function get_succession($lottery_code = 'MB',$days = 0)
    {        
        //Check if it cached before
        $succession = $this->get_cache($lottery_code,"succession($days)");
        if ($succession && date('Y-m-d',$succession->time)==date('Y-m-d')) //Available and up-to-date
        {
            $succession = unserialize($succession->content);
            return $succession;
        }        
                
        if ($lottery_code!='MB')
        {
            $open_days = $this->get_open_days($lottery_code);
        }
        else
        {
            $open_days = 'MB';
        }
        
        $temp = $this->get_last_loto($lottery_code);
        $date = date('Y-m-d',$temp->time);                        
        $l = $this->_get_loto_result_array($lottery_code,$date);        
        $date = $this->get_previous_day($open_days,$date);              
        $l2 = $this->_get_loto_result_array($lottery_code,$date);
                
        $l = array_intersect($l,$l2);
        $return = array();
        foreach ($l as $item)
        {
            $return[$item] = 2;
        }        
        
        while(count($l)>0)        
        {
            $date = $this->get_previous_day($open_days,$date);                      
            $l2 = $this->_get_loto_result_array($lottery_code,$date);   
            $l = array_intersect($l,$l2);                  
            foreach ($l as $item)
            {
                $return[$item] += 1;
            }               
        }  
        
        ksort($return);
        
        //Cache for later use
        $this->cache($lottery_code,"succession($days)",serialize($return));
        
        return $return;
    }    
    
    /**
     * Get last occur of fist digit of db (loto)
     * @param   $lottery_code
     * @return  array, each items is an array consist of 2 elements: first and date (last occur date).
     *          out put ordered by date ASC
     * */
    public function get_last_occur_db_first($lottery_code = 'MB')
    {
        $this->db->select('SUBSTR(`value`,(LENGTH(`value`) - 1),1) AS `first`, MAX(`lottery_date`) AS `date`',FALSE)
                ->from('lottery_prize')
                ->join('lottery_result','lottery_prize.id = lottery_result.prize_id')
                ->where('lottery_code',$lottery_code)
                ->where('prize_code','DB')
                ->group_by('lottery_code')
                ->group_by('first')                
                ->order_by('date');                 
        $query = $this->db->get();
        
        return $query->result_array();
    }
    
    /**
     * Get last occur of last digit of db result
     * @param   $lottery_code
     * @return  array, each items is an array consist of 2 elements: last and date (last occur date).
     *          out put ordered by date ASC
     * */
    public function get_last_occur_db_last($lottery_code = 'MB')
    {
        $this->db->select('RIGHT(`value`,1) AS `last`, MAX(`lottery_date`) AS `date`',FALSE)
                ->from('lottery_prize')
                ->join('lottery_result','lottery_prize.id = lottery_result.prize_id')
                ->where('lottery_code',$lottery_code)
                ->where('prize_code','DB')
                ->group_by('lottery_code')
                ->group_by('last')                
                ->order_by('date');        
        $query = $this->db->get();
        
        return $query->result_array();
    }    
    
    /**
     * Get pair of loto result which repeat much in specified days
     * @param   string.
     * @param   date (Y-m-d). can be interger for date_sub
     * @param   interger, num of items to return
     * @retun   array: loto1, loto2, count. This array ordered by count desc.
     * */
    public function get_loto_repeated_pair($lottery_code = 'MB', $from_date = 30, $element = 3)
    {                
        if (is_integer($from_date))
        {
            $from_date = strtotime("$from_date days ago");
            $from_date = date('Y-m-d',$from_date);
        }        
        
        //Check if it was cached before
        $repeated_pair = $this->get_cache($lottery_code,"get_loto_repeated_pair($lottery_code,$from_date,$element)");
        if ($repeated_pair && date('Y-m-d',$repeated_pair->time)==date('Y-m-d')) //Available and up-to-date
        {
            $repeated_pair = unserialize($repeated_pair->content);
        }
        else
        {
            //not cached, make it and cache for later use
            $sql = "SELECT *
                    FROM `m_lottery_loto_date`
                    WHERE `lottery_code` = '$lottery_code'
                    AND `lottery_date` >= '$from_date'";                       
            $sql = "SELECT `t1`.`result` AS `loto1`, `t2`.`result` AS `loto2`, count( * ) AS `count`
                    FROM ($sql) AS `t1`
                    JOIN ($sql) AS `t2` 
                    ON `t1`.`lottery_date` = `t2`.`lottery_date`
                    WHERE `t1`.`result` < `t2`.`result`
                    GROUP BY `loto1`, `loto2`
                    ORDER BY `count` DESC
                    LIMIT $element;";
            
            $query = $this->db->query($sql);
            $repeated_pair = $query->result_array();
            $this->cache($lottery_code,"get_loto_repeated_pair($lottery_code,$from_date,$element)",serialize($repeated_pair));           
        }
        
        return $repeated_pair;
    }
}
/* End of file*/