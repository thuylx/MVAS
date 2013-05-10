<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Result_model extends CI_Model
{
    public $region;
    public $code;
    public $area_code;
	public $date;
    public $result = array();
    
    public function __construct()
    {
        write_log('debug','Result Model constructing');
    }
    
    public function reset()
    {
        unset($this->code);
        unset($this->area_code);
        unset($this->date);
        $this->result = array();
    }
    
    
    public function get_max_prize()
    {        
        $prizes = array_keys($this->result);
        $max = (in_array('DB',$prizes))?'DB':max($prizes);
        return $max;
    }
    
    /**
     * Parse result string for lottery result
     *  content result string usually is MO content
     * @param   input result string
     * */
    public function parse_result_string($result_string)    
    {
        write_log("debug","Result model parses result string");
        
        //Format of input
        $format_code = '[A-Z]{2,3}';
        $format_date = '[0-9]{1,2}[\/\-][0-9]{1,2}';
        $format_prize = '[1-9]|DB';
        $format_result = '[0-9]+( *- *[0-9]+)*';        
        
        $result_string_pattern = "^($format_code) +($format_date)(( +($format_prize) *: *($format_result))+)";           
        
        $is_match = preg_match('/'.$result_string_pattern.'/i',$result_string,$matches);
        
        if ( ! $is_match)
        {            
            write_log("error","Input result string is not correct, check input format.");
            return FALSE;
        }        
        
        //--------------------------------------------------------------------------------------
        //Code
        //--------------------------------------------------------------------------------------        
        //Check if it is an area code
        $this->db->select('lottery_code')
                 ->from('lottery_area')
                 ->where('code',$matches[1]);
        $query = $this->db->get();
        if ($query->num_rows()>0)
        {
            $row = $query->row();
            $this->code = $row->lottery_code;
            $this->area_code = $matches[1];
        }
        else
        {
            $this->code = $matches[1];
        }              
        
        //--------------------------------------------------------------------------------------
        //Date
        //--------------------------------------------------------------------------------------
        preg_match('/([0-9]{1,2})[^0-9a-zA-Z]([0-9]{1,2})/',$matches[2],$date);
        $day = str_pad($date[1],2,'0',STR_PAD_LEFT);
        $month = str_pad($date[2],2,'0',STR_PAD_LEFT);
        $year = date("Y");      
        $date = "$year-$month-$day";  
        if (! ($date == date("Y-m-d")))
        {
            write_log("error","Input lottery result string is not correct, check date argument");
            return FALSE;
        }
        $this->date = "$year-$month-$day";
        
        //--------------------------------------------------------------------------------------
        //Result
        //--------------------------------------------------------------------------------------        
        $results = explode(' ',trim($matches[3]));        
        foreach($results as $result)
        {
            $result = explode(':',$result);
            $result[0] = trim($result[0]);
            
            $this->result[$result[0]] = explode('-',$result[1]);                                    
            array_walk($this->result[$result[0]],create_function('&$str','$str = trim($str);'));
        }
        ksort($this->result);
        write_log("debug","Parsed input string.");                
        return TRUE;
    }
    
    /**
     * Generate result string
     * */
    public function generate_result_string()
    {
        write_log("debug","Result Model: generating result string");
        if (count($this->result) == 0)
        {
            //Not loaded yet
            return FALSE;
        }
        //=======================================================================================
        //Area code
        //=======================================================================================
        $result = ($this->area_code == "")?strtoupper($this->code):strtoupper($this->area_code);
        
        //=======================================================================================
        //Date
        //=======================================================================================
        $result .= " ".date("d/m",strtotime($this->date));
        
        //=======================================================================================
        //Result
        //=======================================================================================                
        $temp = array();
        foreach ($this->result as $key=>$value)
        {
            if ($key == 'DB')
            {
                $DB = $key.":".implode('-',$value);
            }
            else
            {
                $temp[] = $key.":".implode('-',$value);                
            }                        
        }        
        if (isset($DB))
        {
            array_unshift($temp,$DB);
        }                
        $result .= "\n".implode($temp,"\n");                
        
        return $result;
    }
    
    /**
     * Generate loto result string
     * */
    public function generate_loto_string()
    {
        write_log("debug","Result Model: generating loto result string");
        if (count($this->result) == 0)
        {
            //Not loaded yet
            return FALSE;
        }
        
        $return = "LOTO ";
        
        //=======================================================================================
        //Area code
        //=======================================================================================
        $return .= ($this->area_code == "")?strtoupper($this->code):strtoupper($this->area_code);
        
        //=======================================================================================
        //Date
        //=======================================================================================
        $return .= " ".date("d/m",strtotime($this->date));
        
        //=======================================================================================
        //Result
        //=======================================================================================
        $loto = array();    
        foreach($this->result as $prize=>$result)
        {
            foreach($result as $value)
            {
                $first = substr($value,strlen($value)-2,1);
                $second = substr($value,strlen($value)-1,1);                        
                $loto[$first][$second] = (isset($loto[$first][$second]))?$loto[$first][$second]+1:1;
            }
        }                

        $result = array();
        foreach($loto as $key=>$row)
        {       
            $result[$key] = array();
            $special_result = substr($this->result['DB'][0],strlen($this->result['DB'][0])-2,2);
            ksort($row);
            foreach($row as $row_key => $value)
            {
                //$special_mark = ("$key$row_key"==$special_result)?"(DB)":"";
                
                for ($i=0;$i<$value;$i++)
                {
                    $result[$key][] = "$key$row_key";
                }
                
                //$result[$key][$row_key]= "$key$row_key";//"$key$row_key$special_mark:$value";
                //if ($value>1)
                //{
                //    $result[$key][$row_key].= "($value)";                    
                //}                
            }    
            $result[$key] = implode('-',$result[$key]);
        }     
                           
        ksort($result);        
        $result = implode(chr(10),$result);
        
        $return .= chr(10).$result;        
        $return .= chr(10)."DB: $special_result";
        
        return $return;        
    }
    
    
    /**
     * Load from database
     * @param   $code: code of lottery
     * @param   $date: result date to be loaded, set to today if not set.
     * @return  FALSE if not available
     * */     
    public function load($code = NULL, $date = NULL)
    {
        //Reset object data
        $this->reset();
        
        //Prepare data
        if ($code == '')
        {
            write_log('debug',"Result model function load: code is not specified");
            return FALSE;
        }
        $today = date("Y-m-d",time());
        $date = ($date == '')?$today:$date;        
        write_log('debug',"Result Model loading lottery result with code = $code on $date");
        
        //=======================================================================================
        //Area and lottery code
        //=======================================================================================
        $code = strtoupper($code);
        $this->code = $code;
        if ($code != 'MB')
        {            
            $area_code = NULL;            
        }
        else
        {
            $this->db->select('area_code')
                     ->from('lottery_sub')
                     ->where('lottery_code',$code)
                     ->where('lottery_date',$date);
            $query = $this->db->get();
            if ($query->num_rows()>0)
            {
                $row = $query->first_row();
                $area_code = $row->area_code;
            }
            else
            {
                return FALSE;
            }            
        }
                
        $this->area_code = $area_code;
        
        //=======================================================================================
        //date
        //=======================================================================================        
        $this->date = $date;
        
        //=======================================================================================
        //Lottery result
        //=======================================================================================        
        //Get prize list first
        $this->db->select('id,prize_code')
                 ->from('lottery_prize')
                 ->where('lottery_code',$code)
                 ->where('lottery_date',$date)
                 ->order_by('prize_code','ASC');
        $query = $this->db->get();        
        if ($query->num_rows()===0)
        {
            return FALSE;
        }
        $lottery_result = '';
        $prizes = array();
        foreach($query->result() as $row)
        {
            $prizes[] = $row->id;
        }                        
        
        
        //Get result list according to above prize list
        $lottery_sub_result = $row->prize_code.':';
        $this->db->select('value,prize_id')
                 ->from('lottery_result')
                 ->where_in('prize_id',$prizes)
                 ->order_by('order','ASC');
        $sub_query = $this->db->get();
        
        //Fetch lottery result to corresponding prize
        foreach($query->result() as $row)
        {
            foreach($sub_query->result() as $sub_row)
            {
                if ($sub_row->prize_id == $row->id)
                {
                    $this->result[$row->prize_code][]= $sub_row->value;
                }
            }
        }
        
        return TRUE;
    }        
    
    /**
     * Insert new lottery result into database
     * */
    public function insert()
    {
        write_log("debug","Result Model: inserting lottery result");
        //==============================================================================
        //  Insert lottery_sub
        //==============================================================================    
        if ($this->area_code != "")
        {
            $this->db->insert('lottery_sub',array('lottery_code'=>$this->code,'lottery_date'=>$this->date,'area_code'=>$this->area_code));
        }
        
        //==============================================================================
        //  Insert lottery result (prize and its results)
        //==============================================================================
        foreach($this->result as $prize=>$result) 
        {
            //Insert prize
            $this->db->insert('lottery_prize',array('lottery_code'=>$this->code,'prize_code'=>$prize,'lottery_date'=>$this->date));
            $prize_id = $this->db->insert_id();
            
            //Insert prize result
            $batch = array();$i = 0;
            foreach($result as $value)
            {
                $i++;
                $batch[] = array('prize_id'=>$prize_id,'value'=>$value,'order'=>$i);
            }
            if ($batch)
            {
                $this->db->insert_batch('lottery_result',$batch);                
            }            
        }            
    }
    
    /**
     * Delete lottery result of a specific date
     * */
    public function delete($date)
    {
        write_log("debug","Result Model: deleting lottery result");
        //==============================================================================
        //  Delete lottery_sub
        //==============================================================================    
        if ($this->area_code != "")
        {
            $this->db->where(array('lottery_code'=>$this->code,'lottery_date'=>$this->date))            
                     ->delete('lottery_sub');
        }        
                
        //==============================================================================
        //  Delete lottery result (prize and its results)
        //==============================================================================
        //Get prize id list first
        $this->db->select('id')
                 ->from('lottery_prize')
                 ->where(array('lottery_code'=>$this->code,'lottery_date'=>$this->date));
        $query = $this->db->get();
        if ($query->num_rows() == 0)
        {
            return;
        }
        foreach($query->result() as $row)
        {
            $ids[] = $row->id;
        }
        //Delete result
        $this->db->where_in('prize_id',$ids)
                 ->delete('lottery_result');
        //Delete prize
        $this->db->where_in('id',$ids)
                 ->delete('lottery_prize');                                     
    }
    
    /**
     * Update lottery result
     * */
    public function update()
    {
        write_log("debug","Result Model: updating lottery result");
        //==============================================================================
        //  Update lottery_sub
        //==============================================================================    
        if ($this->area_code != "")
        {
            $this->db->where(array('lottery_code'=>$this->code,'lottery_date'=>$this->date))
                     ->set('area_code',$this->area_code)
                     ->update('lottery_sub');
        }
        
        //==============================================================================
        //  Update lottery result (prize and its results)
        //==============================================================================
        //Get prize id list first to delete all inserted data
        $this->db->select('id')
                 ->from('lottery_prize')
                 ->where(array('lottery_code'=>$this->code,'lottery_date'=>$this->date));
        $query = $this->db->get();
        if ($query->num_rows() == 0)
        {
            return;
        }
        foreach($query->result() as $row)
        {
            $ids[] = $row->id;
        }
        //Delete result
        $this->db->where_in('prize_id',$ids)
                 ->delete('lottery_result');
        //Delete prize
        $this->db->where_in('id',$ids)
                 ->delete('lottery_prize'); 
                     
                     
        //Insert new data
        foreach($this->result as $prize=>$result)
        {
            //Insert prize
            $this->db->insert('lottery_prize',array('lottery_code'=>$this->code,'prize_code'=>$prize,'lottery_date'=>$this->date));
            $prize_id = $this->db->insert_id();
            
            //Insert prize result
            $batch = array();$i = 0;
            foreach($result as $value)
            {
                $i++;
                $batch[] = array('prize_id'=>$prize_id,'value'=>$value,'order'=>$i);
            }
            if ($batch)
            {
                $this->db->insert_batch('lottery_result',$batch);                
            }            
        }        
    }
}
/* End of file*/