<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Mo_queue_model extends CI_Model
{
    private $queue_start = 0; 
    private $queue_size = NULL;
    private $EOQ = FALSE; //End of Queue flag    
  
	//constructor
	public function __construct()
    {      
        parent::__construct();
        
        //Declare a database object which will be used TO GET QUEUE ONLY.
        //All of parameters of this object will be cached and flushed in reset procedure
        $CI =& get_instance();        
        $CI->queue_db = $this->load->database('default', TRUE); //Have to use $CI instead of $this for profiler
        $CI->queue_db->start_cache();         

        $this->_init_queue_db();          
    }    
    
    //Put default queue setting here
    private function _init_queue_db()
    {                        
        $this->queue_db->from('mo_queue');        
        
        //Discard disabled smsc by default
        $disabled_smsc = $this->config->item('disabled_smsc');
        if (count($disabled_smsc) > 0)
        {
            $this->queue_db->where_not_in('smsc_id',$disabled_smsc);            
        }               
    }
    
    public function set_queue_start($position = 0)
    {        
        $this->queue_start = $position;
        
        if (! $this->EOQ)
        {
            //Queue start should not be changed manually, it will be reset by reset function
            write_log('error',"WARNING: queue window starting posision has been changed manually!",'core');            
        }
    }
    
    public function get_queue_start()
    {
        return $this->queue_start;
    }
    
    public function set_queue_size($size = 1000)
    {        
        if ( ! ($this->EOQ || $this->queue_start == 0))
        {
            write_log('error',"WARNING: queue window size has been changed when getting queue!",'core');
            
        }    
        
        $this->queue_size = $size;    
    }
    
    public function get_queue_size()
    {
        return $this->queue_size;
    }            
    
    /**
     * Group by given field when getting queue
     * @param   array $field_list: field to be group_by
     * */
    public function set_distinct($field_list)
    {        
        $this->queue_db->group_by($field_list);
    }
    
    /**
     * Set filter to get queue
     * @param   String $field: including: msisdn, short_code, time, smsc_id, keyword, argument, balance, tag
     * @param   Array $value: a single or array of acepted value for $field
     * @param   Boolean $escape: whether or not escape the $value when apply, available only if $value is a single value, not array
     * @return  void, this setting will be aply when fetch queue
     * */
    public function set_filter($field,$value,$escape = TRUE)
    {
        if ( is_array($value))
        {
            $this->queue_db->where_in($field,$value);
        }
        else
        {
            $this->queue_db->where($field,$value,$escape);
        }
    }
    
    public function reset()
    {
        $this->queue_start = 0;
        $this->EOQ = FALSE;
        $this->queue_db->flush_cache();
        $this->_init_queue_db();
    }
    
    /**
     * Check whether end of queue or not
     * */
    public function EOQ()
    {
        return $this->EOQ;
    }    
    
    /**
     * Get queued message
     * If there are more than one MO with the same msisdn, short_code, smsc_id, keyword then get latest one (max id)     
     * @return  array of mo, each mo is an array of properties of MO object
     * */
    public function get_queue()
    {
        write_log("debug","MO Queue is getting queue, start position = $this->queue_start and offset = $this->queue_size",'core');
        if ( ! $this->queue_size)
        {
            write_log("error","MO Queue window size has not been set",'core');            
            return array();            
        }             
        
        if ($this->EOQ)
        {
            write_log("debug","End of MO Queue, cannot get any more item",'core');
            return array();
        }        
               
        $this->queue_db->select('mo_queue.*');
        $this->queue_db->limit($this->queue_size,$this->queue_start);        
                                                                                                                   
        $query = $this->queue_db->get();
        
        $num_rows = $query->num_rows();
        
        //Shift queue_start after getting a queue window
        $this->queue_start += $num_rows;
                
        if ($num_rows < $this->queue_size)
        {
            $this->EOQ = TRUE; //Turn on End of Queue flag
            write_log("debug","End of MO Queue",'core');
        }
                
        return $query->result();
    }    
    
    /**
     * Update balance for MO in queue
     * @param   $data: two dimensions array in below format:
     *          $data[$value] = array of mo_id which have balance = value.
     * */
    public function update_balance($data)
    {                        
        foreach($data as $delta=>$mo_ids)
        {        
            $this->db->set('`balance`',"IFNULL(`balance`,0) + ($delta)",FALSE)
                     ->where_in('id',$mo_ids)
                     ->update('mo');                        
        }                        
    }
    
    /**
     * Get total balance
     * @param   $msisdn: phone number to caculate total balance 
     * @param   $short_code: NULL mean ALL
     * @param   $keyword: MO keyword to count
     * */
    public function get_total_balance($msisdn = NULL,$short_code = NULL,$keyword = NULL,$argument = NULL)
    {   
        if ( ! $msisdn)
        {
            return FALSE;
        }
        $this->db->select_sum('balance')
                 ->from('mo_queue');
                 
        if ($msisdn)
        {
            if (is_array($msisdn))
            {
                $this->db->where_in('msisdn',$msisdn);                
            }
            else
            {
                $this->db->where('msisdn',$msisdn);
            }
        }
        
        if ($keyword)
        {
            if (is_array($keyword))
            {
                $this->db->where_in('keyword',$keyword);                
            }
            else
            {
                $this->db->where('keyword',$keyword);
            }
        }
        
        if ($argument)
        {
            if (is_array($argument))
            {
                $this->db->where_in('argument',$argument);                
            }
            else
            {
                $this->db->where('argument',$argument);
            }            
        }
                
        if ($short_code)
        {
            if (is_array($short_code))
            {
                $this->db->where_in('short_code',$short_code);
            }
            else
            {
                $this->db->where('short_code',$short_code);
            }            
        }     
                        
        $query = $this->db->get();
        
        if ($query->num_rows() == 0)
        {
            return FALSE;
        }
        
        $row = $query->row();
        return $row->balance;
    }
    
}

//End of Queue