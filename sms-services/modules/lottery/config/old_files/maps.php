<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
Map pair of keyword, short_code to specific controller/method
If not defined it will be service_keyword/index as default.
Example:
$config['XS'] = array(
    '7227' => array('module' => 'lottery', 'controller'=>'xstt', 'method'=>'index')    
);
*/

$config['KQ_disabled'] = array(
    '7227' => array('controller' => 'xstt'), //to be 'kq'
    'default' => array('controller' => 'xs')
);

/*End of file*/