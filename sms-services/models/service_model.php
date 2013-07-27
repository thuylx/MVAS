<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/* Service controller class */
class Service_model extends CI_Model
{
	//constructor
	public function __construct()
    {
        parent::__construct();
        $this->load->library('Expression');
    }
    
    /**
     * Get service information from database
     * @param   $keyword: keyword or alias of service
     * @return  service database row object, FALSE if service not found
     * */
    public function get_service($keyword)
    {        
        write_log('debug','Service Model gets service '.$keyword,'core');
        $keyword = strtoupper($keyword);      
        
        $this->db->select('service_catalog.*')
                 ->from('service_catalog')
                 ->join('service_alias','service_catalog.service = service_alias.service','left')
                 ->where("(".$this->db->protect_identifiers('service_catalog', TRUE).".".$this->db->protect_identifiers('service')." = ".$this->db->escape($keyword)." OR ".$this->db->protect_identifiers('service_alias', TRUE).".".$this->db->protect_identifiers('alias')." = ".$this->db->escape($keyword).")",NULL,TRUE)                 
                 ->where("(".$this->db->protect_identifiers('service_alias', TRUE).".".$this->db->protect_identifiers('enable')." = 1 OR ".$this->db->protect_identifiers('service_alias', TRUE).".".$this->db->protect_identifiers('enable')." IS NULL)")
                 ->where('service_catalog.enable',TRUE);
                 
        $query = $this->db->get();                
        
        if ($query->num_rows() == 0)
        {
            return FALSE;
        }                                                                           
        return $query->first_row();
    }      
    
    /**
     * Get list of services which trigged by given trigger.
     * @param   $trigger
     * @return  array of service database row object, FALSE if no service not found
     * */      
    public function get_trigged_services($trigger)
    {
        $this->db->select('service,expression')
                 ->from('service_trigger')
                 ->where('LOWER(`trigger`)',strtolower($trigger))
                 ->where('enable',TRUE)
                 ->order_by('order');
        $query = $this->db->get();
        
        if ($query->num_rows() == 0)
        {
            return FALSE;
        }                
        
        $return = array();
        foreach($query->result() as $row)        
        {            
            write_log('debug',"Checking service ".$row->service,'core');
            $exp = $this->expression->evaluate($row->expression,$this->Evr);   
            
            if ($exp)
            {
                write_log('debug',"Service ".highlight_content($row->service)." is OK to run",'core');
                $return[] = $row->service;
            }                    
        }
        
        return $return;
    }
    
    /**
     * Get array of aliases for specified keyword
     * @param   $service: service keyword to get aliases
     * @return  array of aliases
     * */
    public function get_aliases($service)    
    {        
        $this->db->select('alias')
                 ->from('service_alias')
                 ->where('enable',TRUE)
                 ->where('service',$service);
        $query = $this->db->get();
        
        $aliases = array();
        
        foreach($query->result() as $row)
        {
            $aliases[] = $row->alias;
        }
        
        return $aliases;
    }   
    
    /**
     * Get service keyword from given aliases
     * @param   $alias: alias or keyword of service
     * @return  keyword of matched service or FALSE if not found.
     */    
     
    public function get_keyword($alias)    
    {        
        $this->db->select('service')
                 ->from('service_keyword')
                 ->where('enable',TRUE)
                 ->where('keyword',$alias)
                 ->limit(1);
        $query = $this->db->get();
        
        if ($query->num_rows() == 0)
        {
            return FALSE;
        }
        
        $row = $query->row();
        
        return $row->service;
    }    
    
    /**
     * Get array of accepted keyword for specified service including service keyword and aliases
     * @param   $alias: alias or keyword of service.
     * @param   $quick_mode: TRUE if $alias is service keyword, not an alias 
     * @return  array of keyword.
     */    
     
    public function get_keywords($alias, $quick_mode = TRUE)    
    {        
        if ($quick_mode)
        {
            $service = $alias;
        }
        else
        {
            $service = $this->get_keyword($alias);
        }
                    
        $keywords = $this->get_aliases($service);
        $keywords[] = $service;
        return $keywords;
    }      
}
//End of Service class
