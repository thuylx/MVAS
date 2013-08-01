<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Stat_model extends CI_Model
{
    private $filter = array();
    private $fields = array();
    
    public function reset()
    {
        $this->filter = array();        
        $this->fields = array();
    }
    
    /**
     * Add field to group statistic on
     * @param $field: can be date,smsc_id,short_code,keyword
     * */
    public function group_by($field)
    {
        if (array_search($field,$this->fields) === FALSE)
        {
            $this->fields[] = $field;            
        }        
    }
    
    
    /**
     * Set filter to get queue
     * @param   $field: can be date, smsc_id, short_code or keyword
     * @param   $value: a single or array of acepted value for $field
     * @param   $escape: whether or not escape the $value when apply, available only if $value is a single value, not array
     * @return  void, this setting will be aply when calculate statistic
     * */
    public function set_filter($field,$value,$escape = TRUE)
    {
        if ( ! $field)
        {
            write_log('debug',"Field to filter MO Queue does not entered",'core');
            return FALSE;
        }        
        
        $this->filter[$field]['value'] = $value;
        if ( ! is_array($value))
        {
            $this->filter[$field]['escape'] = $escape;
        }        
    }    

    /**
     * Get statistic base on loaded filter and group fields     
     * */     
    public function get_statistic()
    {        
        if ($this->fields)
        {
            $this->db->select($this->fields);
            $this->db->group_by($this->fields);
        }        
        
        //Apply filter         
        foreach($this->filter as $field=>$values)
        {
            if (is_array($values['value']))
            {
                $this->db->where_in($field,$values['value']);                
            }
            else
            {
                $this->db->where($field,$values['value'],$values['escape']);
            }                                      
        }        
        
        $this->db->select_sum('mo')
                 //->select_sum('mt')
                 ->select_sum('revenue')
                 ->from('statistic_mo_revenue');
        $query = $this->db->get();
        
        //Reset filter, fields
        $this->reset();
        
        
        return $query->result_array();        
    }
    
}
/* End of file*/