<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Diem_model extends CI_Model
{
    /**
     * Get dap_an from database
     * @param   $ma_tinh: required
     * @param   $SBD: required
     * @return  FALSE if not available
     * */     
    public function get_diem($ma_tinh, $SBD)
    {
        $this->db->from('tn2011_diem_thi')
                 ->where('ma_tinh',$ma_tinh)
                 ->where('sbd',$SBD);
        $query = $this->db->get();
        if ($query->num_rows() == 0)
        {
            return FALSE;
        }

        return $query->row();
    }
    
    public function verify_ma_tinh($ma_tinh)
    {
        $this->db->from('tn2011_tinh')
                 ->where('ma_tinh',$ma_tinh)
                 ->or_where('ten_tinh',$ma_tinh);
        $query = $this->db->get();
        
        if ($query->num_rows() == 0)
        {
            return FALSE;
        }
        $row = $query->row();
        return $row->ma_tinh;
    }

}
/* End of file*/