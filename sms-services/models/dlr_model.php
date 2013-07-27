<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/* Service controller class */
class Dlr_model extends CI_Model
{
//    public function update($mt_id,$status,$time)
//    {
//        $sql =  'INSERT INTO '.$this->db->protect_identifiers('dlr',TRUE).' ('.$this->db->protect_identifiers('mt_id').','.$this->db->protect_identifiers('status').','.$this->db->protect_identifiers('time').') '.
//                'VALUES ('.$this->db->escape($mt_id).','.$this->db->escape($status).','.$this->db->escape($time).') '.
//                'ON DUPLICATE KEY UPDATE '.$this->db->protect_identifiers('status').'='.$this->db->escape($status).';';
//        $this->db->query($sql);
//        
//        if ($this->db->affected_rows() == 0)
//        {
//            write_log('error','Update DLR failed, MT ID = '.$mt_id,'core');
//            return FALSE;
//        }
//        write_log('debug','Update DLR successfully, status = '.$status.', MT ID = '.$mt_id,'core');
//        return TRUE;
//        
//    }
    
    public function update($mt_id,$status,$time)
    {
        $this->db->set('status',$status)
                ->set('last_status_time',$time)
                ->where('id',$mt_id);
        $this->db->update('mt');    
        if ($this->db->affected_rows() == 0)
        {
            write_log('error','Update DLR failed, MT ID = '.$mt_id,'core');
            return FALSE;
        }
        write_log('debug','Update DLR successfully, status = '.$status.', MT ID = '.$mt_id,'core');        
        return TRUE;        
    }
}
/*End of file*/