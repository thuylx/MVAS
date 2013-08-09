<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/* Service controller class */
class Dba extends CI_Model
{    
    private $db_mgw;
    
    public function __construct()
    {
        parent::__construct();
        $this->db_mgw = $this->load->database('mgw',TRUE);        
    }
    
    /**
     * Function to flush dlr from kannel database     
     * @param   $date: data older than this date will be purged     
     * */
    public function purge_dlr($date)
    {        
        $sql = "DELETE FROM `kannel`.`dlr` WHERE `time` < ".$this->db_mgw->escape(strtotime($date)).";";
        $this->db_mgw->query($sql);
        
        $affected_rows = $this->db_mgw->affected_rows();
        
        write_log('error',"Flushed $affected_rows dlr: ".$sql,'maintenance');
        
        if ($affected_rows)
        {
            $sql = "OPTIMIZE TABLE `kannel`.`dlr`";
            $this->db_mgw->query($sql);
        }
        
        return $affected_rows;
    }    
    
    /**
     * Function to flush old sms from kannel database     
     * @param   $date: data older than this date will be purged     
     * */
    public function purge_kannel_sent_sms($date)
    {        
        $date = strtotime($date);
//        $sql = "DELETE FROM `kannel`.`sent_sms` WHERE DATE(FROM_UNIXTIME(`time`)) < ".$this->db_mgw->escape($date).";";
        $sql = "DELETE FROM `kannel`.`sent_sms` WHERE `time` < ".$this->db_mgw->escape($date).";";
        $this->db_mgw->query($sql);
        
        $affected_rows = $this->db_mgw->affected_rows();
        
        write_log('error',"Flushed $affected_rows Kannel sent SMS: ".$sql,'maintenance');
        
        if ($affected_rows)
        {
            $sql = "OPTIMIZE TABLE `kannel`.`sent_sms`";
            $this->db_mgw->query($sql);
        }
        
        return $affected_rows;
    }       
    
    /**
     * Function to flush old MO from mvas database     
     * @param   $date: data older than this date will be purged     
     * */
    public function purge_mo($date)
    {
//        $sql = "DELETE FROM ".$this->db->protect_identifiers('mo',TRUE)." WHERE DATE(FROM_UNIXTIME(".$this->db->protect_identifiers('time').")) < ".$this->db->escape($date).";";
        $date = strtotime($date);
        $sql = "DELETE FROM ".$this->db->protect_identifiers('mo',TRUE)." WHERE `time` < ".$this->db->escape($date).";";
        $this->db->query($sql);
        $affected_rows = $this->db->affected_rows();
        
        write_log('error',"Purged $affected_rows MO: ".$sql,'maintenance');
        
        if ($affected_rows)
        {
            $sql = "OPTIMIZE TABLE ".$this->db->protect_identifiers('mo',TRUE);
            $this->db->query($sql);            
        }
        
        return $affected_rows;
    }
    
    /**
     * Function to flush old MO from mvas database     
     * @param   $date: data older than this date will be purged     
     * */
    public function purge_mt($date)
    {        
        $table = $this->db->protect_identifiers('mt',TRUE);
//        $table2 = $this->db->protect_identifiers('dlr',TRUE);
        
        $date = strtotime($date);
        
        //Purge dlr
//        $sql = "DELETE FROM $table2".
//               " WHERE `mt_id` IN (SELECT `id` FROM `mvas`.$table WHERE DATE(FROM_UNIXTIME(`mvas`.$table.`time`)) < ".$this->db->escape($date).");";
//        $sql = "DELETE FROM $table2".
//               " WHERE `mt_id` IN (SELECT `id` FROM `mvas`.$table WHERE `mvas`.$table.`time` < ".$this->db->escape($date).");";
//        
//        $this->db->query($sql);
//        $affected_rows = $this->db->affected_rows();
//        
//        write_log('error',"Purged $affected_rows DLR: ".$sql,'maintenance');
//        
//        if ($affected_rows)
//        {        
//            $sql = "OPTIMIZE TABLE $table2";
//            $this->db->query($sql);                        
//        }    
        
        //Purge MT                
//        $sql = "DELETE FROM $table WHERE DATE(FROM_UNIXTIME(".$this->db->protect_identifiers('time').")) < ".$this->db->escape($date).";";
        $sql = "DELETE FROM $table WHERE `time` < ".$this->db->escape($date).";";
        $this->db->query($sql);
        $affected_rows = $this->db_mgw->affected_rows();
        
        write_log('error',"Purged $affected_rows MT: ".$sql,'maintenance');
        
        if ($affected_rows)
        {        
            $sql = "OPTIMIZE TABLE $table";
            $this->db->query($sql);                        
        }                                                
        
        return $affected_rows;
    }    
    
    public function purge_mt_without_mo()
    {
        $table = $this->db->protect_identifiers('mt',TRUE);
//        $table2 = $this->db->protect_identifiers('dlr',TRUE);
        $table_mo = $this->db->protect_identifiers('mo',TRUE);
        
        //Purge MT                
        $sql = "DELETE FROM $table WHERE mo_id NOT IN (SELECT id FROM $table_mo)";
        $this->db->query($sql);
        $affected_rows = $this->db_mgw->affected_rows();
        
        write_log('error',"Purged $affected_rows MT: ".$sql,'maintenance');
        
        if ($affected_rows)
        {        
            $sql = "OPTIMIZE TABLE $table";
            $this->db->query($sql);                        
        }
        
//        //Purge dlr
//        $sql = "DELETE FROM $table2".
//               " WHERE `mt_id` NOT IN (SELECT `id` FROM `mvas`.$table)";
//        $this->db->query($sql);
//        $affected_rows = $this->db->affected_rows();
//        
//        write_log('error',"Purged $affected_rows DLR: ".$sql,'maintenance');
//        
//        if ($affected_rows)
//        {        
//            $sql = "OPTIMIZE TABLE $table2";
//            $this->db->query($sql);                        
//        }    
        
        return $affected_rows;        
    }
    
    /**
     * Function to flush old MO from mvas database     
     * @param   $date: customer which last_date older than this date will be purged     
     * */
    public function purge_customer($date)
    {
        $date = strtotime($date);
        $sql = "DELETE FROM ".$this->db->protect_identifiers('customer',TRUE)." WHERE ".$this->db->protect_identifiers('last_mo_time')." < ".$this->db->escape($date).";";
        $this->db->query($sql);
        $affected_rows = $this->db->affected_rows();
        
        write_log('error',"Purged $affected_rows Customers: ".$sql,'maintenance');
        
        if ($affected_rows)
        {        
            $sql = "OPTIMIZE TABLE ".$this->db->protect_identifiers('customer',TRUE);
            $this->db->query($sql);
        }          
        
        return $affected_rows;
    }       
    
    /**
     * Function to archive database
     *  - Copy old data to archive database     
     * @param   $date: data older than this date will be archived
     * @return  affected_rows
     * */    
    public function archive_kannel_sent_sms($date)
    {        
//        $date = strtotime($date);
//        $sql =  "INSERT IGNORE INTO `archive`.`kannel_sent_sms` ".
//                "SELECT * FROM `kannel`.`sent_sms` WHERE `kannel`.`sent_sms`.`smsc_id` NOT IN ('GSM-Modem','GSM-Modem-2') AND `kannel`.`sent_sms`.`momt` IN ('MO','MT') AND `kannel`.`sent_sms`.`time` < ".$this->db->escape($date);
//                
//        $this->db_mgw->query($sql);
//        
//        write_log('error','Archived '.$this->db_mgw->affected_rows().' Kannel messages: '.$sql,'maintenance');
//        
//        return $this->db_mgw->affected_rows();            
    }    
    
    /**
     * Function to archive database
     *  - Copy old data to archive database     
     * @param   $date: data older than this date will be archived
     * @return  affected_rows
     * */    
    public function archive_mo($date)
    {
        $date = strtotime($date);
        $table = $this->db->protect_identifiers('mo',TRUE);
        $sql =  "INSERT IGNORE INTO `archive`.$table ".
                "SELECT * FROM `mvas`.$table WHERE  `mvas`.$table.`smsc_id` NOT IN ('GSM-Modem', 'GSM-Modem-2') AND `mvas`.$table.`time` < ".$this->db->escape($date);
                
        $this->db->query($sql);
        
        write_log('error','Archived '.$this->db->affected_rows().' MO: '.$sql,'maintenance');
        
        return $this->db->affected_rows();            
    }
    
    /**
     * Function to archive database
     *  - Copy old data to archive database     
     * @param   $date: data older than this date will be archived
     * @return  affected_rows
     * */    
    public function archive_mt($date)
    {
        $date = strtotime($date);
        $table = $this->db->protect_identifiers('mt',TRUE);
        $sql =  "INSERT IGNORE INTO `archive`.$table ".
                "SELECT * FROM `mvas`.$table WHERE `mvas`.$table.`time` < ".$this->db->escape($date);                        
        $this->db->query($sql);
        
        write_log('error','Archived '.$this->db->affected_rows().' MT: '.$sql,'maintenance');
        
        $success = $this->db->affected_rows();
        
        if ($success)
        {
            $table2 = $this->db->protect_identifiers('dlr',TRUE);
            $sql =  "INSERT IGNORE INTO `archive`.$table2 ".
                    "SELECT * FROM `mvas`.$table2 WHERE `mt_id` IN (SELECT `id` FROM `mvas`.$table WHERE `mvas`.$table.`time` < ".$this->db->escape($date).");";                        
            $this->db->query($sql);        
            
            write_log('error','Archived '.$this->db->affected_rows().' DLR: '.$sql,'maintenance');      
        }      
                        
        return $success;             
    }    
    
    public function archive_mt_without_mo()
    {
        $table = $this->db->protect_identifiers('mt',TRUE);
        $sql =  "INSERT IGNORE INTO `archive`.$table ".
                "SELECT * FROM `mvas`.$table WHERE mo_id NOT IN (SELECT id FROM m_mo) AND smsc_id NOT IN ('GSM-Modem','GSM-Modem-2')";                        
        $this->db->query($sql);
        
        write_log('error','Archived '.$this->db->affected_rows().' MT: '.$sql,'maintenance');
        
        $success = $this->db->affected_rows();
        
//        if ($success)
//        {
//            $table2 = $this->db->protect_identifiers('dlr',TRUE);
//            $sql =  "INSERT INTO `archive`.$table2 ".
//                    "SELECT * FROM `mvas`.$table2 WHERE `mt_id` IN (SELECT `id` FROM `mvas`.$table WHERE DATE(FROM_UNIXTIME(`mvas`.$table.`time`)) < ".$this->db->escape($date).");";                        
//            $this->db->query($sql);        
//            
//            write_log('error','Archived '.$this->db->affected_rows().' DLR: '.$sql,'maintenance');      
//        }      
                        
        return $success;         
    }
    
    /**
     * Function to archive database
     *  - Copy old data to archive database     
     * @param   $date: data older than this date will be archived
     * @return  affected_rows
     * */    
    public function archive_customer($date)
    {
        $date = strtotime($date);
        $table = $this->db->protect_identifiers('customer',TRUE);
        $sql =  "INSERT INTO `archive`.$table ".
                "SELECT * FROM `mvas`.$table WHERE `mvas`.$table.`last_mo_time`< ".$this->db->escape($date)." ".
                " AND `mvas`.$table.`last_mt_time`< ".$this->db->escape($date)." ".
                "ON DUPLICATE KEY UPDATE  ".
                "`archive`.$table.`last_mo_time` = VALUES(`last_mo_time`), ".
                "`archive`.$table.`last_mt_time` = VALUES(`last_mt_time`), ".
                "`archive`.$table.`count` = `archive`.`m_customer`.`count` + VALUES(`count`), ".
                "`archive`.$table.`revenue` = `archive`.`m_customer`.`revenue` + VALUES(`revenue`), ".
                "`archive`.$table.`birthday` = VALUES(`birthday`), ".
                "`archive`.$table.`sex` = VALUES(`sex`), ".
                "`archive`.$table.`area_id` = VALUES(`area_id`), ".
                "`archive`.$table.`vip` = VALUES(`vip`), ".
                "`archive`.$table.`smsc_id` = VALUES(`smsc_id`), ".
                "`archive`.$table.`enable` = VALUES(`enable`), ".
                "`archive`.$table.`description` = VALUES(`description`)";
                
        $this->db->query($sql);
        
        write_log('error','Archived '.$this->db->affected_rows().' Customers: '.$sql,'maintenance');
        
        return $this->db->affected_rows();
    }    
    
    /**
     * Function to flush old lottery cache items from mvas database     
     * @param   $date: data older than this date will be purged     
     * */
    public function purge_lottery_cache($date)
    {
        $date = strtotime($date);
        $sql = "DELETE FROM ".$this->db->protect_identifiers('lottery_cache',TRUE)." WHERE ".$this->db->protect_identifiers('time')." < ".$this->db->escape($date).";";
        $this->db->query($sql);
        $affected_rows = $this->db->affected_rows();
        
        write_log('error',"Purged $affected_rows Lottery Cached Items: ".$sql,'maintenance');
        
        if ($affected_rows)
        {
            $sql = "OPTIMIZE TABLE ".$this->db->protect_identifiers('lottery_cache',TRUE);
            $this->db->query($sql);            
        }
        
        return $affected_rows;
    }
}
/*End of file*/