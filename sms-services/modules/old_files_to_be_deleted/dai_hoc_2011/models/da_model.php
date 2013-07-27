<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Da_model extends CI_Model
{
    /**
     * Get dap_an from database
     * @param   $ten_mon: required
     * @param   $ma_de: required
     * @return  FALSE if not available
     * */     
    public function get_dap_an($ten_mon, $ma_de) 
    {
        $this->db->select('id,dap_an')
                 ->from('tn2011_dap_an')
                 ->where('ten_mon',$ten_mon)
                 ->where('ma_de',$ma_de);
        $query = $this->db->get();
        if ($query->num_rows() == 0)
        {
            return FALSE;
        }
        $row = $query->row();
        return $row->dap_an;
    }

}
/* End of file*/