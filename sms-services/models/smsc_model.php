<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/* Service controller class */
class Smsc_model extends CI_Model
{
    private $max_mt_cache = array(); //cache
    
	//constructor
	public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * Return maximum mt number allowed by telco.
     * This parameter is set in database
     * @param   $smsc_id
     * @param   $short_code
     * @return  interger
     * */ 
    public function get_max_mt_num($smsc_id, $short_code)
    {        
        
        if (isset($this->max_mt_cache[$smsc_id][$short_code]))
        {
            return $this->max_mt_cache[$smsc_id][$short_code];
        }
        
        $this->db->select('max_mt')
                 ->from('m_smsc_detail')
                 ->where('smsc_id',$smsc_id)
                 ->where('short_code',$short_code);
        $query = $this->db->get();
                
        if ($query->num_rows() == 0)
        {
            $this->max_mt_cache[$smsc_id][$short_code] = 0;
        }
        else
        {
            $row = $query->row();
            $this->max_mt_cache[$smsc_id][$short_code] = $row->max_mt;
        }
               
        return $this->max_mt_cache[$smsc_id][$short_code];
    }    
}
/*End of file*/
