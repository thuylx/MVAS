<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
 
require_once(FUEL_PATH.'models/base_module_model.php');
 
class Service_aliases_model extends Base_module_model {
    
    public $filters = array('service','alias');
    //public $foreign_keys = array('service'=>'service_catalogues_model');
    public $required = array('service','alias');
     
    public $record_class = 'Service_alias';
     
    function __construct()
    {
        parent::__construct('m_service_alias');
    }
    
    function list_items($limit = NULL, $offset = NULL, $col = 'service', $order = 'asc')
    {        
        $this->db->select('id,alias,service,enable');
        $data = parent::list_items($limit, $offset, $col, $order);        
        return $data;
    }
    
    function form_fields($values=array())
    {
        $fields = parent::form_fields($values);
        $CI =& get_instance();
        $CI->load->model('service_catalogues_model');
        $options = $CI->service_catalogues_model->options_list('service','service');
        $fields['service'] = array('type'=>'select', 'options'=>$options);
        return $fields;
    }
}
 
class Service_alias_model extends Base_module_record {
}