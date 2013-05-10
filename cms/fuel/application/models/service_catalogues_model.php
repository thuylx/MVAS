<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
 
require_once(FUEL_PATH.'models/base_module_model.php');
 
class Service_catalogues_model extends Base_module_model {
     
    public $record_class = 'Service_catalog';
    public $key_field = 'service';
    public $has_auto_increment = FALSE;    
    //public $unique_fields = array('service');
    public $required = array('service','category');

    function __construct()
    {
        parent::__construct('m_service_catalog');
    }
    
    function list_items($limit = NULL, $offset = NULL, $col = 'service', $order = 'asc')
    {                
        $this->db->select('service_catalog.service,service_catalog.service as name, service_catalog.title,service_catalog.module,service_actions.id as root_action_id,service_actions.title as root_action,service_catalog.category,service_catalog.enable')
                 ->join('service_actions','service_catalog.root_service_action = service_actions.id','left');
        $data = parent::list_items($limit, $offset, $col, $order);
        $temp = array();
        foreach ($data as $item)
        {
            $item['root_action'] = "<a href = '".fuel_url('service_actions?root='.$item['root_action_id'])."'>".$item['root_action']."</a>";
            unset($item['root_action_id']);
            $temp[] = $item;
        }
        return $temp;
    }
    
    // cleanup category to articles    
    function on_after_delete($where)
    {
        $CI =& get_instance();
        $CI->load->model('service_aliases_model');
        if (is_array($where) && isset($where['service']))
        {
            $where = array('service' => $where['service']);
            $CI->service_aliases_model->delete($where);
        }
    }
    
    function form_fields($values = array())
    {        
        $fields = parent::form_fields($values);

        $CI =& get_instance();
        $CI->load->model('service_actions_model');
        $list = $CI->service_actions_model->get_options_list(0);        
        $fields['root_service_action'] = array('type' => 'select', 'options' => $list);        
        return $fields;
    }
}
 
class Service_catalog_model extends Base_module_record {
}