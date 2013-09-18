<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
|--------------------------------------------------------------------------
| Logging setting
|--------------------------------------------------------------------------
*/
$config['log_show_benchmark']   = FALSE;
$config['log_show_class']       = FALSE; //Class in which function write_log be called
$config['log_show_item']        = FALSE; //write_log($level,$message,$item)
$config['log_print_out']        = FALSE;
$config['log_print_log_level']  = FALSE;

/*
|--------------------------------------------------------------------------
| Debug items
|--------------------------------------------------------------------------
| Apply for all log as debug level, set to FALSE if you would like to eliminate any log items.
*/
$config['log_debug_items']['core']      = FALSE;
$config['log_debug_items']['mo']        = TRUE;
$config['log_debug_items']['mt']        = TRUE;
$config['log_debug_items']['service']   = TRUE;
$config['log_debug_items']['maintenance']   = TRUE;

//$config['log_threshold'] = 1;
/* End of file config/production.php*/