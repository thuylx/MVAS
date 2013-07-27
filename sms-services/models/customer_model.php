<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/* Service controller class */
class Customer_model extends CI_Model
{
	//constructor
	public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * Load customer information from database
     * @param   $msisdn: phone number
     * @return  database row object, FALSE if customer not found
     * */
    public function load($msisdn)
    {        
        $this->db->from('customer')
                 ->where('customer.msisdn',$msisdn);                     
                 
        $query = $this->db->get();                
        
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
        $this->db->insert('customer',$data);

        // Check to see if the query actually performed correctly
        if ($this->db->affected_rows() > 0) {
          return TRUE;
        }
        return FALSE;
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
        
        if (! isset($data['msisdn']))
        {
            return FALSE;
        }
        
        if ($data['msisdn'] == '')
        {            
            return FALSE;
        }
        
        $this->db->where('msisdn',$data['msisdn'])->update('customer',$data);

        // Check to see if the query actually performed correctly
        if ($this->db->affected_rows() > 0) {
          return TRUE;
        }
        return FALSE;
    }      
    
    /**
     * UPDATE BATCH OF CUSTOMER 
     * @param   array   $msisdn_array
     * @param   string  $property_name
     * @param   value
     * @return effected items
     */
    public function update_batch($msisdn_array, $property_name, $value)
    {
        $this->db->set($property_name,$value);
        $this->db->where_in('msisdn',$msisdn_array);
        $this->db->update('customer');
        
        // Check to see if the query actually performed correctly
        $return = $this->db->affected_rows();
        $return = ($return>0)?$return:FALSE;
        return $return;        
    }
    
    /**
     * Calculate cost a customer has paid in a day
     * @param   string, msisdn of customer
     * @param   date, in format of yyyy-mm-dd, NULL mean today
     * @return  total cost
     * */
    public function cal_day_cost($msisdn, $date = NULL)
    {
        /*
        SELECT `m_mo`.msisdn, date(from_unixtime(`m_mo`.time)) as `date`,sum(m_short_code.price) as cost FROM `m_mo` JOIN m_short_code ON m_mo.short_code = m_short_code.short_code
where msisdn = '+84904848688'
group by msisdn, date(from_unixtime(time))
*/
        $date = (is_null($date))?date('Y-m-d'):$date;
        $date_string = 'date(from_unixtime('.$this->db->protect_identifiers('mo',TRUE).'.'.$this->db->protect_identifiers('time').'))'; 
        $this->db->select_sum('short_code.price','cost')
                 ->from('mo')->join('short_code','mo.short_code = short_code.short_code')
                 ->where('msisdn',$msisdn)
                 ->where($date_string, $date)
                 ->group_by('msisdn')
                 ->group_by($date_string);
                 
        $query = $this->db->get();
        
        if ($query->num_rows() == 0)
        {
            return 0;
        }
        
        $row = $query->row();
        
        return $row->cost;
    }
}
/*End of file*/
