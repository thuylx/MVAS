<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Coi_model extends CI_Model
{
    //Attention: below object properties have to match with database fields
    public $id;
    public $serial_number;
    public $secret_number;
    public $oid;
    public $status;
    public $status_time;
    public $updated_by; 
    
    const secret_number_pattern = '/^([0-9]{3,3})[- ]?([0-9]{3,3})[- ]?([0-9]{3,3})/';
    
    public function reset()
    {
        foreach (get_object_vars($this) as $property => $value)
        {                
            $this->$property = NULL;
        }
    }
    
    /** 
     *Correct key to defined format
     * @param   String, secret_number or serial_number
     * @param   String, key to be corrected
     * @return  String        
     **/
    public function correct_key($type, $key)
    {
        //init return value
        $return = $key;
        
        //do correction
        switch ($type)
        {
            case "secret_number":
                $return = preg_replace(self::secret_number_pattern, '$1-$2-$3', $key);                
                break;
        }
        
        //return corrected value
        return $return;
    }

    /**
     * Load from database
     * @param   $key: required, can be id, secret_number or serial_number depend on $key_field parameter
     * @param   $key_field: key field to search $key. Can be 'id', 'secret_number', 'serial_number' or array of them for multi condition joined by 'OR'    
     * @return  FALSE if not found, otherwise return TRUE
     * */     
    public function load_from_database($key, $key_field = 'secret_number')
    {
        //Reset object data first
        $this->reset();
        
        $this->db->select('id,serial_number,secret_number,oid,status,status_time,updated_by')
                 ->from('mic_coi');
        if ($key_field)
        {
            if (is_array($key_field))
            {
                foreach($key_field as $field)
                {
                    $this->db->or_where($field,$this->correct_key($field,$key));
                }
            }
            else 
            {
                $this->db->where($key_field,$key);
            }
        }                 
                 
        $query = $this->db->get();
        if ($query->num_rows() == 0)
        {
            return FALSE;
        }
        
        //$this->vars = $query->row_array();
        $row = $query->row_array();
        
        foreach ($row as $key=>$value)
        {
            $this->$key = $value;
        }                
                
        return TRUE;
    }
    
    /**
     * Update status by secret_number
     * @param   $secret_number: one or an array of secret number which will be active. NULL mean loaded one   
     * @param   $status: status to be updated
     * @param   $updated_by: user or msisdn who perform updating
     * @return  TRUE if successful, otherwise FALSE
     * */
    public function update_status($secret_number = NULL, $status, $updated_by = NULL)
    {        
        write_log('debug',"Updating inputed secret number(s)");
        $secret_number = (is_null($secret_number))?$this->secret_number:$secret_number;
        $secret_number = (is_array($secret_number))?$secret_number:array($secret_number);
        
        $this->db->set('status',$status)            
                 ->set('updated_by',$updated_by)
                 ->where_in('secret_number',$secret_number)
                 ->update('mic_coi');
        if ($this->db->affected_rows() == 0)
        {
            write_log('error',"Active failed.");
            return FALSE;
        }
        write_log('debug',"Updated");
        return TRUE;
    }
    
    /**
     * Update COI whith loaded properties
     * @return  TRUE if successful, otherwise FALSE
     * */    
    public function update()
    {                
        if ($this->secret_number == '')
        {
            write_log('debug','COI has not loaded to active');
            return FALSE;
        }                
        
        $CI =& get_instance();
        if (isset($CI->ORI_MO))
        {            
            $this->updated_by = $CI->ORI_MO->msisdn;
                    
        }                        
                
        $this->db->where('secret_number',$this->secret_number)
                 ->update('mic_coi',get_object_vars($this));
                 
        if ($this->db->affected_rows() == 0)
        {
            write_log('error',"Update failed. $this->secret_number");
            return FALSE;
        }
        write_log('debug',"Updated");
        return TRUE;
    }
}
/* End of file*/