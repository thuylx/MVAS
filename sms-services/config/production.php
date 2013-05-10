<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
|--------------------------------------------------------------------------
| Logging setting
|--------------------------------------------------------------------------
*/
$config['log_show_benchmark']   = FALSE;
$config['log_show_class']       = FALSE;
$config['log_print_out']        = FALSE;
$config['log_print_log_level']  = FALSE;

/*
|--------------------------------------------------------------------------
| Debug items
|--------------------------------------------------------------------------
| Apply for all log as debug level, only log of belows items will be recorded
| by function write_log
*/
$config['log_debug_items']['core']      = TRUE;
$config['log_debug_items']['mo']        = TRUE;
$config['log_debug_items']['mt']        = TRUE;
$config['log_debug_items']['service']   = TRUE;
$config['log_debug_items']['maintenance']   = TRUE;

$config['log_threshold'] = 4;
/* End of file config/production.php*/