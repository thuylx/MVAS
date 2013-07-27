<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

//-----------------------------------------------------------------------------------------------------
// DEFAULT MODULE
//-----------------------------------------------------------------------------------------------------
$config['default_module'] = 'mvas';
//-----------------------------------------------------------------------------------------------------
// Error handle service
//-----------------------------------------------------------------------------------------------------
$config['error_handle_service'] = '_ERROR';

//-----------------------------------------------------------------------------------------------------
//QUOTA SETTING
//-----------------------------------------------------------------------------------------------------
//As might be requested by operator, any customer does not allow to use exceed this amount of total cost per day 
$config['customer_quota'] = array(
    'Viettel' => 150000
);


//-----------------------------------------------------------------------------------------------------
//MEMORY MANAGEMENT
//-----------------------------------------------------------------------------------------------------
$config['max_mo_box_cache_size'] = 100;
$config['max_mt_box_cache_size'] = 100;
$config['mo_queue_cache_size'] = 10000;

//-----------------------------------------------------------------------------------------------------
// Process queued MO message one by one
//-----------------------------------------------------------------------------------------------------
//$config['process_one_by_one'] = TRUE;

//-----------------------------------------------------------------------------------------------------
//Whether or not do statistic imediatly after provisioning.
//-----------------------------------------------------------------------------------------------------
$config['real_time_statistic'] = FALSE;


//-----------------------------------------------------------------------------------------------------
// Outgoing MT which sent via this smsc will be canceled. 
// Queued MO which sent by these smsc will be discared when get queue
//-----------------------------------------------------------------------------------------------------
$config['disabled_smsc'] = array();


//-----------------------------------------------------------------------------------------------------
//Default MO Queue distinct fields
//-----------------------------------------------------------------------------------------------------
//If set, MO_QUEUE will get MO from database disctinctly by given field(s). (use group by clause in query string)
// Otherwise, it get all MO in database (much faster in this case but users who sent more than one message to
// a same service will get more than one response concurrently relatively).
//-----------------------------------------------------------------------------------------------------
$config['queue_distinct_fields'] = array('mo_queue.keyword','mo_queue.msisdn','mo_queue.short_code');

//-----------------------------------------------------------------------------------------------------
// MSISDN
//-----------------------------------------------------------------------------------------------------
// Only MO which send from a mobile number match with this pattern will be accepted
// and response. If the msisdn is not match, the MO will be marked as msisdn_invalid
// and forwarded to admin.
//For GSM-Modem
$config['accepted_msisdn_pattern']['GSM-Modem'] = '^injector|(\+?(84|0)?)([0-9]{3,4}|904757227|983098334|996664444|904848688|904847999|1219085923|973521999|985270999|902290888|989986018|904757227)$';
$config['accepted_msisdn_pattern']['GSM-Modem-2'] = '^(\+?(84|0)?)([0-9]{3,4}|904757227|983098334|996664444|904848688|904847999|1219085923|973521999|985270999|902290888|989986018|904757227)$';
//For other smsc_id
$config['accepted_msisdn_pattern']['default'] = '^(\+?(84|0)?)((9[0-9]{8,8})|(1[0-9]{9,9}))$';

//-----------------------------------------------------------------------------------------------------
// BLACK LIST
//-----------------------------------------------------------------------------------------------------
// msisdn which match to below pattern will be rejected.
// The black list defination is applied to each kind of service (customer, agent, system) separatlly. 
// All of MO come from msisdn which match with black list will be discarded.
$config['black_list']['customer'] = '^(\+?(84|0)?)([0-9]{3,4})$';
//$config['black_list']['agent'] = '';
$config['black_list']['system'] = '^(\+?(84|0)?)([0-9]{3,4})$';

//-----------------------------------------------------------------------------------------------------
// WHITE LIST
//-----------------------------------------------------------------------------------------------------
// The white list defination is applied to each kind of service (customer, agent, system)
// If defined the system will block all of msisdn and allow only MO come from msisdn which match with these
// patterns and does not match with black list pattern.
//$config['white_list']['customer'] = '';
//$config['white_list']['agent'] = '';
//$config['white_list']['system'] = '';

//-----------------------------------------------------------------------------------------------------
// Max resend time
// --------------------------------------------------------------------------
// Once the system get DLR which inidcate that SMS has not delivered to SMSC,
// the system will re-try to send it.
// This parameter indicate max number of retry time system try to send sms to SMSC 
$config['max_resend_time'] = 0;

//-----------------------------------------------------------------------------------------------------
// Default SMS length
//-----------------------------------------------------------------------------------------------------
$config['sms_len_threshold'] = 160;

//-----------------------------------------------------------------------------------------------------
// DEFAULT SERVICE
//-----------------------------------------------------------------------------------------------------
// If MO is empty, its content will be changed to this value before all of processing
$config['default_service'] = 'XS';

//-----------------------------------------------------------------------------------------------------
// DEFAULT MT
//-----------------------------------------------------------------------------------------------------
// Use these values to insert into kannel sqlbox
$config['MT']['momt']       = 'MT';
$config['MT']['sms_type']   = 2;
$config['MT']['dlr_mask']   = 24;
$config['MT']['dlr_url']    = "http://appsrv.mvas.vn/".ENVIRONMENT."/sms-services/dlr/process/%d/%T/";        
$config['MT']['sender']     = '7727';
$config['MT']['smsc_id']    = 'GSM-Modem';
$config['MT']['coding']     = 0;
$config['MT']['compress']   = 0; 
$config['MT']['pid']        = 0; 
$config['MT']['alt_dcs']    = 0;
$config['MT']['msgdata']    = "";
$config['MT']['account']    = 0;
$config['MT']['time']       = time();
$config['MT']['boxc_id']    = 'smsbox';

//-----------------------------------------------------------------------------------------------------
// SMSC WHICH DLR SHOULD BE DISABLED
//-----------------------------------------------------------------------------------------------------
// When send out messages, all of MT which routed to below SMSCs will be set dlr_mask to 0 regardless it's current value
// Used for modem for example
$config['no_dlr_smsc']      = array('GSM-Modem','GSM-Modem-2'); 

//-----------------------------------------------------------------------------------------------------
// MESSAGE SIGNATURE
//-----------------------------------------------------------------------------------------------------
// Use these values to sign MT messsage before sending out.
$config['sms_signagures'] = array(
                                "Cam on ban da su dung dich vu!",
                                "Cam on ban da su dung dich vu",
                                "Cam on ban!",
                                "Cam on ban"
                            );
/**
*--------------------------------------------------------------------------
* Cellphone number of administrator
*--------------------------------------------------------------------------
*
* Used for contact in case of need.
*
**/
$config['admin_mobile'] = array("+84989986018"); //Chien

/**
*--------------------------------------------------------------------------
* PHP Maximum execution time
*--------------------------------------------------------------------------
*
* Applied in MY_Controller construction function
* See PHP set_time_limit for more detail. 0 mean no limitation.
*
**/
$config['time_limit'] = 0;

/**
 * --------------------------------------------------------------------------
 * Push Proxy Gateway
 * --------------------------------------------------------------------------
 * This is infomation of push proxy gateway which will be used for wap pushing.
 * See configuration of kannel (wapbox) for details.
 * */
$config['PPG'] = array(
    'host'      => 'mgwsrv.mvas.vn',
    'port'      => '8080',
    'url'       => '/wappush',
    'username'  => 'wapmvas',
    'password'  => 'Sec@6789ret'
);

/* End of file core.php */
/* Location: ./sms-services/config/core.php */
