<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
There are four hooks point:
pre_execution: trigged when new MO coming before initiate any controller. can re-route service keyword there.
post_execution: after process new MO
pre_run: trigged when module called, apply for all case, not only new MO.
post_run: after run module controllers
*/
$config['pre_execution'] = array(
    'controller' => 'hooker',
    'method'     => 'pre_execution'
);
/*End of file*/