<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
There are four hooks point:
pre_execution: trigged when new MO coming before initiate any controller. can re-route service keyword there.
post_execution: after process new MO
pre_run: trigged when module called, apply for all case, not only new MO.
post_run: after run module controllers
*/

//pre_execution: trigged when new MO coming before initiate any controller. can re-route service keyword there.
$config['pre_execution'] = array(
    'controller' => 'hook_controller',
    'method'     => 'hook_method_of_controller'
);

//post_execution: after process new MO
$config['post_execution'] = array(
    'controller' => 'hook_controller',
    'method'     => 'hook_method_of_controller'
);


//pre_run: trigged when module called, apply for all case, not only new MO.
$config['pre_run'] = array(
    'controller' => 'hook_controller',
    'method'     => 'hook_method_of_controller'
);


//post_run: after run module controllers
$config['post_run'] = array(
    'controller' => 'hook_controller',
    'method'     => 'hook_method_of_controller'
);
/*End of file*/