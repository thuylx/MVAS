<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Balance_model extends CI_Model 
{       
    public function __construct()
    {
        parent::__construct();
    }  
    
    /**
     * Get list of item in view of customer_balance
     * @param   all of params used to set filter. short_code, keyword, argument, smsc_id parameters can be an array.
     * @return  query result array. Each row have 4 properties: msisdn,short_code,smsc_id,last_mo_id
     * */
    public function get_list($balance = NULL,$short_code=NULL,$keyword=NULL,$argument=NULL,$last_provision_date=NULL,$smsc_id=NULL)
    {
        $this->db->select('msisdn,short_code,smsc_id,last_mo_id')->from('customer_balance');
        if (isset($balance))
        {
            $this->db->where('balance',$balance);
        }
        if (isset($short_code))
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
        if (isset($keyword))
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
        
        if (isset($argument))
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
        if (isset($last_provision_date))
        {
            $this->db->where('DATE(FROM_UNIXTIME(`last_provision_time`))',$this->db->escape($last_provision_date),FALSE);
        }
        
        if (isset($smsc_id))
        {
            if (is_array($argument))
            {
                $this->db->where_in('smsc_id',$smsc_id);                
            }
            else
            {
                $this->db->where('smsc_id',$smsc_id);
            }               
        }
                 
        $query = $this->db->get();
        
        return $query->result();
    }
}
/* End of file*/