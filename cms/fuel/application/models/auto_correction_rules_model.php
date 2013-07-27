<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
 
require_once(FUEL_PATH.'models/base_module_model.php');
 
class Auto_correction_rules_model extends Base_module_model {
    
    //public $filters = array('service','alias');
    //public $foreign_keys = array('service'=>'service_catalogues_model');
    //public $required = array('service','alias');
    //public $unique_fields = array('msisdn'); 
    //public $key_field = 'msisdn'; 
    //public $has_auto_increment = FALSE;
    //public $displayonly = TRUE;
    //public $table_actions = array();//EDIT, VIEW, DELETE
    //public $item_actions = array('save', 'view', 'activate', 'delete', 'duplicate');
    //public $record_class = 'Service_alias';
     
    function __construct()
    {
        parent::__construct('m_mo_auto_correct_rule');
    }
        
    function list_items($limit = 100, $offset = NULL, $col = 'code', $order = 'desc')
    {        
        //$this->db->select('msisdn,msisdn as number,first_date,from_unixtime(last_mo_time),from_unixtime(last_mt_time),count,revenue,birthday,sex,area_id,vip,smsc_id,enable,description',FALSE);
        $data = parent::list_items($limit, $offset, $col, $order);                
        return $data;
    }
    
    function form_fields($values = array())
    {        
        $fields = parent::form_fields($values);                              
        $fields['pattern']['class'] = 'no_editor';
        return $fields;
    }    
}
 
class Auto_correction_rule_model extends Base_module_record {
}