<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
 
require_once(FUEL_PATH.'models/base_module_model.php');
 
class Customer_ads_rejections_model extends Base_module_model {
    
    //public $filters = array('msisdn');
    //public $foreign_keys = array('service'=>'service_catalogues_model');
    public $required = array('msisdn');         
    public $key_field = 'msisdn';
    public $has_auto_increment = FALSE;
     
    function __construct()
    {
        parent::__construct('m_customer_ads_rejection');
    }
    
    function list_items($limit = NULL, $offset = NULL, $col = 'rejection_time', $order = 'desc')
    {       
        $this->db->select('msisdn,msisdn as number,rejection_time,enable');
        $data = parent::list_items($limit, $offset, $col, $order);        
        return $data;
    }
}
 
class Customer_ads_rejection_model extends Base_module_record {
}