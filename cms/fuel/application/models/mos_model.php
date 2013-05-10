<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
 
require_once(FUEL_PATH.'models/base_module_model.php');
 
class Mos_model extends Base_module_model {
    
    //public $filters = array('service','alias');
    //public $foreign_keys = array('service'=>'service_catalogues_model');
    //public $required = array('service','alias');
    //public $unique_fields = array('msisdn'); 
    public $displayonly = TRUE;
    public $table_actions = array('EDIT','DELETE');//EDIT, VIEW, DELETE
    public $item_actions = array('save', 'view', 'activate', 'delete', 'duplicate');
    //public $record_class = 'Service_alias';
     
    function __construct()
    {
        parent::__construct('m_mo');
    }
        
    function list_items($limit = 100, $offset = NULL, $col = 'actual_time', $order = 'desc')
    {        
        $this->db->select('id,keyword,argument,content,msisdn,short_code,smsc_id as smsc,actual_time,status,balance,tag,cancelled',FALSE);
        $data = parent::list_items($limit, $offset, $col, $order);        
        return $data;
    }
    
    function options_list($key = NULL, $val = NULL, $where = array(), $order = TRUE) {
        //parent::options_list($key, $val, $where, $order);
        //Do nothing to speed up form loading
    }    
    
    function form_fields($values = array())
    {        
        $fields = parent::form_fields($values);                                
        $fields['argument']['class'] = 'no_editor';
        $fields['content']['class'] = 'no_editor';
        return $fields;
    }      
     
}
 
class Mo_model extends Base_module_record {
}