<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/* Service controller class */
class Mt_template_model extends CI_Model
{
    
    /**
     * Get templates for mt content from database
     * @param   $service: optional, keyword of service
     * @param   $code: optional, code of the template
     * @return  template string, FALSE if not found
     * */
    public function get_templates($service=NULL,$code=NULL)
    {
        $service = strtoupper($service);
        
        $this->db->select('id,code,content,advertisement,expression')->from('mt_template')->where('enable',TRUE)->order_by('order');
        
        if ( ! is_null($service))
        {
            $this->db->where('service',$service);
        }

        if ( ! is_null($code))
        {
            $this->db->where('code',$code);
        }                  
                 
        $query = $this->db->get();                
        
        if ($query->num_rows() == 0)
        {
            write_log("debug","No template found.",'core');
        }                                                                           
        return $query->result();
    }

}
/*End of file*/
