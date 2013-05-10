<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 *-------------------------------------------------------------------------- 
 * DEFAULT LOTTERY CODE
 *--------------------------------------------------------------------------
 **/
$config['lottery_default_code'] = "MB";

/**
 *-------------------------------------------------------------------------- 
 * SHORT CODE TO PROVISION
 *--------------------------------------------------------------------------
 **/
$config['lottery_short_code'] = "7727";

/**
 * For SC service
 **/
//Number of suggestion for customer
$config['lottery_sug_limit'] = 5; //Default
$config['lottery_sug_loto_limit'] = 3; //loto service
$config['lottery_sug_special_limit'] = 10; //special prize service
$config['lottery_sug_loto_cache_size'] = 10; //loto service 
//Based on number of recent days
$config['lottery_sug_num_day'] = 0; //=0 mean no limit, form lottery start date below
$config['lottery_sug_special_num_day'] = 0; //=0 mean no limit, form lotter start date below. For special prize
//The day since which we have full data of lottery
$config['lottery_start_date'] = '2005-01-01';
//$config['lottery_start_date'] = '2010-07-07';

/**
 *-------------------------------------------------------------------------- 
 * USE CACHE FOR NEWSEST LOTTERY RESULT
 *--------------------------------------------------------------------------
 * By default, newest lottery result will be write to a field (cache) in table
 * 'lottery'. Enable this setting to enable cache this to text file.
 * 
 **/
 $config['lottery_enable_db_cache'] = FALSE;
 

/* End of file lottery.php */
/* Location: ./sms-services/config/lottery.php */