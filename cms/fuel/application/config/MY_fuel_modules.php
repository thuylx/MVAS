<?php 
/*
|--------------------------------------------------------------------------
| MY Custom Modules
|--------------------------------------------------------------------------
|
| Specifies the module controller (key) and the name (value) for fuel
*/


/*********************** EXAMPLE ***********************************

$config['modules']['quotes'] = array(
	'preview_path' => 'about/what-they-say',
);

$config['modules']['projects'] = array(
	'preview_path' => 'showcase/project/{slug}',
	'sanitize_images' => FALSE // to prevent false positives with xss_clean image sanitation
);

*********************** EXAMPLE ***********************************/

/*
$config['modules']['authors'] = array();

$config['modules']['articles'] = array(
    'precedence_col'=>'order',    
    'filters' => array('author_id'=>array())
);

$config['modules']['categories'] = array('precedence_col'=>'order'); 
*/

$config['modules']['customers'] = array(
    'module_name' => 'Khách hàng',
    'display_field' => 'msisdn'
    //'default_col'=>'service'
);
$config['modules']['customer_ads_rejections'] = array(
    'module_name' => 'Khách hàng từ chối QC',
    'display_field' => 'msisdn'
);
$config['modules']['service_catalogues'] = array(
    'module_name' => 'Danh mục dịch vụ',
    'display_field' => 'service',
    'default_col'=>'service',
    'filters' => array('module' => array('size'=>10))
); 
$config['modules']['service_aliases'] = array(
    'module_name' => 'Mã dịch vụ cho phép',
    'display_field' => 'service',
    'default_col'=>'service',
    'filters' => array('service' => array('size'=>5))
);

$config['modules']['sms_agents'] = array(
    'module_name' => 'Cho nhập liệu',
    'display_field' => 'msisdn',
    'default_col'=>'msisdn',
    'filters' => array('service' => array('size'=>5))
);

if (defined('FUEL_ADMIN'))
{ 
    $CI =& get_instance();
    $CI->load->model(service_actions_model);    
    $temp = $CI->service_actions_model->get_options_list(2);        
    $root_options[''] = '';
    foreach ($temp as $key => $val)
    {
        $root_options[$key] = $val;
    }       
    $id = $CI->uri->segment(4);
}
 
$config['modules']['service_actions'] = array(
    'module_name' => 'Kịch bản',
    'display_field' => 'title',
    'precedence_col'=>'precedence',
    'default_col'=>'precedence',
    'filters' => array(               
        'root_tree' => array('label'=>'Root', 'type'=>'select', 'options'=>$root_options),
        'root' => array('type'=>'hidden'),
        'level' => array('label'=>'show level', 'default'=>1, 'type' => 'select', 'options'=>array(0,1,2,3,4,5,6,7,8,9,10))
    ),
    'instructions'=> "Neu la XML phai chuan hoa. Xem them file <a target='_blank' href='http://appsrv.mvas.vn/".ENVIRONMENT."/cms/fuel/input_sample.xhtml'>input_sample.xhtml</a> de biet them chi tiet.",
    'item_actions' => array(
        'save', 'view', 'publish', 'activate', 'delete', 'duplicate', 'create', 
        'others' => array(            
            'service_actions?root='.$id => 'Explore',
            'service_actions' => 'Exit',
            'ctrl_service_actions/copy'=>'Copy action and children'
        )
    )/*,
    'table_actions' => array(
        'EDIT',
        'DELETE',
        'Copy' => 'ctrl_service_actions/copy/{id}'
    )  
     * 
     */  
); 
$config['modules']['auto_correction_rules'] = array(
    'module_name' => 'Luật sửa tin tự động',
    'display_field' => 'replacement',
    'precedence_col'=>'order',
    'filters' => array('code' => array('size'=>5))
);
$config['modules']['mos'] = array(
    'module_name' => 'MO',
    'display_field' => 'actual_time',
    'default_order' => 'desc'
);
$config['modules']['mts'] = array(
    'module_name' => 'MT',
    'display_field' => 'msisdn'
);