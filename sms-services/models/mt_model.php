<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Mt_model extends CI_Model
{                    
    private $db_mgw;
    private $detected_smsc; //cache detected smsc for reuse
    private $CI;
    
	//constructor
	public function __construct()
    {        
        write_log('debug',"MT Model constructing",'core');
        parent::__construct();
        $this->CI =& get_instance();
    }    
    
    /**
     * Load MT from database
     * @param   $mt_id: ID of MT record in mt table
     * @return  a database row object
     * */
    public function get_mt($mt_id)
    {

            $this->db->select('mt.*')
                     ->from('mt')                         
                     ->where('mt.id',$mt_id);
                     
            $query = $this->db->get();                
            
            if ($query->num_rows() == 0)
            {
                return FALSE;
            }
            
            return $query->row();                        
    }
    
    /**
     * Insert a MT into database (table mt)
     * @param   $MT: MT object
     * @return  inserted mt_id
     * */
    public function insert(&$MT)
    {
        $props = $MT->get_all_properties(TRUE);
        if (isset($props['id']))
        {
            unset($props['id']);
        }
        $this->db->insert('mt',$props);        
        $MT->id = $this->db->insert_id();
        // Check to see if the query actually performed correctly
        if ($this->db->affected_rows() > 0) {
            return $MT->id;
        }
        return FALSE;           
        
    }
    
    /**
     * Update a MT in database (table mo)
     * @param   $MT: message object
     * */
    public function update($MT)
    {           
        if ((! $MT->is_set('id')) || $MT->id =='')
        {
            return FALSE;
        }
        
        $update = $MT->get_all_properties(TRUE,TRUE);
        
        if ($update)
        {
            $this->db->where('id',$MT->id)->update('mt',$update);
        }
          
        // Check to see if the query actually performed correctly
        if ($this->db->affected_rows() > 0) 
        {
            return TRUE;
        }
        return FALSE;        
    }
    
    /**
     * Save (insert/update) $cache to database
     * */
    public function insert_batch($MT_array)
    {   
        $now = time();
        write_log('debug',"MT Model saving cached MT messages",'core');
        
        if ( ! is_array($MT_array))
        {
            $MT_array = array($MT_array);
        }
        
        if (count($MT_array) == 0)
        {
            write_log('debug',"MT Cache is empty, nothing to save",'core');
            return FALSE;
        }
        
        //Prepare data to insert
        $data = array();
        foreach($MT_array as $MT)
        {            
            if ($MT->saved)
            {
                write_log('debug',"MT message inserted to database already, cancelled",'core');
            }
            else
            {
                $MT->time = $now;
                $props = $MT->get_all_properties(TRUE);
                $data[] = $props;                
                $MT->saved = TRUE;                
            }   
        }  
                                          
        $affected = insert_batch($this->db,'mt',$data);
        if ($affected == 0)
        {
            write_log("error", "<strong>MT model cannot insert batch of MT into database</strong>",'core');
            return FALSE;
        }
        
        $id = $this->db->insert_id();
                
        for ($i=0;$i<$affected;$i++)
        {
            $MT_array[$i]->id = $id;
            $id += $this->db->auto_incremental_offset;            
        }                    
                        
        write_log('debug',"MT Cache saved ($affected messages)",'core');
        return TRUE;
    }    
    
    /**
     * Send a single MT object to gateway
     * */
    public function send($MT)
    {
        write_log('debug',"MT Model sending messages",'core');        
        
        if ( ! $MT)
        {
            write_log('error',"Nothing to send",'core');
            return FALSE;
        }
        
        if (! isset($this->db_mgw))
        {
            $this->db_mgw = $this->load->database('mgw',TRUE);            
        }                                

        if ($MT->sent)
        {
            write_log('debug',"Message sent before, cancelled",'core');
        }
        elseif (in_array($MT->smsc_id,$this->CI->config->item('disabled_smsc')))
        {
            write_log('error',"Message sent via a disabled SMSC, cancelled",'core');
        }        
        else
        {                                   
            $props = $this->CI->config->item('MT');
            $props['account']   = $MT->mo_id;
            $props['receiver']  = $MT->msisdn;
            $props['msgdata']   = $MT->content;
            $props['sender']    = $MT->short_code;
            $props['smsc_id']   = $MT->smsc_id;
            $props['service']   = $MT->keyword;
            $props['time']      = $MT->time;
            $props['dlr_url']   = $props['dlr_url'].$MT->id;                                                    
                            
            $MT->sent = TRUE;                
        }                       
                       
        $affected = $this->db_mgw->insert('send_sms',$data);
        
        if ($affected == 0)
        {
            write_log("error","<strong>Send batch of MT message to gateway failed</strong>",'core');
            return FALSE;
        }
        else
        {                
            write_log('debug',"Sent $affected cached messages to messaging gateway",'core');
            return TRUE;
        }                
    }        
    
    /**
     * Send MT in $cache to gateway
     * */
    public function send_batch($MT_array)
    {
        write_log('debug',"MT Model sending messages",'core');        
        
        if (count($MT_array) == 0)
        {
            write_log('debug',"Cache is empty, nothing to send",'core');
            return FALSE;
        }
                        
        if (! isset($this->db_mgw))
        {
            $this->db_mgw = $this->load->database('mgw',TRUE);            
        }

        $data = array();
        $return = array();

        foreach($MT_array as $MT)
        {            
            if ($MT->sent)
            {
                write_log('debug',"Message sent before, cancelled",'core');
            }
            elseif (in_array($MT->smsc_id,$this->CI->config->item('disabled_smsc')))
            {                
                write_log('error',"Message sent via a disabled SMSC, cancelled",'core');                
            }
            else
            {                                   
                $props = $this->CI->config->item('MT');
                $props['account']   = $MT->mo_id;
                $props['receiver']  = $MT->msisdn;
                $props['msgdata']   = $MT->content;
                $props['sender']    = $MT->short_code;
                $props['smsc_id']   = $MT->smsc_id;
                $props['service']   = $MT->keyword;
                $props['time']      = $MT->time;                
                if (in_array($MT->smsc_id,$this->CI->config->item('no_dlr_smsc')))
                {
                    $props['dlr_mask'] = 0;
                    $props['dlr_url'] = NULL;                    
                }
                else
                {
                    $props['dlr_url']   = $props['dlr_url'].$MT->id; 
                }                                                
                                            
                $data[] = $props;                
                                
                $MT->sent = TRUE;
                
                $return[] = $MT;
            }        
        }           
                     
        $affected = '0';
        if ($data)
        {
            $affected = insert_batch($this->db_mgw,'send_sms',$data);            
        }        
        
        if ($affected === 0)
        {
            write_log("error","<strong>Send batch of MT message to gateway failed</strong>",'core');
            return FALSE;
        }
        else
        {                
            write_log('debug',"Sent $affected cached messages to messaging gateway",'core');
            return $return;
        }        
    }
    
    /**
     * Detect most suitable SMSC to send MT
     * @param   msisdn: phone number of receiver
     * @param   type: can be 'telco', 'modem' or 'all'. Default is 'all'
     * @return  smsc_id or FALSE if not found
     * */
    public function detect_smsc($msisdn, $type = 'all')
    {
        if ( ! $msisdn)
        {
            return FALSE;
        }
        
        if ( ! isset($this->detected_smsc[$msisdn]))
        {                    
            $this->db->select('smsc_id')
                     ->from('smsc')
                     ->where("'$msisdn' REGEXP ".$this->db->protect_identifiers('msisdn_pattern'),NULL,FALSE);
                     
            if ($type != 'all')
            {
                $this->db->where('type',$type);
            }
            $this->db->order_by('order')
                     ->limit(1);
            
                     
            $query = $this->db->get();
            if ($query->num_rows() == 0)
            {
                return FALSE;
            }
            
            $row = $query->row();
            $this->detected_smsc[$msisdn] = $row->smsc_id;           
        }
        
        return $this->detected_smsc[$msisdn];
    }
    
    /**
     *Check if a MT sent to msisdn
     * @param interger $action_id
     * @param array $msisdns
     * @param datetime $from: begin time, NULL means 00:00:00 today
     * @param datetime $to: end time, NUL means 23:59:59 today
     * 
     * @return interger: number of mt message found
     */
    public function is_exist($action_id, $msisdns = NULL, $from = NULL, $to = NULL)
    {
        $this->db->select('id')
                 ->from('mt')
                 ->where('service_action_id',$action_id);
        if ($msisdns)
        {            
            $this->db->where_in('msisdn',$msisdns);
        }
        
//        $from = (is_null($from))?date('Y-m-d 00:00:00'):$from;
//        $to = (is_null($to))?date('Y-m-d 23:59:59'):$to;
//        $this->db->where("FROM_UNIXTIME(".$this->db->protect_identifiers('mt',TRUE).".".$this->db->protect_identifiers('time').") BETWEEN ".$this->db->escape($from)." AND ".$this->db->escape($to));
        
        $from = strtotime((is_null($from))?date('Y-m-d 00:00:00'):$from);
        $to = strtotime((is_null($to))?date('Y-m-d 23:59:59'):$to);
        $this->db->where($this->db->protect_identifiers('time')." BETWEEN ".$this->db->escape($from)." AND ".$this->db->escape($to));
        
        $query = $this->db->get();
        
        return $query->num_rows();
    }
}
/* End of file */