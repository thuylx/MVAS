<?php
//This configuration is used to overide core configuration


//-----------------------------------------------------------------------------------------------------
// MESSAGE SIGNATURE
//-----------------------------------------------------------------------------------------------------
// Use these values to sign MT messsage before sending out.
//$CI =& get_instance();
//$short_code = (isset($CI->MO))?$CI->MO->short_code:'7x27';
$short_code = '7727';
$config['sms_signagures'] = array(
                                $short_code." - TONG DAI MAY MAN CUA BAN!",
                                $short_code." - TONG DAI MAY MAN CUA BAN",
                                $short_code." TONG DAI MAY MAN CUA BAN",
                                "TONG DAI MAY MAN ".$short_code,
                                "TONG DAI MAY MAN",
                                "Cam on ban!",
                                "Cam on ban",                                 
                            );