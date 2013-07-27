<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/* Service controller class */
class Test_model extends CI_Model
{
    private $CI;

	//constructor
	public function __construct()
    {
        parent::__construct();
        $this->CI =& get_instance();
    }
    
    /**
     * Load customer information from database
     * @param   $msisdn: phone number
     * @param   $keyword: service keyword
     * @return  database row object, FALSE if customer not found
     * */
    public function load($msisdn,$keyword)
    {        
        $this->CI->db->from('sms_agent')
                     ->where('sms_agent.msisdn',$msisdn)
                     ->where('sms_agent.keyword',$keyword);                     
                 
        $query = $this->CI->db->get();                
        
        if ($query->num_rows() == 0)
        {
            return FALSE;
        }                                                                           
        return $query->first_row();
    }
    
    /**
     * Insert customer into database
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
        $this->CI->db->insert('sms_agent',$data);

        return TRUE; 
    }    
    
    /**
     * Insert customer into database
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
        
        $this->CI->db->where('id',$data['id'])->update('sms_agent',$data);

        return TRUE; 
    }      
}
/*End of file*/
