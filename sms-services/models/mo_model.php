<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Mo_model extends CI_Model
{
	//constructor
	public function __construct()
    {
        write_log('debug',"MO Model constructing",'core');
        parent::__construct();
    }             

    /**
     * Get MO from database
     * @param   $mo_id: ID of MO record in mo table     
     * @return  rowObject - A database row object, FALSE if not found
     * */    
    public function get_mo($mo_id)
    {

            $this->db->select('mo.*')
                     ->from('mo')                         
                     ->where('mo.id',$mo_id);
                     
            $query = $this->db->get();                
            
            if ($query->num_rows() == 0)
            {
                return FALSE;
            }
                        
            return $query->row();
    }
    
    /**
     * Check if a MO exist in MO database base on sender, receiver, time and content
     * @param   $MO: MO object
     * @return  Boolean: TRUE if exist, FALSE otherwise
     */
    function is_inserted($MO)
    {
        $this->db->select('id')
                 ->from('mo')
                 ->where('msisdn',$MO->msisdn)
                 ->where('short_code',$MO->short_code)
                 ->where('time',$MO->time)
                 ->where('content',$MO->content);
        $query = $this->db->get();        
        if ($query->num_rows() == 0) return FALSE;
        return TRUE;
    }
    
    /**
     * Insert a MO into database (table mo)
     * @param   $MO: MO object
     * @return  inserted mo_id
     * */
    function insert(&$MO)
    {                       
        $props = $MO->get_all_properties(TRUE);
                
        if (isset($props['id']))
        {
            unset($props['id']);
        }
        $this->db->insert('mo',$props);        
        $MO->id = $this->db->insert_id();
        
        //Mark as newly added - use when update/insert balance in function "save"        
        $MO->inserted = TRUE;                        

        // Check to see if the query actually performed correctly
        if ($this->db->affected_rows() > 0) {
            $MO->clear_changes();
            return $MO->id;
        }
        
        write_log("error","<strong>MO model cannot insert MO object into database</strong>",'core');
        return FALSE;        
    }
    
    /**
     * Update changed properties of a MO into database (table mo)
     * @param   $MO: MO object
     * */
    function update($MO)
    {           
        if ((! $MO->is_set('id')) || ($MO->id == ''))
        {
            return FALSE;
        }    
        
        $update = $MO->get_all_properties(TRUE,TRUE);
        
        if (count($update) == 0)
        {
            return FALSE;
        }        
        $this->db->where('id',$MO->id)->update('mo',$update);  
        // Check to see if the query actually performed correctly            
        if ($this->db->affected_rows() > 0) {
            $MO->clear_changes();
            return $MO->id;
        }            
//        write_log("error","<strong>MO model cannot update MO object into database.SQL string:\n</strong>".$this->db->last_query(),'core');
        return FALSE;
    }
    
    private function update_last_provision_time($mo_id_array)
    {
        if ( ! is_array($mo_id_array))
        {
            $mo_id_array = array($mo_id_array);
        }
        
        if (count($mo_id_array) == 0)
        {
            return FALSE;
        }
        
        $now = time();
        $this->db->set('last_provision_time',$now)
                 ->where_in('id',$mo_id_array)
                 ->update('m_mo');
        // Check to see if the query actually performed correctly
        if ($this->db->affected_rows() > 0) {
            return TRUE;
        }
        write_log("error","MO model cannot update last provision time",'core');
        return FALSE;                 
    }
    
    /**
     * Update an array of MO object into database     
     * */
    function update_batch($MO_array)
    {                
        write_log('debug',"MO Model saves cached MO",'core');
        $updates = array(); //store balance update information
        $inserts = array(); //store balance insert information
        
        $cached_ids = array(); //store responded mo_id to update last provision time field
        
        foreach($MO_array as $MO)
        {            
            if ($MO->saved)
            {
                write_log('debug',"MO message saved before already, canceled.",'core');                
            }
            else
            {                
                //Process balance for Queue update
                if($MO->is_changed('balance') || $MO->is_changed('delta_balance'))
                {               
                    if ($MO->delta_balance != 0)
                    {                        
                        $updates[$MO->delta_balance][] = $MO->id;                        
                    }                    
                    
                    //clear this change if any as it will be updated later after adding to above array
                    $MO->clear_changes('balance');
                    $MO->clear_changes('delta_balance');
                }
                
                //Process last response time to update batch
                if ($MO->responded)
                {
                    $cached_ids[] = $MO->id;
                    $MO->clear_changes('responded');                    
                }
                                
                //Update MO information into mo table
                $this->update($MO);                                
                $MO->saved = TRUE;                
            }
        }
        
        //Update last provision time        
        $this->update_last_provision_time($cached_ids);
        
        //Update balance in queue                       
        if ($updates)
        {
            $this->load->model('Mo_queue_model','MO_Queue');
            $this->MO_Queue->update_balance($updates);
            
        }                            
    }
    
    
    /**
     * Get array of auto correct rule
     * for specified module.
     * By default, get rules of core system.
     * @param   $code: code which rules belong to.
     * */
    public function get_auto_correct_rules($code = 'core')
    {        
        write_log('debug','Getting auto correction rule with the code of "'.$code.'"','core');
        $this->db->select('pattern,replacement')
                 ->from('mo_auto_correct_rule')
                 ->where("`pattern`<> '' AND `pattern` IS NOT NULL",NULL,FALSE) //Always discard items with empty pattern
                 ->where("code",$code)
                 ->where('enable',TRUE)                 
                 ->order_by('order')
                 ->order_by('length(`replacement`)','DESC')
                 ->order_by('replacement','DESC');
        $query = $this->db->get();
        
        return $query->result_array();
    }
    
    /**
     * Canccel MOs
     * @param   $msisdn: phone number of MOs which will be cancelled
     * @param   $keyword: customer MO whith this keyword will be cancelled, NULL mean all
     * */
    public function cancel($msisdn, $keyword = NULL)
    {
        if (! $msisdn)
        {
            return FALSE;
        }
        
        $this->db->set('cancelled',TRUE)
                 ->where('msisdn',$msisdn);
                 
        if ($keyword)
        {
            $this->db->where('keyword',$keyword);
        }
        
        $this->db->update('mo');
        
        return TRUE;
    }
        
    /**
     * Get list of template code which have sent to sender in specified period.
     * @param   msisdn
     * @param   from: begin time, NULL mean 00:00:00 today
     * @param   to: end time, NUL mean 23:59:59 today
     * @return  array of template code (string)
     * */
    /*
    public function get_replied_mt($msisdn, $from = NULL, $to = NULL)
    {
        $from = (is_null($from))?date('Y-m-d 00:00:00'):$from;
        $to = (is_null($to))?date('Y-m-d 23:59:59'):$to;
        $this->db->select('mt.id,mo.keyword,mt_template.code')
                 ->from('mo')
                 ->join('mt','mo.id = mt.mo_id','right')
                 ->join('mt_template','mt.template_id = mt_template.id')
                 ->where('mo.msisdn',$msisdn) 
                 ->where("FROM_UNIXTIME(".$this->db->protect_identifiers('mo',TRUE).".".$this->db->protect_identifiers('time').") BETWEEN ".$this->db->escape($from)." AND ".$this->db->escape($to));
        $query = $this->db->get();
        
        return $query->result_array();
    } 
     * 
     */   
    
    /**
     * Count mumber of MTs which have been reply to a MO
     * @param interger $mo_id
     * @return interger
     */
    public function count_replied_mt($mo_id)
    {
        $this->db->where('mo_id')->from('mt');
        return $this->db->count_all_results();
    }    
    
}
/* End of file */