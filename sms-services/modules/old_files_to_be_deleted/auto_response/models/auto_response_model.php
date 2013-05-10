<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Auto_response_model extends CI_Model
{   
    public function __construct()
    {
        write_log('debug','Auto response model constructing');
    }
    
    /**
     * Get templage codes tobe sent automatically
     * @param   $keyword: keyword of the service
     * @param   $short_code
     * */
    public function get_template_codes($keyword,$short_code)
    {
        $this->db->select('template_code, from, to, smsc, expression')
                 ->from('auto_response')
                 ->where('service',$keyword)
                 ->where('enable',TRUE)
                 ->order_by('order');
        $query = $this->db->get();
            
        if ($query->num_rows() == 0)
        {
            write_log("debug","NOTICE: No auto response item found");
        }
        
        return $query->result();
    }
}
/* End of file*/