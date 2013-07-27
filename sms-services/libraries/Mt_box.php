<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Mt_box
{        
    //Cache mo message to save after processing by service controller
    private $cache = array();        
    
    private $default_MT; //use when add new MT into box
    
    private $max_cache_size = 1000;    
        
	//constructor
    public function __construct()
    {        
        write_log('debug',"MT_Box Constructing",'core');                                        
        
        $this->default_MT = new Mt();                                     
    }
    
    public function set_max_cache_size($size = 1000)
    {
        $this->max_cache_size = $size;
    }
    
    public function get_max_cache_size()
    {
        return $this->max_cache_size;
    }    
    
    /**
     * Clear and reset cache
     * */
    public function reset()
    {
        $this->cache = array();
    }
    
    /**
     * Add signature into MT content
     * 
     * */
    function _sign($content)
    {        
        $l1 = 160; //160
        $l2 = strlen($content);            
        $leng = ($l2==0)?$l1:(($l1+(($l1-$l2)%$l1))%$l1);    
        
        $CI =& get_instance();
        $signatures = $CI->config->item('sms_signagures');   
        
        foreach ($signatures as $signature)
        {            
            if (strlen($signature)<$leng)
            {
                $content .= "\n$signature";
                return $content;
            }
        }        
        return $content;        
    }            
    
    public function set_default_MT($data)
    {
        $this->default_MT->reset();
        $this->default_MT->load($data);
    }
    
    public function get_default_MT()
    {
        return $this->default_MT;
    }
    
    public function clear_default_MT()
    {
        $this->default_MT->reset();
    }
    
    /**
     * Add MT into cache
     * @param   $MT: MT message object
     * */
    public function add($MT)
    {
        if ( ! is_object($MT))
        {            
            $props = (is_array($MT))?$MT:array('content'=>$MT);
        }
        else
        {
            $props = $MT->get_all_properties();
            if ( ! isset($props['content']))
            {
                $props['content'] = '';
            }
        }       
                
        $props['content'] = trim($props['content']);
              
        $New_MT = clone $this->default_MT;
        $New_MT->load($props);
        
        if ($New_MT->content == '')
        {  
            write_log('error',"<em>MT_Box aborts an empty message from</em> $New_MT->short_code <em>to</em> $New_MT->msisdn <em>via</em> $New_MT->smsc_id",'mt');
            return FALSE;
        }
        
        if($New_MT->short_code != '' && $New_MT->msisdn != '' && $New_MT->smsc_id != '')
        {
            if ( ! ($New_MT->is_set("no_signature") && $New_MT->no_signature))
            {                
                $New_MT->content = $this->_sign($New_MT->content);
            }
                                  
            $this->cache[] = $New_MT;
            write_log('debug',highlight_info("<strong>MT_Box added a message from $New_MT->short_code to $New_MT->msisdn via $New_MT->smsc_id:</strong>\n").highlight_content($New_MT->content),'mt');
            
            if (strlen($New_MT->content) > config_item('sms_len_threshold'))
            {
                write_log('error','<strong>[WARNING] Length of added MT is '.strlen($New_MT->content).', exceed threshold of '.config_item('sms_len_threshold').'. Template id = '.$New_MT->service_action_id.'</strong>','mt');
            }            
        }
        else
        {
            write_log('error',"MT_Box discarded a MT. <em>Missing short_code or msisdn or smsc_id.</em>",'mt');
            return FALSE;
        }                
        
        if (count($this->cache) == $this->max_cache_size)
        {            
            write_log('debug',"MT Box reaches max cache size of $this->max_cache_size, do provisioning to free up memory",'core');                  
            $this->process();                    
        }
        
        return TRUE;        
    }   
    
    /**
     * Process cached messages
     * - save to database
     * - send to gateway     
     * - reset cache
     * */
    public function process()
    {
        $CI =& get_instance();
        
        $CI->scp->process_mt_box();
    }
    
    
    /**
     * Get array of cached message
     * @param   $saved: saving status of cached MT will be got. TRE, FALSE or 'ALL' (for not specify).      
     * @param   $sent: sending status. TRUE, FALSE, 'ALL' (for not specify).
     * @return  array of cached MT object.
     * */     
    public function get_cache($saved = 'ALL', $sent = 'ALL')
    {
        $return = array();
        foreach ($this->cache as $MT)
        {
            $ok = (($saved == 'ALL') || ($MT->saved == $saved)) && (($sent == 'ALL') || ($MT->sent == $sent));
            if ($ok)
            {
                $return[] = $MT;
            }                                                        
        }
        
        return $return;
    }  
}
/* End of file */