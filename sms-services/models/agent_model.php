<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/* Service controller class */
class Agent_model extends CI_Model
{
	//constructor
	public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * Load agent information from database
     * @param   $msisdn: phone number
     * @param   $service: service keyword
     * @return  database row object, FALSE if customer not found
     * */
    public function load($msisdn,$service,$password = NULL)
    {        
        //standarize msisdn
        $msisdn = preg_replace('/^(\+?(84|0)?)/','',$msisdn);
        $msisdns[] = "+84$msisdn";
        $msisdns[] = "+$msisdn";
        $msisdns[] = "0$msisdn";
        $msisdns[] = "$msisdn"; 
        $this->db->from('sms_agent')
                 ->where_in('sms_agent.msisdn',$msisdns)
                 ->where('sms_agent.service',$service);   
                 
        if ($password)
        {
            $this->db->where('password',$password);
        }                  
                 
        $query = $this->db->get();
        
        if ($query->num_rows() == 0)
        {
            return FALSE;
        }                                                                           
        return $query->first_row();
    }
    
    /**
     * Insert agent into database
     * @param   $data: customer information (object or array)          
     * */      
    public function insert($data)    
    {                          
        if (is_object($data))
        {
            $data = get_object_vars($data);
        }        
        $now = time();
        $data['first_date'] = date("Y-m-d", $now);
        $data['last_date'] = date("Y-m-d", $now);
        $this->db->insert('sms_agent',$data);

        // Check to see if the query actually performed correctly
        if ($this->db->affected_rows() > 0) {
          return TRUE;
        }
        return FALSE;        
    }    
    
    /**
     * update agent into database
     * @param   $data: customer information (object or array)      
     * */      
    public function update($data)
    {
        if (is_object($data))
        {
            $data = get_object_vars($data);
        }        
        
        if (! isset($data['id']))
        {
            return FALSE;
        }
        
        if ($data['id'] == '')
        {            
            return FALSE;
        }
        
        $this->db->where('id',$data['id'])->update('sms_agent',$data);

        // Check to see if the query actually performed correctly
        if ($this->db->affected_rows() > 0) {
          return TRUE;
        }
        return FALSE;        
    }      
}
/*End of file*/
