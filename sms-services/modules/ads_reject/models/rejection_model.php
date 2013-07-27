<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Rejection_model extends CI_Model
{
    public function add($msisdn) 
    {
        $sql =  "INSERT IGNORE INTO ".$this->db->protect_identifiers('customer_ads_rejection',TRUE).
                " SET ".$this->db->protect_identifiers('msisdn')." = ".$this->db->escape($msisdn).",".
                $this->db->protect_identifiers('rejection_time')." = ".$this->db->escape(date('Y-m-d H:i:s',time())).";";
        $this->db->query($sql);
    }
}
/* End of file*/