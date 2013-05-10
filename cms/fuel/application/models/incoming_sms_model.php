<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
 
require_once(FUEL_PATH.'models/base_module_model.php');
 
class Incoming_sms_model extends Base_module_model {
    
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
        $this->db->select('id,msisdn,short_code,keyword,argument,content,actual_time,status,balance,smsc_id as smsc,tag,from_unixtime(time) as time,from_unixtime(last_provision_time) as last_provision_time,active',FALSE);
        $data = parent::list_items($limit, $offset, $col, $order);        
        return $data;
    }     
     
}
 
class Incoming_sm_model extends Base_module_record {
}