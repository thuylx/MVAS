<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
 
require_once(FUEL_PATH.'models/base_module_model.php');
 
class Sms_agents_model extends Base_module_model {
    
    public $filters = array('service','msisdn');
    //public $foreign_keys = array('service'=>'service_catalogues_model');
    public $required = array('service','msisdn');
     
    public $record_class = 'Sms_agent';
     
    function __construct()
    {
        parent::__construct('m_sms_agent');
    }
    
    function list_items($limit = NULL, $offset = NULL, $col = 'service', $order = 'asc')
    {        
        $this->db->select('id,msisdn,service,first_date,last_date,description,count,enable');
        $data = parent::list_items($limit, $offset, $col, $order);        
        return $data;
    }
    
    function form_fields($values=array())
    {
        $fields = parent::form_fields($values);
        $CI =& get_instance();
        $this->db->select('service')->from('service_catalog')->where('category','agent')->order_by('service');
        $query = $this->db->get();
        $options = array();
        foreach ($query->result() as $row)
        {
            $options[$row->service] = $row->service;
        }
        
        $fields['service'] = array('type'=>'select', 'options'=>$options);
        return $fields;
    }
}
 
class Sms_agent_model extends Base_module_record {
}